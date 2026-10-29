<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pricezaim\Sdk\Webhook\WebhookEvent;
use Pricezaim\Sdk\Webhook\WebhookEventType;

final class WebhookEventTest extends TestCase
{
    public function test_parses_auction_started_envelope(): void
    {
        $event = WebhookEvent::fromJson(json_encode([
            'event_id' => 'fdf6c6fe-1ed1-48b8-b61f-9b67e534ba3b',
            'payload_version' => 1,
            'event' => 'auction.started',
            'timestamp' => '2026-10-29T12:00:00+00:00',
            'auction' => ['id' => 1022, 'status' => 'active'],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame('fdf6c6fe-1ed1-48b8-b61f-9b67e534ba3b', $event->id());
        $this->assertSame('auction.started', $event->name());
        $this->assertSame(WebhookEventType::AuctionStarted, $event->type());
        $this->assertTrue($event->isKnown());
        $this->assertTrue($event->is(WebhookEventType::AuctionStarted));
        $this->assertTrue($event->is('auction.started'));
        $this->assertSame(1022, $event->auctionId());
        $this->assertSame('active', $event->payload()['auction']['status']);
    }

    public function test_supports_numeric_event_id_and_deal_auction_id(): void
    {
        $event = WebhookEvent::fromArray([
            'event_id' => 73,
            'event' => 'deal.created',
            'deal' => ['auction_id' => 1008],
        ]);

        $this->assertSame('73', $event->id());
        $this->assertSame(WebhookEventType::DealCreated, $event->type());
        $this->assertSame(1008, $event->auctionId());
    }

    public function test_unknown_event_is_allowed_but_not_known(): void
    {
        $event = WebhookEvent::fromArray([
            'event_id' => 'future-1',
            'event' => 'auction.future_event',
        ]);

        $this->assertNull($event->type());
        $this->assertFalse($event->isKnown());
        $this->assertFalse($event->is('auction.started'));
        $this->assertNull($event->auctionId());
    }

    public function test_missing_event_id_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WebhookEvent::fromArray(['event' => 'auction.started']);
    }

    public function test_empty_event_name_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WebhookEvent::fromArray(['event_id' => '1', 'event' => '']);
    }

    public function test_invalid_json_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WebhookEvent::fromJson('{broken');
    }

    public function test_event_type_catalog(): void
    {
        $this->assertCount(17, WebhookEventType::cases());
        $this->assertTrue(WebhookEventType::isKnown('auction.started'));
        $this->assertTrue(WebhookEventType::isKnown('webhook.test'));
        $this->assertFalse(WebhookEventType::isKnown('auction.created'));
        $this->assertContains('bid.outbid', WebhookEventType::values());
    }
}
