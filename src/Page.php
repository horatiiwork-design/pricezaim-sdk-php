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
 * @implements IteratorAggregate<int, mixed>
 */
final class Page implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * Создаёт страницу результатов.
     *
     * @param array<int, mixed> $items Элементы текущей страницы.
     * @param array<string, mixed> $meta Метаданные пагинации.
     * @param array<string, mixed> $raw Исходный ответ API.
     */
    public function __construct(
        private readonly array $items,
        private readonly array $meta = [],
        private readonly array $raw = [],
    ) {
    }

    /**
     * Создаёт страницу из ответа API с ключами data и meta.
     *
     * Если meta отсутствует, верхнеуровневые поля пагинации переносятся в meta.
     *
     * @param array<string, mixed> $response Ответ API.
     * @return self Страница результатов.
     */
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

    /**
     * Возвращает элементы текущей страницы.
     *
     * @return array<int, mixed>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Псевдоним items() для совместимости с JSON-ответом API.
     *
     * @return array<int, mixed>
     */
    public function data(): array
    {
        return $this->items;
    }

    /**
     * Возвращает метаданные пагинации.
     *
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * Возвращает исходный ответ API.
     *
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }

    /**
     * Возвращает общее количество записей.
     *
     * Если meta.total отсутствует, возвращает количество элементов на странице.
     */
    public function total(): int
    {
        return (int) ($this->meta['total'] ?? count($this->items));
    }

    /**
     * Возвращает номер текущей страницы.
     */
    public function currentPage(): int
    {
        return (int) ($this->meta['current_page'] ?? 1);
    }

    /**
     * Возвращает номер последней страницы.
     */
    public function lastPage(): int
    {
        return (int) ($this->meta['last_page'] ?? 1);
    }

    /**
     * Возвращает размер страницы.
     */
    public function perPage(): int
    {
        return (int) ($this->meta['per_page'] ?? count($this->items));
    }

    /**
     * Проверяет, что текущая страница пуста.
     */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * Преобразует элементы страницы, сохраняя meta и исходный ответ.
     *
     * @param callable(mixed): mixed $mapper Функция преобразования элемента.
     * @return self Новая страница с преобразованными элементами.
     */
    public function map(callable $mapper): self
    {
        return new self(
            array_values(array_map($mapper, $this->items)),
            $this->meta,
            $this->raw,
        );
    }

    /**
     * Возвращает количество элементов на текущей странице.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Позволяет перебирать элементы страницы через foreach.
     *
     * @return \Traversable<int, mixed>
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    /**
     * Возвращает исходный ответ API при json_encode().
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }
}
