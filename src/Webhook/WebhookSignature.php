<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Webhook;

final class WebhookSignature
{
    /**
     * Проверить подпись входящего вебхука.
     *
     * @param string $payload сырое тело запроса (не json_encode повторно!)
     * @param string $secret секрет вебхука
     * @param string $signature значение X-Webhook-Signature (hex)
     * @param string|null $timestamp значение X-Webhook-Timestamp (ISO-8601)
     * @param int $tolerance допустимое расхождение времени в секундах; 0 — не проверять
     */
    public static function verify(
        string $payload,
        string $secret,
        string $signature,
        ?string $timestamp = null,
        int $tolerance = 300,
    ): bool {
        if ($secret === '' || $signature === '') {
            return false;
        }

        if ($tolerance > 0 && $timestamp !== null && $timestamp !== '') {
            $sentAt = strtotime($timestamp);
            if ($sentAt === false || abs(time() - $sentAt) > $tolerance) {
                return false;
            }
        }

        $expected = self::sign($payload, $secret);

        return hash_equals($expected, strtolower($signature));
    }

    /**
     * Проверить подпись по массиву заголовков запроса.
     *
     * Подходит для getallheaders() и $_SERVER. Имена заголовков
     * регистронезависимы; поддерживаются оба варианта:
     * `X-Webhook-Signature` и `HTTP_X_WEBHOOK_SIGNATURE`.
     *
     * @param array<string, mixed> $headers
     */
    public static function verifyRequest(
        string $payload,
        string $secret,
        array $headers,
        int $tolerance = 300,
    ): bool {
        $signature = self::header($headers, 'X-Webhook-Signature');
        if ($signature === null || $signature === '') {
            return false;
        }

        return self::verify(
            $payload,
            $secret,
            $signature,
            self::header($headers, 'X-Webhook-Timestamp'),
            $tolerance,
        );
    }

    public static function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /** @param array<string, mixed> $headers */
    private static function header(array $headers, string $name): ?string
    {
        $target = strtolower($name);
        $serverKey = 'http_'.str_replace('-', '_', $target);

        foreach ($headers as $key => $value) {
            $normalized = strtolower((string) $key);
            if ($normalized !== $target && $normalized !== $serverKey) {
                continue;
            }

            if (is_array($value)) {
                $value = reset($value);
            }

            return is_scalar($value) ? (string) $value : null;
        }

        return null;
    }
}
