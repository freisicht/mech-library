<?php

declare(strict_types=1);

namespace App\Auth;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;

final class DiscordClient
{
    public function __construct(
        private readonly ClientInterface $http,
        private readonly string $baseUrl = DiscordProvider::API_BASE,
    ) {
    }

    /**
     * Servers (guilds) the user is a member of. Discord caps membership at 200,
     * which is also the page size, so one request is enough.
     *
     * @return list<array{id: string, name: string}>
     */
    public function fetchGuilds(string $accessToken): array
    {
        $req = new Request('GET', rtrim($this->baseUrl, '/') . '/users/@me/guilds?limit=200', [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept'        => 'application/json',
        ]);
        $res     = $this->http->send($req, ['http_errors' => true]);
        $decoded = json_decode((string) $res->getBody(), true);

        $out = [];
        foreach (is_array($decoded) ? $decoded : [] as $guild) {
            if (!is_array($guild) || !isset($guild['id'])) {
                continue;
            }
            $id    = (string) $guild['id'];
            $out[] = ['id' => $id, 'name' => (string) ($guild['name'] ?? $id)];
        }
        return $out;
    }
}
