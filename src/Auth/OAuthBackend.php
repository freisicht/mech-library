<?php

declare(strict_types=1);

namespace App\Auth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;

/** Provider-specific half of the OAuth login: which server, and who the token belongs to. */
interface OAuthBackend
{
    public function providerName(): string;

    public function oauth(): AbstractProvider;

    /** @return array{id: string, username: string, email: ?string} */
    public function identity(AccessToken $token): array;
}
