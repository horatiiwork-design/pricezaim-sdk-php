# Справочник методов SDK

Все ресурсы доступны через `Pricezaim\Sdk\Client`:

```php
$client = new Pricezaim\Sdk\Client($apiKey);
```

## Client

| Метод | Возвращает | Назначение |
|---|---|---|
| `auctions()` | `Auctions` | Ресурс аукционов |
| `bids()` | `Bids` | Ресурс ставок |
| `deals()` | `Deals` | Ресурс сделок |
| `webhooks()` | `Webhooks` | Ресурс вебхуков |
| `get($path, $query)` | `array` | GET-запрос с query |
| `post($path, $payload, $idempotencyKey)` | `array` | POST с JSON |
| `postMultipart($path, $multipart, $idempotencyKey)` | `array` | POST multipart |
| `put($path, $payload)` | `array` | PUT с JSON |
| `putMultipart($path, $multipart, $idempotencyKey)` | `array` | PUT multipart |
| `delete($path)` | `array` | DELETE-запрос |
| `request($method, $uri, $options)` | `ResponseInterface` | Низкоуровневый HTTP-запрос |
| `send($method, $uri, $options)` | `array` | Запрос + JSON-декодирование |
| `sendRaw($method, $path, $query)` | `ResponseInterface` | Сырой ответ для файлов |
| `uri($path, $query)` | `string` | Сборка абсолютного URL |
| `baseUri()` | `string` | Базовый URL |
| `retryPolicy()` | `RetryPolicy` | Текущая политика автоповторов |

## Аукционы

```php
$client->auctions()
```

| Метод | HTTP | Возвращает | Назначение |
|---|---|---|---|
| `list($filters)` | `GET /v1/auctions` | `Page` | Доступные аукционы |
| `my($filters)` | `GET /v1/auctions/my` | `Page` | Аукционы организации |
| `listDto($filters)` | `GET /v1/auctions` | `Page<Auction>` | То же, с DTO |
| `myDto($filters)` | `GET /v1/auctions/my` | `Page<Auction>` | То же, с DTO |
| `get($id)` | `GET /v1/auctions/{id}` | `array` | Карточка аукциона |
| `getDto($id)` | `GET /v1/auctions/{id}` | `Auction` | Карточка как DTO |
| `create($payload, $files)` | `POST /v1/auctions` | `array` | Создать draft |
| `createDto($payload, $files)` | `POST /v1/auctions` | `Auction` | Создать и вернуть DTO |
| `update($id, $payload, $files)` | `PUT /v1/auctions/{id}` | `array` | Изменить аукцион |
| `updateDto($id, $payload, $files)` | `PUT /v1/auctions/{id}` | `Auction` | Изменить и вернуть DTO |
| `start($id)` | `POST /v1/auctions/{id}/start` | `array` | Запустить |
| `stop($id)` | `POST /v1/auctions/{id}/stop` | `array` | Архивировать |
| `selectWinner($id, $bidId)` | `POST /v1/auctions/{id}/winner` | `array` | Выбрать победителя |
| `duplicate($id)` | `POST /v1/auctions/{id}/duplicate` | `array` | Пометить дублем |
| `restoreDuplicate($id)` | `DELETE /v1/auctions/{id}/duplicate` | `array` | Снять пометку |
| `refuse($id, $payload)` | `POST /v1/auctions/{id}/refusal` | `array` | Отказаться |
| `restoreRefusal($id)` | `DELETE /v1/auctions/{id}/refusal` | `array` | Снять отказ |
| `photos($id)` | `GET /v1/auctions/{id}/photos` | `array` | Фото и файлы |
| `downloadFile($auctionId, $fileId)` | `GET /v1/auctions/{id}/files/{fileId}` | `string` | Скачать файл |

`$files` — массив:

```php
[
    ['name' => 'photos[]', 'path' => '/tmp/photo.jpg', 'filename' => 'photo.jpg'],
]
```

