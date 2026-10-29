<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Dto;

/**
 * Внутренние помощники для терпимого разбора ответов API.
 *
 * @internal
 */
final class ArrayValue
{
    /**
     * Приводит значение к int или возвращает null.
     *
     * @param array<string, mixed> $data Исходные данные.
     * @param string $key Имя ключа.
     */
    public static function int(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && is_numeric($value) ? (int) $value : null;
    }

    /**
     * Приводит значение к float или возвращает null.
     *
     * @param array<string, mixed> $data Исходные данные.
     * @param string $key Имя ключа.
     */
    public static function float(array $data, string $key): ?float
    {
        $value = $data[$key] ?? null;

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        return is_string($value) && is_numeric($value) ? (float) $value : null;
    }

    /**
     * Приводит скалярное значение к string или возвращает null.
     *
     * @param array<string, mixed> $data Исходные данные.
     * @param string $key Имя ключа.
     */
    public static function string(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (is_string($value)) {
            return $value;
        }

        return is_int($value) || is_float($value) ? (string) $value : null;
    }

    /**
     * Приводит значение к bool с значением по умолчанию.
     *
     * @param array<string, mixed> $data Исходные данные.
     * @param string $key Имя ключа.
     */
    public static function bool(array $data, string $key, bool $default = false): bool
    {
        $value = $data[$key] ?? null;

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $default;
    }

    /**
     * Приводит значение к bool, сохраняя null для отсутствующего поля.
     *
     * @param array<string, mixed> $data Исходные данные.
     * @param string $key Имя ключа.
     */
    public static function boolOrNull(array $data, string $key): ?bool
    {
        $value = $data[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        return is_string($value) ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : null;
    }

    /**
     * Возвращает массив по ключу или пустой массив.
     *
     * @param array<string, mixed> $data Исходные данные.
     * @param string $key Имя ключа.
     * @return array<mixed>
     */
    public static function array(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /**
     * Возвращает массив по ключу или null.
     *
     * @param array<string, mixed> $data Исходные данные.
     * @param string $key Имя ключа.
     * @return array<mixed>|null
     */
    public static function arrayOrNull(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? $value : null;
    }

    /**
     * Возвращает список значений по ключу или пустой массив.
     *
     * @param array<string, mixed> $data Исходные данные.
     * @param string $key Имя ключа.
     * @return list<mixed>
     */
    public static function list(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? array_values($value) : [];
    }
}
