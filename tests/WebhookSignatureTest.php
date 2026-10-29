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

    public function test_verify_request_reads_headers_case_insensitively(): void
    {
        $payload = '{"event":"auction.started","id":1}';
        $secret = 'super-secret';
        $timestamp = gmdate(DATE_ATOM);

        $this->assertTrue(WebhookSignature::verifyRequest($payload, $secret, [
            'x-webhook-signature' => WebhookSignature::sign($payload, $secret),
            'X-Webhook-Timestamp' => $timestamp,
        ]));
    }

    public function test_verify_request_supports_server_header_names(): void
    {
        $payload = '{"event":"auction.started","id":1}';
        $secret = 'super-secret';
        $timestamp = gmdate(DATE_ATOM);

        $this->assertTrue(WebhookSignature::verifyRequest($payload, $secret, [
            'HTTP_X_WEBHOOK_SIGNATURE' => WebhookSignature::sign($payload, $secret),
            'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
        ]));
    }

    public function test_verify_request_rejects_missing_signature(): void
    {
        $this->assertFalse(WebhookSignature::verifyRequest('{"id":1}', 'super-secret', []));
    }

    public function test_verify_request_rejects_old_timestamp(): void
    {
        $payload = '{"id":1}';
        $secret = 'super-secret';

        $this->assertFalse(WebhookSignature::verifyRequest($payload, $secret, [
            'X-Webhook-Signature' => WebhookSignature::sign($payload, $secret),
            'X-Webhook-Timestamp' => gmdate(DATE_ATOM, time() - 3600),
        ]));
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
