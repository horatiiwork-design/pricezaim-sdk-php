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
    /** @param array<string, mixed> $payload */
    private function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly array $payload,
        private readonly ?int $auctionId,
    ) {
    }

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

    /** @param array<string, mixed> $payload */
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

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): ?WebhookEventType
    {
        return WebhookEventType::tryFrom($this->name);
    }

    public function isKnown(): bool
    {
        return $this->type() !== null;
    }

    public function is(WebhookEventType|string $type): bool
    {
        $name = $type instanceof WebhookEventType ? $type->value : $type;

        return $this->name === $name;
    }

    public function auctionId(): ?int
    {
        return $this->auctionId;
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->payload;
    }

    /** @param array<string, mixed> $payload */
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
