<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Webhook;

/**
 * Известные типы вебхуков pricezaim.
 *
 * Значения соответствуют каталогу событий API. `WebhookTest` — служебное
 * событие тестового пинга, оно не входит в обычный каталог подписок.
 */
enum WebhookEventType: string
{
    case DealCreated = 'deal.created';
    case DealApproved = 'deal.approved';
    case DealClosed = 'deal.closed';
    case DealDocumentsUploaded = 'deal.documents_uploaded';

    case AuctionStarted = 'auction.started';
    case AuctionEnded = 'auction.ended';
    case AuctionClosed = 'auction.closed';
    case AuctionRestarted = 'auction.restarted';
    case AuctionCancelled = 'auction.cancelled';

    case ChatAuctionMessage = 'chat.auction_message';
    case ChatDealMessage = 'chat.deal_message';
    case ChatSupportMessage = 'chat.support_message';

    case BidPlacedOwn = 'bid.placed.own';
    case BidPlacedAny = 'bid.placed.any';
    case BidPlacedParticipating = 'bid.placed.participating';
    case BidOutbid = 'bid.outbid';

    case WebhookTest = 'webhook.test';

    /**
     * Возвращает все известные имена событий.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    /**
     * Проверяет, входит ли имя в каталог известных событий.
     *
     * @param string $name Имя события.
     */
    public static function isKnown(string $name): bool
    {
        return self::tryFrom($name) !== null;
    }
}
