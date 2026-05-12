<?php

declare(strict_types=1);

namespace App\Auth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

final class MattermostProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    private string $baseUrl;

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        $this->baseUrl = rtrim((string) ($options['baseUrl'] ?? ''), '/');
        parent::__construct($options, $collaborators);
    }

    public function getBaseAuthorizationUrl(): string
    {
        return $this->baseUrl . '/oauth/authorize';
    }

    /** @param array<string, mixed> $params */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->baseUrl . '/oauth/access_token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->baseUrl . '/api/v4/users/me';
    }

    /** @return string[] */
    protected function getDefaultScopes(): array
    {
        return [];
    }

    /** @param array<string, mixed>|string $data */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            $msg = is_array($data) ? (string) ($data['message'] ?? $response->getReasonPhrase()) : (string) $data;
            throw new \RuntimeException('Mattermost OAuth error: ' . $msg, $response->getStatusCode());
        }
    }

    /** @param array<string, mixed> $response */
    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new GenericResourceOwner($response, 'id');
    }
}
