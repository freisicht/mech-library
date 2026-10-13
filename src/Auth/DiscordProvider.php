<?php

declare(strict_types=1);

namespace App\Auth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

final class DiscordProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    public const API_BASE = 'https://discord.com/api/v10';

    public function getBaseAuthorizationUrl(): string
    {
        return 'https://discord.com/oauth2/authorize';
    }

    /** @param array<string, mixed> $params */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return self::API_BASE . '/oauth2/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return self::API_BASE . '/users/@me';
    }

    /** @return string[] */
    protected function getDefaultScopes(): array
    {
        return ['identify', 'guilds'];
    }

    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    /**
     * prompt=none skips Discord's consent screen for users who already
     * authorized the app.
     *
     * @param  array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function getAuthorizationParameters(array $options): array
    {
        $options += ['prompt' => 'none'];
        $params = parent::getAuthorizationParameters($options);
        unset($params['approval_prompt']);
        return $params;
    }

    /** @param array<string, mixed>|string $data */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            $msg = is_array($data)
                ? (string) ($data['error_description'] ?? $data['message'] ?? $response->getReasonPhrase())
                : (string) $data;
            throw new \RuntimeException('Discord OAuth error: ' . $msg, $response->getStatusCode());
        }
    }

    /** @param array<string, mixed> $response */
    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new GenericResourceOwner($response, 'id');
    }
}
