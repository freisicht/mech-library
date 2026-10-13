<?php

declare(strict_types=1);

namespace App\Tests\Crawler;

use App\Crawler\MulClient;
use App\Crawler\MulSnapshot;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class MulSnapshotTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/mulsnapshot_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '/*') ?: []);
        rmdir($this->tmpDir);
    }

    public function testLoadsManifestAndNamedFiles(): void
    {
        $files = [
            'units' => 'units.a.json', 'unit_types' => 'unit_types.b.json', 'unit_sub_types' => 'unit_sub_types.c.json',
            'roles' => 'roles.d.json', 'technologies' => 'technologies.e.json', 'eras' => 'eras.f.json',
        ];
        $mock = new MockHandler([
            new Response(200, [], (string) json_encode(['files' => $files])),
            new Response(200, [], '[{"id":46,"t":10,"name":"Spheroid DropShip"}]'),
            new Response(200, [], '[{"id":"AAA","n":"Union","t":10,"st":46}]'),
            new Response(200, [], '[{"id":10,"name":"Aerospace Craft"}]'),
            new Response(200, [], '[{"id":1,"name":"None"}]'),
            new Response(200, [], '[{"id":1,"name":"Inner Sphere"}]'),
            new Response(200, [], '[{"id":4,"name":"Clan Invasion"}]'),
        ]);
        $requested = [];
        $stack     = HandlerStack::create($mock);
        $stack->push(Middleware::mapRequest(static function ($r) use (&$requested) {
            $requested[] = (string) $r->getUri();
            return $r;
        }));
        $client = new MulClient(new Client(['handler' => $stack]), $this->tmpDir, 'https://mul.example', sleepFn: static function (int $_us): void {
        });

        $snapshot = MulSnapshot::load($client);

        self::assertSame('https://mul.example/data/manifest.json', $requested[0]);
        self::assertContains('https://mul.example/data/units.a.json', $requested);
        self::assertCount(1, $snapshot->units);
        self::assertSame('Union', $snapshot->units[0]['n']);
        self::assertSame([10 => 'Aerospace Craft'], $snapshot->types);
        self::assertSame(['name' => 'Spheroid DropShip', 'type' => 10], $snapshot->subTypes[46]);
        self::assertSame([4 => 'Clan Invasion'], $snapshot->eras);
    }
}
