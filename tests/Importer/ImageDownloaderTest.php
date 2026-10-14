<?php

declare(strict_types=1);

namespace App\Tests\Importer;

use App\Bootstrap;
use App\Crawler\MulClient;
use App\Importer\ImageDownloader;
use App\Model\Units;
use App\Model\UnitsQuery;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ImageDownloaderTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        if (!isset($_ENV['DB_USER']) && !getenv('DB_USER')) {
            \Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__, 2))->safeLoad();
        }
        Bootstrap::initPropel();
        $this->dir = sys_get_temp_dir() . '/imgdl_test_' . uniqid();
        mkdir($this->dir . '/cache', 0755, true);
        mkdir($this->dir . '/images', 0755, true);
        UnitsQuery::create()->filterByMulUid(['IMGTEST001', 'IMGTEST002'])->delete();
    }

    protected function tearDown(): void
    {
        UnitsQuery::create()->filterByMulUid(['IMGTEST001', 'IMGTEST002'])->delete();
        array_map('unlink', glob($this->dir . '/*/*') ?: []);
        array_map('rmdir', glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
    }

    public function testFileCopiedFromElsewhereIsAdoptedWithoutRequest(): void
    {
        file_put_contents($this->dir . '/images/IMGTEST001.webp', 'pixels');
        $unit = $this->unit('IMGTEST001');

        // No response queued: a request would fail the test.
        $result = $this->downloader(new MockHandler([]))->download($unit);

        self::assertSame('adopted', $result);
        self::assertSame('images/units/IMGTEST001.webp', $unit->getImagePath());
        self::assertSame(hash('sha256', 'pixels'), $unit->getImageHash());
        self::assertFalse($unit->isModified(), 'path is saved');
    }

    public function testMissingFileIsDownloaded(): void
    {
        $unit = $this->unit('IMGTEST002');

        $result = $this->downloader(new MockHandler([new Response(200, [], 'fresh')]))->download($unit);

        self::assertSame('downloaded', $result);
        self::assertSame('fresh', file_get_contents($this->dir . '/images/IMGTEST002.webp'));
    }

    private function unit(string $uid): Units
    {
        $unit = (new Units())->setMulUid($uid)->setName('Image Test')->setImageUrl("https://mul.example/art/{$uid}.webp");
        $unit->save();
        return $unit;
    }

    private function downloader(MockHandler $mock): ImageDownloader
    {
        $client = new MulClient(new Client(['handler' => HandlerStack::create($mock)]), $this->dir . '/cache', 'https://mul.example', sleepFn: static function (int $_us): void {
        });
        return new ImageDownloader($client, $this->dir . '/images');
    }
}
