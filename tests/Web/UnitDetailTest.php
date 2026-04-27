<?php

declare(strict_types=1);

namespace App\Tests\Web;

final class UnitDetailTest extends WebTestCase
{
    private int $unitId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer->upsert($this->makeUnit(99920));
        $unit          = \App\Model\UnitsQuery::create()->findOneByMulId(99920);
        self::assertNotNull($unit);
        $this->unitId = $unit->getId();
    }

    public function testDetailPageReturns200(): void
    {
        $response = $this->get('/units/' . $this->unitId);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testDetailPageShowsUnitName(): void
    {
        $body = (string) $this->get('/units/' . $this->unitId)->getBody();

        self::assertStringContainsString('Test Atlas', $body);
        self::assertStringContainsString('TS-T1', $body);
    }

    public function testDetailPageShowsAlphaStrikeStats(): void
    {
        $body = (string) $this->get('/units/' . $this->unitId)->getBody();

        self::assertStringContainsString('Alpha Strike', $body);
        self::assertStringContainsString('Armor', $body);
    }

    public function testNonExistentUnitReturns404(): void
    {
        $response = $this->get('/units/999999');

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('not found', strtolower((string) $response->getBody()));
    }
}
