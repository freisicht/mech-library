<?php

declare(strict_types=1);

namespace App\Auth;

use App\Model\Users;

final class MattermostGroupSource implements GroupSource
{
    public function __construct(private readonly MattermostClient $mm)
    {
    }

    public function fetchGroupIds(string $externalId): ?array
    {
        $token = Session::oauthToken();
        if ($token === null) {
            return null;
        }
        return $this->mm->fetchChannelIds($token, $externalId);
    }

    public function listGroups(Users $user): array
    {
        $token = Session::oauthToken();
        if ($token === null) {
            return [];
        }
        return $this->mm->fetchChannels($token, $user->getExternalId());
    }

    public function allowsAccess(array $groupIds): bool
    {
        return true;
    }
}
