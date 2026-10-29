<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Webhook;

/**
 * Проверка HMAC-подписи входящих вебхуков.
 */
final class WebhookSignature
{
    /**
     * Проверяет HMAC-SHA256 подпись вебхука.
     *
     * @param string $payload Сырое тело запроса.
     * @param string $secret Секрет вебхука.
     * @param string $signature Значение X-Webhook-Signature.
     * @param string|null $timestamp Значение X-Webhook-Timestamp.
     * @param int $tolerance Допустимое расхождение времени в секундах; 0 отключает проверку.
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
     * Проверяет подпись по массиву заголовков запроса.
     *
     * @param string $payload Сырое тело запроса.
     * @param string $secret Секрет вебхука.
     * @param array<string, mixed> $headers Заголовки getallheaders() или $_SERVER.
     * @param int $tolerance Допустимое расхождение времени в секундах.
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

    /**
     * Считает HMAC-SHA256 подпись для тела вебхука.
     *
     * @param string $payload Сырое тело запроса.
     * @param string $secret Секрет вебхука.
     * @return string Hex-подпись.
     */
    public static function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Ищет заголовок без учёта регистра.
     *
     * Поддерживает имена X-Webhook-* и HTTP_X_WEBHOOK_*.
     *
     * @param array<string, mixed> $headers Заголовки.
     * @param string $name Имя заголовка.
     */
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
