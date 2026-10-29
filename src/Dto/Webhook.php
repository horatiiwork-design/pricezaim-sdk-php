<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Dto;

use InvalidArgumentException;

/**
 * DTO вебхука без секрета: API не возвращает секрет после создания.
 */
final readonly class Webhook
{
    /**
     * Создаёт DTO из уже разобранных полей.
     *
     * @param list<string> $events
     * @param array<string, string> $headers
     * @param array<string, mixed> $raw
     */
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

    /**
     * Создаёт DTO вебхука из ответа API.
     *
     * @param array<string, mixed> $data Данные вебхука.
     *
     * @throws \InvalidArgumentException Если отсутствует id.
     */
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

    /**
     * Возвращает исходные данные API без потери неизвестных полей.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }
}
