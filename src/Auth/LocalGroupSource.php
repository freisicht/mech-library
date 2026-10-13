<?php

declare(strict_types=1);

namespace App\Auth;

use App\Model\Users;

/**
 * Groups of local accounts live in the users table and are managed with
 * `bin/console user:groups`, so there is nothing to refresh.
 */
final class LocalGroupSource implements GroupSource
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function fetchGroupIds(string $externalId): ?array
    {
        return null;
    }

    public function listGroups(Users $user): array
    {
        return array_map(
            static fn (string $g): array => ['id' => $g, 'display_name' => $g],
            $this->users->groups($user),
        );
    }

    public function allowsAccess(array $groupIds): bool
    {
        return true;
    }
}
