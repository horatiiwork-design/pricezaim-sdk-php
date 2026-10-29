<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Resources;

use Pricezaim\Sdk\Dto\Deal;
use Pricezaim\Sdk\Dto\DealDocument;
use Pricezaim\Sdk\Page;

/**
 * Ресурс сделок.
 */
final class Deals extends Resource
{
    /**
     * Возвращает страницу сделок своей организации.
     *
     * @param array<string, mixed> $filters Фильтры: status, date_from, date_to, page, per_page.
     * @return Page Страница сделок.
     */
    public function list(array $filters = []): Page
    {
        return Page::fromArray($this->client->get('/v1/deals', $filters));
    }

    /**
     * Возвращает сделки в виде DTO.
     *
     * @param array<string, mixed> $filters Фильтры API.
     * @return Page Страница с элементами Deal.
     */
    public function listDto(array $filters = []): Page
    {
        return $this->list($filters)->map(static fn (array $deal): Deal => Deal::fromArray($deal));
    }

    /**
     * Возвращает полную карточку сделки.
     *
     * @param int|string $id ID сделки.
     * @return array<string, mixed> Данные сделки.
     */
    public function get(int|string $id): array
    {
        return $this->client->get('/v1/deals/'.rawurlencode((string) $id));
    }

    /**
     * Возвращает карточку сделки в виде DTO.
     *
     * @param int|string $id ID сделки.
     */
    public function getDto(int|string $id): Deal
    {
        return Deal::fromArray($this->get($id));
    }

    /**
     * Возвращает список документов сделки.
     *
     * @param int|string $id ID сделки.
     * @return array<string, mixed> Ответ API с ключом data.
     */
    public function documents(int|string $id): array
    {
        return $this->client->get('/v1/deals/'.rawurlencode((string) $id).'/documents');
    }

    /**
     * Возвращает документы сделки в виде DTO.
     *
     * @param int|string $id ID сделки.
     * @return list<DealDocument>
     */
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
     * Загружает документы в сделку через multipart/form-data.
     *
     * @param int|string $id ID сделки.
     * @param array<int, array{name: string, path: string, filename?: string}> $files Файлы.
     * @param array<string, scalar|null> $fields Дополнительные поля.
     * @param string|null $idempotencyKey Ключ идемпотентности.
     * @return array<string, mixed> Ответ API.
     */
    public function uploadDocuments(int|string $id, array $files, array $fields = [], ?string $idempotencyKey = null): array
    {
        return $this->client->postMultipart(
            '/v1/deals/'.rawurlencode((string) $id).'/documents',
            $this->multipart($fields, $files),
            $idempotencyKey,
        );
    }

    /**
     * Возвращает реквизиты сделки.
     *
     * @param int|string $id ID сделки.
     * @return array<string, mixed> Реквизиты.
     */
    public function requisites(int|string $id): array
    {
        return $this->client->get('/v1/deals/'.rawurlencode((string) $id).'/requisites');
    }

    /**
     * Согласует сделку со стороны текущей организации.
     *
     * @param int|string $id ID сделки.
     * @param array<string, mixed> $payload Необязательная заметка notes.
     * @param string|null $idempotencyKey Ключ идемпотентности.
     * @return array<string, mixed> Результат согласования.
     */
    public function approve(int|string $id, array $payload = [], ?string $idempotencyKey = null): array
    {
        return $this->client->post('/v1/deals/'.rawurlencode((string) $id).'/approve', $payload, $idempotencyKey);
    }

    /**
     * Возвращает историю чата сделки.
     *
     * @param int|string $id ID сделки.
     * @param array<string, mixed> $filters Параметр limit.
     * @return array<string, mixed> Сообщения чата.
     */
    public function chat(int|string $id, array $filters = []): array
    {
        return $this->client->get('/v1/deals/'.rawurlencode((string) $id).'/chat', $filters);
    }

    /**
     * Отправляет сообщение в чат сделки.
     *
     * @param int|string $id ID сделки.
     * @param string $message Текст сообщения.
     * @param array<string, mixed> $payload Дополнительные поля.
     * @param string|null $idempotencyKey Ключ идемпотентности.
     * @return array<string, mixed> Ответ API.
     */
    public function sendChat(int|string $id, string $message, array $payload = [], ?string $idempotencyKey = null): array
    {
        return $this->client->post(
            '/v1/deals/'.rawurlencode((string) $id).'/chat',
            array_merge($payload, ['message' => $message]),
            $idempotencyKey,
        );
    }

    /**
     * Скачивает документ сделки.
     *
     * @param int|string $dealId ID сделки.
     * @param int|string $documentId ID документа.
     * @return string Бинарное содержимое документа.
     */
    public function downloadDocument(int|string $dealId, int|string $documentId): string
    {
        return (string) $this->client
            ->sendRaw('GET', '/v1/deals/'.rawurlencode((string) $dealId).'/documents/'.rawurlencode((string) $documentId))
            ->getBody();
    }

    /**
     * Удаляет документ из сделки.
     *
     * @param int|string $dealId ID сделки.
     * @param int|string $documentId ID документа.
     * @return array<string, mixed> Ответ API.
     */
    public function deleteDocument(int|string $dealId, int|string $documentId): array
    {
        return $this->client->delete('/v1/deals/'.rawurlencode((string) $dealId).'/documents/'.rawurlencode((string) $documentId));
    }
}
