<?php

declare(strict_types=1);

namespace App\Crawler;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

final class MulClientFactory
{
    public const DEFAULT_BASE_URL = 'https://masterunitlist.battletech.com';

    public static function cacheDir(): string
    {
        $dir = self::env('MUL_RAW_CACHE_DIR') ?? 'data/raw';
        return str_starts_with($dir, '/') ? $dir : dirname(__DIR__, 2) . '/' . $dir;
    }

    public static function create(?LoggerInterface $logger = null): MulClient
    {
        $baseUrl  = self::env('MUL_BASE_URL') ?? self::DEFAULT_BASE_URL;
        $delayMs  = max(MulClient::MIN_DELAY_MS, (int) self::env('MUL_REQUEST_DELAY_MS'));
        $cacheDir = self::cacheDir();
        $version  = '0.4';

        $httpClient = new Client([
            'base_uri' => $baseUrl,
            'timeout'  => 30,
            'headers'  => [
                'User-Agent' => self::env('MUL_USER_AGENT')
                    ?? "MechLibrary/{$version} (private use; https://github.com/freisicht/mech-library)",
            ],
        ]);

        return new MulClient($httpClient, $cacheDir, $baseUrl, $delayMs, $logger);
    }

    private static function env(string $key): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);
        return is_string($value) && $value !== '' ? $value : null;
    }
}
