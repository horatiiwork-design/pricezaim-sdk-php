<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Resources;

use InvalidArgumentException;
use Pricezaim\Sdk\Client;

/**
 * Базовый класс ресурса API.
 */
abstract class Resource
{
    /**
     * Создаёт ресурс, привязанный к клиенту API.
     *
     * @param \Pricezaim\Sdk\Client $client Клиент API.
     */
    public function __construct(protected readonly Client $client)
    {
    }

    /**
     * Собирает multipart-части из полей и файлов.
     *
     * null-поля пропускаются, boolean приводятся к 1/0, файлы открываются как
     * бинарные потоки. Путь файла сохраняется для переоткрытия при автоповторе.
     *
     * @param array<string, scalar|array<array-key, scalar|null>|null> $fields Текстовые поля.
     * @param array<int, array{name?: string, path?: string, filename?: string}> $files Файлы.
     * @return array<int, array<string, mixed>> Части для Guzzle multipart.
     *
     * @throws \InvalidArgumentException Если файл не существует или не открывается.
     */
    protected function multipart(array $fields, array $files): array
    {
        $parts = [];
        foreach ($fields as $name => $value) {
            if ($value === null) {
                continue;
            }

            $values = is_array($value) ? $value : [$value];
            foreach ($values as $item) {
                if ($item === null) {
                    continue;
                }

                if (is_bool($item)) {
                    $item = $item ? '1' : '0';
                }

                $parts[] = ['name' => $name, 'contents' => (string) $item];
            }
        }

        foreach ($files as $file) {
            if (! isset($file['name'], $file['path']) || ! is_file($file['path'])) {
                throw new InvalidArgumentException('Each file must contain name and an existing path.');
            }

            $handle = fopen($file['path'], 'rb');
            if ($handle === false) {
                throw new InvalidArgumentException('Unable to open file: '.$file['path']);
            }

            $parts[] = [
                'name' => $file['name'],
                'contents' => $handle,
                'filename' => $file['filename'] ?? basename($file['path']),
                'path' => $file['path'],
            ];
        }

        return $parts;
    }
}
