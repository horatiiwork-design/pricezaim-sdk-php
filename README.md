# pricezaim PHP SDK

Клиент публичного API [pricezaim](https://api.pricezaim.ru/docs) для PHP 8.2+.
Работает через Guzzle, авторизуется по `X-API-Key`, поддерживает аукционы,
ставки, сделки и вебхуки.

## Установка

После публикации в Packagist:

```bash
composer require pricezaim/sdk
```

До публикации пакет можно подключить напрямую из репозитория:

```bash
composer config repositories.pricezaim vcs https://github.com/horatiiwork-design/pricezaim-sdk-php.git
composer require pricezaim/sdk:dev-main
```

Для приватного GitHub-репозитория Composer нужен токен с доступом к нему.
Настройте его через `COMPOSER_AUTH` или `composer config --global github-oauth.github.com`.

## Начало работы

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Pricezaim\Sdk\Client;

$client = new Client(getenv('PRICEZAIM_API_KEY'));

$page = $client->auctions()->list([
    'status' => 'active',
    'per_page' => 20,
]);

foreach ($page as $auction) {
    echo $auction['id'].' — '.$auction['title'].PHP_EOL;
}

echo 'Всего: '.$page->total().PHP_EOL;
```

Клиент принимает базовый URL, таймаут, готовый Guzzle-клиент и дополнительные
заголовки:

```php
$client = new Client($apiKey, [
    'base_uri' => 'https://api.pricezaim.ru',
    'timeout' => 10,
]);
```

## Что доступно

| Ресурс | Методы |
|---|---|
| `auctions()` | список, карточка, создание, изменение, запуск, архивация, победитель, отказ, дубль, фото и файлы |
| `bids()` | свои ставки, ставки аукциона, размещение ставки |
| `deals()` | список, карточка, документы, реквизиты, согласование, чат |
| `webhooks()` | создание, изменение, удаление, тестовая доставка, журнал и повтор доставки |

У методов есть варианты `*Dto()`, которые возвращают объекты из
`Pricezaim\Sdk\Dto` вместо массивов:

```php
use Pricezaim\Sdk\Dto\Auction;

$auction = $client->auctions()->getDto(991);

if ($auction instanceof Auction) {
    echo $auction->requiredAmount;
    echo $auction->clientProfile?->inn;
}
```

DTO не падают на неполном ответе: отсутствующие поля будут `null` или пустым
массивом. Исходные данные всегда лежат в `toArray()`.

## Вебхуки

Подпись проверяется по сырому телу запроса. Не пересобирайте JSON перед
проверкой — байты должны совпадать.

```php
use Pricezaim\Sdk\Webhook\WebhookEvent;
use Pricezaim\Sdk\Webhook\WebhookEventType;
use Pricezaim\Sdk\Webhook\WebhookSignature;

$rawBody = file_get_contents('php://input');

if (! WebhookSignature::verifyRequest($rawBody, getenv('PRICEZAIM_WEBHOOK_SECRET'), getallheaders())) {
    http_response_code(401);
    exit;
}

$event = WebhookEvent::fromJson($rawBody);

if ($event->type() === WebhookEventType::AuctionStarted) {
    $auctionId = $event->auctionId();
    // ...
}
```

`verifyRequest()` понимает обычные заголовки и `HTTP_X_WEBHOOK_*`.
`WebhookEvent` отдаёт `id()` (это `event_id`), `name()`, `type()`,
`isKnown()`, `auctionId()` и `payload()`.

Неизвестное событие — нормальная ситуация: его нужно залогировать и ответить
`200`, чтобы новые события на платформе не ломали приёмник.

## Повторы запросов

По умолчанию повторов нет. Включить:

```bash
export PRICEZAIM_SDK_RETRIES_ENABLED=1
export PRICEZAIM_SDK_RETRY_ATTEMPTS=3
export PRICEZAIM_SDK_RETRY_BASE_DELAY_MS=200
```

Повторяются `429`, `500`, `502`, `503`, `504` и сетевые ошибки. `GET`, `HEAD`,
`PUT`, `DELETE`, `OPTIONS` повторяются всегда; `POST` и `PATCH` — только с
`Idempotency-Key`. Ответы `400`, `401`, `403`, `404`, `422` не повторяются.

## Ошибки

```php
use Pricezaim\Sdk\Exception\ApiException;
use Pricezaim\Sdk\Exception\ValidationException;

try {
    $client->auctions()->get(999999);
} catch (ValidationException $e) {
    print_r($e->errors());
} catch (ApiException $e) {
    echo $e->status();
    echo $e->errorCode();
    echo $e->requestId();
}
```

`TransportException` выбрасывается при сетевой ошибке, таймауте или TLS.

## Разработка

```bash
composer install
composer check
```

`composer check` запускает PHPUnit и PHPStan.

## Документация

Справочник по методам и типам — в [`docs/api.md`](docs/api.md).
