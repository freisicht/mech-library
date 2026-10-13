<?php

declare(strict_types=1);

namespace App\Web\Controller;

use App\Auth\AuthConfig;
use App\Auth\MattermostClient;
use App\Auth\MattermostGroupSource;
use App\Auth\MattermostProvider;
use App\Auth\Session;
use App\Auth\UserRepository;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class MattermostAuthController
{
    public function __construct(
        private readonly MattermostProvider $oauth,
        private readonly MattermostClient $mm,
        private readonly UserRepository $users,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function start(Request $request, Response $response): Response
    {
        $next = (string) ($request->getQueryParams()['next'] ?? '/');
        $url  = $this->oauth->getAuthorizationUrl();
        Session::set('oauth_state', $this->oauth->getState());
        Session::set('oauth_next', $next);
        return $response->withHeader('Location', $url)->withStatus(302);
    }

    public function callback(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $code   = (string) ($params['code'] ?? '');
        $state  = (string) ($params['state'] ?? '');
        $stored = (string) (Session::pull('oauth_state') ?? '');
        $next   = (string) (Session::pull('oauth_next') ?? '/');

        if ($code === '' || $state === '' || !hash_equals($stored, $state)) {
            return $this->fail($response, 'invalid OAuth state');
        }

        try {
            $token = $this->oauth->getAccessToken('authorization_code', ['code' => $code]);
            if (!$token instanceof AccessToken) {
                return $this->fail($response, 'unexpected token type');
            }
            /** @var GenericResourceOwner $owner */
            $owner   = $this->oauth->getResourceOwner($token);
            $data    = $owner->toArray();
            $mmId    = (string) ($data['id'] ?? '');
            $username = (string) ($data['username'] ?? $mmId);
            $email    = isset($data['email']) ? (string) $data['email'] : null;
            if ($mmId === '') {
                return $this->fail($response, 'mattermost user has no id');
            }

            $user = $this->users->upsertExternal(AuthConfig::MATTERMOST, $mmId, $username, $email);

            $channels = $this->mm->fetchChannelIds($token->getToken(), $mmId);
            $this->users->setGroups($user, $channels);

            Session::login($user->getId());
            Session::set(MattermostGroupSource::TOKEN_SESSION_KEY, $token->getToken());
        } catch (\Throwable $e) {
            $this->logger->warning('OAuth callback failed', ['err' => $e->getMessage()]);
            return $this->fail($response, 'login failed');
        }

        return $response->withHeader('Location', AuthController::safeNext($next))->withStatus(302);
    }

    private function fail(Response $response, string $msg): Response
    {
        $response->getBody()->write('<h1>Login error</h1><p>' . htmlspecialchars($msg) . '</p><p><a href="/auth/login">Try again</a></p>');
        return $response->withStatus(400)->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}
