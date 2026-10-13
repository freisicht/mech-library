<?php

declare(strict_types=1);

namespace App\Auth;

use App\Model\Users;

/**
 * Container-shared holder for the request's authenticated user.
 * AuthMiddleware sets it after session lookup; repositories/controllers read it.
 */
final class CurrentUser
{
    private ?Users $user = null;
    /** @var list<string>|null */
    private ?array $groups = null;

    public function set(Users $user, UserRepository $users): void
    {
        $this->user   = $user;
        $this->groups = $users->groups($user);
    }

    public function user(): ?Users
    {
        return $this->user;
    }

    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    /** @return list<string> */
    public function groupIds(): array
    {
        return $this->groups ?? [];
    }

    public function isInGroup(string $groupId): bool
    {
        return in_array($groupId, $this->groupIds(), true);
    }
}
