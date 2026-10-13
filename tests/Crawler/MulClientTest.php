<?php

declare(strict_types=1);

namespace App\Tests\Crawler;

use App\Crawler\MulClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MulClientTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/mulclient_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '/*') ?: []);
        rmdir($this->tmpDir);
    }

    /** Build a MulClient with a no-op sleep so tests never actually wait. */
    private function makeClient(MockHandler $mock): MulClient
    {
        $stack = HandlerStack::create($mock);
        $http  = new Client(['handler' => $stack]);
        return new MulClient(
            $http,
            $this->tmpDir,
            'https://example.com',
            delayMs: MulClient::MIN_DELAY_MS,
            sleepFn: static function (int $_us): void {
            },
        );
    }

    public function testFetchRawReturnsBody(): void
    {
        $mock   = new MockHandler([new Response(200, [], '{"files":{}}')]);
        $client = $this->makeClient($mock);

        self::assertSame('{"files":{}}', $client->fetchRaw($client->url('data/manifest.json')));
    }

    public function testFetchRawHitsCache(): void
    {
        // Only one response queued — a second real request would throw
        $mock   = new MockHandler([new Response(200, [], 'page')]);
        $client = $this->makeClient($mock);

        $client->fetchRaw('https://example.com/u/ABC');

        self::assertSame('page', $client->fetchRaw('https://example.com/u/ABC'));
    }

    public function testFetchRawWithoutCacheRequestsAgain(): void
    {
        $mock   = new MockHandler([new Response(200, [], 'v1'), new Response(200, [], 'v2')]);
        $client = $this->makeClient($mock);

        $client->fetchRaw('https://example.com/data/manifest.json', useCache: false);

        self::assertSame('v2', $client->fetchRaw('https://example.com/data/manifest.json', useCache: false));
    }

    public function testFetchRawThrowsOnHttpError(): void
    {
        $mock = new MockHandler([
            new RequestException('Server error', new Request('GET', 'test')),
        ]);
        $client = $this->makeClient($mock);

        $this->expectException(RuntimeException::class);
        $client->fetchRaw('https://example.com/u/ABC');
    }

    public function testUrlJoinsBaseAndPath(): void
    {
        $client = $this->makeClient(new MockHandler([]));

        self::assertSame('https://example.com/data/units.json', $client->url('/data/units.json'));
    }

    public function testDelayBelowMinimumIsRejected(): void
    {
        $http = new Client();
        $this->expectException(\InvalidArgumentException::class);
        new MulClient($http, $this->tmpDir, 'https://example.com', 1000);
    }

    public function testThrottleCallsSleepFn(): void
    {
        $mock       = new MockHandler([new Response(200, [], 'a'), new Response(200, [], 'b')]);
        $sleepCalls = [];
        $sleepFn    = static function (int $us) use (&$sleepCalls): void {
            $sleepCalls[] = $us;
        };

        $stack  = HandlerStack::create($mock);
        $http   = new Client(['handler' => $stack]);
        $client = new MulClient($http, $this->tmpDir, 'https://example.com', MulClient::MIN_DELAY_MS, null, $sleepFn);

        $client->fetchRaw('https://example.com/u/A');
        $client->fetchRaw('https://example.com/u/B');

        self::assertNotEmpty($sleepCalls, 'sleepFn should be called between requests');
    }
}
