<?php

declare(strict_types=1);

namespace App\Tests\Auth;

use App\Auth\AuthConfig;
use App\Auth\DiscordClient;
use App\Auth\DiscordGroupSource;
use App\Auth\DiscordProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class DiscordTest extends TestCase
{
    public function testAuthorizationUrlAsksForIdentityAndGuilds(): void
    {
        $provider = new DiscordProvider(['clientId' => 'cid', 'redirectUri' => 'https://lib.example/auth/callback']);
        $url      = $provider->getAuthorizationUrl();
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertStringStartsWith('https://discord.com/oauth2/authorize?', $url);
        self::assertSame('identify guilds', $query['scope']);
        self::assertSame('none', $query['prompt']);
        self::assertSame('code', $query['response_type']);
        self::assertArrayNotHasKey('approval_prompt', $query);
        self::assertSame($provider->getState(), $query['state']);
    }

    public function testFetchGuildsSendsBearerTokenAndParsesList(): void
    {
        $history = [];
        $stack   = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode([
                ['id' => '111', 'name' => 'Tuesday Night', 'owner' => false],
                ['id' => '222', 'name' => 'Other'],
                ['bogus' => true],
            ], JSON_THROW_ON_ERROR)),
        ]));
        $stack->push(Middleware::history($history));

        $guilds = (new DiscordClient(new Client(['handler' => $stack])))->fetchGuilds('tok');

        self::assertSame([['id' => '111', 'name' => 'Tuesday Night'], ['id' => '222', 'name' => 'Other']], $guilds);
        $request = $history[0]['request'];
        self::assertSame('Bearer tok', $request->getHeaderLine('Authorization'));
        self::assertSame('/api/v10/users/@me/guilds', $request->getUri()->getPath());
    }

    public function testAccessRequiresAnAllowedGuild(): void
    {
        $source = new DiscordGroupSource(new DiscordClient(new Client()), ['111', '333']);
        self::assertTrue($source->allowsAccess(['222', '333']));
        self::assertFalse($source->allowsAccess(['222']));
        self::assertFalse($source->allowsAccess([]));
    }

    public function testConfigAcceptsDiscord(): void
    {
        $config = AuthConfig::resolve('discord', '');
        self::assertTrue($config->isDiscord());
        self::assertTrue($config->isOAuth());
    }
}
