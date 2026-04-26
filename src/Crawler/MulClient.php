<?php

declare(strict_types=1);

namespace App\Crawler;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

final class MulClient
{
    private const DEFAULT_DELAY_MS = 1000;
    private const MIN_DELAY_MS     = 1000;

    private float $lastRequestTime = 0.0;
    private LoggerInterface $logger;
    /** @var callable(int): void */
    private $sleepFn;

    /**
     * @param callable(int): void|null $sleepFn Injected for testing; defaults to usleep().
     */
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $cacheDir,
        private readonly string $baseUrl,
        private readonly int $delayMs = self::DEFAULT_DELAY_MS,
        ?LoggerInterface $logger = null,
        ?callable $sleepFn = null,
    ) {
        if ($this->delayMs < self::MIN_DELAY_MS) {
            throw new \InvalidArgumentException(
                'MUL request delay must be >= ' . self::MIN_DELAY_MS . ' ms'
            );
        }
        $this->logger  = $logger ?? new NullLogger();
        $this->sleepFn = $sleepFn ?? static function (int $us): void {
            usleep($us);
        };

        if (!is_dir($this->cacheDir) && !mkdir($this->cacheDir, 0755, true)) {
            throw new RuntimeException("Cannot create cache dir: {$this->cacheDir}");
        }
    }

    /**
     * Fetch /Unit/QuickList with the given query parameters.
     * Returns the decoded JSON array on success.
     *
     * @param array<string, scalar> $params
     * @return array<string, mixed>
     */
    public function fetchQuickList(array $params): array
    {
        $cacheKey  = $this->cacheKey('QuickList', $params);
        $cachePath = $this->cacheDir . '/' . $cacheKey . '.json';

        if (file_exists($cachePath)) {
            $this->logger->debug('MulClient cache hit', ['key' => $cacheKey]);
            $body = file_get_contents($cachePath);
            if ($body === false) {
                throw new RuntimeException("Cannot read cache file: {$cachePath}");
            }
            return $this->decode($body);
        }

        $this->throttle();

        $url = rtrim($this->baseUrl, '/') . '/Unit/QuickList';
        $this->logger->info('MulClient fetch', ['url' => $url, 'params' => $params]);

        try {
            $response = $this->httpClient->request('GET', $url, ['query' => $params]);
        } catch (GuzzleException $e) {
            throw new RuntimeException("MUL HTTP error: {$e->getMessage()}", 0, $e);
        }

        $body = (string) $response->getBody();

        if (file_put_contents($cachePath, $body) === false) {
            throw new RuntimeException("Cannot write cache file: {$cachePath}");
        }

        return $this->decode($body);
    }

    /**
     * Fetch an arbitrary URL and return the response body as a string.
     * Used for HTML pages (Faction/Index, Era/Index) and image downloads.
     */
    public function fetchRaw(string $url, bool $useCache = true): string
    {
        $cacheKey  = $this->cacheKey('raw', ['url' => $url]);
        $cachePath = $this->cacheDir . '/' . $cacheKey;

        if ($useCache && file_exists($cachePath)) {
            $this->logger->debug('MulClient raw cache hit', ['url' => $url]);
            $body = file_get_contents($cachePath);
            if ($body === false) {
                throw new RuntimeException("Cannot read cache file: {$cachePath}");
            }
            return $body;
        }

        $this->throttle();

        $this->logger->info('MulClient raw fetch', ['url' => $url]);

        try {
            $response = $this->httpClient->request('GET', $url);
        } catch (GuzzleException $e) {
            throw new RuntimeException("HTTP error fetching {$url}: {$e->getMessage()}", 0, $e);
        }

        $body = (string) $response->getBody();

        if ($useCache && file_put_contents($cachePath, $body) === false) {
            throw new RuntimeException("Cannot write cache: {$cachePath}");
        }

        return $body;
    }

    private function throttle(): void
    {
        if ($this->lastRequestTime === 0.0) {
            $this->lastRequestTime = microtime(true);
            return;
        }
        $elapsed = (microtime(true) - $this->lastRequestTime) * 1000;
        $sleep   = $this->delayMs - $elapsed;
        if ($sleep > 0) {
            ($this->sleepFn)((int) ($sleep * 1000));
        }
        $this->lastRequestTime = microtime(true);
    }

    /** @param array<string, mixed> $params */
    private function cacheKey(string $prefix, array $params): string
    {
        ksort($params);
        return $prefix . '_' . md5(serialize($params));
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException('MUL response is not a JSON object');
        }
        return $data;
    }
}
