<?php

declare(strict_types=1);

/**
 * Пример endpoint: auction.started → проверка клиента → ставка.
 *
 * Запуск:
 *   PRICEZAIM_API_KEY=... \
 *   PRICEZAIM_WEBHOOK_SECRET=... \
 *   php -S 127.0.0.1:8080 examples/webhook-bid.php
 *
 * В реальном проекте проверку клиента и постановку ставки лучше вынести
 * в очередь: платформа ждёт ответ не дольше 10 секунд.
 */

require __DIR__.'/../vendor/autoload.php';

use Pricezaim\Sdk\Client;
use Pricezaim\Sdk\Dto\Auction;
use Pricezaim\Sdk\Exception\ApiException;
use Pricezaim\Sdk\Exception\TransportException;
use Pricezaim\Sdk\Exception\ValidationException;
use Pricezaim\Sdk\Webhook\WebhookEvent;
use Pricezaim\Sdk\Webhook\WebhookEventType;
use Pricezaim\Sdk\Webhook\WebhookSignature;

function respond(int $status, array $body): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function eventAlreadyProcessed(PDO $pdo, string $eventId): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM processed_events WHERE event_id = :id');
    $stmt->execute(['id' => $eventId]);

    return $stmt->fetchColumn() !== false;
}

function markEventProcessed(PDO $pdo, string $eventId): void
{
    $stmt = $pdo->prepare(
        'INSERT OR IGNORE INTO processed_events (event_id, processed_at) VALUES (:id, :at)'
    );
    $stmt->execute(['id' => $eventId, 'at' => gmdate(DATE_ATOM)]);
}

/**
 * Замените на реальную проверку клиента.
 */
function clientPasses(Auction $auction): bool
{
    if ($auction->status !== 'active' || ! $auction->canBid) {
        return false;
    }

    $profile = $auction->clientProfile;
    if ($profile === null) {
        return false;
    }

    if (empty($profile->inn) && empty($profile->passportNumber)) {
        return false;
    }

    $amount = $auction->requiredAmount ?? 0;

    return $amount > 0 && $amount <= 3_000_000;
}

/**
 * Замените на реальный расчёт ставки.
 *
 * @return array{amount: float, interest_rate: float, term: int, comment: string}
 */
function makeBid(Auction $auction): array
{
    return [
        'amount' => $auction->requiredAmount ?? 0,
        'interest_rate' => 19.5,
        'term' => 24,
        'comment' => 'Автоматическая ставка из SDK-примера',
    ];
}

$apiKey = getenv('PRICEZAIM_API_KEY');
$secret = getenv('PRICEZAIM_WEBHOOK_SECRET');

if (! is_string($apiKey) || $apiKey === '' || ! is_string($secret) || $secret === '') {
    error_log('PRICEZAIM_API_KEY и PRICEZAIM_WEBHOOK_SECRET должны быть заданы.');

    respond(500, ['error' => 'Server is not configured']);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false) {
    respond(400, ['error' => 'Cannot read request body']);
}

$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
if (! is_array($headers)) {
    $headers = $_SERVER;
}

if (! WebhookSignature::verifyRequest($rawBody, $secret, $headers)) {
    error_log('Отклонён вебхук с неверной подписью.');

    respond(401, ['error' => 'Invalid signature']);
}

try {
    $event = WebhookEvent::fromJson($rawBody);
} catch (InvalidArgumentException $e) {
    error_log('Некорректный вебхук: '.$e->getMessage());

    respond(400, ['error' => 'Invalid payload']);
}

// Неизвестное событие не ошибка: отвечаем 200, чтобы платформа не ретраила его.
if ($event->type() === null) {
    error_log('Неизвестное событие вебхука: '.$event->name());

    respond(200, ['ok' => true, 'ignored' => 'unknown_event']);
}

if ($event->type() !== WebhookEventType::AuctionStarted) {
    respond(200, ['ok' => true, 'ignored' => 'event']);
}

$auctionId = $event->auctionId();
if ($auctionId === null) {
    respond(400, ['error' => 'auction.started without auction.id']);
}

$dbPath = getenv('PRICEZAIM_EVENTS_DB') ?: __DIR__.'/../var/events.sqlite';
$dbDir = dirname($dbPath);
if (! is_dir($dbDir) && ! mkdir($dbDir, 0775, true) && ! is_dir($dbDir)) {
    error_log('Не удалось создать каталог для SQLite: '.$dbDir);

    respond(500, ['error' => 'Storage is unavailable']);
}

$pdo = new PDO('sqlite:'.$dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS processed_events (
        event_id TEXT PRIMARY KEY,
        processed_at TEXT NOT NULL
    )'
);

// At-least-once: повторный event_id не должен приводить к повторной ставке.
if (eventAlreadyProcessed($pdo, $event->id())) {
    respond(200, ['ok' => true, 'duplicate' => true]);
}

$client = new Client($apiKey, [
    'base_uri' => getenv('PRICEZAIM_API_BASE_URI') ?: 'https://api.pricezaim.ru',
]);

try {
    $auction = $client->auctions()->getDto($auctionId);
} catch (TransportException $e) {
    error_log('API недоступен при чтении аукциона: '.$e->getMessage());

    respond(503, ['error' => 'API temporarily unavailable']);
} catch (ApiException $e) {
    if ($e->isRateLimited() || $e->status() >= 500) {
        respond(503, ['error' => 'API temporarily unavailable']);
    }

    markEventProcessed($pdo, $event->id());
    error_log('Аукцион недоступен: '.$e->getMessage());

    respond(200, ['ok' => true, 'bid' => false, 'reason' => 'auction_unavailable']);
}

if (! clientPasses($auction)) {
    markEventProcessed($pdo, $event->id());

    respond(200, ['ok' => true, 'bid' => false, 'reason' => 'client_check_failed']);
}

$bid = makeBid($auction);

try {
    $result = $client->bids()->place(
        $auctionId,
        $bid,
        'bid-'.$auctionId.'-'.$event->id(),
    );
} catch (ValidationException $e) {
    // 422 — ставка отклонена бизнес-правилами. Повторять бессмысленно.
    markEventProcessed($pdo, $event->id());
    error_log('Ставка отклонена API: '.$e->getMessage());

    respond(200, ['ok' => true, 'bid' => false, 'reason' => 'bid_rejected']);
} catch (TransportException $e) {
    error_log('API недоступен при ставке: '.$e->getMessage());

    // Не помечаем событие обработанным: платформа повторит доставку.
    respond(503, ['error' => 'API temporarily unavailable']);
} catch (ApiException $e) {
    if ($e->isRateLimited() || $e->status() >= 500) {
        respond(503, ['error' => 'API temporarily unavailable']);
    }

    markEventProcessed($pdo, $event->id());
    error_log('Ставка отклонена API: '.$e->getMessage());

    respond(200, ['ok' => true, 'bid' => false, 'reason' => 'bid_rejected']);
}

markEventProcessed($pdo, $event->id());

respond(200, [
    'ok' => true,
    'bid' => true,
    'bid_id' => $result['bid']['id'] ?? null,
]);
