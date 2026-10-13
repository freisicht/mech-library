<?php

declare(strict_types=1);

namespace App\Tests\Importer;

use App\Importer\UnitPageParser;
use PHPUnit\Framework\TestCase;

final class UnitPageParserTest extends TestCase
{
    public function testParsesGroundVehicle(): void
    {
        $stats = (new UnitPageParser())->parse($this->fixture('unit_page_vehicle.html'));

        self::assertNotNull($stats);
        self::assertSame(23, $stats['pv']);
        self::assertSame(1, $stats['size']);
        self::assertSame('24"h', $stats['move']);
        self::assertSame(4, $stats['tmm']);
        self::assertSame(1, $stats['armor']);
        self::assertSame(2, $stats['structure']);
        self::assertSame([[2, false], [2, false], [0, true], [0, false]], $stats['damage']);
        self::assertNull($stats['overheat']);
        self::assertSame(['FC', 'SRM 2/2', 'TUR (2/2/-,SRM2/2)', 'Striker'], $stats['chips']);
        self::assertSame('https://mul.example/point-backs/unit-art/harasser.webp', $stats['image_url']);
    }

    public function testParsesAerospaceWithThresholdAndNoTmm(): void
    {
        $stats = (new UnitPageParser())->parse($this->fixture('unit_page_fighter.html'));

        self::assertNotNull($stats);
        self::assertSame('10a', $stats['move']);
        self::assertNull($stats['tmm']);
        self::assertSame(1, $stats['overheat']);
        self::assertSame(2, $stats['threshold']);
        self::assertSame(['BOMB 1', 'Interceptor'], $stats['chips']);
        self::assertNull($stats['image_url']);
    }

    public function testPageWithoutAlphaStrikeBlockYieldsNull(): void
    {
        self::assertNull((new UnitPageParser())->parse('<html><body><h1>Some Building</h1></body></html>'));
    }

    private function fixture(string $name): string
    {
        return (string) file_get_contents(__DIR__ . '/fixtures/' . $name);
    }
}
