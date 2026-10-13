<?php

declare(strict_types=1);

namespace App\Auth;

use App\Model\Users;
use App\Model\UsersQuery;

final class UserRepository
{
    public const MAX_GROUP_LENGTH = 64;

    public function upsertExternal(string $provider, string $externalId, string $username, ?string $email): Users
    {
        $user = UsersQuery::create()
            ->filterByProvider($provider)
            ->filterByExternalId($externalId)
            ->findOne();
        if ($user === null) {
            $user = new Users();
            $user->setProvider($provider);
            $user->setExternalId($externalId);
        }
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setLastLoginAt(new \DateTime());
        $user->save();
        return $user;
    }

    /** @param list<string> $groups */
    public function createLocal(string $username, string $password, array $groups = [], ?string $email = null): Users
    {
        $username = trim($username);
        if ($username === '') {
            throw new \InvalidArgumentException('username must not be empty');
        }
        if ($this->findLocal($username) !== null) {
            throw new \InvalidArgumentException("user '{$username}' already exists");
        }
        $user = new Users();
        $user->setProvider(AuthConfig::LOCAL);
        $user->setExternalId(self::localId($username));
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPasswordHash(password_hash($password, PASSWORD_DEFAULT));
        $this->setGroups($user, $groups);
        return $user;
    }

    public function findLocal(string $username): ?Users
    {
        return UsersQuery::create()
            ->filterByProvider(AuthConfig::LOCAL)
            ->filterByExternalId(self::localId($username))
            ->findOne();
    }

    /** @return list<Users> */
    public function findAll(): array
    {
        $users = [];
        foreach (UsersQuery::create()->orderByProvider()->orderByUsername()->find() as $user) {
            $users[] = $user;
        }
        return $users;
    }

    public function find(int $id): ?Users
    {
        return UsersQuery::create()->findPk($id);
    }

    public function setPassword(Users $user, string $password): void
    {
        $user->setPasswordHash(password_hash($password, PASSWORD_DEFAULT));
        $user->save();
    }

    public function recordLogin(Users $user): void
    {
        $user->setLastLoginAt(new \DateTime());
        $user->save();
    }

    /** @param list<string> $groups */
    public function setGroups(Users $user, array $groups): void
    {
        $user->setGroupsJson(json_encode(self::normalizeGroups($groups), JSON_THROW_ON_ERROR));
        $user->setGroupsRefreshedAt(new \DateTime());
        $user->save();
    }

    /** @return list<string> */
    public function groups(Users $user): array
    {
        $json = $user->getGroupsJson();
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        return self::normalizeGroups(array_map('strval', $decoded));
    }

    public function groupsAreStale(Users $user, int $ttlSeconds): bool
    {
        $ts = $user->getGroupsRefreshedAt();
        if ($ts === null) {
            return true;
        }
        return ($ts->getTimestamp() + $ttlSeconds) < time();
    }

    /**
     * @param  array<int|string, string> $groups
     * @return list<string>
     */
    public static function normalizeGroups(array $groups): array
    {
        $out = [];
        foreach ($groups as $g) {
            $g = trim($g);
            if ($g === '') {
                continue;
            }
            if (mb_strlen($g) > self::MAX_GROUP_LENGTH) {
                throw new \InvalidArgumentException("group '{$g}' is longer than " . self::MAX_GROUP_LENGTH . ' characters');
            }
            $out[$g] = true;
        }
        return array_keys($out);
    }

    private static function localId(string $username): string
    {
        return mb_strtolower(trim($username));
    }
}