## Ставки

```php
$client->bids()
```

| Метод | HTTP | Возвращает | Назначение |
|---|---|---|---|
| `my($filters)` | `GET /v1/bids/my` | `Page` | Свои ставки |
| `myDto($filters)` | `GET /v1/bids/my` | `Page<Bid>` | Свои ставки как DTO |
| `forAuction($auctionId, $filters)` | `GET /v1/auctions/{id}/bids` | `Page` | Ставки аукциона |
| `forAuctionDto($auctionId, $filters)` | `GET /v1/auctions/{id}/bids` | `Page<Bid>` | Ставки как DTO |
| `place($auctionId, $payload, $idempotencyKey)` | `POST /v1/auctions/{id}/bids` | `array` | Поставить ставку |
| `placeDto($auctionId, $payload, $idempotencyKey)` | `POST /v1/auctions/{id}/bids` | `Bid` | Ставка как DTO |

`$payload`: `amount`, `interest_rate`, `term`, `comment`.

## Сделки

```php
$client->deals()
```

| Метод | HTTP | Возвращает | Назначение |
|---|---|---|---|
| `list($filters)` | `GET /v1/deals` | `Page` | Список сделок |
| `listDto($filters)` | `GET /v1/deals` | `Page<Deal>` | Список как DTO |
| `get($id)` | `GET /v1/deals/{id}` | `array` | Карточка сделки |
| `getDto($id)` | `GET /v1/deals/{id}` | `Deal` | Карточка как DTO |
| `documents($id)` | `GET /v1/deals/{id}/documents` | `array` | Документы |
| `documentsDto($id)` | `GET /v1/deals/{id}/documents` | `list<DealDocument>` | Документы как DTO |
| `uploadDocuments($id, $files, $fields, $key)` | `POST /v1/deals/{id}/documents` | `array` | Загрузить документы |
| `requisites($id)` | `GET /v1/deals/{id}/requisites` | `array` | Реквизиты |
| `approve($id, $payload, $key)` | `POST /v1/deals/{id}/approve` | `array` | Согласовать |
| `chat($id, $filters)` | `GET /v1/deals/{id}/chat` | `array` | История чата |
| `sendChat($id, $message, $payload, $key)` | `POST /v1/deals/{id}/chat` | `array` | Отправить сообщение |
| `downloadDocument($dealId, $documentId)` | `GET /v1/deals/{id}/documents/{docId}` | `string` | Скачать документ |
| `deleteDocument($dealId, $documentId)` | `DELETE /v1/deals/{id}/documents/{docId}` | `array` | Удалить документ |

## Вебхуки

```php
$client->webhooks()
```

| Метод | HTTP | Возвращает | Назначение |
|---|---|---|---|
| `list()` | `GET /v1/webhooks` | `array` | Список без секретов |
| `listDto()` | `GET /v1/webhooks` | `list<Webhook>` | Список как DTO |
| `create($payload, $key)` | `POST /v1/webhooks` | `array` | Создать |
| `createDto($payload, $key)` | `POST /v1/webhooks` | `Webhook` | Создать и вернуть DTO |
| `update($id, $payload)` | `PUT /v1/webhooks/{id}` | `array` | Изменить |
| `updateDto($id, $payload)` | `PUT /v1/webhooks/{id}` | `Webhook` | Изменить и вернуть DTO |
| `delete($id)` | `DELETE /v1/webhooks/{id}` | `array` | Удалить |
| `test($id)` | `POST /v1/webhooks/{id}/test` | `array` | Тестовый пинг |
| `deliveries($id, $filters)` | `GET /v1/webhooks/{id}/deliveries` | `Page` | Журнал доставок |
| `retryDelivery($webhookId, $deliveryId)` | `POST /v1/webhooks/{id}/deliveries/{deliveryId}/retry` | `array` | Повторить доставку |

## Page

`Page` реализует `Countable`, `IteratorAggregate`, `JsonSerializable`.

