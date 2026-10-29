<?php

declare(strict_types=1);

namespace Pricezaim\Sdk;

use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Пагинированный ответ API: data + meta.
 *
 * @implements IteratorAggregate<int, array<string, mixed>>
 */
final class Page implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $raw
     */
    public function __construct(
        private readonly array $items,
        private readonly array $meta = [],
        private readonly array $raw = [],
    ) {
    }

    /** @param array<string, mixed> $response */
    public static function fromArray(array $response): self
    {
        $items = $response['data'] ?? [];
        if (! is_array($items)) {
            $items = [];
        }

        $meta = $response['meta'] ?? [];
        if (! is_array($meta)) {
            $meta = [];
        }

        foreach (['total', 'current_page', 'last_page', 'per_page', 'from', 'to'] as $key) {
            if (array_key_exists($key, $response) && ! array_key_exists($key, $meta)) {
                $meta[$key] = $response[$key];
            }
        }

        return new self(array_values($items), $meta, $response);
    }

    /** @return array<int, array<string, mixed>> */
    public function items(): array
    {
        return $this->items;
    }

    /** @return array<int, array<string, mixed>> */
    public function data(): array
    {
        return $this->items;
    }

    /** @return array<string, mixed> */
    public function meta(): array
    {
        return $this->meta;
    }

    /** @return array<string, mixed> */
    public function raw(): array
    {
        return $this->raw;
    }

    public function total(): int
    {
        return (int) ($this->meta['total'] ?? count($this->items));
    }

    public function currentPage(): int
    {
        return (int) ($this->meta['current_page'] ?? 1);
    }

    public function lastPage(): int
    {
        return (int) ($this->meta['last_page'] ?? 1);
    }

    public function perPage(): int
    {
        return (int) ($this->meta['per_page'] ?? count($this->items));
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function count(): int
    {
        return count($this->items);
    }

    /** @return Traversable<int, array<string, mixed>> */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }
}
