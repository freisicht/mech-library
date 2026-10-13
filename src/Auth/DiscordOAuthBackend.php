<?php

declare(strict_types=1);

namespace App\Auth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;

final class DiscordOAuthBackend implements OAuthBackend
{
    public function __construct(private readonly DiscordProvider $oauth)
    {
    }

    public function providerName(): string
    {
        return AuthConfig::DISCORD;
    }

    public function oauth(): AbstractProvider
    {
        return $this->oauth;
    }

    public function identity(AccessToken $token): array
    {
        $data     = $this->oauth->getResourceOwner($token)->toArray();
        $id       = (string) ($data['id'] ?? '');
        $username = (string) ($data['global_name'] ?? '');
        if ($username === '') {
            $username = (string) ($data['username'] ?? $id);
        }
        return ['id' => $id, 'username' => $username, 'email' => null];
    }
}