| Метод | Возвращает | Назначение |
|---|---|---|
| `items()` | `array` | Элементы страницы |
| `data()` | `array` | Псевдоним `items()` |
| `meta()` | `array` | Метаданные пагинации |
| `raw()` | `array` | Исходный ответ |
| `total()` | `int` | Всего записей |
| `currentPage()` | `int` | Текущая страница |
| `lastPage()` | `int` | Последняя страница |
| `perPage()` | `int` | Размер страницы |
| `isEmpty()` | `bool` | Страница пуста |
| `map($callback)` | `Page` | Преобразовать элементы |
| `count()` | `int` | Элементов на странице |

## DTO

Все DTO имеют `fromArray()` и `toArray()`. `toArray()` возвращает исходные данные.

| DTO | Основные поля |
|---|---|
| `Auction` | `id`, `status`, `requiredAmount`, `canBid`, `clientProfile`, `raw` |
| `ClientProfile` | `inn`, `passportNumber`, `incomeSalary`; содержит ПДн |
| `Bid` | `id`, `auctionId`, `amount`, `interestRate`, `term` |
| `Deal` | `id`, `auctionId`, `status`, `documents`, `documentObjects()` |
| `DealDocument` | `id`, `originalName`, `mime`, `size` |
| `Webhook` | `id`, `url`, `events`, `active`, `hasSecret` |

Пример:

```php
$auction = $client->auctions()->getDto(991);

echo $auction->requiredAmount;
echo $auction->clientProfile?->inn;
```

## Вебхуки: подпись и событие

### `WebhookSignature`

| Метод | Возвращает | Назначение |
|---|---|---|
| `verify($payload, $secret, $signature, $timestamp, $tolerance)` | `bool` | Проверить HMAC по готовым значениям |
| `verifyRequest($payload, $secret, $headers, $tolerance)` | `bool` | Проверить по `getallheaders()` / `$_SERVER` |
| `sign($payload, $secret)` | `string` | Посчитать HMAC-SHA256 |

### `WebhookEvent`

| Метод | Возвращает | Назначение |
|---|---|---|
| `fromJson($json)` | `WebhookEvent` | Разобрать сырое тело |
| `fromArray($payload)` | `WebhookEvent` | Разобрать массив |
| `id()` | `string` | `event_id` |
| `name()` | `string` | Имя события |
| `type()` | `?WebhookEventType` | Известный тип или `null` |
| `isKnown()` | `bool` | Известно ли событие |
| `is($type)` | `bool` | Сравнить с типом |
| `auctionId()` | `?int` | ID аукциона |
| `payload()` | `array` | Полный конверт |

`WebhookEventType` содержит известные события, включая `auction.started`, `bid.outbid`, `deal.created` и `webhook.test`.

## Автоповторы

По умолчанию выключены.

```bash
PRICEZAIM_SDK_RETRIES_ENABLED=1
PRICEZAIM_SDK_RETRY_ATTEMPTS=3
PRICEZAIM_SDK_RETRY_BASE_DELAY_MS=200
```

| Ситуация | Повтор |
|---|---|
| `429`, `500`, `502`, `503`, `504` | Да |
| Сеть/таймаут | Да |
| `GET`, `HEAD`, `PUT`, `DELETE`, `OPTIONS` | Да |
| `POST`, `PATCH` без `Idempotency-Key` | Нет |
| `POST`, `PATCH` с `Idempotency-Key` | Да |
| `400`, `401`, `403`, `404`, `422` | Нет |

## Исключения

| Класс | Когда возникает |
|---|---|
| `ApiException` | HTTP-ошибка `4xx/5xx` или не-JSON ответ |
| `ValidationException` | `422`, содержит `errors()` |
| `TransportException` | Сеть, DNS, таймаут, TLS |

`ApiException` предоставляет `status()`, `errorCode()`, `body()`, `headers()`, `requestId()`, `retryAfter()`, `isRateLimited()`.
