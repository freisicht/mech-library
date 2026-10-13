<?php

declare(strict_types=1);

namespace App\Auth;

use App\Model\Users;

/**
 * Where a user's group memberships come from. Armies can be restricted to one
 * group; what a group is depends on the provider (Mattermost channel, local group).
 */
interface GroupSource
{
    /** @return list<string>|null null when the provider cannot refresh right now */
    public function fetchGroupIds(Users $user): ?array;

    /** @return list<array{id: string, display_name: string, type?: string}> */
    public function listGroups(Users $user): array;
}
