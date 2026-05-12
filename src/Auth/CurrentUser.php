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
    private ?array $channels = null;

    public function set(Users $user, UserRepository $users): void
    {
        $this->user     = $user;
        $this->channels = $users->channels($user);
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
    public function channelIds(): array
    {
        return $this->channels ?? [];
    }

    public function isInChannel(string $channelId): bool
    {
        return in_array($channelId, $this->channelIds(), true);
    }
}
