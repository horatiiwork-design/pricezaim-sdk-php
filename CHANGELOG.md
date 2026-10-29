# Changelog

## 0.4.0 — 2026-10-29

- PHPDoc-комментарии ко всем методам.
- Подробный справочник методов в `docs/api.md`.
- Удалён `examples/webhook-bid.php`.

## 0.3.0 — 2026-10-29

- DTO для `Auction`, `ClientProfile`, `Bid`, `Deal`, `DealDocument`, `Webhook`.
- DTO-методы ресурсов: `getDto`, `listDto`, `placeDto`, `documentsDto`, `listDto` вебхуков.
- `Page::map()` для преобразования элементов страницы.
- Автоповторы с включением только через `PRICEZAIM_SDK_RETRIES_ENABLED`.
- Ретраи `429/500/502/503/504` и сетевых ошибок; POST/PATCH — только с `Idempotency-Key`.

## 0.2.0 — 2026-10-29

- `WebhookSignature::verifyRequest()` — проверка подписи по массиву заголовков.
- `WebhookEvent` — разбор конверта, `event_id`, `event`, `auction_id`, проверка известности.
- `WebhookEventType` — enum известных событий и `webhook.test`.
- Пример `examples/webhook-bid.php`: подпись → дедупликация → проверка клиента → ставка.

## 0.1.0 — 2026-10-29

- Первый MVP PHP SDK для публичного API pricezaim.
- Клиент с `X-API-Key`, JSON/multipart, `Idempotency-Key`, таймаутами и разбором ошибок.
- Ресурсы `auctions`, `bids`, `deals`, `webhooks`.
- Пагинация и проверка подписи вебхуков.
- PHPUnit-тесты базовых сценариев.
