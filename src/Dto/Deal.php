<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Dto;

use InvalidArgumentException;

final readonly class Deal
{
    /**
     * @param array<string, mixed> $client
     * @param array<string, mixed> $bid
     * @param array<string, mixed> $auction
     * @param array<int, array<string, mixed>> $documents
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public ?string $uuid,
        public ?int $auctionId,
        public ?int $partnerId,
        public ?int $managerId,
        public ?int $clientId,
        public ?string $status,
        public ?string $statusName,
        public ?string $notes,
        public mixed $requisites,
        public array $documents,
        public array $client,
        public ?ClientProfile $clientProfile,
        public array $bid,
        public array $auction,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $completedAt,
        public ?string $cancelledAt,
        public array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = ArrayValue::int($data, 'id');
        if ($id === null) {
            throw new InvalidArgumentException('Deal payload must contain an integer id.');
        }

        $profile = ArrayValue::arrayOrNull($data, 'client_profile');

        return new self(
            id: $id,
            uuid: ArrayValue::string($data, 'uuid'),
            auctionId: ArrayValue::int($data, 'auction_id'),
            partnerId: ArrayValue::int($data, 'partner_id'),
            managerId: ArrayValue::int($data, 'manager_id'),
            clientId: ArrayValue::int($data, 'client_id'),
            status: ArrayValue::string($data, 'status'),
            statusName: ArrayValue::string($data, 'status_name'),
            notes: ArrayValue::string($data, 'notes'),
            requisites: $data['requisites'] ?? null,
            documents: ArrayValue::list($data, 'documents'),
            client: ArrayValue::array($data, 'client'),
            clientProfile: $profile === null ? null : ClientProfile::fromArray($profile),
            bid: ArrayValue::array($data, 'bid'),
            auction: ArrayValue::array($data, 'auction'),
            createdAt: ArrayValue::string($data, 'created_at'),
            updatedAt: ArrayValue::string($data, 'updated_at'),
            completedAt: ArrayValue::string($data, 'completed_at'),
            cancelledAt: ArrayValue::string($data, 'cancelled_at'),
            raw: $data,
        );
    }

    /** @return list<DealDocument> */
    public function documentObjects(): array
    {
        return array_map(
            static fn (array $document): DealDocument => DealDocument::fromArray($document),
            array_values(array_filter($this->documents, 'is_array')),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->raw;
    }
}
