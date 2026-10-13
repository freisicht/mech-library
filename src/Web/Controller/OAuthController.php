<?php

declare(strict_types=1);

namespace App\Web\Controller;

use App\Auth\GroupSource;
use App\Auth\OAuthBackend;
use App\Auth\Session;
use App\Auth\UserRepository;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/** OAuth2 login for Mattermost and Discord; the provider-specific parts live in OAuthBackend. */
final class OAuthController
{
    public function __construct(
        private readonly OAuthBackend $backend,
        private readonly GroupSource $groups,
        private readonly UserRepository $users,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function start(Request $request, Response $response): Response
    {
        $next  = (string) ($request->getQueryParams()['next'] ?? '/');
        $oauth = $this->backend->oauth();
        $url   = $oauth->getAuthorizationUrl();
        Session::set('oauth_state', $oauth->getState());
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

        if (isset($params['error'])) {
            return $this->fail($response, 'login was cancelled or refused by the provider');
        }
        if ($code === '' || $state === '' || !hash_equals($stored, $state)) {
            return $this->fail($response, 'invalid OAuth state');
        }

        try {
            $token = $this->backend->oauth()->getAccessToken('authorization_code', ['code' => $code]);
            if (!$token instanceof AccessToken) {
                return $this->fail($response, 'unexpected token type');
            }
            $identity = $this->backend->identity($token);
            if ($identity['id'] === '') {
                return $this->fail($response, 'account has no id');
            }

            Session::setOauthToken($token->getToken());
            $groupIds = $this->groups->fetchGroupIds($identity['id']) ?? [];
            if (!$this->groups->allowsAccess($groupIds)) {
                Session::logout();
                return $this->fail($response, 'your account is not a member of a server this site is open to', 403);
            }

            $user = $this->users->upsertExternal(
                $this->backend->providerName(),
                $identity['id'],
                $identity['username'],
                $identity['email'],
            );
            $this->users->setGroups($user, $groupIds);
            Session::login($user->getId());
        } catch (\Throwable $e) {
            $this->logger->warning('OAuth callback failed', ['err' => $e->getMessage()]);
            return $this->fail($response, 'login failed');
        }

        return $response->withHeader('Location', AuthController::safeNext($next))->withStatus(302);
    }

    private function fail(Response $response, string $msg, int $status = 400): Response
    {
        $response->getBody()->write('<h1>Login error</h1><p>' . htmlspecialchars($msg) . '</p><p><a href="/auth/login">Try again</a></p>');
        return $response->withStatus($status)->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}
