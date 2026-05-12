<?php

declare(strict_types=1);

namespace App\Auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Gates the whole site behind a Mattermost login. Routes whose path begins with
 * /auth/ are public (login/callback/logout). Everything else requires a user.
 *
 * On a missing session: HTML requests redirect to /auth/login (with ?next=…),
 * API requests get 401 JSON.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    private const CHANNEL_TTL_SECONDS = 300;

    public function __construct(
        private readonly UserRepository $users,
        private readonly CurrentUser $current,
        private readonly MattermostClient $mm,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if (str_starts_with($path, '/auth/')) {
            return $handler->handle($request);
        }

        // Test bypass — set MM_AUTH_DISABLED=1 to short-circuit auth (used by phpunit).
        $bypass = $_ENV['MM_AUTH_DISABLED'] ?? getenv('MM_AUTH_DISABLED');
        if (filter_var($bypass, FILTER_VALIDATE_BOOLEAN)) {
            return $handler->handle($request);
        }

        $uid  = Session::userId();
        $user = $uid === null ? null : $this->users->find($uid);
        if ($user === null) {
            Session::logout();
            return $this->unauthorized($request);
        }

        $this->refreshChannelsIfStale($user);
        $this->current->set($user, $this->users);
        return $handler->handle($request);
    }

    private function refreshChannelsIfStale(\App\Model\Users $user): void
    {
        if (!$this->users->channelsAreStale($user, self::CHANNEL_TTL_SECONDS)) {
            return;
        }
        $token = Session::get('mm_access_token');
        if (!is_string($token) || $token === '') {
            return;
        }
        try {
            $channels = $this->mm->fetchChannelIds($token, $user->getMmUserId());
            $this->users->setChannels($user, $channels);
        } catch (\Throwable) {
            // keep stale cache on failure — don't lock the user out over an MM hiccup
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
