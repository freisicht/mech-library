<?php

declare(strict_types=1);

namespace App\Tests\Formation;

use App\Formation\FormationRulesEngine;
use PHPUnit\Framework\TestCase;

final class FormationRulesEngineTest extends TestCase
{
    private FormationRulesEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new FormationRulesEngine();
    }

    // ── Unknown type ──────────────────────────────────────────────────────────

    public function testUnknownTypeReturnsFail(): void
    {
        $result = $this->engine->evaluate('no-such-type', []);
        self::assertFalse($result->compliant);
        self::assertNotEmpty($result->unmetRequirements);
    }

    // ── Unit count bounds ─────────────────────────────────────────────────────

    public function testEmptyFormationFailsCountCheck(): void
    {
        $result = $this->engine->evaluate('battle-lance', []);
        self::assertFalse($result->compliant);
    }

    public function testBattleLanceExactlyCompliant(): void
    {
        $units = [
            $this->unit(bf_size: 4, move: '6"', role: 'Juggernaut'),
            $this->unit(bf_size: 3, move: '6"', role: 'Brawler'),
            $this->unit(bf_size: 2, move: '8"', role: 'Striker'),
            $this->unit(bf_size: 2, move: '10"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('battle-lance', $units);
        self::assertTrue($result->compliant);
        self::assertEmpty($result->unmetRequirements);
    }

    public function testBattleLanceFailsWhenNoHeavies(): void
    {
        $units = [
            $this->unit(bf_size: 2, move: '6"', role: 'Scout'),
            $this->unit(bf_size: 2, move: '6"', role: 'Scout'),
            $this->unit(bf_size: 1, move: '8"', role: 'Striker'),
            $this->unit(bf_size: 2, move: '10"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('battle-lance', $units);
        self::assertFalse($result->compliant);
        self::assertStringContainsString('Size 3', $result->unmetRequirements[0]);
    }

    // ── Assault lance: no light units ─────────────────────────────────────────

    public function testAssaultLanceFailsWithLightUnit(): void
    {
        $units = [
            $this->unit(bf_size: 4, move: '4"', role: 'Juggernaut'),
            $this->unit(bf_size: 3, move: '4"', role: 'Brawler'),
            $this->unit(bf_size: 3, move: '4"', role: 'Brawler'),
            $this->unit(bf_size: 1, move: '8"', role: 'Scout'),   // size 1 — invalid
        ];
        $result = $this->engine->evaluate('assault-lance', $units);
        self::assertFalse($result->compliant);
        $msgs = implode(' ', $result->unmetRequirements);
        self::assertStringContainsString('Size 1', $msgs);
    }

    // ── Fire support: role percentage ────────────────────────────────────────

    public function testFireSupportLanceCompliant(): void
    {
        $units = [
            $this->unit(bf_size: 3, move: '4"', role: 'Missile Boat'),
            $this->unit(bf_size: 3, move: '4"', role: 'Sniper'),
            $this->unit(bf_size: 3, move: '4"', role: 'Missile Boat'),
            $this->unit(bf_size: 2, move: '6"', role: 'Striker'),  // 25 % non-FS → OK at 75 %
        ];
        $result = $this->engine->evaluate('fire-support-lance', $units);
        self::assertTrue($result->compliant);
    }

    public function testFireSupportLanceFailsWithTooFewFireSupportRoles(): void
    {
        $units = [
            $this->unit(bf_size: 3, move: '4"', role: 'Missile Boat'),
            $this->unit(bf_size: 2, move: '6"', role: 'Striker'),
            $this->unit(bf_size: 2, move: '6"', role: 'Brawler'),
            $this->unit(bf_size: 2, move: '6"', role: 'Scout'),
        ];
        $result = $this->engine->evaluate('fire-support-lance', $units);
        self::assertFalse($result->compliant);
    }

    // ── Striker/Cavalry: move and size constraints ────────────────────────────

    public function testStrikerLanceCompliant(): void
    {
        $units = [
            $this->unit(bf_size: 2, move: '10"', role: 'Striker'),
            $this->unit(bf_size: 1, move: '12"', role: 'Skirmisher'),
            $this->unit(bf_size: 2, move: '8"j', role: 'Striker'),   // jump 8 qualifies
            $this->unit(bf_size: 3, move: '10"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('striker-cavalry-lance', $units);
        self::assertTrue($result->compliant);
    }

    public function testStrikerLanceFailsSizeConstraint(): void
    {
        $units = [
            $this->unit(bf_size: 4, move: '10"', role: 'Striker'),   // size 4 — invalid
            $this->unit(bf_size: 2, move: '10"', role: 'Striker'),
            $this->unit(bf_size: 2, move: '10"', role: 'Skirmisher'),
            $this->unit(bf_size: 2, move: '10"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('striker-cavalry-lance', $units);
        self::assertFalse($result->compliant);
    }

    // ── Recon lance: all must have move ≥ 6 ──────────────────────────────────

    public function testReconLanceFailsSlowUnit(): void
    {
        $units = [
            $this->unit(bf_size: 1, move: '10"', role: 'Scout'),
            $this->unit(bf_size: 2, move: '8"', role: 'Striker'),
            $this->unit(bf_size: 1, move: '4"', role: 'Scout'),   // too slow
            $this->unit(bf_size: 2, move: '6"', role: 'Scout'),
        ];
        $result = $this->engine->evaluate('recon-lance', $units);
        self::assertFalse($result->compliant);
        $msgs = implode(' ', $result->unmetRequirements);
        self::assertStringContainsString('Move', $msgs);
    }

    // ── Clan star count bounds ────────────────────────────────────────────────

    public function testBattleStarRequiresFiveUnits(): void
    {
        $units = array_fill(0, 3, $this->unit(bf_size: 4, move: '4"', role: 'Juggernaut'));
        $result = $this->engine->evaluate('battle-star', $units);
        self::assertFalse($result->compliant);
        self::assertStringContainsString('Minimum', $result->unmetRequirements[0]);
    }

    public function testBattleStarCompliant(): void
    {
        $units = [
            $this->unit(bf_size: 4, move: '4"', role: 'Juggernaut'),
            $this->unit(bf_size: 4, move: '4"', role: 'Juggernaut'),
            $this->unit(bf_size: 3, move: '6"', role: 'Brawler'),
            $this->unit(bf_size: 3, move: '6"', role: 'Brawler'),
            $this->unit(bf_size: 3, move: '4"', role: 'Sniper'),
        ];
        $result = $this->engine->evaluate('battle-star', $units);
        self::assertTrue($result->compliant);
    }

    // ── Custom type has no rules ──────────────────────────────────────────────

    public function testCustomTypeAlwaysCompliant(): void
    {
        $units = [$this->unit(bf_size: 1, move: '4"', role: '')];
        $result = $this->engine->evaluate('custom', $units);
        self::assertTrue($result->compliant);
    }

    // ── Move parsing edge cases ───────────────────────────────────────────────

    public function testJumpSuffixParsed(): void
    {
        // "8j" should give jump = 8, qualifying for striker/cavalry jump constraint
        $units = [
            $this->unit(bf_size: 2, move: '8"j', role: 'Striker'),
            $this->unit(bf_size: 2, move: '8"j', role: 'Striker'),
            $this->unit(bf_size: 1, move: '10"', role: 'Skirmisher'),
            $this->unit(bf_size: 2, move: '12"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('striker-cavalry-lance', $units);
        self::assertTrue($result->compliant);
    }

    // ── ComplianceResult helpers ──────────────────────────────────────────────

    public function testComplianceResultToArray(): void
    {
        $result = $this->engine->evaluate('battle-lance', [
            $this->unit(bf_size: 4, move: '6"', role: 'Juggernaut'),
            $this->unit(bf_size: 3, move: '6"', role: 'Brawler'),
        ]);
        $arr = $result->toArray();
        self::assertArrayHasKey('compliant', $arr);
        self::assertArrayHasKey('unmet_requirements', $arr);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function unit(int $bf_size, string $move, string $role, array $extra = []): array
    {
        return array_merge([
            'id'       => 0,
            'bf_size'  => $bf_size,
            'bf_move'  => $move,
            'role'     => $role,
            'pv'       => 20,
            'dmg_s'    => 2,
            'dmg_m'    => 2,
            'dmg_l'    => 1,
        ], $extra);
    }
}
