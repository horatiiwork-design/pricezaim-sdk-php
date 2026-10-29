<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Resources;

use Pricezaim\Sdk\Dto\Auction;
use Pricezaim\Sdk\Page;

final class Auctions extends Resource
{
    /** @param array<string, mixed> $filters */
    public function list(array $filters = []): Page
    {
        return Page::fromArray($this->client->get('/v1/auctions', $filters));
    }

    /** @param array<string, mixed> $filters */
    public function my(array $filters = []): Page
    {
        return Page::fromArray($this->client->get('/v1/auctions/my', $filters));
    }

    public function listDto(array $filters = []): Page
    {
        return $this->list($filters)->map(static fn (array $auction): Auction => Auction::fromArray($auction));
    }

    public function myDto(array $filters = []): Page
    {
        return $this->my($filters)->map(static fn (array $auction): Auction => Auction::fromArray($auction));
    }

    /** @return array<string, mixed> */
    public function get(int|string $id): array
    {
        return $this->client->get('/v1/auctions/'.rawurlencode((string) $id));
    }

    public function getDto(int|string $id): Auction
    {
        return Auction::fromArray($this->get($id));
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, array{name: string, path: string, filename?: string}> $files
     * @return array<string, mixed>
     */
    public function create(array $payload, array $files = []): array
    {
        if ($files === []) {
            return $this->client->post('/v1/auctions', $payload);
        }

        return $this->client->postMultipart('/v1/auctions', $this->multipart($payload, $files));
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, array{name: string, path: string, filename?: string}> $files
     */
    public function createDto(array $payload, array $files = []): Auction
    {
        return Auction::fromArray($this->create($payload, $files));
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, array{name: string, path: string, filename?: string}> $files
     * @return array<string, mixed>
     */
    public function update(int|string $id, array $payload, array $files = []): array
    {
        $path = '/v1/auctions/'.rawurlencode((string) $id);

        if ($files === []) {
            return $this->client->put($path, $payload);
        }

        return $this->client->putMultipart($path, $this->multipart($payload, $files));
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, array{name: string, path: string, filename?: string}> $files
     */
    public function updateDto(int|string $id, array $payload, array $files = []): Auction
    {
        return Auction::fromArray($this->update($id, $payload, $files));
    }

    /** @return array<string, mixed> */
    public function start(int|string $id): array
    {
        return $this->client->post('/v1/auctions/'.rawurlencode((string) $id).'/start');
    }

    /** @return array<string, mixed> */
    public function stop(int|string $id): array
    {
        return $this->client->post('/v1/auctions/'.rawurlencode((string) $id).'/stop');
    }

    /** @return array<string, mixed> */
    public function selectWinner(int|string $id, int|string $bidId): array
    {
        return $this->client->post('/v1/auctions/'.rawurlencode((string) $id).'/winner', [
            'bid_id' => $bidId,
        ]);
    }

    /** @return array<string, mixed> */
    public function duplicate(int|string $id): array
    {
        return $this->client->post('/v1/auctions/'.rawurlencode((string) $id).'/duplicate');
    }

    /** @return array<string, mixed> */
    public function restoreDuplicate(int|string $id): array
    {
        return $this->client->delete('/v1/auctions/'.rawurlencode((string) $id).'/duplicate');
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function refuse(int|string $id, array $payload): array
    {
        return $this->client->post('/v1/auctions/'.rawurlencode((string) $id).'/refusal', $payload);
    }

    /** @return array<string, mixed> */
    public function restoreRefusal(int|string $id): array
    {
        return $this->client->delete('/v1/auctions/'.rawurlencode((string) $id).'/refusal');
    }

    /** @return array<string, mixed> */
    public function photos(int|string $id): array
    {
        return $this->client->get('/v1/auctions/'.rawurlencode((string) $id).'/photos');
    }

    /** Скачать фото/файл аукциона. */
    public function downloadFile(int|string $auctionId, int|string $fileId): string
    {
        return (string) $this->client
            ->sendRaw('GET', '/v1/auctions/'.rawurlencode((string) $auctionId).'/files/'.rawurlencode((string) $fileId))
            ->getBody();
    }
}
