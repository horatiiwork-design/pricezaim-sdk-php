<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Resources;

use Pricezaim\Sdk\Dto\Webhook;
use Pricezaim\Sdk\Page;

final class Webhooks extends Resource
{
    /** @return array<string, mixed> */
    public function list(): array
    {
        return $this->client->get('/v1/webhooks');
    }

    /** @return list<Webhook> */
    public function listDto(): array
    {
        $result = $this->list();
        $webhooks = $result['data'] ?? [];

        if (! is_array($webhooks)) {
            return [];
        }

        return array_map(
            static fn (array $webhook): Webhook => Webhook::fromArray($webhook),
            array_values(array_filter($webhooks, 'is_array')),
        );
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function create(array $payload, ?string $idempotencyKey = null): array
    {
        return $this->client->post('/v1/webhooks', $payload, $idempotencyKey);
    }

    /** @param array<string, mixed> $payload */
    public function createDto(array $payload, ?string $idempotencyKey = null): Webhook
    {
        $result = $this->create($payload, $idempotencyKey);
        $webhook = $result['data'] ?? [];

        return Webhook::fromArray(is_array($webhook) ? $webhook : []);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function update(int|string $id, array $payload): array
    {
        return $this->client->put('/v1/webhooks/'.rawurlencode((string) $id), $payload);
    }

    /** @param array<string, mixed> $payload */
    public function updateDto(int|string $id, array $payload): Webhook
    {
        $result = $this->update($id, $payload);
        $webhook = $result['data'] ?? [];

        return Webhook::fromArray(is_array($webhook) ? $webhook : []);
    }

    /** @return array<string, mixed> */
    public function delete(int|string $id): array
    {
        return $this->client->delete('/v1/webhooks/'.rawurlencode((string) $id));
    }

    /** @return array<string, mixed> */
    public function test(int|string $id): array
    {
        return $this->client->post('/v1/webhooks/'.rawurlencode((string) $id).'/test');
    }

    /** @param array<string, mixed> $filters */
    public function deliveries(int|string $id, array $filters = []): Page
    {
        return Page::fromArray($this->client->get(
            '/v1/webhooks/'.rawurlencode((string) $id).'/deliveries',
            $filters,
        ));
    }

    /** @return array<string, mixed> */
    public function retryDelivery(int|string $webhookId, int|string $deliveryId): array
    {
        return $this->client->post(
            '/v1/webhooks/'.rawurlencode((string) $webhookId).'/deliveries/'.rawurlencode((string) $deliveryId).'/retry',
        );
    }
}
