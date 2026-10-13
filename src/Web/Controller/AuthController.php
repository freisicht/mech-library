<?php

declare(strict_types=1);

namespace App\Web\Controller;

use App\Auth\AuthConfig;
use App\Auth\Session;
use App\Auth\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

final class AuthController
{
    private const CSRF_SESSION_KEY = 'login_csrf';

    // Hash of a random throwaway password. Unknown usernames are verified against it,
    // so response time does not reveal which names exist.
    private const DUMMY_HASH = '$2y$12$t8r.01Wri9RiYMo1FJzdjOG.AAg/c7Y6Tl/dMpUGKiM4SK45L9RUC';

    public function __construct(
        private readonly AuthConfig $config,
        private readonly UserRepository $users,
        private readonly Environment $twig,
    ) {
    }

    public function showLogin(Request $request, Response $response): Response
    {
        $next = (string) ($request->getQueryParams()['next'] ?? '/');
        return $this->renderLogin($response, $next);
    }

    public function login(Request $request, Response $response): Response
    {
        $body     = (array) $request->getParsedBody();
        $username = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $next     = (string) ($body['next'] ?? '/');
        $csrf     = (string) ($body['csrf'] ?? '');
        $stored   = (string) (Session::pull(self::CSRF_SESSION_KEY) ?? '');

        if ($stored === '' || !hash_equals($stored, $csrf)) {
            return $this->renderLogin($response, $next, 'Your session expired, please try again.', $username)
                ->withStatus(400);
        }

        $user = $username === '' ? null : $this->users->findLocal($username);
        $hash = $user?->getPasswordHash() ?? self::DUMMY_HASH;
        if (!password_verify($password, $hash) || $user === null) {
            sleep(1);
            return $this->renderLogin($response, $next, 'Unknown user or wrong password.', $username)
                ->withStatus(401);
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->users->setPassword($user, $password);
        }
        $this->users->recordLogin($user);
        Session::login($user->getId());

        return $response->withHeader('Location', self::safeNext($next))->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        Session::logout();
        return $response->withHeader('Location', '/auth/login')->withStatus(302);
    }

    public static function safeNext(string $next): string
    {
        if ($next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//')) {
            return '/';
        }
        return $next;
    }

    private function renderLogin(Response $response, string $next, ?string $error = null, string $username = ''): Response
    {
        $csrf = '';
        if ($this->config->isLocal()) {
            $csrf = bin2hex(random_bytes(16));
            Session::set(self::CSRF_SESSION_KEY, $csrf);
        }
        $html = $this->twig->render('auth/login.html.twig', [
            'provider' => $this->config->provider,
            'next'     => $next,
            'mm_host'  => $this->config->mattermostHost(),
            'csrf'     => $csrf,
            'error'    => $error,
            'username' => $username,
        ]);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}
