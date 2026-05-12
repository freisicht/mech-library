<?php

declare(strict_types=1);

namespace App\Web\Controller;

use App\Auth\CurrentUser;
use App\Auth\MattermostClient;
use App\Auth\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class MeController
{
    public function __construct(
        private readonly CurrentUser $current,
        private readonly MattermostClient $mm,
    ) {
    }

    public function me(Request $request, Response $response): Response
    {
        $user = $this->current->user();
        if ($user === null) {
            return $this->json($response, ['error' => 'unauthenticated'], 401);
        }
        return $this->json($response, [
            'id'           => $user->getId(),
            'username'     => $user->getUsername(),
            'email'        => $user->getEmail(),
            'mm_user_id'   => $user->getMmUserId(),
            'channel_ids'  => $this->current->channelIds(),
        ]);
    }

    /**
     * Channels (id + display_name) for the current user. Used by army admin UI
     * to populate the "required channel" dropdown.
     */
    public function channels(Request $request, Response $response): Response
    {
        $user = $this->current->user();
        if ($user === null) {
            return $this->json($response, ['error' => 'unauthenticated'], 401);
        }
        $token = Session::get('mm_access_token');
        if (!is_string($token) || $token === '') {
            return $this->json($response, ['error' => 'no access token in session'], 409);
        }
        try {
            $channels = $this->mm->fetchChannels($token, $user->getMmUserId());
        } catch (\Throwable $e) {
            return $this->json($response, ['error' => 'mattermost lookup failed'], 502);
        }
        return $this->json($response, $channels);
    }

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withStatus($status);
    }
}
