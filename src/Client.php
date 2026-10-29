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

/**
 * Клиент публичного API pricezaim.
 *
 * Хранит API-ключ, настройки транспорта и лениво создаёт ресурсы.
 */
final class Client
{
    public const VERSION = '0.1.0';

    private readonly ClientInterface $http;
    private readonly string $apiKey;
    private readonly string $baseUri;
    private readonly string $userAgent;
    private readonly float $timeout;
    /** @var array<string, string> */
    private readonly array $defaultHeaders;
    private readonly RetryPolicy $retryPolicy;

    private ?Auctions $auctions = null;
    private ?Bids $bids = null;
    private ?Deals $deals = null;
    private ?Webhooks $webhooks = null;

    /**
     * Создаёт клиент API.
     *
     * @param string $apiKey API-ключ партнёра; передаётся в заголовке X-API-Key.
     * @param array{
     *     base_uri?: string,
     *     timeout?: float|int,
     *     http_client?: \GuzzleHttp\ClientInterface,
     *     user_agent?: string,
     *     headers?: array<string, string>
     * } $options Настройки транспорта. Автоповторы читаются из env.
     *
     * @throws \InvalidArgumentException Если API-ключ пустой.
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
        $this->retryPolicy = RetryPolicy::fromEnvironment();

        $this->defaultHeaders = array_merge([
            'X-API-Key' => $this->apiKey,
            'Accept' => 'application/json',
            'User-Agent' => $this->userAgent,
        ], $options['headers'] ?? []);
    }

    /**
     * Возвращает ресурс для работы с аукционами.
     */
    public function auctions(): Auctions
    {
        return $this->auctions ??= new Auctions($this);
    }

    /**
     * Возвращает ресурс для работы со ставками.
     */
    public function bids(): Bids
    {
        return $this->bids ??= new Bids($this);
    }

    /**
     * Возвращает ресурс для работы со сделками.
     */
    public function deals(): Deals
    {
        return $this->deals ??= new Deals($this);
    }

    /**
     * Возвращает ресурс для управления вебхуками.
     */
    public function webhooks(): Webhooks
    {
        return $this->webhooks ??= new Webhooks($this);
    }

    /**
     * Выполняет GET-запрос к API.
     *
     * @param string $path Путь относительно base_uri, например `/v1/auctions`.
     * @param array<string, mixed> $query Query-параметры; null-значения отбрасываются.
     * @return array<string, mixed> Декодированный JSON.
     *
     * @throws \Pricezaim\Sdk\Exception\ApiException При HTTP-ошибке или не-JSON ответе.
     * @throws \Pricezaim\Sdk\Exception\TransportException При сетевой ошибке.
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $this->uri($path, $query));
    }

    /**
     * Выполняет POST-запрос с JSON-телом.
     *
     * @param string $path Путь относительно base_uri.
     * @param array<string, mixed> $payload JSON-тело; пустой массив означает запрос без тела.
     * @param string|null $idempotencyKey Ключ идемпотентности для безопасного повтора.
     * @return array<string, mixed> Декодированный JSON.
     *
     * @throws \Pricezaim\Sdk\Exception\ApiException При HTTP-ошибке.
     * @throws \Pricezaim\Sdk\Exception\TransportException При сетевой ошибке.
     */
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

    /**
     * Выполняет POST-запрос с multipart/form-data.
     *
     * @param string $path Путь относительно base_uri.
     * @param array<int, array<string, mixed>> $multipart Части формы Guzzle.
     * @param string|null $idempotencyKey Ключ идемпотентности.
     * @return array<string, mixed> Декодированный JSON.
     *
     * @throws \Pricezaim\Sdk\Exception\ApiException При HTTP-ошибке.
     * @throws \Pricezaim\Sdk\Exception\TransportException При сетевой ошибке.
     */
    public function postMultipart(string $path, array $multipart, ?string $idempotencyKey = null): array
    {
        return $this->send('POST', $this->uri($path), [
            'multipart' => $multipart,
            'headers' => $idempotencyKey !== null ? ['Idempotency-Key' => $idempotencyKey] : [],
        ]);
    }

    /**
     * Выполняет PUT-запрос с JSON-телом.
     *
     * @param string $path Путь относительно base_uri.
     * @param array<string, mixed> $payload JSON-тело.
     * @return array<string, mixed> Декодированный JSON.
     *
     * @throws \Pricezaim\Sdk\Exception\ApiException При HTTP-ошибке.
     * @throws \Pricezaim\Sdk\Exception\TransportException При сетевой ошибке.
     */
    public function put(string $path, array $payload): array
    {
        return $this->send('PUT', $this->uri($path), ['json' => $payload]);
    }

    /**
     * Выполняет PUT-запрос с multipart/form-data.
     *
     * @param string $path Путь относительно base_uri.
     * @param array<int, array<string, mixed>> $multipart Части формы Guzzle.
     * @param string|null $idempotencyKey Ключ идемпотентности.
     * @return array<string, mixed> Декодированный JSON.
     *
     * @throws \Pricezaim\Sdk\Exception\ApiException При HTTP-ошибке.
     * @throws \Pricezaim\Sdk\Exception\TransportException При сетевой ошибке.
     */
    public function putMultipart(string $path, array $multipart, ?string $idempotencyKey = null): array
    {
        $options = ['multipart' => $multipart];
        if ($idempotencyKey !== null) {
            $options['headers'] = ['Idempotency-Key' => $idempotencyKey];
        }

        return $this->send('PUT', $this->uri($path), $options);
    }

    /**
     * Выполняет DELETE-запрос.
     *
     * @param string $path Путь относительно base_uri.
     * @return array<string, mixed> Декодированный JSON.
     *
     * @throws \Pricezaim\Sdk\Exception\ApiException При HTTP-ошибке.
     * @throws \Pricezaim\Sdk\Exception\TransportException При сетевой ошибке.
     */
    public function delete(string $path): array
    {
        return $this->send('DELETE', $this->uri($path));
    }

