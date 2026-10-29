<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Resources;

use Pricezaim\Sdk\Dto\Bid;
use Pricezaim\Sdk\Page;

/**
 * Ресурс ставок.
 */
final class Bids extends Resource
{
    /**
     * Возвращает страницу своих ставок.
     *
     * @param array<string, mixed> $filters Фильтры: status, page, per_page.
     * @return Page Страница ставок.
     */
    public function my(array $filters = []): Page
    {
        return Page::fromArray($this->client->get('/v1/bids/my', $filters));
    }

    /**
     * Возвращает актуальные ставки аукциона.
     *
     * @param int|string $auctionId ID аукциона.
     * @param array<string, mixed> $filters Пагинация.
     * @return Page Страница ставок.
     */
    public function forAuction(int|string $auctionId, array $filters = []): Page
    {
        return Page::fromArray($this->client->get(
            '/v1/auctions/'.rawurlencode((string) $auctionId).'/bids',
            $filters,
        ));
    }

    /**
     * Возвращает свои ставки в виде DTO.
     *
     * @param array<string, mixed> $filters Фильтры API.
     * @return Page Страница с элементами Bid.
     */
    public function myDto(array $filters = []): Page
    {
        return $this->my($filters)->map(static fn (array $bid): Bid => Bid::fromArray($bid));
    }

    /**
     * Возвращает ставки аукциона в виде DTO.
     *
     * @param int|string $auctionId ID аукциона.
     * @param array<string, mixed> $filters Пагинация.
     * @return Page Страница с элементами Bid.
     */
    public function forAuctionDto(int|string $auctionId, array $filters = []): Page
    {
        return $this->forAuction($auctionId, $filters)
            ->map(static fn (array $bid): Bid => Bid::fromArray($bid, (int) $auctionId));
    }

    /**
     * Размещает или заменяет ставку партнёра на аукционе.
     *
     * @param int|string $auctionId ID аукциона.
     * @param array<string, mixed> $payload Поля amount, interest_rate, term, comment.
     * @param string|null $idempotencyKey Ключ идемпотентности.
     * @return array<string, mixed> Ответ API с блоком bid.
     */
    public function place(int|string $auctionId, array $payload, ?string $idempotencyKey = null): array
    {
        return $this->client->post(
            '/v1/auctions/'.rawurlencode((string) $auctionId).'/bids',
            $payload,
            $idempotencyKey,
        );
    }

    /**
     * Размещает ставку и возвращает её DTO.
     *
     * @param int|string $auctionId ID аукциона.
     * @param array<string, mixed> $payload Поля ставки.
     * @param string|null $idempotencyKey Ключ идемпотентности.
     */
    public function placeDto(int|string $auctionId, array $payload, ?string $idempotencyKey = null): Bid
    {
        $result = $this->place($auctionId, $payload, $idempotencyKey);
        $bid = $result['bid'] ?? [];

        return Bid::fromArray(is_array($bid) ? $bid : [], (int) $auctionId);
    }
}
