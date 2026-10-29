<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Exception;

class ValidationException extends ApiException
{
    /** @param array<string, mixed> $body @param array<string, string> $headers @param array<string, array<int, string>> $validationErrors */
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

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->validationErrors;
    }
}
