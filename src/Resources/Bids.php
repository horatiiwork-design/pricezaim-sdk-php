<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Resources;

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

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function place(int|string $auctionId, array $payload, ?string $idempotencyKey = null): array
    {
        return $this->client->post(
            '/v1/auctions/'.rawurlencode((string) $auctionId).'/bids',
            $payload,
            $idempotencyKey,
        );
    }
}
