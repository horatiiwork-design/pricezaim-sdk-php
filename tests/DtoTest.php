<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pricezaim\Sdk\Dto\Auction;
use Pricezaim\Sdk\Dto\Bid;
use Pricezaim\Sdk\Dto\Deal;
use Pricezaim\Sdk\Dto\DealDocument;
use Pricezaim\Sdk\Dto\Webhook;
use Pricezaim\Sdk\Page;

final class DtoTest extends TestCase
{
    public function test_auction_dto_maps_nested_client_profile_and_keeps_raw(): void
    {
        $data = [
            'id' => 991,
            'uuid' => 'c7a17db8-0daf-4bff-b2f6-2873e66e5e6b',
            'title' => 'Мой первый аукцион',
            'status' => 'active',
            'is_own' => false,
            'can_bid' => true,
            'can_manage' => false,
            'required_amount' => 600000,
            'city' => 'Москва',
            'client_profile' => [
                'last_name' => 'Иванов',
                'inn' => '500100732259',
                'income_salary' => 120000,
                'marketing_agreed' => true,
            ],
            'photos' => ['car' => []],
            'bids_count' => 2,
        ];

        $auction = Auction::fromArray($data);

        $this->assertSame(991, $auction->id);
        $this->assertSame('active', $auction->status);
        $this->assertTrue($auction->canBid);
        $this->assertFalse($auction->isOwn);
        $this->assertSame(600000.0, $auction->requiredAmount);
        $this->assertSame('Иванов', $auction->clientProfile?->lastName);
        $this->assertSame('500100732259', $auction->clientProfile?->inn);
        $this->assertSame(120000, $auction->clientProfile?->incomeSalary);
        $this->assertTrue($auction->clientProfile?->marketingAgreed);
        $this->assertSame($data, $auction->toArray());
    }

    public function test_auction_dto_requires_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Auction::fromArray(['title' => 'No id']);
    }

    public function test_bid_deal_and_webhook_dtos(): void
    {
        $bid = Bid::fromArray([
            'id' => 477,
            'amount' => 150000,
            'interest_rate' => 19.5,
            'term' => 24,
            'bid_number' => 1,
            'partner_id' => 8,
        ], 991);

        $this->assertSame(477, $bid->id);
        $this->assertSame(991, $bid->auctionId);
        $this->assertSame(150000.0, $bid->amount);
        $this->assertSame(19.5, $bid->interestRate);

        $deal = Deal::fromArray([
            'id' => 157,
            'auction_id' => 991,
            'status' => 'active',
            'documents' => [[
                'id' => '12199020-b13e-4d81-b2ae-632bda05cd07',
                'original_name' => 'договор.pdf',
                'mime' => 'application/pdf',
                'size' => 24035,
            ]],
        ]);

        $this->assertSame(157, $deal->id);
        $this->assertCount(1, $deal->documentObjects());
        $this->assertInstanceOf(DealDocument::class, $deal->documentObjects()[0]);
        $this->assertSame('договор.pdf', $deal->documentObjects()[0]->originalName);

        $webhook = Webhook::fromArray([
            'id' => 172,
            'url' => 'https://example.com/hook',
            'events' => ['auction.started'],
            'headers' => ['X-Integration' => 'my-1c'],
            'active' => true,
            'has_secret' => true,
        ]);

        $this->assertSame(172, $webhook->id);
        $this->assertSame(['auction.started'], $webhook->events);
        $this->assertTrue($webhook->hasSecret);
    }

    public function test_page_map_preserves_meta_and_raw_response(): void
    {
        $page = Page::fromArray([
            'data' => [['id' => 1], ['id' => 2]],
            'meta' => ['total' => 2, 'current_page' => 1, 'last_page' => 1, 'per_page' => 15],
        ]);

        $mapped = $page->map(static fn (array $item): int => $item['id']);

        $this->assertSame([1, 2], $mapped->items());
        $this->assertSame(2, $mapped->total());
        $this->assertSame($page->raw(), $mapped->raw());
    }
}
