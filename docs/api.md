# Справочник методов

## Client

```php
$client = new Pricezaim\Sdk\Client($apiKey, $options);
```

`$options`: `base_uri`, `timeout`, `http_client`, `headers`, `user_agent`.

| Метод | Назначение |
|---|---|
| `auctions()` | ресурс аукционов |
| `bids()` | ресурс ставок |
| `deals()` | ресурс сделок |
| `webhooks()` | ресурс вебхуков |
| `get($path, $query)` | GET |
| `post($path, $payload, $key)` | POST с JSON |
| `postMultipart($path, $multipart, $key)` | POST с файлами |
| `put($path, $payload)` | PUT с JSON |
| `putMultipart($path, $multipart, $key)` | PUT с файлами |
| `delete($path)` | DELETE |
| `send($method, $uri, $options)` | запрос с JSON-ответом |
| `sendRaw($method, $path, $query)` | сырой `ResponseInterface` |
| `request($method, $uri, $options)` | низкоуровневый запрос |
| `uri($path, $query)` | собрать URL |
| `baseUri()` | базовый URL |
| `retryPolicy()` | текущая политика повторов |

## Аукционы

```php
$client->auctions()
```

| Метод | Возвращает |
|---|---|
| `list($filters)` | `Page` |
| `my($filters)` | `Page` |
| `listDto($filters)` | `Page<Auction>` |
| `myDto($filters)` | `Page<Auction>` |
| `get($id)` | `array` |
| `getDto($id)` | `Auction` |
| `create($payload, $files)` | `array` |
| `createDto($payload, $files)` | `Auction` |
| `update($id, $payload, $files)` | `array` |
| `updateDto($id, $payload, $files)` | `Auction` |
| `start($id)` | `array` |
| `stop($id)` | `array` |
| `selectWinner($id, $bidId)` | `array` |
| `duplicate($id)` | `array` |
| `restoreDuplicate($id)` | `array` |
| `refuse($id, $payload)` | `array` |
| `restoreRefusal($id)` | `array` |
| `photos($id)` | `array` |
| `downloadFile($auctionId, $fileId)` | `string` |

Файлы передаются частями Guzzle:

```php
$client->auctions()->update(991, ['required_amount' => 600000], [
    ['name' => 'photos[]', 'path' => '/tmp/photo.jpg', 'filename' => 'photo.jpg'],
]);
```

`$filters` — query-параметры API: `status`, `city`, `city[]`, `date_from`,
`date_to`, `page`, `per_page` и другие.

## Ставки

```php
$client->bids()
```

| Метод | Возвращает |
|---|---|
| `my($filters)` | `Page` |
| `myDto($filters)` | `Page<Bid>` |
| `forAuction($auctionId, $filters)` | `Page` |
| `forAuctionDto($auctionId, $filters)` | `Page<Bid>` |
| `place($auctionId, $payload, $key)` | `array` |
| `placeDto($auctionId, $payload, $key)` | `Bid` |

`$payload`: `amount`, `interest_rate`, `term`, `comment`.

## Сделки

```php
$client->deals()
```

| Метод | Возвращает |
|---|---|
| `list($filters)` | `Page` |
| `listDto($filters)` | `Page<Deal>` |
| `get($id)` | `array` |
| `getDto($id)` | `Deal` |
| `documents($id)` | `array` |
| `documentsDto($id)` | `list<DealDocument>` |
| `uploadDocuments($id, $files, $fields, $key)` | `array` |
| `requisites($id)` | `array` |
| `approve($id, $payload, $key)` | `array` |
| `chat($id, $filters)` | `array` |
| `sendChat($id, $message, $payload, $key)` | `array` |
| `downloadDocument($dealId, $documentId)` | `string` |
| `deleteDocument($dealId, $documentId)` | `array` |

## Вебхуки

```php
$client->webhooks()
```

| Метод | Возвращает |
|---|---|
| `list()` | `array` |
| `listDto()` | `list<Webhook>` |
| `create($payload, $key)` | `array` |
| `createDto($payload, $key)` | `Webhook` |
| `update($id, $payload)` | `array` |
| `updateDto($id, $payload)` | `Webhook` |
| `delete($id)` | `array` |
| `test($id)` | `array` |
| `deliveries($id, $filters)` | `Page` |
| `retryDelivery($webhookId, $deliveryId)` | `array` |

## Page

`Page` реализует `Countable`, `IteratorAggregate`, `JsonSerializable`.

| Метод | Возвращает |
|---|---|
| `items()` / `data()` | `array` |
| `meta()` | `array` |
| `raw()` | `array` |
| `total()` | `int` |
| `currentPage()` | `int` |
| `lastPage()` | `int` |
| `perPage()` | `int` |
| `isEmpty()` | `bool` |
| `map($callback)` | `Page` |
| `count()` | `int` |

## DTO

Все DTO имеют `fromArray()` и `toArray()`. `toArray()` возвращает исходный
ответ API.

| DTO | Поля |
|---|---|
| `Auction` | `id`, `uuid`, `status`, `requiredAmount`, `canBid`, `clientProfile` |
| `ClientProfile` | `inn`, `passportNumber`, `incomeSalary`; содержит ПДн |
| `Bid` | `id`, `auctionId`, `amount`, `interestRate`, `term` |
| `Deal` | `id`, `auctionId`, `status`, `documents`, `documentObjects()` |
| `DealDocument` | `id`, `originalName`, `mime`, `size` |
| `Webhook` | `id`, `url`, `events`, `active`, `hasSecret` |

## Вебхуки: подпись и событие

```php
WebhookSignature::sign($payload, $secret): string
WebhookSignature::verify($payload, $secret, $signature, $timestamp, $tolerance): bool
WebhookSignature::verifyRequest($payload, $secret, $headers, $tolerance): bool

WebhookEvent::fromJson($json): WebhookEvent
WebhookEvent::fromArray($payload): WebhookEvent

$event->id();          // event_id
$event->name();        // имя события
$event->type();        // WebhookEventType|null
$event->isKnown();     // известно ли событие
$event->is($type);     // сравнение с типом
$event->auctionId();   // int|null
$event->payload();     // весь конверт
```

## Повторы

| Ситуация | Повтор |
|---|---|
| `429`, `500`, `502`, `503`, `504` | да |
| сеть или таймаут | да |
| `GET`, `HEAD`, `PUT`, `DELETE`, `OPTIONS` | да |
| `POST`, `PATCH` без `Idempotency-Key` | нет |
| `POST`, `PATCH` с `Idempotency-Key` | да |
| `400`, `401`, `403`, `404`, `422` | нет |

Включаются переменной `PRICEZAIM_SDK_RETRIES_ENABLED`. Дополнительно:
`PRICEZAIM_SDK_RETRY_ATTEMPTS`, `PRICEZAIM_SDK_RETRY_BASE_DELAY_MS`.

## Исключения

`ApiException` — HTTP-ошибка или не-JSON ответ. Доступны `status()`,
`errorCode()`, `body()`, `headers()`, `requestId()`, `retryAfter()`,
`isRateLimited()`.

`ValidationException` — ответ `422`, ошибки полей в `errors()`.

`TransportException` — сеть, DNS, таймаут, TLS.
