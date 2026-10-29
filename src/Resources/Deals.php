<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Resources;

use Pricezaim\Sdk\Dto\Deal;
use Pricezaim\Sdk\Dto\DealDocument;
use Pricezaim\Sdk\Page;

final class Deals extends Resource
{
    /** @param array<string, mixed> $filters */
    public function list(array $filters = []): Page
    {
        return Page::fromArray($this->client->get('/v1/deals', $filters));
    }

    public function listDto(array $filters = []): Page
    {
        return $this->list($filters)->map(static fn (array $deal): Deal => Deal::fromArray($deal));
    }

    /** @return array<string, mixed> */
    public function get(int|string $id): array
    {
        return $this->client->get('/v1/deals/'.rawurlencode((string) $id));
    }

    public function getDto(int|string $id): Deal
    {
        return Deal::fromArray($this->get($id));
    }

    /** @return array<string, mixed> */
    public function documents(int|string $id): array
    {
        return $this->client->get('/v1/deals/'.rawurlencode((string) $id).'/documents');
    }

    /** @return list<DealDocument> */
    public function documentsDto(int|string $id): array
    {
        $result = $this->documents($id);
        $documents = $result['data'] ?? [];

        if (! is_array($documents)) {
            return [];
        }

        return array_map(
            static fn (array $document): DealDocument => DealDocument::fromArray($document),
            array_values(array_filter($documents, 'is_array')),
        );
    }

    /**
     * @param array<int, array{name: string, path: string, filename?: string}> $files
     * @param array<string, scalar|null> $fields
     * @return array<string, mixed>
     */
    public function uploadDocuments(int|string $id, array $files, array $fields = [], ?string $idempotencyKey = null): array
    {
        return $this->client->postMultipart(
            '/v1/deals/'.rawurlencode((string) $id).'/documents',
            $this->multipart($fields, $files),
            $idempotencyKey,
        );
    }

    /** @return array<string, mixed> */
    public function requisites(int|string $id): array
    {
        return $this->client->get('/v1/deals/'.rawurlencode((string) $id).'/requisites');
    }

    /** @return array<string, mixed> */
    public function approve(int|string $id, array $payload = [], ?string $idempotencyKey = null): array
    {
        return $this->client->post('/v1/deals/'.rawurlencode((string) $id).'/approve', $payload, $idempotencyKey);
    }

    /** @return array<string, mixed> */
    public function chat(int|string $id, array $filters = []): array
    {
        return $this->client->get('/v1/deals/'.rawurlencode((string) $id).'/chat', $filters);
    }

    /** @return array<string, mixed> */
    public function sendChat(int|string $id, string $message, array $payload = [], ?string $idempotencyKey = null): array
    {
        return $this->client->post(
            '/v1/deals/'.rawurlencode((string) $id).'/chat',
            array_merge($payload, ['message' => $message]),
            $idempotencyKey,
        );
    }

    /** Скачать документ сделки. */
    public function downloadDocument(int|string $dealId, int|string $documentId): string
    {
        return (string) $this->client
            ->sendRaw('GET', '/v1/deals/'.rawurlencode((string) $dealId).'/documents/'.rawurlencode((string) $documentId))
            ->getBody();
    }

    /** @return array<string, mixed> */
    public function deleteDocument(int|string $dealId, int|string $documentId): array
    {
        return $this->client->delete('/v1/deals/'.rawurlencode((string) $dealId).'/documents/'.rawurlencode((string) $documentId));
    }
}
