<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Dto;

use InvalidArgumentException;

/**
 * DTO карточки аукциона. Содержит основные поля и исходный ответ в `raw`.
 */
final readonly class Auction
{
    /**
     * Создаёт DTO из уже разобранных полей.
     */
    public function __construct(
        public int $id,
        public ?string $uuid,
        public ?string $title,
        public ?string $status,
        public bool $isOwn,
        public bool $canBid,
        public bool $canManage,
        public bool $canSelectWinner,
        public ?int $creatorId,
        public ?string $comment,
        public array $client,
        public ?ClientProfile $clientProfile,
        public ?string $city,
        public ?string $cityFias,
        public ?string $vin,
        public ?string $make,
        public ?string $model,
        public ?int $carYear,
        public ?string $engine,
        public ?string $fuelType,
        public ?int $power,
        public ?float $engineCapacity,
        public ?float $requiredAmount,
        public ?string $ptsSeries,
        public ?string $ptsNumber,
        public ?string $ptsDate,
        public ?string $ptsIssuedBy,
        public ?string $stsSeries,
        public ?string $stsNumber,
        public ?string $stsDate,
        public array $photos,
        public int $bidsCount,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $startedAt,
        public ?string $endsAt,
        public array $raw,
    ) {
    }

    /**
     * Создаёт DTO аукциона из ответа API.
     *
     * @param array<string, mixed> $data Данные аукциона.
     *
     * @throws \InvalidArgumentException Если отсутствует id.
     */
    public static function fromArray(array $data): self
    {
        $id = ArrayValue::int($data, 'id');
        if ($id === null) {
            throw new InvalidArgumentException('Auction payload must contain an integer id.');
        }

        $profile = ArrayValue::arrayOrNull($data, 'client_profile');

        return new self(
            id: $id,
            uuid: ArrayValue::string($data, 'uuid'),
            title: ArrayValue::string($data, 'title'),
            status: ArrayValue::string($data, 'status'),
            isOwn: ArrayValue::bool($data, 'is_own'),
            canBid: ArrayValue::bool($data, 'can_bid'),
            canManage: ArrayValue::bool($data, 'can_manage'),
            canSelectWinner: ArrayValue::bool($data, 'can_select_winner'),
            creatorId: ArrayValue::int($data, 'creator_id'),
            comment: ArrayValue::string($data, 'comment'),
            client: ArrayValue::array($data, 'client'),
            clientProfile: $profile === null ? null : ClientProfile::fromArray($profile),
            city: ArrayValue::string($data, 'city'),
            cityFias: ArrayValue::string($data, 'city_fias'),
            vin: ArrayValue::string($data, 'vin'),
            make: ArrayValue::string($data, 'make'),
            model: ArrayValue::string($data, 'model'),
            carYear: ArrayValue::int($data, 'car_year'),
            engine: ArrayValue::string($data, 'engine'),
            fuelType: ArrayValue::string($data, 'fuel_type'),
            power: ArrayValue::int($data, 'power'),
            engineCapacity: ArrayValue::float($data, 'engine_capacity'),
            requiredAmount: ArrayValue::float($data, 'required_amount'),
            ptsSeries: ArrayValue::string($data, 'pts_series'),
            ptsNumber: ArrayValue::string($data, 'pts_number'),
            ptsDate: ArrayValue::string($data, 'pts_date'),
            ptsIssuedBy: ArrayValue::string($data, 'pts_issued_by'),
            stsSeries: ArrayValue::string($data, 'sts_series'),
            stsNumber: ArrayValue::string($data, 'sts_number'),
            stsDate: ArrayValue::string($data, 'sts_date'),
            photos: ArrayValue::array($data, 'photos'),
            bidsCount: ArrayValue::int($data, 'bids_count') ?? 0,
            createdAt: ArrayValue::string($data, 'created_at'),
            updatedAt: ArrayValue::string($data, 'updated_at'),
            startedAt: ArrayValue::string($data, 'started_at'),
            endsAt: ArrayValue::string($data, 'ends_at'),
            raw: $data,
        );
    }

    /**
     * Возвращает исходные данные API без потери неизвестных полей.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }
}
