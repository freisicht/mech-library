<?php

declare(strict_types=1);

namespace App\Tests\Importer;

use App\Bootstrap;
use App\Importer\UnitImporter;
use App\Model\UnitAbilitiesQuery;
use App\Model\UnitsQuery;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for UnitImporter::upsert().
 *
 * Requires a live MySQL mech_library database (uses the same .env as the app).
 * Each test cleans up its own fixtures.
 */
final class UnitImporterTest extends TestCase
{
    private UnitImporter $importer;

    /** @var int[] */
    private array $createdMulIds = [];

    protected function setUp(): void
    {
        $this->loadEnv();
        Bootstrap::initPropel();
        $this->importer = new UnitImporter();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdMulIds as $mulId) {
            $unit = UnitsQuery::create()->findOneByMulId($mulId);
            if ($unit !== null) {
                UnitAbilitiesQuery::create()->filterByUnitId($unit->getId())->delete();
                $unit->delete();
            }
        }
    }

    private function loadEnv(): void
    {
        if (!isset($_ENV['DB_USER']) && !getenv('DB_USER')) {
            $envFile = dirname(__DIR__, 2) . '/.env';
            if (file_exists($envFile)) {
                // createUnsafeImmutable populates both $_ENV and putenv(), so
                // getenv() works regardless of the PHP variables_order ini setting.
                \Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__, 2))->safeLoad();
            }
        }
    }

    /** @return array<string, mixed> */
    private function makeUnit(int $mulId = 99901): array
    {
        $this->createdMulIds[] = $mulId;
        return [
            'Id'               => $mulId,
            'Name'             => 'Test Atlas TS-T1',
            'Class'            => 'Atlas',
            'Variant'          => 'TS-T1',
            'Tonnage'          => 100,
            'BattleValue'      => 1000,
            'Cost'             => 5000000,
            'Rules'            => 'Standard',
            'DateIntroduced'   => '3025',
            'EraId'            => 9999,
            'EraStart'         => 2571,
            'ImageUrl'         => null,
            'IsPublished'      => true,
            'Release'          => 1.0,
            'Type'             => ['Id' => 18, 'Name' => 'BattleMech'],
            'Technology'       => ['Id' => 1,  'Name' => 'Inner Sphere'],
            'Role'             => ['Id' => 1,  'Name' => 'Juggernaut'],
            'BFType'           => 'BM',
            'BFSize'           => 4,
            'BFMove'           => '6"',
            'BFTMM'            => 0,
            'BFArmor'          => 10,
            'BFStructure'      => 8,
            'BFThreshold'      => 0,
            'BFDamageShort'    => 5,
            'BFDamageShortMin' => false,
            'BFDamageMedium'   => 5,
            'BFDamageMediumMin' => false,
            'BFDamageLong'     => 1,
            'BFDamageLongMin'  => false,
            'BFDamageExtreme'  => 0,
            'BFDamageExtemeMin' => false,
            'BFOverheat'       => 0,
            'BFPointValue'     => 52,
            'BFAbilities'      => 'AC2/2/-,IF1',
        ];
    }

    public function testUpsertInsertsNewUnit(): void
    {
        $changed = $this->importer->upsert($this->makeUnit());
        self::assertTrue($changed);

        $unit = UnitsQuery::create()->findOneByMulId(99901);
        self::assertNotNull($unit);
        self::assertSame('Test Atlas TS-T1', $unit->getName());
        self::assertSame(100, $unit->getTonnage());
    }

    public function testUpsertIsIdempotent(): void
    {
        $data = $this->makeUnit();
        $this->importer->upsert($data);

        // Second call with same data must produce no DB write
        $changed = $this->importer->upsert($data);
        self::assertFalse($changed);
    }

    public function testUpsertUpdatesChangedField(): void
    {
        $data = $this->makeUnit();
        $this->importer->upsert($data);

        $data['BFPointValue'] = 99;
        $changed = $this->importer->upsert($data);
        self::assertTrue($changed);

        $unit = UnitsQuery::create()->findOneByMulId(99901);
        self::assertNotNull($unit);
        self::assertSame(99, $unit->getBfPointValue());
    }

    public function testAbilitiesAreParsedAndStored(): void
    {
        $this->importer->upsert($this->makeUnit());

        $unit = UnitsQuery::create()->findOneByMulId(99901);
        self::assertNotNull($unit);

        $abilities = UnitAbilitiesQuery::create()
            ->filterByUnitId($unit->getId())
            ->find();

        self::assertCount(2, $abilities);
        $tags = array_map(fn ($a) => $a->getTag(), $abilities->getData());
        self::assertContains('AC', $tags);
        self::assertContains('IF', $tags);
    }

    public function testUpsertWithoutIdIsSkipped(): void
    {
        $data    = $this->makeUnit();
        unset($data['Id']);
        $changed = $this->importer->upsert($data);
        self::assertFalse($changed);
    }
}
