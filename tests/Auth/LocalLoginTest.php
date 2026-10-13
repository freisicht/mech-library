<?php

declare(strict_types=1);

namespace App\Tests\Auth;

use App\Auth\AuthConfig;
use App\Auth\Session;
use App\Auth\UserRepository;
use App\Model\UsersQuery;
use App\Web\AppFactory;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

// PHP refuses to start a session once output was sent, and PHPUnit prints
// progress before each test; a fresh process has sent nothing yet.
#[RunTestsInSeparateProcesses]
final class LocalLoginTest extends TestCase
{
    private const PASSWORD = 'correct horse battery';

    /** @var App<\Psr\Container\ContainerInterface|null> */
    private App $app;

    private UserRepository $users;

    private string $username;

    protected function setUp(): void
    {
        $_ENV['AUTH_PROVIDER'] = 'local';
        putenv('AUTH_PROVIDER=local');
        $_ENV['AUTH_DISABLED'] = '0';
        putenv('AUTH_DISABLED=0');

        $this->app      = AppFactory::create(dirname(__DIR__, 2));
        $this->users    = new UserRepository();
        $this->username = 'phpunit_' . bin2hex(random_bytes(4));
        $this->users->createLocal($this->username, self::PASSWORD, ['tuesday']);
    }

    protected function tearDown(): void
    {
        UsersQuery::create()->filterByUsername($this->username)->delete();
    }

    public function testLoginPageShowsForm(): void
    {
        $response = $this->request('GET', '/auth/login');
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('name="password"', (string) $response->getBody());
        self::assertStringNotContainsString('Mattermost', (string) $response->getBody());
    }

    public function testSuccessfulLoginRedirectsAndAuthenticates(): void
    {
        $response = $this->login(strtoupper($this->username), self::PASSWORD, '/units/1');
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/units/1', $response->getHeaderLine('Location'));

        $me = $this->request('GET', '/api/me');
        self::assertSame(200, $me->getStatusCode());
        $data = json_decode((string) $me->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($this->username, $data['username']);
        self::assertSame(AuthConfig::LOCAL, $data['provider']);
        self::assertSame(['tuesday'], $data['group_ids']);

        $groups = json_decode((string) $this->request('GET', '/api/me/groups')->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([['id' => 'tuesday', 'display_name' => 'tuesday']], $groups);
    }

    public function testWrongPasswordIsRejected(): void
    {
        $response = $this->login($this->username, 'wrong password');
        self::assertSame(401, $response->getStatusCode());
        self::assertNull(Session::userId());
    }

    public function testExternalRedirectTargetIsIgnored(): void
    {
        $response = $this->login($this->username, self::PASSWORD, '//evil.example');
        self::assertSame('/', $response->getHeaderLine('Location'));
    }

    public function testLoginWithoutCsrfTokenIsRejected(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/auth/login')
            ->withParsedBody(['username' => $this->username, 'password' => self::PASSWORD]);
        self::assertSame(400, $this->app->handle($request)->getStatusCode());
        self::assertNull(Session::userId());
    }

    public function testMattermostRoutesAreNotRegistered(): void
    {
        $patterns = array_map(
            static fn ($route): string => $route->getPattern(),
            $this->app->getRouteCollector()->getRoutes(),
        );
        self::assertNotContains('/auth/start', $patterns);
        self::assertNotContains('/auth/callback', $patterns);
    }

    public function testSessionOfOtherProviderIsRejected(): void
    {
        $mmUser = $this->users->upsertExternal(AuthConfig::MATTERMOST, 'mm_' . $this->username, $this->username, null);
        try {
            Session::login($mmUser->getId());
            self::assertSame(401, $this->request('GET', '/api/me')->getStatusCode());
        } finally {
            $mmUser->delete();
        }
    }

    private function login(string $username, string $password, string $next = '/'): ResponseInterface
    {
        $this->request('GET', '/auth/login');
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/auth/login')
            ->withParsedBody([
                'username' => $username,
                'password' => $password,
                'next'     => $next,
                'csrf'     => (string) Session::get('login_csrf'),
            ]);
        return $this->app->handle($request);
    }

    private function request(string $method, string $uri): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri)
            ->withHeader('Accept', 'application/json');
        return $this->app->handle($request);
    }
}
