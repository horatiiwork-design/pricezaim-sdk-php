<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Exception;

use RuntimeException;
use Throwable;

/**
 * Сетевая ошибка или таймаут.
 */
class TransportException extends RuntimeException
{
    /**
     * Создаёт исключение сетевой ошибки.
     *
     * @param string $message Описание ошибки.
     * @param \Throwable|null $previous Исходное исключение.
     */
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
