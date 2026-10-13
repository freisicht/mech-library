<?php

declare(strict_types=1);

namespace App\Tests\Importer;

use App\Crawler\MulSnapshot;
use App\Importer\Lookups;
use App\Importer\SnapshotUnitMapper;
use PHPUnit\Framework\TestCase;

final class SnapshotUnitMapperTest extends TestCase
{
    private SnapshotUnitMapper $mapper;

    /** @var list<array{string, string}> */
    private array $resolved = [];

    protected function setUp(): void
    {
        $snapshot = new MulSnapshot(
            [],
            [1 => 'BattleMech', 2 => 'Battle Armor', 4 => 'Combat Vehicle', 5 => 'Fighter Craft', 10 => 'Aerospace Craft'],
            [
                5  => ['name' => 'Conventional Fighter', 'type' => 5],
                18 => ['name' => 'OmniFighter', 'type' => 5],
                26 => ['name' => 'Small Craft', 'type' => 10],
                46 => ['name' => 'Spheroid DropShip', 'type' => 10],
            ],
            [9 => 'Striker', 14 => 'Interceptor'],
            [1 => 'Inner Sphere'],
            [4 => 'Clan Invasion'],
        );

        $lookups = new class ($this->resolved) implements Lookups {
            /** @param list<array{string, string}> $log */
            public function __construct(private array &$log)
            {
            }

            public function resolve(string $table, string $name): int
            {
                $this->log[] = [$table, $name];
                return 1000 + count($this->log);
            }
        };

        $this->mapper = new SnapshotUnitMapper($snapshot, $lookups);
    }

    public function testMapsVehicle(): void
    {
        $record = $this->mapper->map(
            ['id' => 'ABC123', 'n' => 'Harasser', 'm' => 'Missile Platform', 't' => 4, 'st' => 16, 'r' => 9, 'te' => 1, 'ton' => 25, 'pv' => 23, 'bv' => 800, 'iy' => 3010, 'ie' => 4],
            [
                'pv' => 23, 'size' => 1, 'move' => '24"h', 'tmm' => 4, 'armor' => 1, 'structure' => 2,
                'threshold' => null, 'overheat' => null, 'damage' => [[2, false], [2, false], [0, true], [0, false]],
                'chips' => ['FC', 'SRM 2/2', 'TUR (2/2/-,SRM2/2)', 'Striker'], 'image_url' => 'https://mul.example/a.webp',
            ],
        );

        self::assertSame('ABC123', $record['Uid']);
        self::assertSame('Harasser Missile Platform', $record['Name']);
        self::assertSame(25.0, $record['Tonnage']);
        self::assertSame('CV', $record['BFType']);
        self::assertSame(4, $record['TMM']);
        self::assertTrue($record['BFDamageLongMin']);
        self::assertSame('FC,SRM2/2,TUR(2/2/-,SRM2/2)', $record['BFAbilities']);
        self::assertSame('Striker', $record['Role']['Name']);
        self::assertContains(['roles', 'Striker'], $this->resolved);
        self::assertContains(['unit_types', 'Combat Vehicle'], $this->resolved);
        self::assertContains(['eras', 'Clan Invasion'], $this->resolved);
    }

    public function testTmmFallsBackToMoveForGroundUnits(): void
    {
        $record = $this->mapper->map(
            ['id' => 'BA1', 'n' => 'Elemental', 't' => 2, 'ton' => 1.5],
            ['move' => '6"j', 'tmm' => null, 'damage' => [], 'chips' => []],
        );

        self::assertSame('BA', $record['BFType']);
        self::assertSame(1, $record['TMM']);
        self::assertSame(1.5, $record['Tonnage']);
        self::assertSame('Elemental', $record['Name']);
    }

    public function testAerospaceKeepsNullTmmAndThreshold(): void
    {
        $record = $this->mapper->map(
            ['id' => 'AF1', 'n' => 'Avar', 'm' => 'Prime', 't' => 5, 'st' => 18, 'r' => 14],
            ['move' => '10a', 'tmm' => null, 'threshold' => 2, 'damage' => [[4, false]], 'chips' => ['BOMB 1', 'Interceptor']],
        );

        self::assertSame('AF', $record['BFType']);
        self::assertNull($record['TMM']);
        self::assertSame(2, $record['BFThreshold']);
        self::assertSame('BOMB1', $record['BFAbilities']);
    }

    /** @return iterable<string, array{int, int, string}> */
    public static function bfTypes(): iterable
    {
        yield 'conventional fighter' => [5, 5, 'CF'];
        yield 'small craft'          => [10, 26, 'SC'];
        yield 'dropship'             => [10, 46, 'DS'];
        yield 'battlemech'           => [1, 0, 'BM'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('bfTypes')]
    public function testBfTypeFromTypeAndSubType(int $type, int $subType, string $expected): void
    {
        $record = $this->mapper->map(['id' => 'X', 'n' => 'X', 't' => $type, 'st' => $subType], ['move' => '', 'damage' => [], 'chips' => []]);

        self::assertSame($expected, $record['BFType']);
    }

    public function testDisplayNamePrefersHullTitle(): void
    {
        self::assertSame('Aegis class Heavy Cruiser', SnapshotUnitMapper::displayName(['n' => 'Aegis', 'm' => null, 'dt' => 'Aegis class Heavy Cruiser']));
    }
}
