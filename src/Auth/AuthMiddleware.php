<?php

declare(strict_types=1);

namespace App\Auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Gates the whole site behind a login. Routes whose path begins with
 * /auth/ are public (login/callback/logout). Everything else requires a user.
 *
 * On a missing session: HTML requests redirect to /auth/login (with ?next=…),
 * API requests get 401 JSON.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    private const GROUP_TTL_SECONDS = 300;

    public function __construct(
        private readonly AuthConfig $config,
        private readonly UserRepository $users,
        private readonly CurrentUser $current,
        private readonly GroupSource $groups,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if (str_starts_with($path, '/auth/')) {
            return $handler->handle($request);
        }

        // Test bypass: AUTH_DISABLED=1 short-circuits auth (used by phpunit).
        $bypass = $_ENV['AUTH_DISABLED'] ?? getenv('AUTH_DISABLED');
        if (filter_var($bypass, FILTER_VALIDATE_BOOLEAN)) {
            return $handler->handle($request);
        }

        // No logout here: the session may hold pre-login state (CSRF token, OAuth
        // state) that a parallel request such as /favicon.ico must not wipe.
        $uid = Session::userId();
        if ($uid === null) {
            return $this->unauthorized($request);
        }
        $user = $this->users->find($uid);
        // A session from another provider (after AUTH_PROVIDER was switched) does not count.
        if ($user === null || $user->getProvider() !== $this->config->provider) {
            Session::logout();
            return $this->unauthorized($request);
        }

        $this->refreshGroupsIfStale($user);
        $this->current->set($user, $this->users);
        return $handler->handle($request);
    }

    private function refreshGroupsIfStale(\App\Model\Users $user): void
    {
        if (!$this->users->groupsAreStale($user, self::GROUP_TTL_SECONDS)) {
            return;
        }
        try {
            $groups = $this->groups->fetchGroupIds($user);
            if ($groups !== null) {
                $this->users->setGroups($user, $groups);
            }
        } catch (\Throwable) {
            // keep stale cache on failure, don't lock the user out over a provider hiccup
        }
    }

    private function unauthorized(ServerRequestInterface $request): ResponseInterface
    {
        $accept = $request->getHeaderLine('Accept');
        $isApi  = str_starts_with($request->getUri()->getPath(), '/api/')
            || str_contains($accept, 'application/json');

        $response = new Response();
        if ($isApi) {
            $response->getBody()->write(json_encode(['error' => 'unauthenticated'], JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json; charset=UTF-8')
                ->withStatus(401);
        }

        $next = $request->getUri()->getPath();
        $qs   = $request->getUri()->getQuery();
        if ($qs !== '') {
            $next .= '?' . $qs;
        }
        return $response
            ->withHeader('Location', '/auth/login?next=' . rawurlencode($next))
            ->withStatus(302);
    }
}
