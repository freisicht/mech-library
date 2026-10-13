<?php

declare(strict_types=1);

namespace App\Auth;

use App\Model\Users;

final class MattermostGroupSource implements GroupSource
{
    public const TOKEN_SESSION_KEY = 'mm_access_token';

    public function __construct(private readonly MattermostClient $mm)
    {
    }

    public function fetchGroupIds(Users $user): ?array
    {
        $token = $this->token();
        if ($token === null) {
            return null;
        }
        return $this->mm->fetchChannelIds($token, $user->getExternalId());
    }

    public function listGroups(Users $user): array
    {
        $token = $this->token();
        if ($token === null) {
            return [];
        }
        return $this->mm->fetchChannels($token, $user->getExternalId());
    }

    private function token(): ?string
    {
        $token = Session::get(self::TOKEN_SESSION_KEY);
        return is_string($token) && $token !== '' ? $token : null;
    }
}
