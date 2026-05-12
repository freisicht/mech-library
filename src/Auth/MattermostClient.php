<?php

declare(strict_types=1);

namespace App\Auth;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;

final class MattermostClient
{
    public function __construct(
        private readonly ClientInterface $http,
        private readonly string $baseUrl,
    ) {
    }

    /** @return list<string> Mattermost channel IDs the user belongs to (across all teams) */
    public function fetchChannelIds(string $accessToken, string $mmUserId): array
    {
        return array_values(array_unique(array_column(
            $this->fetchChannels($accessToken, $mmUserId),
            'id',
        )));
    }

    /**
     * Channels the user belongs to, with display info, across all teams.
     *
     * @return list<array{id: string, display_name: string, name: string, team_id: string}>
     */
    public function fetchChannels(string $accessToken, string $mmUserId): array
    {
        $teams = $this->get($accessToken, "/api/v4/users/{$mmUserId}/teams");
        $out   = [];
        $seen  = [];
        foreach ((array) $teams as $team) {
            if (!is_array($team) || !isset($team['id'])) {
                continue;
            }
            $channels = $this->get(
                $accessToken,
                "/api/v4/users/{$mmUserId}/teams/{$team['id']}/channels",
            );
            foreach ((array) $channels as $ch) {
                if (!is_array($ch) || !isset($ch['id']) || !is_string($ch['id'])) {
                    continue;
                }
                if (isset($seen[$ch['id']])) {
                    continue;
                }
                $seen[$ch['id']] = true;
                $out[] = [
                    'id'           => $ch['id'],
                    'display_name' => (string) ($ch['display_name'] ?? $ch['name'] ?? $ch['id']),
                    'name'         => (string) ($ch['name'] ?? ''),
                    'team_id'      => (string) $team['id'],
                ];
            }
        }
        return $out;
    }

    /** @return array<int|string, mixed> */
    private function get(string $accessToken, string $path): array
    {
        $req = new Request('GET', rtrim($this->baseUrl, '/') . $path, [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept'        => 'application/json',
        ]);
        $res  = $this->http->send($req, ['http_errors' => true]);
        $body = (string) $res->getBody();
        /** @var array<int|string, mixed>|null $decoded */
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }
}
