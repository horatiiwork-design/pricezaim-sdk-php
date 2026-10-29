<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Resources;

use Pricezaim\Sdk\Dto\Auction;
use Pricezaim\Sdk\Page;

/**
 * Ресурс аукционов.
 */
final class Auctions extends Resource
{
    /**
     * Возвращает страницу доступных аукционов.
     *
     * @param array<string, mixed> $filters Фильтры API: status, city, city[], page, per_page и др.
     * @return Page Страница аукционов (элементы — массивы).
     */
    public function list(array $filters = []): Page
    {
        return Page::fromArray($this->client->get('/v1/auctions', $filters));
    }

    /**
     * Возвращает страницу аукционов своей организации.
     *
     * @param array<string, mixed> $filters Фильтры и пагинация.
     * @return Page Страница аукционов.
     */
    public function my(array $filters = []): Page
    {
        return Page::fromArray($this->client->get('/v1/auctions/my', $filters));
    }

    /**
     * Возвращает страницу аукционов в виде DTO.
     *
     * @param array<string, mixed> $filters Фильтры API.
     * @return Page Страница с элементами Auction.
     */
    public function listDto(array $filters = []): Page
    {
        return $this->list($filters)->map(static fn (array $auction): Auction => Auction::fromArray($auction));
    }

    /**
     * Возвращает страницу аукционов своей организации в виде DTO.
     *
     * @param array<string, mixed> $filters Фильтры API.
     * @return Page Страница с элементами Auction.
     */
    public function myDto(array $filters = []): Page
    {
        return $this->my($filters)->map(static fn (array $auction): Auction => Auction::fromArray($auction));
    }

    /**
     * Возвращает карточку аукциона.
     *
     * @param int|string $id ID аукциона.
     * @return array<string, mixed> Данные аукциона.
     */
    public function get(int|string $id): array
    {
        return $this->client->get('/v1/auctions/'.rawurlencode((string) $id));
    }

    /**
     * Возвращает карточку аукциона в виде DTO.
     *
     * @param int|string $id ID аукциона.
     */
    public function getDto(int|string $id): Auction
    {
        return Auction::fromArray($this->get($id));
    }

    /**
     * Создаёт аукцион в статусе draft.
     *
     * API принимает создание только как multipart/form-data.
     *
     * @param array<string, mixed> $payload Поля аукциона и анкеты клиента.
     * @param array<int, array{name: string, path: string, filename?: string}> $files Фото и файлы.
     * @return array<string, mixed> Созданный аукцион.
     */
    public function create(array $payload, array $files = []): array
    {
        return $this->client->postMultipart('/v1/auctions', $this->multipart($payload, $files));
    }

    /**
     * Создаёт аукцион и возвращает его DTO.
     *
     * @param array<string, mixed> $payload Поля аукциона и анкеты клиента.
     * @param array<int, array{name: string, path: string, filename?: string}> $files Фото и файлы.
     */
    public function createDto(array $payload, array $files = []): Auction
    {
        return Auction::fromArray($this->create($payload, $files));
    }

    /**
     * Изменяет аукцион своей организации.
     *
     * Без файлов отправляется JSON, с файлами — multipart/form-data.
     *
     * @param int|string $id ID аукциона.
     * @param array<string, mixed> $payload Изменяемые поля.
     * @param array<int, array{name: string, path: string, filename?: string}> $files Новые фото и файлы.
     * @return array<string, mixed> Обновлённый аукцион.
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
     * Изменяет аукцион и возвращает его DTO.
     *
     * @param int|string $id ID аукциона.
     * @param array<string, mixed> $payload Изменяемые поля.
     * @param array<int, array{name: string, path: string, filename?: string}> $files Новые фото и файлы.
     */
    public function updateDto(int|string $id, array $payload, array $files = []): Auction
    {
        return Auction::fromArray($this->update($id, $payload, $files));
    }

    /**
     * Запускает аукцион: draft → active.
     *
     * @param int|string $id ID аукциона.
     * @return array<string, mixed> Обновлённый аукцион.
     */
    public function start(int|string $id): array
    {
        return $this->client->post('/v1/auctions/'.rawurlencode((string) $id).'/start');
    }

    /**
     * Останавливает аукцион и переводит его в архив.
     *
     * @param int|string $id ID аукциона.
     * @return array<string, mixed> Обновлённый аукцион.
     */
    public function stop(int|string $id): array
    {
        return $this->client->post('/v1/auctions/'.rawurlencode((string) $id).'/stop');
    }

    /**
     * Выбирает победителя по партнёрскому аукциону.
     *
     * @param int|string $id ID аукциона.
     * @param int|string $bidId ID ставки-победителя.
     * @return array<string, mixed> Завершённый аукцион.
     */
    public function selectWinner(int|string $id, int|string $bidId): array
    {
        return $this->client->post('/v1/auctions/'.rawurlencode((string) $id).'/winner', [
            'bid_id' => $bidId,
        ]);
    }

    /**
     * Помечает чужой активный аукцион как дубль.
     *
     * @param int|string $id ID аукциона.
     * @return array<string, mixed> Ответ API.
     */
    public function duplicate(int|string $id): array
    {
        return $this->client->post('/v1/auctions/'.rawurlencode((string) $id).'/duplicate');
    }

    /**
     * Снимает пометку «дубль» с аукциона.
     *
     * @param int|string $id ID аукциона.
     * @return array<string, mixed> Ответ API.
     */
    public function restoreDuplicate(int|string $id): array
    {
        return $this->client->delete('/v1/auctions/'.rawurlencode((string) $id).'/duplicate');
    }

    /**
     * Отказывается от чужого активного аукциона с указанием причины.
     *
     * @param int|string $id ID аукциона.
     * @param array<string, mixed> $payload Причина отказа.
     * @return array<string, mixed> Ответ API.
     */
    public function refuse(int|string $id, array $payload): array
    {
        return $this->client->post('/v1/auctions/'.rawurlencode((string) $id).'/refusal', $payload);
    }

    /**
     * Снимает отказ от аукциона.
     *
     * @param int|string $id ID аукциона.
     * @return array<string, mixed> Ответ API.
     */
    public function restoreRefusal(int|string $id): array
    {
        return $this->client->delete('/v1/auctions/'.rawurlencode((string) $id).'/refusal');
    }

    /**
     * Возвращает фото и файлы аукциона, сгруппированные по типам.
     *
     * @param int|string $id ID аукциона.
     * @return array<string, mixed> Блок photos и auction_id.
     */
    public function photos(int|string $id): array
    {
        return $this->client->get('/v1/auctions/'.rawurlencode((string) $id).'/photos');
    }

    /**
     * Скачивает фото или файл аукциона.
     *
     * @param int|string $auctionId ID аукциона.
     * @param int|string $fileId ID записи файла.
     * @return string Бинарное содержимое файла.
     */
    public function downloadFile(int|string $auctionId, int|string $fileId): string
    {
        return (string) $this->client
            ->sendRaw('GET', '/v1/auctions/'.rawurlencode((string) $auctionId).'/files/'.rawurlencode((string) $fileId))
            ->getBody();
    }
}
