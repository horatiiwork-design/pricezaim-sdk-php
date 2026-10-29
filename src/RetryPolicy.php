<?php

declare(strict_types=1);

namespace Pricezaim\Sdk;

/**
 * Политика автоповторов.
 *
 * По умолчанию выключена. Включается только переменной окружения
 * `PRICEZAIM_SDK_RETRIES_ENABLED=true|1|yes|on`.
 *
 * Дополнительно:
 * - `PRICEZAIM_SDK_RETRY_ATTEMPTS` — всего попыток, по умолчанию 3;
 * - `PRICEZAIM_SDK_RETRY_BASE_DELAY_MS` — базовая пауза, по умолчанию 200 мс.
 */
final class RetryPolicy
{
    /**
     * Создаёт политику автоповторов.
     *
     * @param bool $enabled Включены ли повторы.
     * @param int $maxAttempts Общее число попыток, включая первую.
     * @param int $baseDelayMs Базовая задержка в миллисекундах.
     */
    public function __construct(
        public bool $enabled = false,
        public int $maxAttempts = 3,
        public int $baseDelayMs = 200,
    ) {
        $this->maxAttempts = max(1, $this->maxAttempts);
    }

    /**
     * Создаёт политику из переменных окружения.
     *
     * Флаг PRICEZAIM_SDK_RETRIES_ENABLED выключен по умолчанию.
     */
    public static function fromEnvironment(): self
    {
        $attempts = getenv('PRICEZAIM_SDK_RETRY_ATTEMPTS');
        $baseDelay = getenv('PRICEZAIM_SDK_RETRY_BASE_DELAY_MS');

        return new self(
            enabled: self::envBool('PRICEZAIM_SDK_RETRIES_ENABLED'),
            maxAttempts: $attempts === false ? 3 : max(1, (int) $attempts),
            baseDelayMs: $baseDelay === false ? 200 : max(0, (int) $baseDelay),
        );
    }

    /**
     * Возвращает выключенную политику без повторов.
     */
    public static function disabled(): self
    {
        return new self(false);
    }

    /**
     * Проверяет, можно ли повторять запрос с учётом метода и номера попытки.
     *
     * GET/HEAD/PUT/DELETE/OPTIONS считаются безопасными. POST/PATCH требуют
     * заголовок Idempotency-Key.
     *
     * @param string $method HTTP-метод.
     * @param array<string, mixed> $headers Заголовки запроса.
     * @param int $attempt Номер текущей попытки, начиная с 1.
     */
    public function allowsRetry(string $method, array $headers, int $attempt): bool
    {
        if (! $this->enabled || $attempt >= $this->maxAttempts) {
            return false;
        }

        $method = strtoupper($method);

        if (in_array($method, ['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS'], true)) {
            return true;
        }

        if (in_array($method, ['POST', 'PATCH'], true)) {
            return self::hasHeader($headers, 'Idempotency-Key');
        }

        return false;
    }

    /**
     * Проверяет, является ли HTTP-статус временной ошибкой.
     *
     * @param int $status HTTP-статус.
     */
    public function shouldRetryStatus(int $status): bool
    {
        return in_array($status, [429, 500, 502, 503, 504], true);
    }

    /**
     * Рассчитывает задержку перед следующей попыткой.
     *
     * @param int $attempt Номер текущей попытки.
     * @param int|null $retryAfterSeconds Значение Retry-After в секундах.
     * @return int Задержка в миллисекундах.
     */
    public function delayMs(int $attempt, ?int $retryAfterSeconds = null): int
    {
        if ($retryAfterSeconds !== null) {
            return min(30_000, max(0, $retryAfterSeconds * 1000));
        }

        return min(5_000, $this->baseDelayMs * (2 ** max(0, $attempt - 1)));
    }

    /**
     * Разбирает Retry-After: число секунд или HTTP-дату.
     *
     * @param string $value Значение заголовка.
     * @return int|null Секунды до повтора или null, если заголовок пуст/некорректен.
     */
    public static function retryAfterSeconds(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : max(0, $timestamp - time());
    }

    /**
     * Проверяет наличие непустого заголовка без учёта регистра.
     *
     * @param array<string, mixed> $headers Заголовки.
     * @param string $name Имя заголовка.
     */
    private static function hasHeader(array $headers, string $name): bool
    {
        $target = strtolower($name);

        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === $target && $value !== '' && $value !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Читает boolean-флаг из env.
     *
     * @param string $name Имя переменной окружения.
     */
    private static function envBool(string $name): bool
    {
        $value = getenv($name);

        if ($value === false) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
