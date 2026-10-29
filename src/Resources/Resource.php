<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Resources;

use InvalidArgumentException;
use Pricezaim\Sdk\Client;

abstract class Resource
{
    public function __construct(protected readonly Client $client)
    {
    }

    /**
     * Собрать multipart из полей и файлов.
     *
     * $files: [['name' => 'photos[]', 'path' => '/tmp/a.jpg', 'filename' => 'a.jpg'], ...]
     *
     * @param array<string, scalar|null> $fields
     * @param array<int, array{name: string, path: string, filename?: string}> $files
     * @return array<int, array<string, mixed>>
     */
    protected function multipart(array $fields, array $files): array
    {
        $parts = [];
        foreach ($fields as $name => $value) {
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            $parts[] = ['name' => $name, 'contents' => (string) $value];
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
            ];
        }

        return $parts;
    }
}
