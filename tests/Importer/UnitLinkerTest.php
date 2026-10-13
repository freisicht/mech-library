<?php

declare(strict_types=1);

namespace App\Tests\Importer;

use App\Bootstrap;
use App\Importer\LookupResolver;
use App\Importer\UnitLinker;
use App\Model\Units;
use App\Model\UnitsQuery;
use PHPUnit\Framework\TestCase;
use Propel\Runtime\Propel;

final class UnitLinkerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!isset($_ENV['DB_USER']) && !getenv('DB_USER')) {
            \Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__, 2))->safeLoad();
        }
        Bootstrap::initPropel();
        $this->tearDown();
    }

    protected function tearDown(): void
    {
        UnitsQuery::create()->filterByMulId([99950, 99951, 99952, 99953])->delete();
        Propel::getConnection()->exec("DELETE FROM roles WHERE name = 'Linker Test Role'");
    }

    public function testLinksUniqueNamesOnly(): void
    {
        $this->unit(99950, 'Zzlinker Alpha A1');
        $this->unit(99951, 'Zzlinker Twin T1');
        $this->unit(99952, 'Zzlinker Twin T1');
        $this->unit(99953, 'Zzlinker Unknown U1');

        $counts = (new UnitLinker())->link([
            ['id' => 'ZZLINK0001', 'n' => 'Zzlinker Alpha', 'm' => 'A1'],
            ['id' => 'ZZLINK0002', 'n' => 'Zzlinker Twin', 'm' => 'T1'],
        ]);

        self::assertSame(1, $counts['linked']);
        self::assertSame(1, $counts['ambiguous']);
        // The linker writes with plain SQL; drop Propel's cached objects.
        \App\Model\Map\UnitsTableMap::clearInstancePool();
        self::assertSame('ZZLINK0001', UnitsQuery::create()->findOneByMulId(99950)?->getMulUid());
        self::assertNull(UnitsQuery::create()->findOneByMulId(99951)?->getMulUid());
        self::assertNull(UnitsQuery::create()->findOneByMulId(99953)?->getMulUid());
    }

    public function testResolverMatchesByNormalisedNameAndCreatesMissing(): void
    {
        $resolver = new LookupResolver();
        $first    = $resolver->resolve('roles', 'Linker Test Role');

        self::assertSame($first, (new LookupResolver())->resolve('roles', 'linker  test role'));
        self::assertSame('late succession war - lostech', LookupResolver::normalise("Late Succession War \u{2013} LosTech"));
    }

    private function unit(int $mulId, string $name): void
    {
        (new Units())->setMulId($mulId)->setName($name)->save();
    }
}
