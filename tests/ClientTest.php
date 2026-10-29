<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Pricezaim\Sdk\Client;
use Pricezaim\Sdk\Exception\ApiException;
use Pricezaim\Sdk\Exception\ValidationException;
use Psr\Http\Message\RequestInterface;

final class ClientTest extends TestCase
{
    /** @var array<int, RequestInterface> */
    private array $requests = [];

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

    private function jsonResponse(int $status, array $body, array $headers = []): Response
    {
        return new Response($status, array_merge(['Content-Type' => 'application/json'], $headers), json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    public function test_auction_list_sends_auth_and_query_and_returns_page(): void
    {
        $mock = new MockHandler([
            $this->jsonResponse(200, [
                'data' => [['id' => 10, 'title' => 'Auction']],
                'meta' => ['total' => 12, 'current_page' => 1, 'last_page' => 2, 'per_page' => 10],
            ]),
        ]);

        $page = $this->client($mock)->auctions()->list([
            'status' => 'active',
            'city' => ['Москва', 'Омск'],
            'per_page' => 10,
        ]);

        $this->assertCount(1, $page);
        $this->assertSame(12, $page->total());
        $this->assertSame(2, $page->lastPage());
        $this->assertSame('Auction', $page->items()[0]['title']);

        $request = $this->requests[0];
        $this->assertSame('test-api-key', $request->getHeaderLine('X-API-Key'));
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('api.test', $request->getUri()->getHost());
        $this->assertStringContainsString('/v1/auctions', $request->getUri()->getPath());
        $this->assertStringContainsString('status=active', $request->getUri()->getQuery());
        $this->assertStringContainsString('city%5B0%5D=', $request->getUri()->getQuery());
    }

    public function test_validation_error_exposes_field_errors(): void
    {
        $mock = new MockHandler([
            $this->jsonResponse(422, [
                'message' => 'The amount field is required.',
                'code' => 'VALIDATION_ERROR',
                'errors' => ['amount' => ['The amount field is required.']],
            ], ['X-Request-Id' => 'req-422']),
        ]);

        try {
            $this->client($mock)->auctions()->create([]);
            $this->fail('ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->status());
            $this->assertSame('VALIDATION_ERROR', $e->errorCode());
            $this->assertSame('req-422', $e->requestId());
            $this->assertSame(['amount' => ['The amount field is required.']], $e->errors());
        }
    }

    public function test_api_error_exposes_retry_after(): void
    {
        $mock = new MockHandler([
            $this->jsonResponse(429, ['message' => 'Too many requests', 'code' => 'TOO_MANY_REQUESTS'], ['Retry-After' => '7']),
        ]);

        try {
            $this->client($mock)->deals()->get(1);
            $this->fail('ApiException was not thrown');
        } catch (ApiException $e) {
            $this->assertSame(429, $e->status());
            $this->assertTrue($e->isRateLimited());
            $this->assertSame(7, $e->retryAfter());
        }
    }

    public function test_post_sends_idempotency_key_and_json_body(): void
    {
        $mock = new MockHandler([
            $this->jsonResponse(201, ['success' => true, 'bid' => ['id' => 55]]),
        ]);

        $result = $this->client($mock)->bids()->place(10, [
            'amount' => 150000,
            'interest_rate' => 19.5,
            'term' => 24,
        ], 'idem-1');

        $this->assertSame(55, $result['bid']['id']);
        $request = $this->requests[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('idem-1', $request->getHeaderLine('Idempotency-Key'));
        $this->assertStringContainsString('"amount":150000', (string) $request->getBody());
    }
}
