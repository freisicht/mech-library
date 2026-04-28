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
        // AS:CE p.117: ≥50 % Size 3+ AND ≥3 units must be Brawler/Sniper/Skirmisher.
        $units = [
            $this->unit(bf_size: 4, move: '6"', role: 'Brawler'),
            $this->unit(bf_size: 3, move: '6"', role: 'Sniper'),
            $this->unit(bf_size: 2, move: '8"', role: 'Skirmisher'),
            $this->unit(bf_size: 2, move: '10"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('battle-lance', $units);
        self::assertTrue($result->compliant);
        self::assertEmpty($result->unmetRequirements);
    }

    public function testBattleLanceFailsWhenTooFewBrawlerSniperSkirmisher(): void
    {
        // 50% Size 3+ satisfied, but only 1 Brawler/Sniper/Skirmisher → fails role rule.
        $units = [
            $this->unit(bf_size: 4, move: '6"', role: 'Juggernaut'),
            $this->unit(bf_size: 3, move: '6"', role: 'Brawler'),
            $this->unit(bf_size: 2, move: '8"', role: 'Striker'),
            $this->unit(bf_size: 2, move: '10"', role: 'Scout'),
        ];
        $result = $this->engine->evaluate('battle-lance', $units);
        self::assertFalse($result->compliant);
        self::assertStringContainsString('Brawler', implode(' ', $result->unmetRequirements));
    }

    public function testBattleLanceFailsWhenNoHeavies(): void
    {
        $units = [
            $this->unit(bf_size: 2, move: '6"', role: 'Brawler'),
            $this->unit(bf_size: 2, move: '6"', role: 'Sniper'),
            $this->unit(bf_size: 1, move: '8"', role: 'Skirmisher'),
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

    // ── New AS:CE rules: Assault medium-attack + min ground move ──────────────

    public function testAssaultLanceFailsWithLowMediumDamage(): void
    {
        // Sizes/role satisfy ≥3 Size 3+ and no Size 1; but dmg_m = 2 < 3 → fails 75 % rule.
        $units = [
            $this->unit(bf_size: 4, move: '4"', role: 'Juggernaut'),
            $this->unit(bf_size: 3, move: '4"', role: 'Brawler', extra: ['dmg_m' => 2]),
            $this->unit(bf_size: 3, move: '4"', role: 'Brawler', extra: ['dmg_m' => 2]),
            $this->unit(bf_size: 3, move: '4"', role: 'Brawler', extra: ['dmg_m' => 2]),
        ];
        $result = $this->engine->evaluate('assault-lance', $units);
        self::assertFalse($result->compliant);
        self::assertStringContainsString('Medium', implode(' ', $result->unmetRequirements));
    }

    public function testAssaultLanceCompliantWithStrongMediumDamage(): void
    {
        $units = [
            $this->unit(bf_size: 4, move: '4"', role: 'Juggernaut', extra: ['dmg_m' => 4]),
            $this->unit(bf_size: 3, move: '4"', role: 'Brawler', extra: ['dmg_m' => 3]),
            $this->unit(bf_size: 3, move: '4"', role: 'Sniper', extra: ['dmg_m' => 3]),
            $this->unit(bf_size: 3, move: '4"', role: 'Brawler', extra: ['dmg_m' => 3]),
        ];
        $result = $this->engine->evaluate('assault-lance', $units);
        self::assertTrue($result->compliant);
    }

    // ── Striker/Cavalry: ALL units must be fast (pct=100) ─────────────────────

    public function testStrikerCavalryFailsIfAnyUnitIsSlow(): void
    {
        $units = [
            $this->unit(bf_size: 2, move: '10"', role: 'Striker'),
            $this->unit(bf_size: 2, move: '10"', role: 'Striker'),
            $this->unit(bf_size: 2, move: '10"', role: 'Skirmisher'),
            $this->unit(bf_size: 3, move: '6"', role: 'Brawler'),   // too slow → fails
        ];
        $result = $this->engine->evaluate('striker-cavalry-lance', $units);
        self::assertFalse($result->compliant);
        self::assertStringContainsString('All units', implode(' ', $result->unmetRequirements));
    }

    // ── Recon: all Move ≥ 12 + ≥2 Scout/Striker ───────────────────────────────

    public function testReconLanceCompliantWithFastScouts(): void
    {
        $units = [
            $this->unit(bf_size: 1, move: '12"', role: 'Scout'),
            $this->unit(bf_size: 1, move: '14"', role: 'Scout'),
            $this->unit(bf_size: 2, move: '12"', role: 'Striker'),
            $this->unit(bf_size: 3, move: '12"', role: 'Brawler'),
        ];
        $result = $this->engine->evaluate('recon-lance', $units);
        self::assertTrue($result->compliant);
    }

    public function testReconLanceFailsWithFewScouts(): void
    {
        $units = [
            $this->unit(bf_size: 2, move: '12"', role: 'Brawler'),
            $this->unit(bf_size: 2, move: '12"', role: 'Sniper'),
            $this->unit(bf_size: 2, move: '12"', role: 'Sniper'),
            $this->unit(bf_size: 2, move: '12"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('recon-lance', $units);
        self::assertFalse($result->compliant);
        self::assertStringContainsString('Scout', implode(' ', $result->unmetRequirements));
    }

    // ── Pursuit: 75 % Move ≥ 12 + ≥1 Medium-attack > 1 ────────────────────────

    public function testPursuitLanceCompliant(): void
    {
        $units = [
            $this->unit(bf_size: 2, move: '14"', role: 'Striker', extra: ['dmg_m' => 2]),
            $this->unit(bf_size: 1, move: '14"', role: 'Scout', extra: ['dmg_m' => 1]),
            $this->unit(bf_size: 2, move: '12"', role: 'Striker', extra: ['dmg_m' => 1]),
            $this->unit(bf_size: 2, move: '12"', role: 'Striker', extra: ['dmg_m' => 1]),
        ];
        $result = $this->engine->evaluate('pursuit-lance', $units);
        self::assertTrue($result->compliant);
    }

    public function testPursuitLanceFailsWithoutMediumAttacker(): void
    {
        // All Move 12+, but every dmg_m = 1 → no unit > 1.
        $units = [
            $this->unit(bf_size: 2, move: '12"', role: 'Striker', extra: ['dmg_m' => 1]),
            $this->unit(bf_size: 2, move: '12"', role: 'Striker', extra: ['dmg_m' => 1]),
            $this->unit(bf_size: 1, move: '12"', role: 'Scout', extra: ['dmg_m' => 1]),
            $this->unit(bf_size: 2, move: '12"', role: 'Striker', extra: ['dmg_m' => 1]),
        ];
        $result = $this->engine->evaluate('pursuit-lance', $units);
        self::assertFalse($result->compliant);
        self::assertStringContainsString('Medium', implode(' ', $result->unmetRequirements));
    }

    // ── Direct Fire: all units need long-range damage ≥ 2 ─────────────────────

    public function testDirectFireLanceFailsWithoutLongRangeDamage(): void
    {
        $units = [
            $this->unit(bf_size: 3, move: '4"', role: 'Sniper', extra: ['dmg_l' => 2]),
            $this->unit(bf_size: 3, move: '4"', role: 'Sniper', extra: ['dmg_l' => 2]),
            $this->unit(bf_size: 2, move: '6"', role: 'Brawler', extra: ['dmg_l' => 1]),  // fails
            $this->unit(bf_size: 2, move: '6"', role: 'Brawler', extra: ['dmg_l' => 2]),
        ];
        $result = $this->engine->evaluate('direct-fire-lance', $units);
        self::assertFalse($result->compliant);
        self::assertStringContainsString('Long-range', implode(' ', $result->unmetRequirements));
    }

    // ── Damage value parsing: "0*" treated as 0.5, threshold 1 fails ──────────

    public function testAttackValueStarSuffixIsMinimal(): void
    {
        $units = [
            $this->unit(bf_size: 2, move: '12"', role: 'Striker', extra: ['dmg_m' => '0*']),
            $this->unit(bf_size: 2, move: '12"', role: 'Striker', extra: ['dmg_m' => '0*']),
            $this->unit(bf_size: 1, move: '12"', role: 'Scout', extra: ['dmg_m' => '0*']),
            $this->unit(bf_size: 2, move: '12"', role: 'Striker', extra: ['dmg_m' => '0*']),
        ];
        // Pursuit needs ≥1 unit Medium > 1; 0* (= 0.5) is below the threshold.
        $result = $this->engine->evaluate('pursuit-lance', $units);
        self::assertFalse($result->compliant);
    }

    // ── Lance variations (PR2) ────────────────────────────────────────────────

    public function testLightBattleLanceCompliant(): void
    {
        $units = [
            $this->unit(bf_size: 1, move: '8"', role: 'Scout'),
            $this->unit(bf_size: 1, move: '8"', role: 'Striker'),
            $this->unit(bf_size: 1, move: '8"', role: 'Skirmisher'),
            $this->unit(bf_size: 1, move: '8"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('light-battle-lance', $units);
        self::assertTrue($result->compliant);
    }

    public function testLightBattleLanceFailsWithoutScout(): void
    {
        $units = [
            $this->unit(bf_size: 1, move: '8"', role: 'Striker'),
            $this->unit(bf_size: 1, move: '8"', role: 'Striker'),
            $this->unit(bf_size: 1, move: '8"', role: 'Striker'),
            $this->unit(bf_size: 1, move: '8"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('light-battle-lance', $units);
        self::assertFalse($result->compliant);
        self::assertStringContainsString('Scout', implode(' ', $result->unmetRequirements));
    }

    public function testMediumBattleLanceFailsWhenSizeOneDominates(): void
    {
        // Only 1 of 4 is Size 2 → 25 % < 75 %.
        $units = [
            $this->unit(bf_size: 2, move: '8"', role: 'Brawler'),
            $this->unit(bf_size: 1, move: '8"', role: 'Scout'),
            $this->unit(bf_size: 1, move: '8"', role: 'Scout'),
            $this->unit(bf_size: 1, move: '8"', role: 'Scout'),
        ];
        $result = $this->engine->evaluate('medium-battle-lance', $units);
        self::assertFalse($result->compliant);
        self::assertStringContainsString('Size 2', implode(' ', $result->unmetRequirements));
    }

    public function testHeavyBattleLanceCompliant(): void
    {
        $units = [
            $this->unit(bf_size: 4, move: '4"', role: 'Juggernaut'),
            $this->unit(bf_size: 3, move: '4"', role: 'Brawler'),
            $this->unit(bf_size: 2, move: '8"', role: 'Striker'),
            $this->unit(bf_size: 2, move: '8"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('heavy-battle-lance', $units);
        self::assertTrue($result->compliant);
    }

    public function testAnvilLanceFailsBelowArmorThreshold(): void
    {
        $units = [
            $this->unit(bf_size: 4, move: '4"', role: 'Juggernaut', extra: ['armor' => 6]),
            $this->unit(bf_size: 3, move: '4"', role: 'Brawler', extra: ['armor' => 5]),
            $this->unit(bf_size: 3, move: '4"', role: 'Brawler', extra: ['armor' => 5]),
            $this->unit(bf_size: 3, move: '4"', role: 'Brawler', extra: ['armor' => 5]),
        ];
        $result = $this->engine->evaluate('anvil-lance', $units);
        self::assertFalse($result->compliant);
        self::assertStringContainsString('armor', implode(' ', $result->unmetRequirements));
    }

    public function testFastAssaultLanceCompliantWithJumpJets(): void
    {
        $units = [
            $this->unit(bf_size: 4, move: '4"j', role: 'Juggernaut', extra: ['dmg_m' => 4]),
            $this->unit(bf_size: 3, move: '5"', role: 'Brawler', extra: ['dmg_m' => 3]),
            $this->unit(bf_size: 3, move: '5"', role: 'Sniper', extra: ['dmg_m' => 3]),
            $this->unit(bf_size: 3, move: '6"', role: 'Brawler', extra: ['dmg_m' => 3]),
        ];
        $result = $this->engine->evaluate('fast-assault-lance', $units);
        self::assertTrue($result->compliant);
    }

    public function testLightStrikerCavalryLanceFailsWhenTooSlow(): void
    {
        $units = [
            $this->unit(bf_size: 2, move: '12"', role: 'Striker'),
            $this->unit(bf_size: 2, move: '12"', role: 'Skirmisher'),
            $this->unit(bf_size: 1, move: '10"', role: 'Striker'),  // < 12" w/o jump
            $this->unit(bf_size: 2, move: '12"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('light-striker-cavalry-lance', $units);
        self::assertFalse($result->compliant);
    }

    public function testHeavyStrikerCavalryLanceCompliant(): void
    {
        $units = [
            $this->unit(bf_size: 3, move: '8"', role: 'Striker', extra: ['dmg_m' => 3]),
            $this->unit(bf_size: 3, move: '8"', role: 'Striker', extra: ['dmg_m' => 2]),
            $this->unit(bf_size: 4, move: '8"', role: 'Brawler', extra: ['dmg_m' => 2]),
            $this->unit(bf_size: 2, move: '8"', role: 'Skirmisher', extra: ['dmg_m' => 2]),
        ];
        $result = $this->engine->evaluate('heavy-striker-cavalry-lance', $units);
        self::assertTrue($result->compliant);
    }

    public function testLightReconLanceFailsWhenNotAllScouts(): void
    {
        $units = [
            $this->unit(bf_size: 1, move: '14"', role: 'Scout'),
            $this->unit(bf_size: 1, move: '14"', role: 'Scout'),
            $this->unit(bf_size: 1, move: '14"', role: 'Striker'),  // not Scout
            $this->unit(bf_size: 1, move: '14"', role: 'Scout'),
        ];
        $result = $this->engine->evaluate('light-recon-lance', $units);
        self::assertFalse($result->compliant);
        self::assertStringContainsString('Scout', implode(' ', $result->unmetRequirements));
    }

    public function testHeavyReconLanceCompliant(): void
    {
        $units = [
            $this->unit(bf_size: 3, move: '10"', role: 'Scout'),
            $this->unit(bf_size: 2, move: '10"', role: 'Scout'),
            $this->unit(bf_size: 2, move: '8"', role: 'Striker'),
            $this->unit(bf_size: 2, move: '8"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('heavy-recon-lance', $units);
        self::assertTrue($result->compliant);
    }

    public function testProbeLanceCompliant(): void
    {
        $units = [
            $this->unit(bf_size: 2, move: '12"', role: 'Striker', extra: ['dmg_m' => 2]),
            $this->unit(bf_size: 2, move: '12"', role: 'Striker', extra: ['dmg_m' => 2]),
            $this->unit(bf_size: 3, move: '10"', role: 'Brawler', extra: ['dmg_m' => 3]),
            $this->unit(bf_size: 1, move: '8"j', role: 'Scout', extra: ['dmg_m' => 2]),
            // Above unit: Move 8 + jump 0 (no j-suffix on number); add jump alt below
        ];
        // Replace 4th to make 75 % fast (3/4 = 75 %): keep Move 8 unit but ensure jump 10
        $units[3] = $this->unit(bf_size: 1, move: '8"', role: 'Scout', extra: ['dmg_m' => 2]);
        $result = $this->engine->evaluate('probe-lance', $units);
        self::assertTrue($result->compliant);
    }

    public function testSweepLanceFailsOnSizeAndShortDamage(): void
    {
        $units = [
            $this->unit(bf_size: 3, move: '10"', role: 'Striker', extra: ['dmg_s' => 1]),  // size 3 → fails
            $this->unit(bf_size: 2, move: '10"', role: 'Striker', extra: ['dmg_s' => 2]),
            $this->unit(bf_size: 2, move: '10"', role: 'Striker', extra: ['dmg_s' => 2]),
            $this->unit(bf_size: 2, move: '10"', role: 'Striker', extra: ['dmg_s' => 1]),  // < 2 → fails
        ];
        $result = $this->engine->evaluate('sweep-lance', $units);
        self::assertFalse($result->compliant);
    }

    // ── PR3: Aerospace squadrons + misc lances ────────────────────────────────

    public function testInterceptorSquadronCompliant(): void
    {
        $units = [
            $this->unit(bf_size: 1, move: '8"', role: 'Interceptor'),
            $this->unit(bf_size: 1, move: '8"', role: 'Interceptor'),
            $this->unit(bf_size: 1, move: '8"', role: 'Dogfighter'),
            $this->unit(bf_size: 2, move: '6"', role: 'Attack'),
        ];
        $result = $this->engine->evaluate('interceptor-squadron', $units);
        self::assertTrue($result->compliant);
    }

    public function testTransportSquadronFailsWithoutTransports(): void
    {
        $units = [
            $this->unit(bf_size: 1, move: '8"', role: 'Interceptor'),
            $this->unit(bf_size: 1, move: '8"', role: 'Interceptor'),
            $this->unit(bf_size: 2, move: '6"', role: 'Attack'),
            $this->unit(bf_size: 2, move: '6"', role: 'Attack'),
        ];
        $result = $this->engine->evaluate('transport-squadron', $units);
        self::assertFalse($result->compliant);
        self::assertStringContainsString('Transport', implode(' ', $result->unmetRequirements));
    }

    public function testFireLanceCompliant(): void
    {
        $units = [
            $this->unit(bf_size: 3, move: '4"', role: 'Missile Boat'),
            $this->unit(bf_size: 3, move: '4"', role: 'Sniper'),
            $this->unit(bf_size: 3, move: '4"', role: 'Missile Boat'),
            $this->unit(bf_size: 2, move: '6"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('fire-lance', $units);
        self::assertTrue($result->compliant);
    }

    public function testHordeLanceFailsOnLargerUnits(): void
    {
        $units = array_fill(0, 6, $this->unit(bf_size: 1, move: '8"', role: 'Striker'));
        $units[] = $this->unit(bf_size: 2, move: '6"', role: 'Striker'); // size 2 → fail
        $result = $this->engine->evaluate('horde-lance', $units);
        self::assertFalse($result->compliant);
    }

    public function testRifleLanceCompliant(): void
    {
        $units = [
            $this->unit(bf_size: 2, move: '8"', role: 'Brawler'),
            $this->unit(bf_size: 3, move: '8"', role: 'Sniper'),
            $this->unit(bf_size: 3, move: '8"', role: 'Brawler'),
            $this->unit(bf_size: 2, move: '8"', role: 'Skirmisher'),
        ];
        $result = $this->engine->evaluate('rifle-lance', $units);
        self::assertTrue($result->compliant);
    }

    public function testHammerLanceFailsWhenAnyUnitSlow(): void
    {
        $units = [
            $this->unit(bf_size: 2, move: '8"', role: 'Striker'),
            $this->unit(bf_size: 2, move: '8"', role: 'Striker'),
            $this->unit(bf_size: 3, move: '6"', role: 'Brawler'),  // < 8"
            $this->unit(bf_size: 2, move: '8"', role: 'Striker'),
        ];
        $result = $this->engine->evaluate('hammer-lance', $units);
        self::assertFalse($result->compliant);
    }

    public function testOrderLanceHasNoEnforceableRules(): void
    {
        // Model-identity matching is not encoded; any 4 units pass count check.
        $units = array_fill(0, 4, $this->unit(bf_size: 2, move: '6"', role: 'Brawler'));
        $result = $this->engine->evaluate('order-lance', $units);
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
