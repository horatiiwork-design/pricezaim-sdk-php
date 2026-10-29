<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Dto;

use InvalidArgumentException;

final readonly class Bid
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public int $id,
        public ?int $auctionId,
        public ?float $amount,
        public ?float $interestRate,
        public ?string $ratePeriod,
        public bool $isFinal,
        public ?int $term,
        public ?string $comment,
        public ?int $bidNumber,
        public ?string $partnerName,
        public ?int $partnerId,
        public ?string $createdAt,
        public array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, ?int $auctionId = null): self
    {
        $id = ArrayValue::int($data, 'id');
        if ($id === null) {
            throw new InvalidArgumentException('Bid payload must contain an integer id.');
        }

        return new self(
            id: $id,
            auctionId: $auctionId,
            amount: ArrayValue::float($data, 'amount'),
            interestRate: ArrayValue::float($data, 'interest_rate'),
            ratePeriod: ArrayValue::string($data, 'rate_period'),
            isFinal: ArrayValue::bool($data, 'is_final'),
            term: ArrayValue::int($data, 'term'),
            comment: ArrayValue::string($data, 'comment'),
            bidNumber: ArrayValue::int($data, 'bid_number'),
            partnerName: ArrayValue::string($data, 'partner_name'),
            partnerId: ArrayValue::int($data, 'partner_id'),
            createdAt: ArrayValue::string($data, 'created_at'),
            raw: $data,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->raw;
    }
}
