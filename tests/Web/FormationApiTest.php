<?php

declare(strict_types=1);

namespace App\Tests\Web;

use Propel\Runtime\Propel;

final class FormationApiTest extends WebTestCase
{
    /** @var int[] */
    private array $formationIds = [];

    /** @var int[] */
    private array $armyIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        // A real unit to add to formations
        $this->importer->upsert($this->makeUnit(99920));
    }

    protected function tearDown(): void
    {
        $conn = Propel::getConnection();
        foreach ($this->formationIds as $id) {
            $conn->prepare('DELETE FROM formations WHERE id = ?')->execute([$id]);
        }
        foreach ($this->armyIds as $id) {
            $conn->prepare('DELETE FROM armies WHERE id = ?')->execute([$id]);
        }
        // Clear any stray active flags
        $conn->prepare('UPDATE formations SET is_active = 0')->execute([]);
        parent::tearDown();
    }

    // ── Formation type catalogue ──────────────────────────────────────────────

    public function testFormationTypesEndpoint(): void
    {
        $data = $this->getJson('/api/formation-types');
        self::assertIsArray($data);
        self::assertNotEmpty($data);

        $slugs = array_column($data, 'slug');
        self::assertContains('battle-lance', $slugs);
        self::assertContains('battle-star', $slugs);
        self::assertContains('custom', $slugs);
        self::assertCount(count(\App\Formation\FormationTypeRegistry::all()), $data);

        $groups = array_values(array_unique(array_column($data, 'group')));
        self::assertSame(array_keys(\App\Formation\FormationTypeRegistry::groups()), $groups);
    }

    // ── Army CRUD ─────────────────────────────────────────────────────────────

    public function testCreateArmy(): void
    {
        $response = $this->post('/api/armies', ['name' => 'Test Army']);
        self::assertSame(201, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        self::assertArrayHasKey('id', $body);
        self::assertSame('Test Army', $body['name']);

        $this->armyIds[] = (int) $body['id'];
    }

    public function testCreateArmyRequiresName(): void
    {
        $response = $this->post('/api/armies', []);
        self::assertSame(422, $response->getStatusCode());
    }

    public function testListArmies(): void
    {
        $createBody = json_decode(
            (string) $this->post('/api/armies', ['name' => 'List Test Army'])->getBody(),
            true,
        );
        $this->armyIds[] = (int) $createBody['id'];

        $data = $this->getJson('/api/armies');
        self::assertIsArray($data);
        $names = array_column($data, 'name');
        self::assertContains('List Test Army', $names);
    }

    // ── Formation CRUD ────────────────────────────────────────────────────────

    public function testCreateFormation(): void
    {
        $response = $this->post('/api/formations', [
            'name'           => 'Alpha Company',
            'formation_type' => 'battle-lance',
            'pv_budget'      => 200,
        ]);
        self::assertSame(201, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('Alpha Company', $body['name']);
        self::assertSame('battle-lance', $body['formation_type']);
        self::assertSame(200, $body['pv_budget']);
        self::assertArrayHasKey('compliance', $body);
        self::assertArrayHasKey('units', $body);

        $this->formationIds[] = (int) $body['id'];
    }

    public function testCreateFormationRejectsUnknownType(): void
    {
        $response = $this->post('/api/formations', [
            'name'           => 'Bad Formation',
            'formation_type' => 'not-a-real-type',
        ]);
        self::assertSame(422, $response->getStatusCode());
    }

    public function testUpdateFormation(): void
    {
        $id = $this->createFormation('To Rename', 'custom');

        $response = $this->put('/api/formations/' . $id, [
            'name'           => 'Renamed',
            'formation_type' => 'battle-lance',
            'pv_budget'      => 300,
        ]);
        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('Renamed', $body['name']);
        self::assertSame(300, $body['pv_budget']);
    }

    // ── Active formation ──────────────────────────────────────────────────────

    public function testActivateAndGetActiveFormation(): void
    {
        $id = $this->createFormation('Active Test', 'custom');

        $response = $this->post('/api/formations/' . $id . '/activate');
        self::assertSame(200, $response->getStatusCode());

        $active = $this->getJson('/api/formations/active');
        self::assertArrayHasKey('formation', $active);
        self::assertNotNull($active['formation']);
        self::assertSame($id, (int) $active['formation']['id']);
    }

    public function testDeactivateFormation(): void
    {
        $id = $this->createFormation('Deactivate Test', 'custom');
        $this->post('/api/formations/' . $id . '/activate');

        $response = $this->delete('/api/formations/active');
        self::assertSame(204, $response->getStatusCode());

        $active = $this->getJson('/api/formations/active');
        self::assertNull($active['formation']);
    }

    // ── Add / remove units ────────────────────────────────────────────────────

    public function testAddAndRemoveUnit(): void
    {
        $formationId = $this->createFormation('Unit Test Formation', 'battle-lance');
        $unitId      = $this->getTestUnitId();

        // Add
        $response = $this->post('/api/formations/' . $formationId . '/units', ['unit_id' => $unitId]);
        self::assertSame(201, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertCount(1, $body['units']);
        self::assertSame(1, $body['unit_count']);

        // Same unit again → second entry
        $response2 = $this->post('/api/formations/' . $formationId . '/units', ['unit_id' => $unitId]);
        self::assertSame(201, $response2->getStatusCode());
        $body2 = json_decode((string) $response2->getBody(), true);
        self::assertCount(2, $body2['units']);
        self::assertSame($unitId, (int) $body2['units'][1]['id']);
        self::assertNotSame($body2['units'][0]['entry_id'], $body2['units'][1]['entry_id']);

        // Removing one entry keeps the other copy
        $firstEntry = (int) $body2['units'][0]['entry_id'];
        $response3  = $this->delete('/api/formations/' . $formationId . '/entries/' . $firstEntry);
        self::assertSame(200, $response3->getStatusCode());
        $body3 = json_decode((string) $response3->getBody(), true);
        self::assertCount(1, $body3['units']);
        self::assertSame((int) $body2['units'][1]['entry_id'], (int) $body3['units'][0]['entry_id']);
    }

    public function testAddUnitReturnsComplianceAndSummary(): void
    {
        $formationId = $this->createFormation('Compliance Test', 'battle-lance', 300);
        $unitId      = $this->getTestUnitId();

        $body = json_decode(
            (string) $this->post('/api/formations/' . $formationId . '/units', ['unit_id' => $unitId])->getBody(),
            true,
        );

        self::assertArrayHasKey('compliance', $body);
        self::assertArrayHasKey('compliant', $body['compliance']);
        self::assertArrayHasKey('unmet_requirements', $body['compliance']);
        self::assertArrayHasKey('pv_sum', $body);
        self::assertArrayHasKey('pv_remaining', $body);
        self::assertArrayHasKey('dmg_s_total', $body);
    }

    // ── Delete formation ──────────────────────────────────────────────────────

    public function testDeleteFormation(): void
    {
        $id = $this->createFormation('To Delete', 'custom');
        // Do NOT add to $this->formationIds — we expect deletion to work

        $response = $this->delete('/api/formations/' . $id);
        self::assertSame(204, $response->getStatusCode());

        $response2 = $this->get('/api/formations/' . $id);
        self::assertSame(404, $response2->getStatusCode());
    }

    // ── Print route ───────────────────────────────────────────────────────────

    public function testPrintViewReturnsHtml(): void
    {
        $formationId = $this->createFormation('Print Test', 'battle-lance');

        $response = $this->get('/formations/' . $formationId . '/print');
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('Print Test', (string) $response->getBody());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createFormation(string $name, string $type, ?int $budget = null): int
    {
        $payload = ['name' => $name, 'formation_type' => $type];
        if ($budget !== null) {
            $payload['pv_budget'] = $budget;
        }
        $body = json_decode(
            (string) $this->post('/api/formations', $payload)->getBody(),
            true,
        );
        $id                   = (int) $body['id'];
        $this->formationIds[] = $id;
        return $id;
    }

    private function getTestUnitId(): int
    {
        $data = $this->getJson('/api/units?search=Test+Atlas&per_page=1');
        if (!empty($data['data'][0]['id'])) {
            return (int) $data['data'][0]['id'];
        }
        // Fallback: get any unit
        $all = $this->getJson('/api/units?per_page=1');
        return (int) $all['data'][0]['id'];
    }
}
