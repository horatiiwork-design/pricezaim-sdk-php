# pricezaim PHP SDK

PHP-клиент для публичного API [pricezaim](https://api.pricezaim.ru/docs).

Текущий статус — **ранний MVP**:

- авторизация по `X-API-Key`;
- базовый HTTP-клиент и разбор ошибок API;
- ресурсы `auctions`, `bids`, `deals`, `webhooks`;
- пагинация;
- проверка подписи входящих вебхуков;
- разбор события, `event_id`, `auction_id` и типа события;
- DTO для основных сущностей;
- автоповторы при сетевых/временных ошибках (по env-флагу);
- идемпотентные POST-запросы.

Требования: PHP 8.2+, Composer.

## Установка

```bash
composer require pricezaim/sdk
```

Пока пакет не опубликован в Packagist, подключите приватный репозиторий:

```bash
composer config repositories.pricezaim vcs https://github.com/horatiiwork-design/pricezaim-sdk-php.git
composer require pricezaim/sdk:dev-main
```

Для приватного репозитория GitHub нужен настроенный доступ Composer
(например, `COMPOSER_AUTH` с токеном).

## Быстрый старт

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Pricezaim\Sdk\Client;

$client = new Client(getenv('PRICEZAIM_API_KEY'));

$page = $client->auctions()->list([
    'status' => 'active',
    'per_page' => 20,
]);

foreach ($page->items() as $auction) {
    echo $auction['id'].' — '.$auction['title'].PHP_EOL;
}

echo 'Всего: '.$page->total().PHP_EOL;
```

## DTO

Для типизированной работы есть DTO:

```php
use Pricezaim\Sdk\Dto\Auction;

/** @var Auction $auction */
$auction = $client->auctions()->getDto(991);

echo $auction->id;
echo $auction->requiredAmount;
echo $auction->clientProfile?->inn;

$page = $client->auctions()->listDto();
foreach ($page as $item) {
    // $item — Pricezaim\Sdk\Dto\Auction
}
```

Доступны:

- `Pricezaim\Sdk\Dto\Auction`;
- `Pricezaim\Sdk\Dto\ClientProfile`;
- `Pricezaim\Sdk\Dto\Bid`;
- `Pricezaim\Sdk\Dto\Deal`;
- `Pricezaim\Sdk\Dto\DealDocument`;
- `Pricezaim\Sdk\Dto\Webhook`.

Методы:

- `getDto()` — один объект;
- `listDto()` / `myDto()` — `Page` с DTO;
- `placeDto()` — ставка;
- `documentsDto()` — документы сделки;
- `listDto()` / `createDto()` / `updateDto()` — вебхуки.

DTO терпимы к неполным ответам: отсутствующие поля становятся `null` или
пустыми массивами. Исходный ответ всегда доступен через `toArray()` и `raw`.

## Автоповторы

По умолчанию **выключены**. Включаются только переменной окружения:

```bash
PRICEZAIM_SDK_RETRIES_ENABLED=1
```

Дополнительные настройки:

```bash
PRICEZAIM_SDK_RETRY_ATTEMPTS=3          # всего попыток
PRICEZAIM_SDK_RETRY_BASE_DELAY_MS=200   # базовая пауза
```

Что повторяется:

- `429`, `500`, `502`, `503`, `504`;
- сетевые ошибки/таймауты;
- `GET`, `HEAD`, `PUT`, `DELETE`, `OPTIONS`;
- `POST`/`PATCH` — только если передан `Idempotency-Key`.

Не повторяются `400`, `401`, `403`, `404`, `422`.

При `429` учитывается `Retry-After`. Задержка растёт экспоненциально, но
ограничена 5 секундами (для `Retry-After` — 30 секундами).

## Аукционы

```php
$auction = $client->auctions()->get(991);

$created = $client->auctions()->create([
    'title' => 'Заявка №...',
    'city' => 'Москва',
    'required_amount' => 500000,
]);

$client->auctions()->start($created['id']);
$client->auctions()->stop($created['id']);

// Дозагрузка фото/файлов при редактировании:
$client->auctions()->update(991, ['required_amount' => 600000], [
    ['name' => 'photos[]', 'path' => '/tmp/photo.jpg'],
]);
```

## Ставки

```php
$client->bids()->place(991, [
    'amount' => 150000,
    'interest_rate' => 19.5,
    'term' => 24,
], idempotencyKey: 'bid-991-1');
```

## Сделки

```php
$deals = $client->deals()->list(['status' => 'active']);
$deal = $client->deals()->get(157);

$client->deals()->sendChat(157, 'Документы отправлены.');
```

## Вебхуки

```php
use Pricezaim\Sdk\Webhook\WebhookEvent;
use Pricezaim\Sdk\Webhook\WebhookEventType;
use Pricezaim\Sdk\Webhook\WebhookSignature;

$rawBody = file_get_contents('php://input');

if (! WebhookSignature::verifyRequest(
    payload: $rawBody,
    secret: getenv('PRICEZAIM_WEBHOOK_SECRET'),
    headers: getallheaders(),
)) {
    http_response_code(401);
    exit;
}

$event = WebhookEvent::fromJson($rawBody);

if ($event->type() === WebhookEventType::AuctionStarted) {
    $auctionId = $event->auctionId();
    // ...
}
```

`verifyRequest()` берёт `X-Webhook-Signature` и `X-Webhook-Timestamp` из
заголовков (поддерживаются и имена вида `HTTP_X_WEBHOOK_SIGNATURE`).

`WebhookEvent` даёт:

- `id()` — `event_id` для дедупликации;
- `name()` — имя события;
- `type()` — `WebhookEventType` или `null` для неизвестного события;
- `isKnown()` — известно ли событие текущей версии SDK;
- `auctionId()` — id аукциона, если он есть в конверте;
- `payload()` — весь массив.

Неизвестные события нужно логировать и отвечать `200`, а не `400`: так SDK
не сломается, когда платформа добавит новое событие.

SDK проверяет подпись по сырому телу запроса. Не передавайте в `verify()` или
`verifyRequest()` результат повторного `json_encode()` — байты должны совпадать
с телом, которое отправил pricezaim.

Подробный справочник по методам: [`docs/api.md`](docs/api.md).

## Ошибки

```php
use Pricezaim\Sdk\Exception\ApiException;
use Pricezaim\Sdk\Exception\ValidationException;

try {
    $client->auctions()->get(999999);
} catch (ValidationException $e) {
    // $e->errors() — ошибки полей из ответа 422
} catch (ApiException $e) {
    // $e->status(), $e->errorCode(), $e->requestId(), $e->retryAfter()
}
```
