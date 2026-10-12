<?php

declare(strict_types=1);

namespace App\Tests\Web;

final class ApiUnitsTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importer->upsert($this->makeUnit(99910));
        // A second unit with lower PV to test filtering
        $low = $this->makeUnit(99911);
        $low['BFPointValue'] = 10;
        $low['BFDamageShort'] = 1;
        $low['BFDamageMedium'] = 1;
        $low['BFDamageLong'] = 0;
        $low['BFAbilities'] = 'CASE';
        $this->importer->upsert($low);
    }

    public function testApiReturnsValidEnvelope(): void
    {
        $data = $this->getJson('/api/units');

        self::assertArrayHasKey('total', $data);
        self::assertArrayHasKey('last_page', $data);
        self::assertArrayHasKey('data', $data);
        self::assertIsArray($data['data']);
        self::assertGreaterThanOrEqual(2, $data['total']);
    }

    public function testApiResponseHasExpectedFields(): void
    {
        $data = $this->getJson('/api/units?pv_min=50&pv_max=55');
        self::assertNotEmpty($data['data']);
        $row = $data['data'][0];

        foreach (['id', 'name', 'class', 'variant', 'tonnage', 'pv', 'move', 'tmm', 'dmg_s', 'dmg_m', 'dmg_l', 'armor', 'structure', 'abilities'] as $field) {
            self::assertArrayHasKey($field, $row, "Missing field: {$field}");
        }
    }

    public function testPvRangeFilter(): void
    {
        $data = $this->getJson('/api/units?pv_min=50&pv_max=55');

        foreach ($data['data'] as $row) {
            self::assertGreaterThanOrEqual(50, $row['pv']);
            self::assertLessThanOrEqual(55, (int) $row['pv']);
        }
    }

    public function testPvMaxExcludesHighPvUnit(): void
    {
        // The test unit 99910 has PV 52; filtering max=9 should exclude it
        $data = $this->getJson('/api/units?pv_max=9');

        $ids = array_column($data['data'], 'id');
        $unit = \App\Model\UnitsQuery::create()->findOneByMulId(99910);
        self::assertNotNull($unit);
        self::assertNotContains((string) $unit->getId(), array_map('strval', $ids));
    }

    public function testAbilityFilter(): void
    {
        // Combine ability filter with search to guarantee fixture units are in page 1
        $data = $this->getJson('/api/units?' . http_build_query(['ability' => ['CASE'], 'search' => 'Test Atlas']));

        $ids = array_column($data['data'], 'id');
        $withCase    = \App\Model\UnitsQuery::create()->findOneByMulId(99911);
        $withoutCase = \App\Model\UnitsQuery::create()->findOneByMulId(99910);
        self::assertNotNull($withCase);
        self::assertNotNull($withoutCase);

        self::assertContains($withCase->getId(), array_map('intval', $ids));
        self::assertNotContains($withoutCase->getId(), array_map('intval', $ids));
    }

    public function testSearchFilter(): void
    {
        $data = $this->getJson('/api/units?search=Test+Atlas');

        self::assertGreaterThanOrEqual(2, $data['total']);
        foreach ($data['data'] as $row) {
            self::assertStringContainsStringIgnoringCase('atlas', strtolower($row['name'] . $row['class']));
        }
    }

    public function testPaginationReturnsCorrectPage(): void
    {
        // Smallest allowed page size is 25; setUp already created two units.
        for ($mulId = 99920; $mulId < 99944; $mulId++) {
            $this->importer->upsert($this->makeUnit($mulId));
        }
        $query = 'search=Test+Atlas&size=25';
        $page1 = $this->getJson("/api/units?{$query}&page=1");
        $page2 = $this->getJson("/api/units?{$query}&page=2");

        self::assertCount(25, $page1['data']);
        self::assertGreaterThan(1, $page1['last_page']);

        $ids1 = array_column($page1['data'], 'id');
        $ids2 = array_column($page2['data'], 'id');
        self::assertNotEmpty($ids2);
        self::assertEmpty(array_intersect($ids1, $ids2), 'Page 1 and page 2 must not share rows');
    }

    public function testContentTypeIsJson(): void
    {
        $response = $this->get('/api/units');

        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testTabulatorStyleSortParam(): void
    {
        $response = $this->get('/api/units?page=1&size=25&sort[0][field]=tonnage&sort[0][dir]=desc');
        $body     = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringStartsWith('{', $body, 'Body must be pure JSON, not a PHP warning prefix');

        $payload = json_decode($body, true);
        self::assertIsArray($payload);
        self::assertNotEmpty($payload['data']);

        $tonnages = array_column($payload['data'], 'tonnage');
        $sorted   = $tonnages;
        rsort($sorted);
        self::assertSame($sorted, $tonnages, 'Rows must be sorted by tonnage desc');
    }
}
