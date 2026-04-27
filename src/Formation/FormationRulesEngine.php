<?php

declare(strict_types=1);

namespace App\Formation;

/**
 * Evaluates a list of units against the rule descriptors for a formation type.
 *
 * Units are passed as plain associative arrays with at minimum:
 *   bf_size  (int|null)
 *   bf_move  (string|null)  — raw string e.g. "8\"j", "10"
 *   role     (string|null)  — role name from DB join
 *
 * Move is extracted as a numeric inch value by stripping the type suffix.
 */
final class FormationRulesEngine
{
    /** @param list<array<string, mixed>> $units */
    public function evaluate(string $formationTypeSlug, array $units): ComplianceResult
    {
        $typeDef = FormationTypeRegistry::get($formationTypeSlug);

        if ($typeDef === null) {
            return ComplianceResult::fail(["Unknown formation type: {$formationTypeSlug}"]);
        }

        $unmet = [];
        $count = count($units);

        // Unit count bounds
        ['min' => $min, 'max' => $max] = $typeDef['count'];
        if ($count < $min) {
            $unmet[] = "Minimum {$min} unit(s) required (have {$count})";
        } elseif ($count > $max) {
            $unmet[] = "Maximum {$max} unit(s) allowed (have {$count})";
        }

        if ($count === 0) {
            return $unmet === [] ? ComplianceResult::ok() : ComplianceResult::fail($unmet);
        }

        foreach ($typeDef['rules'] as $rule) {
            $violation = $this->applyRule($rule, $units);
            if ($violation !== null) {
                $unmet[] = $violation;
            }
        }

        return $unmet === [] ? ComplianceResult::ok() : ComplianceResult::fail($unmet);
    }

    /**
     * @param array<string, mixed>         $rule
     * @param list<array<string, mixed>>   $units
     */
    private function applyRule(array $rule, array $units): ?string
    {
        return match ($rule['type']) {
            'min_count_by_size' => $this->checkMinCountBySize($rule, $units),
            'max_size_all'      => $this->checkMaxSizeAll($rule, $units),
            'no_size'           => $this->checkNoSize($rule, $units),
            'min_move_all'      => $this->checkMinMoveAll($rule, $units),
            'min_pct_fast'      => $this->checkMinPctFast($rule, $units),
            'min_pct_by_role'   => $this->checkMinPctByRole($rule, $units),
            default             => null,
        };
    }

    /**
     * @param array<string, mixed>       $rule
     * @param list<array<string, mixed>> $units
     */
    private function checkMinCountBySize(array $rule, array $units): ?string
    {
        $qualifying = array_filter($units, fn ($u) => ($u['bf_size'] ?? 0) >= $rule['size']);
        if (count($qualifying) < $rule['count']) {
            return $rule['label'];
        }
        return null;
    }

    /**
     * @param array<string, mixed>       $rule
     * @param list<array<string, mixed>> $units
     */
    private function checkMaxSizeAll(array $rule, array $units): ?string
    {
        foreach ($units as $u) {
            if (($u['bf_size'] ?? 0) > $rule['size']) {
                return $rule['label'];
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed>       $rule
     * @param list<array<string, mixed>> $units
     */
    private function checkNoSize(array $rule, array $units): ?string
    {
        foreach ($units as $u) {
            if (($u['bf_size'] ?? 0) === $rule['size']) {
                return $rule['label'];
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed>       $rule
     * @param list<array<string, mixed>> $units
     */
    private function checkMinMoveAll(array $rule, array $units): ?string
    {
        foreach ($units as $u) {
            if ($this->parseMoveInches($u['bf_move'] ?? null) < $rule['move']) {
                return $rule['label'];
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed>       $rule
     * @param list<array<string, mixed>> $units
     */
    private function checkMinPctFast(array $rule, array $units): ?string
    {
        $count = count($units);
        $qualifying = 0;
        foreach ($units as $u) {
            $mv   = $this->parseMoveInches($u['bf_move'] ?? null);
            $jump = $this->parseJumpInches($u['bf_move'] ?? null);
            if ($mv >= $rule['move'] || ($rule['jump'] > 0 && $jump >= $rule['jump'])) {
                $qualifying++;
            }
        }
        $pct = ($qualifying / $count) * 100;
        if ($pct < $rule['pct']) {
            return $rule['label'];
        }
        return null;
    }

    /**
     * @param array<string, mixed>       $rule
     * @param list<array<string, mixed>> $units
     */
    private function checkMinPctByRole(array $rule, array $units): ?string
    {
        $count      = count($units);
        $qualifying = 0;
        foreach ($units as $u) {
            if (in_array($u['role'] ?? '', $rule['roles'], true)) {
                $qualifying++;
            }
        }
        $pct = ($qualifying / $count) * 100;
        if ($pct < $rule['pct']) {
            return $rule['label'];
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // Move parsing
    // -------------------------------------------------------------------------

    /**
     * Extract the primary ground/VTOL move value from the raw bf_move string.
     * Examples: "6", "8\"j", "10/8j", "4v" → 6, 8, 10, 4
     */
    private function parseMoveInches(?string $raw): int
    {
        if ($raw === null || $raw === '') {
            return 0;
        }
        // Take the part before "/" (jump speed listed after slash in some formats)
        $primary = explode('/', $raw)[0];
        // Strip non-numeric suffix
        $numeric = preg_replace('/[^0-9]/', '', $primary);
        return $numeric !== '' ? (int) $numeric : 0;
    }

    /**
     * Extract jump move value. bf_move formats:
     *   "8j"      → jump 8
     *   "6/4j"    → jump 4
     *   "10"      → jump 0 (wheeled/tracked/no jump)
     */
    private function parseJumpInches(?string $raw): int
    {
        if ($raw === null || $raw === '') {
            return 0;
        }
        // Jump suffix is a lowercase 'j' after digits
        if (preg_match('/(\d+)["\']?j/i', $raw, $m)) {
            return (int) $m[1];
        }
        return 0;
    }
}
