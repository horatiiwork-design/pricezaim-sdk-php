<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Pricezaim\Sdk\Client;
use Pricezaim\Sdk\Exception\ApiException;
use Pricezaim\Sdk\RetryPolicy;
use Psr\Http\Message\RequestInterface;

final class RetryTest extends TestCase
{
    /** @var array<int, RequestInterface> */
    private array $requests = [];

    protected function tearDown(): void
    {
        foreach ([
            'PRICEZAIM_SDK_RETRIES_ENABLED',
            'PRICEZAIM_SDK_RETRY_ATTEMPTS',
            'PRICEZAIM_SDK_RETRY_BASE_DELAY_MS',
        ] as $name) {
            putenv($name);
        }
    }

    private function enableRetries(int $attempts = 3): void
    {
        putenv('PRICEZAIM_SDK_RETRIES_ENABLED=1');
        putenv('PRICEZAIM_SDK_RETRY_ATTEMPTS='.$attempts);
        putenv('PRICEZAIM_SDK_RETRY_BASE_DELAY_MS=0');
    }

    private function client(MockHandler $mock): Client
    {
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request): RequestInterface {
            $this->requests[] = $request;

            return $request;
        }));

        return new Client('test-api-key', [
            'base_uri' => 'https://api.test',
            'http_client' => new GuzzleClient(['handler' => $stack]),
        ]);
    }

    private function json(int $status, array $body): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body));
    }

    public function test_retries_are_disabled_by_default(): void
    {
        putenv('PRICEZAIM_SDK_RETRIES_ENABLED');
        $mock = new MockHandler([
            $this->json(500, ['error' => 'temporary']),
            $this->json(200, ['data' => []]),
        ]);

        try {
            $this->client($mock)->auctions()->list();
            $this->fail('ApiException was not thrown');
        } catch (ApiException $e) {
            $this->assertSame(500, $e->status());
        }

        $this->assertCount(1, $this->requests);
    }

    public function test_get_retries_500_when_env_flag_enabled(): void
    {
        $this->enableRetries();
        $mock = new MockHandler([
            $this->json(500, ['error' => 'temporary']),
            $this->json(200, ['data' => [['id' => 1]]]),
        ]);

        $page = $this->client($mock)->auctions()->list();

        $this->assertSame(1, $page->total());
        $this->assertCount(2, $this->requests);
    }

    public function test_get_retries_429_and_honours_retry_after(): void
    {
        $this->enableRetries();
        $mock = new MockHandler([
            new Response(429, ['Content-Type' => 'application/json', 'Retry-After' => '0'], '{}'),
            $this->json(200, ['data' => []]),
        ]);

        $this->client($mock)->auctions()->list();

        $this->assertCount(2, $this->requests);
    }

    public function test_get_retries_transport_error(): void
    {
        $this->enableRetries();
        $mock = new MockHandler([
            new ConnectException('Connection refused', new Request('GET', 'https://api.test/v1/auctions')),
            $this->json(200, ['data' => []]),
        ]);

        $this->client($mock)->auctions()->list();

        $this->assertCount(2, $this->requests);
    }

    public function test_post_without_idempotency_key_is_not_retried(): void
    {
        $this->enableRetries();
        $mock = new MockHandler([
            $this->json(500, ['error' => 'temporary']),
            $this->json(201, ['success' => true, 'bid' => ['id' => 1]]),
        ]);

        try {
            $this->client($mock)->bids()->place(10, ['amount' => 1]);
            $this->fail('ApiException was not thrown');
        } catch (ApiException $e) {
            $this->assertSame(500, $e->status());
        }

        $this->assertCount(1, $this->requests);
    }

    public function test_post_with_idempotency_key_is_retried(): void
    {
        $this->enableRetries();
        $mock = new MockHandler([
            $this->json(500, ['error' => 'temporary']),
            $this->json(201, ['success' => true, 'bid' => ['id' => 1]]),
        ]);

        $result = $this->client($mock)->bids()->place(10, ['amount' => 1], 'idem-1');

        $this->assertSame(1, $result['bid']['id']);
        $this->assertCount(2, $this->requests);
    }

    public function test_policy_validates_methods_and_delay(): void
    {
        $policy = new RetryPolicy(true, 3, 200);

        $this->assertTrue($policy->allowsRetry('GET', [], 1));
        $this->assertFalse($policy->allowsRetry('GET', [], 3));
        $this->assertFalse($policy->allowsRetry('POST', [], 1));
        $this->assertTrue($policy->allowsRetry('POST', ['Idempotency-Key' => 'x'], 1));
        $this->assertTrue($policy->shouldRetryStatus(503));
        $this->assertFalse($policy->shouldRetryStatus(422));
        $this->assertSame(200, $policy->delayMs(1));
        $this->assertSame(400, $policy->delayMs(2));
        $this->assertSame(2000, $policy->delayMs(1, 2));
    }
}
