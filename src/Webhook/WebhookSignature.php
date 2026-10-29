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

    public static function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }
}
