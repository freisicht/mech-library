<?php

declare(strict_types=1);

namespace App\Crawler;

use RuntimeException;

/**
 * The static data snapshot the MUL frontend loads from /data.
 *
 * The manifest is always fetched fresh; the files it names carry a content
 * hash in their filename, so the client cache can keep them indefinitely.
 */
final class MulSnapshot
{
    /**
     * @param list<array<string, mixed>>          $units
     * @param array<int, string>                  $types    id => name
     * @param array<int, array{name: string, type: int}> $subTypes
     * @param array<int, string>                  $roles    id => name
     * @param array<int, string>                  $techs    id => name
     * @param array<int, string>                  $eras     id => name
     */
    public function __construct(
        public readonly array $units,
        public readonly array $types,
        public readonly array $subTypes,
        public readonly array $roles,
        public readonly array $techs,
        public readonly array $eras,
    ) {
    }

    public static function load(MulClient $client): self
    {
        $manifest = self::decode($client->fetchRaw($client->url('data/manifest.json'), useCache: false));
        $files    = $manifest['files'] ?? null;
        if (!is_array($files)) {
            throw new RuntimeException('MUL manifest has no "files" map');
        }

        $file = static function (string $key) use ($client, $files): array {
            if (!isset($files[$key]) || !is_string($files[$key])) {
                throw new RuntimeException("MUL manifest lacks file '{$key}'");
            }
            return self::decode($client->fetchRaw($client->url('data/' . $files[$key])));
        };

        $subTypes = [];
        foreach ($file('unit_sub_types') as $row) {
            $subTypes[(int) $row['id']] = ['name' => (string) $row['name'], 'type' => (int) $row['t']];
        }

        /** @var list<array<string, mixed>> $units */
        $units = array_values($file('units'));

        return new self(
            $units,
            self::names($file('unit_types')),
            $subTypes,
            self::names($file('roles')),
            self::names($file('technologies')),
            self::names($file('eras')),
        );
    }

    /**
     * @param array<mixed> $rows
     * @return array<int, string>
     */
    private static function names(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (is_array($row) && isset($row['id'], $row['name'])) {
                $out[(int) $row['id']] = (string) $row['name'];
            }
        }
        return $out;
    }

    /** @return array<mixed> */
    private static function decode(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON from MUL: ' . json_last_error_msg());
        }
        return $data;
    }
}
