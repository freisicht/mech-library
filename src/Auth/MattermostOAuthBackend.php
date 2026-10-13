<?php

declare(strict_types=1);

namespace App\Auth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;

final class MattermostOAuthBackend implements OAuthBackend
{
    public function __construct(private readonly MattermostProvider $oauth)
    {
    }

    public function providerName(): string
    {
        return AuthConfig::MATTERMOST;
    }

    public function oauth(): AbstractProvider
    {
        return $this->oauth;
    }

    public function identity(AccessToken $token): array
    {
        $data = $this->oauth->getResourceOwner($token)->toArray();
        $id   = (string) ($data['id'] ?? '');
        return [
            'id'       => $id,
            'username' => (string) ($data['username'] ?? $id),
            'email'    => isset($data['email']) ? (string) $data['email'] : null,
        ];
    }
}
