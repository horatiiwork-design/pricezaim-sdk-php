<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ApiException extends RuntimeException
{
    /** @param array<string, mixed> $body @param array<string, string> $headers */
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

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    /** @return array<string, mixed> */
    public function body(): array
    {
        return $this->body;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function isRateLimited(): bool
    {
        return $this->status === 429;
    }

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
