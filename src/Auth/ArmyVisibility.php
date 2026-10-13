<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * SQL helper for army-level group gating.
 *
 * An army is visible if its required_group_id is NULL, or if it equals one
 * of the groups the current user belongs to (per their cached list).
 *
 * Returned $sql fragment expects the armies table aliased as {alias} and is
 * always wrapped in parentheses so it can be ANDed into any WHERE clause.
 */
final class ArmyVisibility
{
    public function __construct(private readonly CurrentUser $current)
    {
    }

    /**
     * @return array{sql: string, params: array<string, string>}
     */
    public function clause(string $armyAlias = 'a'): array
    {
        $groups = $this->current->groupIds();
        if ($groups === []) {
            return [
                'sql'    => "({$armyAlias}.required_group_id IS NULL)",
                'params' => [],
            ];
        }
        $params = [];
        $placeholders = [];
        foreach (array_values($groups) as $i => $g) {
            $key = ":vis_grp_{$i}";
            $params[$key]   = $g;
            $placeholders[] = $key;
        }
        $in = implode(', ', $placeholders);
        return [
            'sql'    => "({$armyAlias}.required_group_id IS NULL OR {$armyAlias}.required_group_id IN ({$in}))",
            'params' => $params,
        ];
    }

    public function canAccessArmy(?string $requiredGroupId): bool
    {
        if ($requiredGroupId === null || $requiredGroupId === '') {
            return true;
        }
        return $this->current->isInGroup($requiredGroupId);
    }
}
