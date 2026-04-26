<?php

declare(strict_types=1);

namespace App\Crawler;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

final class MulClientFactory
{
    public static function create(?LoggerInterface $logger = null): MulClient
    {
        $baseUrl  = $_ENV['MUL_BASE_URL']         ?? 'https://masterunitlist.info';
        $delayMs  = (int) ($_ENV['MUL_REQUEST_DELAY_MS'] ?? 1000);
        $cacheDir = $_ENV['MUL_RAW_CACHE_DIR']    ?? 'data/raw';
        $version  = '0.1';

        // Resolve relative cache dir from project root
        if (!str_starts_with($cacheDir, '/')) {
            $cacheDir = dirname(__DIR__, 2) . '/' . $cacheDir;
        }

        $httpClient = new Client([
            'base_uri' => $baseUrl,
            'timeout'  => 30,
            'headers'  => [
                'User-Agent' => $_ENV['MUL_USER_AGENT']
                    ?? "MechLibrary/{$version} (private use; see README)",
                'Accept'     => 'application/json',
            ],
        ]);

        return new MulClient($httpClient, $cacheDir, $baseUrl, $delayMs, $logger);
    }
}
