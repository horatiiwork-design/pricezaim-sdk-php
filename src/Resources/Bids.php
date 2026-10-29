<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Resources;

use Pricezaim\Sdk\Dto\Bid;
use Pricezaim\Sdk\Page;

final class Bids extends Resource
{
    /** @param array<string, mixed> $filters */
    public function my(array $filters = []): Page
    {
        return Page::fromArray($this->client->get('/v1/bids/my', $filters));
    }

    /** @param array<string, mixed> $filters */
    public function forAuction(int|string $auctionId, array $filters = []): Page
    {
        return Page::fromArray($this->client->get(
            '/v1/auctions/'.rawurlencode((string) $auctionId).'/bids',
            $filters,
        ));
    }

    public function myDto(array $filters = []): Page
    {
        return $this->my($filters)->map(static fn (array $bid): Bid => Bid::fromArray($bid));
    }

    public function forAuctionDto(int|string $auctionId, array $filters = []): Page
    {
        return $this->forAuction($auctionId, $filters)
            ->map(static fn (array $bid): Bid => Bid::fromArray($bid, (int) $auctionId));
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function place(int|string $auctionId, array $payload, ?string $idempotencyKey = null): array
    {
        return $this->client->post(
            '/v1/auctions/'.rawurlencode((string) $auctionId).'/bids',
            $payload,
            $idempotencyKey,
        );
    }

    /** @param array<string, mixed> $payload */
    public function placeDto(int|string $auctionId, array $payload, ?string $idempotencyKey = null): Bid
    {
        $result = $this->place($auctionId, $payload, $idempotencyKey);
        $bid = $result['bid'] ?? [];

        return Bid::fromArray(is_array($bid) ? $bid : [], (int) $auctionId);
    }
}
