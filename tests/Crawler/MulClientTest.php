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

    /** Build a MulClient with a no-op sleep so tests never actually wait 1s. */
    private function makeClient(MockHandler $mock): MulClient
    {
        $stack = HandlerStack::create($mock);
        $http  = new Client(['handler' => $stack]);
        return new MulClient(
            $http,
            $this->tmpDir,
            'https://example.com',
            delayMs: 1000,
            sleepFn: static function (int $_us): void {
            },
        );
    }

    public function testFetchQuickListReturnsParsedJson(): void
    {
        $payload = json_encode(['Units' => [['Id' => 1, 'Name' => 'Atlas AS7-D']]]);
        $mock    = new MockHandler([new Response(200, [], $payload)]);
        $client  = $this->makeClient($mock);

        $result = $client->fetchQuickList(['Types' => 18, 'MinTons' => 100, 'MaxTons' => 100]);

        self::assertArrayHasKey('Units', $result);
        self::assertCount(1, $result['Units']);
        self::assertSame('Atlas AS7-D', $result['Units'][0]['Name']);
    }

    public function testFetchQuickListHitsCache(): void
    {
        $payload = json_encode(['Units' => [['Id' => 2]]]);
        // Only one response queued — a second real request would throw
        $mock   = new MockHandler([new Response(200, [], $payload)]);
        $client = $this->makeClient($mock);

        $params = ['Types' => 18, 'MinTons' => 20, 'MaxTons' => 20];
        $client->fetchQuickList($params);
        // Second call must use cache, not the mock (which has no more responses)
        $result = $client->fetchQuickList($params);

        self::assertArrayHasKey('Units', $result);
    }

    public function testFetchQuickListThrowsOnHttpError(): void
    {
        $mock = new MockHandler([
            new RequestException('Server error', new Request('GET', 'test')),
        ]);
        $client = $this->makeClient($mock);

        $this->expectException(RuntimeException::class);
        $client->fetchQuickList(['Types' => 18, 'MinTons' => 5, 'MaxTons' => 5]);
    }

    public function testDelayBelowMinimumIsRejected(): void
    {
        $http = new Client();
        $this->expectException(\InvalidArgumentException::class);
        new MulClient($http, $this->tmpDir, 'https://example.com', 500);
    }

    public function testThrottleCallsSleepFn(): void
    {
        $payload   = json_encode(['Units' => []]);
        $mock      = new MockHandler([
            new Response(200, [], $payload),
            new Response(200, [], $payload),
        ]);
        $sleepCalls = [];
        $sleepFn    = static function (int $us) use (&$sleepCalls): void {
            $sleepCalls[] = $us;
        };

        $stack  = HandlerStack::create($mock);
        $http   = new Client(['handler' => $stack]);
        $client = new MulClient($http, $this->tmpDir, 'https://example.com', 1000, null, $sleepFn);

        $client->fetchQuickList(['Types' => 18, 'MinTons' => 5, 'MaxTons' => 5]);
        $client->fetchQuickList(['Types' => 18, 'MinTons' => 10, 'MaxTons' => 10]);

        // Sleep should have been called at least once for the second request
        self::assertNotEmpty($sleepCalls, 'sleepFn should be called between requests');
    }
}
