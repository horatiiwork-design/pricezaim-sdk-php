<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Exception;

/**
 * Ошибка валидации 422.
 */
class ValidationException extends ApiException
{
    /**
     * Создаёт исключение ошибки валидации 422.
     *
     * @param int $status HTTP-статус.
     * @param string|null $errorCode Код ошибки.
     * @param string $message Сообщение.
     * @param array<string, mixed> $body Тело ответа.
     * @param array<string, string> $headers Заголовки.
     * @param string|null $requestId X-Request-Id.
     * @param int|null $retryAfter Retry-After.
     * @param array<string, array<int, string>> $validationErrors Ошибки полей.
     */
    public function __construct(
        int $status,
        ?string $errorCode,
        string $message,
        array $body = [],
        array $headers = [],
        ?string $requestId = null,
        ?int $retryAfter = null,
        private readonly array $validationErrors = [],
    ) {
        parent::__construct($status, $errorCode, $message, $body, $headers, $requestId, $retryAfter);
    }

    /**
     * Возвращает ошибки полей из ответа 422.
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->validationErrors;
    }
}
