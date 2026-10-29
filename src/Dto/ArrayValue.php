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
    /** @param array<string, mixed> $data */
    public static function int(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && is_numeric($value) ? (int) $value : null;
    }

    /** @param array<string, mixed> $data */
    public static function float(array $data, string $key): ?float
    {
        $value = $data[$key] ?? null;

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        return is_string($value) && is_numeric($value) ? (float) $value : null;
    }

    /** @param array<string, mixed> $data */
    public static function string(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (is_string($value)) {
            return $value;
        }

        return is_int($value) || is_float($value) ? (string) $value : null;
    }

    /** @param array<string, mixed> $data */
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

    /** @param array<string, mixed> $data */
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

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public static function array(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /** @param array<string, mixed> $data @return array<string, mixed>|null */
    public static function arrayOrNull(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? $value : null;
    }

    /** @param array<string, mixed> $data @return list<mixed> */
    public static function list(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? array_values($value) : [];
    }
}
