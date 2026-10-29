<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Dto;

use InvalidArgumentException;

final readonly class Webhook
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public int $id,
        public ?string $url,
        public array $events,
        public array $headers,
        public bool $active,
        public bool $failing,
        public int $consecutiveFailures,
        public bool $hasSecret,
        public ?string $createdAt,
        public ?string $updatedAt,
        public array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = ArrayValue::int($data, 'id');
        if ($id === null) {
            throw new InvalidArgumentException('Webhook payload must contain an integer id.');
        }

        return new self(
            id: $id,
            url: ArrayValue::string($data, 'url'),
            events: ArrayValue::list($data, 'events'),
            headers: ArrayValue::array($data, 'headers'),
            active: ArrayValue::bool($data, 'active', true),
            failing: ArrayValue::bool($data, 'failing'),
            consecutiveFailures: ArrayValue::int($data, 'consecutive_failures') ?? 0,
            hasSecret: ArrayValue::bool($data, 'has_secret'),
            createdAt: ArrayValue::string($data, 'created_at'),
            updatedAt: ArrayValue::string($data, 'updated_at'),
            raw: $data,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->raw;
    }
}
