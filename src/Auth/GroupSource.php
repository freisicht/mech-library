<?php

declare(strict_types=1);

namespace App\Auth;

use App\Model\Users;

/**
 * Where a user's group memberships come from. Armies can be restricted to one
 * group; what a group is depends on the provider (Mattermost channel, Discord
 * server, local group).
 */
interface GroupSource
{
    /** @return list<string>|null null when the provider cannot refresh right now */
    public function fetchGroupIds(string $externalId): ?array;

    /** @return list<array{id: string, display_name: string, type?: string}> */
    public function listGroups(Users $user): array;

    /**
     * Whether these memberships are enough to use the site at all.
     *
     * @param list<string> $groupIds
     */
    public function allowsAccess(array $groupIds): bool;
}
