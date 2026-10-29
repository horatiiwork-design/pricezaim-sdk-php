<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Webhook;

use InvalidArgumentException;
use JsonException;

/**
 * Разобранный конверт входящего вебхука.
 *
 * Не проверяет подпись сам — сначала вызовите WebhookSignature::verifyRequest().
 */
final class WebhookEvent
{
    /**
     * Создаёт разобранное событие.
     *
     * @param string $id event_id.
     * @param string $name Имя события.
     * @param array<string, mixed> $payload Полный конверт.
     * @param int|null $auctionId ID аукциона, если он есть.
     */
    private function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly array $payload,
        private readonly ?int $auctionId,
    ) {
    }

    /**
     * Разбирает JSON-тело вебхука.
     *
     * Подпись не проверяет — сначала используйте WebhookSignature::verifyRequest().
     *
     * @param string $json Сырое JSON-тело.
     *
     * @throws \InvalidArgumentException При некорректном JSON или конверте.
     */
    public static function fromJson(string $json): self
    {
        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Webhook payload is not valid JSON.', 0, $e);
        }

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Webhook payload must be a JSON object.');
        }

        return self::fromArray($payload);
    }

    /**
     * Создаёт событие из уже разобранного массива.
     *
     * @param array<string, mixed> $payload Конверт вебхука.
     *
     * @throws \InvalidArgumentException Если нет event_id или event.
     */
    public static function fromArray(array $payload): self
    {
        $id = $payload['event_id'] ?? null;
        if (! is_string($id) && ! is_int($id)) {
            throw new InvalidArgumentException('Webhook payload must contain a string or integer event_id.');
        }

        $id = trim((string) $id);
        if ($id === '') {
            throw new InvalidArgumentException('Webhook event_id must not be empty.');
        }

        $name = $payload['event'] ?? null;
        if (! is_string($name) || trim($name) === '') {
            throw new InvalidArgumentException('Webhook payload must contain a non-empty event.');
        }

        return new self($id, trim($name), $payload, self::extractAuctionId($payload));
    }

    /**
     * Возвращает event_id для дедупликации.
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Возвращает имя события.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Возвращает известный тип события или null для неизвестного.
     */
    public function type(): ?WebhookEventType
    {
        return WebhookEventType::tryFrom($this->name);
    }

    /**
     * Проверяет, известно ли событие текущей версии SDK.
     */
    public function isKnown(): bool
    {
        return $this->type() !== null;
    }

    /**
     * Сравнивает событие с типом или строкой имени.
     *
     * @param WebhookEventType|string $type Тип или имя события.
     */
    public function is(WebhookEventType|string $type): bool
    {
        $name = $type instanceof WebhookEventType ? $type->value : $type;

        return $this->name === $name;
    }

    /**
     * Возвращает ID аукциона из конверта, если он присутствует.
     */
    public function auctionId(): ?int
    {
        return $this->auctionId;
    }

    /**
     * Возвращает полный конверт вебхука.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * Ищет ID аукциона в auction.id, auction_id или deal.auction_id.
     *
     * @param array<string, mixed> $payload Конверт вебхука.
     */
    private static function extractAuctionId(array $payload): ?int
    {
        $auction = $payload['auction'] ?? null;
        $deal = $payload['deal'] ?? null;

        $candidates = [
            is_array($auction) ? ($auction['id'] ?? null) : null,
            $payload['auction_id'] ?? null,
            is_array($deal) ? ($deal['auction_id'] ?? null) : null,
        ];

        foreach ($candidates as $candidate) {
            if (is_int($candidate)) {
                return $candidate;
            }

            if (is_string($candidate) && ctype_digit($candidate)) {
                return (int) $candidate;
            }
        }

        return null;
    }
}
