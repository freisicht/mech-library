<?php

declare(strict_types=1);

namespace App\Auth;

use App\Model\Users;
use App\Model\UsersQuery;

final class UserRepository
{
    public function upsertFromMattermost(string $mmUserId, string $username, ?string $email): Users
    {
        $user = UsersQuery::create()->filterByMmUserId($mmUserId)->findOne();
        if ($user === null) {
            $user = new Users();
            $user->setMmUserId($mmUserId);
        }
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setLastLoginAt(new \DateTime());
        $user->save();
        return $user;
    }

    public function find(int $id): ?Users
    {
        return UsersQuery::create()->findPk($id);
    }

    /** @param list<string> $channelIds */
    public function setChannels(Users $user, array $channelIds): void
    {
        $user->setChannelsJson(json_encode(array_values($channelIds), JSON_THROW_ON_ERROR));
        $user->setChannelsRefreshedAt(new \DateTime());
        $user->save();
    }

    /** @return list<string> */
    public function channels(Users $user): array
    {
        $json = $user->getChannelsJson();
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_values(array_filter(array_map('strval', $decoded), static fn ($s) => $s !== ''));
    }

    public function channelsAreStale(Users $user, int $ttlSeconds): bool
    {
        $ts = $user->getChannelsRefreshedAt();
        if ($ts === null) {
            return true;
        }
        return ($ts->getTimestamp() + $ttlSeconds) < time();
    }
}
