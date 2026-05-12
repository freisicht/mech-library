<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * SQL helper for army-level channel gating.
 *
 * An army is visible if its required_mm_channel_id is NULL, or if it equals one
 * of the channel IDs the current user is a member of (per their cached list).
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
        $channels = $this->current->channelIds();
        if ($channels === []) {
            return [
                'sql'    => "({$armyAlias}.required_mm_channel_id IS NULL)",
                'params' => [],
            ];
        }
        $params = [];
        $placeholders = [];
        foreach (array_values($channels) as $i => $ch) {
            $key = ":vis_ch_{$i}";
            $params[$key]   = $ch;
            $placeholders[] = $key;
        }
        $in = implode(', ', $placeholders);
        return [
            'sql'    => "({$armyAlias}.required_mm_channel_id IS NULL OR {$armyAlias}.required_mm_channel_id IN ({$in}))",
            'params' => $params,
        ];
    }

    public function canAccessArmy(?string $requiredChannelId): bool
    {
        if ($requiredChannelId === null || $requiredChannelId === '') {
            return true;
        }
        return $this->current->isInChannel($requiredChannelId);
    }
}