    /**
     * Отправляет HTTP-запрос с авторизацией, таймаутом и автоповторами по env-флагу.
     *
     * HTTP-ошибки здесь не преобразуются в исключения: возвращается PSR-7 Response,
     * чтобы `send()` и `sendRaw()` могли разобрать тело ответа.
     *
     * @param string $method HTTP-метод.
     * @param string $uri Абсолютный URL.
     * @param array<string, mixed> $options Опции Guzzle.
     * @return \Psr\Http\Message\ResponseInterface Ответ API.
     *
     * @throws \Pricezaim\Sdk\Exception\TransportException Если сеть недоступна после всех попыток.
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        $options['headers'] = array_merge(
            $this->defaultHeaders,
            is_array($options['headers'] ?? null) ? $options['headers'] : [],
        );
        $options['http_errors'] = false;
        $options['timeout'] = $options['timeout'] ?? $this->timeout;

        $headers = $options['headers'];
        $maxAttempts = $this->retryPolicy->enabled ? max(1, $this->retryPolicy->maxAttempts) : 1;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $this->http->request($method, $uri, $options);
            } catch (GuzzleException $e) {
                if ($this->retryPolicy->allowsRetry($method, $headers, $attempt)) {
                    if (! $this->rewindMultipart($options)) {
                        throw new TransportException($e->getMessage(), $e);
                    }

                    $this->wait($this->retryPolicy->delayMs($attempt));

                    continue;
                }

                throw new TransportException($e->getMessage(), $e);
            }

            $status = $response->getStatusCode();
            if (
                $this->retryPolicy->allowsRetry($method, $headers, $attempt)
                && $this->retryPolicy->shouldRetryStatus($status)
            ) {
                if (! $this->rewindMultipart($options)) {
                    return $response;
                }

                $retryAfter = RetryPolicy::retryAfterSeconds($response->getHeaderLine('Retry-After'));
                $this->wait($this->retryPolicy->delayMs($attempt, $retryAfter));

                continue;
            }

            return $response;
        }

        throw new TransportException('HTTP request failed after retries.');
    }

    /**
     * Возвращает политику автоповторов, применённую при создании клиента.
     */
    public function retryPolicy(): RetryPolicy
    {
        return $this->retryPolicy;
    }

    /**
     * Отправляет запрос и декодирует JSON-ответ.
     *
     * @param string $method HTTP-метод.
     * @param string $uri Абсолютный URL.
     * @param array<string, mixed> $options Опции Guzzle.
     * @return array<string, mixed> Декодированный JSON или пустой массив для 204.
     *
     * @throws \Pricezaim\Sdk\Exception\ApiException При HTTP-ошибке или не-JSON ответе.
     * @throws \Pricezaim\Sdk\Exception\TransportException При сетевой ошибке.
     */
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

    /**
     * Отправляет запрос и возвращает сырой PSR-7-ответ без JSON-декодирования.
     *
     * Используется для скачивания файлов.
     *
     * @param string $method HTTP-метод.
     * @param string $path Путь относительно base_uri.
     * @param array<string, mixed> $query Query-параметры.
     * @return \Psr\Http\Message\ResponseInterface Сырой ответ.
     *
     * @throws \Pricezaim\Sdk\Exception\ApiException При HTTP-ошибке.
     * @throws \Pricezaim\Sdk\Exception\TransportException При сетевой ошибке.
     */
    public function sendRaw(string $method, string $path, array $query = []): ResponseInterface
    {
        $response = $this->request($method, $this->uri($path, $query));
        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromResponse($response, (string) $response->getBody());
        }

        return $response;
    }

    /**
     * Собирает абсолютный URL из base_uri, пути и query-параметров.
     *
     * @param string $path Путь относительно base_uri.
     * @param array<string, mixed> $query Query-параметры.
     * @return string Абсолютный URL.
     */
    public function uri(string $path, array $query = []): string
    {
        $uri = $this->baseUri.'/'.ltrim($path, '/');
        $query = array_filter($query, static fn (mixed $value): bool => $value !== null);
        if ($query === []) {
            return $uri;
        }

        return $uri.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Возвращает базовый URL API.
     */
    public function baseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * Приостанавливает выполнение перед следующей попыткой.
     *
     * @param int $delayMs Задержка в миллисекундах.
     */
    private function wait(int $delayMs): void
    {
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }

    /**
     * Готовит multipart-части к повторной отправке.
     *
     * Перематывает открытые файловые потоки, а закрытые потоки переоткрывает по
     * сохранённому пути. Если поток восстановить нельзя, повтор не выполняется.
     *
     * @param array<string, mixed> $options Опции Guzzle; изменяются по ссылке.
     * @return bool true, если все части готовы к повтору.
     */
    private function rewindMultipart(array &$options): bool
    {
        if (! isset($options['multipart']) || ! is_array($options['multipart'])) {
            return true;
        }

        foreach ($options['multipart'] as $index => $part) {
            if (! is_array($part) || ! array_key_exists('contents', $part)) {
                continue;
            }

            $contents = $part['contents'];
            if (is_resource($contents)) {
                if (rewind($contents)) {
                    continue;
                }
            } elseif (! isset($part['path'])) {
                // Обычное текстовое поле multipart: переоткрывать нечего.
                continue;
            }

            if (isset($part['path']) && is_string($part['path']) && is_file($part['path'])) {
                $handle = fopen($part['path'], 'rb');
                if ($handle !== false) {
                    $options['multipart'][$index]['contents'] = $handle;

                    continue;
                }
            }

            return false;
        }

        return true;
    }
}
