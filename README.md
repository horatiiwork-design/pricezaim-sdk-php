# pricezaim PHP SDK

PHP-клиент для публичного API [pricezaim](https://api.pricezaim.ru/docs).

Текущий статус — **ранний MVP**:

- авторизация по `X-API-Key`;
- базовый HTTP-клиент и разбор ошибок API;
- ресурсы `auctions`, `bids`, `deals`, `webhooks`;
- пагинация;
- проверка подписи входящих вебхуков;
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
use Pricezaim\Sdk\Webhook\WebhookSignature;

$ok = WebhookSignature::verify(
    payload: file_get_contents('php://input'),
    secret: getenv('PRICEZAIM_WEBHOOK_SECRET'),
    signature: $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '',
    timestamp: $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] ?? null,
);

if (! $ok) {
    http_response_code(401);
    exit;
}
```

SDK проверяет подпись по сырому телу запроса. Не передавайте в `verify()`
результат повторного `json_encode()` — байты должны совпадать с телом,
которое отправил pricezaim.

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

## Что пока не входит

- DTO-классы для всех сущностей (сейчас возвращаются массивы);
- автоматические повторы с backoff;
- загрузка вложений через отдельный удобный API;
- публикация пакета в Packagist и CI.
