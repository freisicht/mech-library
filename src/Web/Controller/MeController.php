<?php

declare(strict_types=1);

namespace App\Web\Controller;

use App\Auth\CurrentUser;
use App\Auth\GroupSource;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class MeController
{
    public function __construct(
        private readonly CurrentUser $current,
        private readonly GroupSource $groups,
    ) {
    }

    public function me(Request $request, Response $response): Response
    {
        $user = $this->current->user();
        if ($user === null) {
            return $this->json($response, ['error' => 'unauthenticated'], 401);
        }
        return $this->json($response, [
            'id'          => $user->getId(),
            'username'    => $user->getUsername(),
            'email'       => $user->getEmail(),
            'provider'    => $user->getProvider(),
            'external_id' => $user->getExternalId(),
            'group_ids'   => $this->current->groupIds(),
        ]);
    }

    /**
     * Groups (id + display_name) for the current user. Used by army admin UI
     * to populate the "required group" dropdown.
     */
    public function groups(Request $request, Response $response): Response
    {
        $user = $this->current->user();
        if ($user === null) {
            return $this->json($response, ['error' => 'unauthenticated'], 401);
        }
        try {
            $groups = $this->groups->listGroups($user);
        } catch (\Throwable) {
            return $this->json($response, ['error' => 'group lookup failed'], 502);
        }
        return $this->json($response, $groups);
    }

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withStatus($status);
    }
}
