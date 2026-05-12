<?php

declare(strict_types=1);

namespace App\Web\Controller;

use App\Auth\MattermostClient;
use App\Auth\MattermostProvider;
use App\Auth\Session;
use App\Auth\UserRepository;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

final class AuthController
{
    public function __construct(
        private readonly MattermostProvider $oauth,
        private readonly MattermostClient $mm,
        private readonly UserRepository $users,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function showLogin(Request $request, Response $response): Response
    {
        $next = (string) ($request->getQueryParams()['next'] ?? '/');
        $html = $this->twig->render('auth/login.html.twig', ['next' => $next]);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
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

            $user = $this->users->upsertFromMattermost($mmId, $username, $email);

            $channels = $this->mm->fetchChannelIds($token->getToken(), $mmId);
            $this->users->setChannels($user, $channels);

            Session::login($user->getId());
            Session::set('mm_access_token', $token->getToken());
        } catch (\Throwable $e) {
            $this->logger->warning('OAuth callback failed', ['err' => $e->getMessage()]);
            return $this->fail($response, 'login failed');
        }

        return $response->withHeader('Location', $this->safeNext($next))->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        Session::logout();
        return $response->withHeader('Location', '/auth/login')->withStatus(302);
    }

    private function fail(Response $response, string $msg): Response
    {
        $response->getBody()->write('<h1>Login error</h1><p>' . htmlspecialchars($msg) . '</p><p><a href="/auth/login">Try again</a></p>');
        return $response->withStatus(400)->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    private function safeNext(string $next): string
    {
        if ($next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//')) {
            return '/';
        }
        return $next;
    }
}
