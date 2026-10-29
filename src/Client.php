<?php

declare(strict_types=1);

namespace Pricezaim\Sdk;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Pricezaim\Sdk\Exception\ApiException;
use Pricezaim\Sdk\Exception\TransportException;
use Pricezaim\Sdk\Resources\Auctions;
use Pricezaim\Sdk\Resources\Bids;
use Pricezaim\Sdk\Resources\Deals;
use Pricezaim\Sdk\Resources\Webhooks;
use Psr\Http\Message\ResponseInterface;

final class Client
{
    public const VERSION = '0.1.0';

    private readonly ClientInterface $http;
    private readonly string $apiKey;
    private readonly string $baseUri;
    private readonly string $userAgent;
    private readonly float $timeout;
    private readonly array $defaultHeaders;

    private ?Auctions $auctions = null;
    private ?Bids $bids = null;
    private ?Deals $deals = null;
    private ?Webhooks $webhooks = null;

    /**
     * @param array{
     *     base_uri?: string,
     *     timeout?: float|int,
     *     http_client?: ClientInterface,
     *     user_agent?: string,
     *     headers?: array<string, string>
     * } $options
     */
    public function __construct(string $apiKey, array $options = [])
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            throw new InvalidArgumentException('API key must not be empty.');
        }

        $this->apiKey = $apiKey;
        $this->baseUri = rtrim((string) ($options['base_uri'] ?? 'https://api.pricezaim.ru'), '/');
        $this->timeout = (float) ($options['timeout'] ?? 15);
        $this->userAgent = (string) ($options['user_agent'] ?? 'pricezaim-sdk-php/'.self::VERSION);
        $this->http = $options['http_client'] ?? new GuzzleClient();

        $this->defaultHeaders = array_merge([
            'X-API-Key' => $this->apiKey,
            'Accept' => 'application/json',
            'User-Agent' => $this->userAgent,
        ], $options['headers'] ?? []);
    }

    public function auctions(): Auctions
    {
        return $this->auctions ??= new Auctions($this);
    }

    public function bids(): Bids
    {
        return $this->bids ??= new Bids($this);
    }

    public function deals(): Deals
    {
        return $this->deals ??= new Deals($this);
    }

    public function webhooks(): Webhooks
    {
        return $this->webhooks ??= new Webhooks($this);
    }

    /** @param array<string, mixed> $query */
    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $this->uri($path, $query));
    }

    /** @param array<string, mixed> $payload */
    public function post(string $path, array $payload = [], ?string $idempotencyKey = null): array
    {
        $options = [];
        if ($payload !== []) {
            $options['json'] = $payload;
        }
        if ($idempotencyKey !== null) {
            $options['headers'] = ['Idempotency-Key' => $idempotencyKey];
        }

        return $this->send('POST', $this->uri($path), $options);
    }

    /** @param array<int, array<string, mixed>> $multipart */
    public function postMultipart(string $path, array $multipart, ?string $idempotencyKey = null): array
    {
        return $this->send('POST', $this->uri($path), [
            'multipart' => $multipart,
            'headers' => $idempotencyKey !== null ? ['Idempotency-Key' => $idempotencyKey] : [],
        ]);
    }

    /** @param array<string, mixed> $payload */
    public function put(string $path, array $payload): array
    {
        return $this->send('PUT', $this->uri($path), ['json' => $payload]);
    }

    public function delete(string $path): array
    {
        return $this->send('DELETE', $this->uri($path));
    }

    /** @param array<string, mixed> $options */
    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        $options['headers'] = array_merge(
            $this->defaultHeaders,
            is_array($options['headers'] ?? null) ? $options['headers'] : [],
        );
        $options['http_errors'] = false;
        $options['timeout'] = $options['timeout'] ?? $this->timeout;

        try {
            return $this->http->request($method, $uri, $options);
        } catch (GuzzleException $e) {
            throw new TransportException($e->getMessage(), $e);
        }
    }

    /** @param array<string, mixed> $options */
    public function send(string $method, string $uri, array $options = []): array
    {
        $response = $this->request($method, $uri, $options);
        $raw = (string) $response->getBody();
        $status = $response->getStatusCode();

        if ($status >= 400) {
            throw ApiException::fromResponse($response, $raw);
        }

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new ApiException(
                status: $status,
                errorCode: 'INVALID_RESPONSE',
                message: 'API returned a non-JSON response.',
                body: ['raw' => $raw],
                requestId: $response->getHeaderLine('X-Request-Id') ?: null,
            );
        }

        return $decoded;
    }

    /** @param array<string, mixed> $query */
    public function sendRaw(string $method, string $path, array $query = []): ResponseInterface
    {
        $response = $this->request($method, $this->uri($path, $query));
        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromResponse($response, (string) $response->getBody());
        }

        return $response;
    }

    /** @param array<string, mixed> $query */
    public function uri(string $path, array $query = []): string
    {
        $uri = $this->baseUri.'/'.ltrim($path, '/');
        $query = array_filter($query, static fn (mixed $value): bool => $value !== null);
        if ($query === []) {
            return $uri;
        }

        return $uri.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public function baseUri(): string
    {
        return $this->baseUri;
    }
}
