<?php

declare(strict_types=1);

namespace App\Tests\Auth;

use App\Auth\AuthConfig;
use App\Auth\AuthMiddleware;
use App\Auth\CurrentUser;
use App\Auth\DiscordClient;
use App\Auth\DiscordGroupSource;
use App\Auth\DiscordOAuthBackend;
use App\Auth\DiscordProvider;
use App\Auth\Session;
use App\Auth\UserRepository;
use App\Bootstrap;
use App\Model\UsersQuery;
use App\Web\Controller\OAuthController;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

// Sessions need a process that has not printed anything yet, see LocalLoginTest.
#[RunTestsInSeparateProcesses]
final class DiscordLoginTest extends TestCase
{
    private const ALLOWED_GUILD = '111';

    private UserRepository $users;

    private string $discordId;

    protected function setUp(): void
    {
        if (!isset($_ENV['DB_USER']) && !getenv('DB_USER') && file_exists(dirname(__DIR__, 2) . '/.env')) {
            \Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__, 2))->safeLoad();
        }
        Bootstrap::initPropel();
        $this->users     = new UserRepository();
        $this->discordId = (string) random_int(10 ** 17, 10 ** 18);
    }

    protected function tearDown(): void
    {
        UsersQuery::create()->filterByProvider(AuthConfig::DISCORD)->filterByExternalId($this->discordId)->delete();
    }

    public function testMemberOfAllowedGuildIsLoggedIn(): void
    {
        $response = $this->runCallback([['id' => self::ALLOWED_GUILD, 'name' => 'Tuesday'], ['id' => '999', 'name' => 'Private']]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/units/7', $response->getHeaderLine('Location'));
        $user = UsersQuery::create()->filterByProvider(AuthConfig::DISCORD)->filterByExternalId($this->discordId)->findOne();
        self::assertNotNull($user);
        self::assertSame('Display Name', $user->getUsername());
        self::assertSame([self::ALLOWED_GUILD], $this->users->groups($user));
        self::assertSame($user->getId(), Session::userId());
    }

    public function testNonMemberIsRejectedAndNotStored(): void
    {
        $response = $this->runCallback([['id' => '999', 'name' => 'Private']]);

        self::assertSame(403, $response->getStatusCode());
        self::assertNull(Session::userId());
        self::assertNull(UsersQuery::create()->filterByProvider(AuthConfig::DISCORD)->filterByExternalId($this->discordId)->findOne());
    }

    public function testCancelledLoginShowsMessage(): void
    {
        Session::set('oauth_state', 'st');
        $request  = (new ServerRequestFactory())->createServerRequest('GET', '/auth/callback?error=access_denied&state=st');
        $response = $this->controller([])->callback($request, new Response());

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('cancelled', (string) $response->getBody());
    }

    public function testUserWhoLeftAllowedGuildsIsLoggedOut(): void
    {
        $user = $this->users->upsertExternal(AuthConfig::DISCORD, $this->discordId, 'left', null);
        $this->users->setGroups($user, [self::ALLOWED_GUILD]);
        Session::login($user->getId());
        Session::setOauthToken('tok');

        $user->setGroupsRefreshedAt(new \DateTime('-1 hour'))->save();
        $middleware = new AuthMiddleware(
            AuthConfig::resolve('discord', ''),
            $this->users,
            new CurrentUser(),
            $this->groupSource([['id' => '999', 'name' => 'Private']]),
        );
        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/armies');

        self::assertSame(401, $middleware->process($request, $handler)->getStatusCode());
        self::assertNull(Session::userId());
    }

    /** @param list<array{id: string, name: string}> $guilds */
    private function runCallback(array $guilds): ResponseInterface
    {
        Session::set('oauth_state', 'st');
        Session::set('oauth_next', '/units/7');
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/auth/callback?code=c0de&state=st');
        return $this->controller($guilds)->callback($request, new Response());
    }

    /** @param list<array{id: string, name: string}> $guilds */
    private function controller(array $guilds): OAuthController
    {
        $oauthHttp = new Client(['handler' => HandlerStack::create(new MockHandler([
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode([
                'access_token' => 'tok', 'token_type' => 'Bearer', 'expires_in' => 604800,
            ], JSON_THROW_ON_ERROR)),
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode([
                'id' => $this->discordId, 'username' => 'handle', 'global_name' => 'Display Name',
            ], JSON_THROW_ON_ERROR)),
        ]))]);
        $provider = new DiscordProvider(
            ['clientId' => 'cid', 'clientSecret' => 'secret', 'redirectUri' => 'https://lib.example/auth/callback'],
            ['httpClient' => $oauthHttp],
        );
        return new OAuthController(
            new DiscordOAuthBackend($provider),
            $this->groupSource($guilds),
            $this->users,
            new NullLogger(),
        );
    }

    /** @param list<array{id: string, name: string}> $guilds */
    private function groupSource(array $guilds): DiscordGroupSource
    {
        $http = new Client(['handler' => HandlerStack::create(new MockHandler([
            new GuzzleResponse(200, [], json_encode($guilds, JSON_THROW_ON_ERROR)),
        ]))]);
        return new DiscordGroupSource(new DiscordClient($http), [self::ALLOWED_GUILD]);
    }
}
