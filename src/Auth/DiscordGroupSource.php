<?php

declare(strict_types=1);

namespace App\Auth;

use App\Model\Users;

/**
 * Groups are Discord servers, limited to DISCORD_ALLOWED_GUILDS. Anyone can
 * create a Discord account, so membership in one of those servers is what
 * grants access at all; other servers of the user are never stored.
 */
final class DiscordGroupSource implements GroupSource
{
    /** @param list<string> $allowedGuildIds */
    public function __construct(
        private readonly DiscordClient $discord,
        private readonly array $allowedGuildIds,
    ) {
    }

    public function fetchGroupIds(string $externalId): ?array
    {
        $token = Session::oauthToken();
        if ($token === null) {
            return null;
        }
        return array_column($this->allowedGuilds($token), 'id');
    }

    public function listGroups(Users $user): array
    {
        $token = Session::oauthToken();
        if ($token === null) {
            return [];
        }
        $groups = array_map(
            static fn (array $g): array => ['id' => $g['id'], 'display_name' => $g['name']],
            $this->allowedGuilds($token),
        );
        usort($groups, static fn (array $a, array $b): int => strcasecmp($a['display_name'], $b['display_name']));
        return $groups;
    }

    public function allowsAccess(array $groupIds): bool
    {
        return array_intersect($groupIds, $this->allowedGuildIds) !== [];
    }

    /** @return list<array{id: string, name: string}> */
    private function allowedGuilds(string $token): array
    {
        return array_values(array_filter(
            $this->discord->fetchGuilds($token),
            fn (array $g): bool => in_array($g['id'], $this->allowedGuildIds, true),
        ));
    }
}
