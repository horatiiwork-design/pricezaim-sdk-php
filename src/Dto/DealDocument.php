<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Dto;

use InvalidArgumentException;

final readonly class DealDocument
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public string $id,
        public ?string $originalName,
        public ?string $path,
        public ?string $mime,
        public ?int $size,
        public ?string $uploadedBy,
        public ?string $uploadedAt,
        public array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = ArrayValue::string($data, 'id');
        if ($id === null || $id === '') {
            throw new InvalidArgumentException('Deal document payload must contain an id.');
        }

        return new self(
            id: $id,
            originalName: ArrayValue::string($data, 'original_name'),
            path: ArrayValue::string($data, 'path'),
            mime: ArrayValue::string($data, 'mime'),
            size: ArrayValue::int($data, 'size'),
            uploadedBy: ArrayValue::string($data, 'uploaded_by'),
            uploadedAt: ArrayValue::string($data, 'uploaded_at'),
            raw: $data,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->raw;
    }
}
