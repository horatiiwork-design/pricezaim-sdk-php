<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Pricezaim\Sdk\Webhook\WebhookSignature;

final class WebhookSignatureTest extends TestCase
{
    public function test_valid_signature(): void
    {
        $payload = '{"event":"deal.closed","id":1}';
        $secret = 'super-secret';
        $signature = WebhookSignature::sign($payload, $secret);

        $this->assertTrue(WebhookSignature::verify($payload, $secret, $signature));
    }

    public function test_tampered_payload_is_rejected(): void
    {
        $signature = WebhookSignature::sign('{"id":1}', 'super-secret');

        $this->assertFalse(WebhookSignature::verify('{"id":2}', 'super-secret', $signature));
    }

    public function test_old_timestamp_is_rejected(): void
    {
        $payload = '{"id":1}';
        $secret = 'super-secret';

        $this->assertFalse(WebhookSignature::verify(
            $payload,
            $secret,
            WebhookSignature::sign($payload, $secret),
            gmdate(DATE_ATOM, time() - 3600),
            300,
        ));
    }
}
