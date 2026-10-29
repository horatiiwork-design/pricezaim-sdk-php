<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Resources;

use Pricezaim\Sdk\Dto\Webhook;
use Pricezaim\Sdk\Page;

/**
 * Ресурс вебхуков.
 */
final class Webhooks extends Resource
{
    /**
     * Возвращает список вебхуков организации без секретов.
     *
     * @return array<string, mixed> Ответ API с ключом data.
     */
    public function list(): array
    {
        return $this->client->get('/v1/webhooks');
    }

    /**
     * Возвращает список вебхуков в виде DTO.
     *
     * @return list<Webhook>
     */
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

    /**
     * Создаёт вебхук.
     *
     * @param array<string, mixed> $payload Поля url, events, secret, headers, active.
     * @param string|null $idempotencyKey Ключ идемпотентности.
     * @return array<string, mixed> Ответ API с ключом data.
     */
    public function create(array $payload, ?string $idempotencyKey = null): array
    {
        return $this->client->post('/v1/webhooks', $payload, $idempotencyKey);
    }

    /**
     * Создаёт вебхук и возвращает его DTO.
     *
     * @param array<string, mixed> $payload Поля вебхука.
     * @param string|null $idempotencyKey Ключ идемпотентности.
     */
    public function createDto(array $payload, ?string $idempotencyKey = null): Webhook
    {
        $result = $this->create($payload, $idempotencyKey);
        $webhook = $result['data'] ?? [];

        return Webhook::fromArray(is_array($webhook) ? $webhook : []);
    }

    /**
     * Обновляет вебхук.
     *
     * @param int|string $id ID вебхука.
     * @param array<string, mixed> $payload Поля url, events, secret, headers, active.
     * @return array<string, mixed> Ответ API с ключом data.
     */
    public function update(int|string $id, array $payload): array
    {
        return $this->client->put('/v1/webhooks/'.rawurlencode((string) $id), $payload);
    }

    /**
     * Обновляет вебхук и возвращает его DTO.
     *
     * @param int|string $id ID вебхука.
     * @param array<string, mixed> $payload Поля вебхука.
     */
    public function updateDto(int|string $id, array $payload): Webhook
    {
        $result = $this->update($id, $payload);
        $webhook = $result['data'] ?? [];

        return Webhook::fromArray(is_array($webhook) ? $webhook : []);
    }

    /**
     * Удаляет вебхук.
     *
     * @param int|string $id ID вебхука.
     * @return array<string, mixed> Ответ API.
     */
    public function delete(int|string $id): array
    {
        return $this->client->delete('/v1/webhooks/'.rawurlencode((string) $id));
    }

    /**
     * Отправляет тестовый пинг на URL вебхука.
     *
     * @param int|string $id ID вебхука.
     * @return array<string, mixed> Результат проверки.
     */
    public function test(int|string $id): array
    {
        return $this->client->post('/v1/webhooks/'.rawurlencode((string) $id).'/test');
    }

    /**
     * Возвращает журнал доставок вебхука.
     *
     * @param int|string $id ID вебхука.
     * @param array<string, mixed> $filters Фильтры и пагинация.
     * @return Page Страница доставок.
     */
    public function deliveries(int|string $id, array $filters = []): Page
    {
        return Page::fromArray($this->client->get(
            '/v1/webhooks/'.rawurlencode((string) $id).'/deliveries',
            $filters,
        ));
    }

    /**
     * Повторяет доставку вебхука.
     *
     * @param int|string $webhookId ID вебхука.
     * @param int|string $deliveryId ID доставки.
     * @return array<string, mixed> Ответ API.
     */
    public function retryDelivery(int|string $webhookId, int|string $deliveryId): array
    {
        return $this->client->post(
            '/v1/webhooks/'.rawurlencode((string) $webhookId).'/deliveries/'.rawurlencode((string) $deliveryId).'/retry',
        );
    }
}
