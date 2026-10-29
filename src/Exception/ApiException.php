<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Ошибка HTTP API.
 */
class ApiException extends RuntimeException
{
    /**
     * Создаёт исключение HTTP-ошибки API.
     *
     * @param int $status HTTP-статус.
     * @param string|null $errorCode Код ошибки из body.code.
     * @param string $message Человекочитаемое сообщение.
     * @param array<string, mixed> $body Тело ответа.
     * @param array<string, string> $headers Заголовки ответа.
     * @param string|null $requestId X-Request-Id.
     * @param int|null $retryAfter Секунды из Retry-After.
     */
    public function __construct(
        private readonly int $status,
        private readonly ?string $errorCode,
        string $message,
        private readonly array $body = [],
        private readonly array $headers = [],
        private readonly ?string $requestId = null,
        private readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message, $status);
    }

    /**
     * Создаёт исключение из PSR-7-ответа.
     *
     * Для 422 возвращает ValidationException.
     *
     * @param \Psr\Http\Message\ResponseInterface $response Ответ API.
     * @param string $rawBody Сырое тело ответа.
     */
    public static function fromResponse(ResponseInterface $response, string $rawBody): self
    {
        $body = json_decode($rawBody, true);
        if (! is_array($body)) {
            $body = [];
        }

        $status = $response->getStatusCode();
        $message = (string) ($body['message'] ?? $body['detail'] ?? $body['error'] ?? 'API request failed');
        $errorCode = isset($body['code']) ? (string) $body['code'] : null;
        $requestId = $response->getHeaderLine('X-Request-Id') ?: null;
        $retryAfter = self::parseRetryAfter($response->getHeaderLine('Retry-After'));

        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        if ($status === 422) {
            return new ValidationException(
                status: $status,
                errorCode: $errorCode,
                message: $message,
                body: $body,
                headers: $headers,
                requestId: $requestId,
                retryAfter: $retryAfter,
                validationErrors: is_array($body['errors'] ?? null) ? $body['errors'] : [],
            );
        }

        return new self(
            status: $status,
            errorCode: $errorCode,
            message: $message,
            body: $body,
            headers: $headers,
            requestId: $requestId,
            retryAfter: $retryAfter,
        );
    }

    /**
     * Возвращает HTTP-статус.
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Возвращает код ошибки API.
     */
    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Возвращает тело ответа.
     */
    public function body(): array
    {
        return $this->body;
    }

    /**
     * Возвращает заголовки ответа.
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Возвращает X-Request-Id.
     */
    public function requestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Возвращает задержку из Retry-After в секундах.
     */
    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Проверяет, является ли ошибка 429.
     */
    public function isRateLimited(): bool
    {
        return $this->status === 429;
    }

    /**
     * Разбирает Retry-After: секунды или HTTP-дату.
     */
    private static function parseRetryAfter(string $value): ?int
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
}
