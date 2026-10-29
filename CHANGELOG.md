# Changelog

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
