<?php

declare(strict_types=1);

namespace App\Tests\Doctor;

use App\Auth\UserRepository;
use App\Bootstrap;
use App\Command\DoctorCommand;
use App\Doctor\CheckResult;
use App\Doctor\Doctor;
use App\Doctor\Status;
use App\Model\UsersQuery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DoctorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/mechlib-doctor-' . bin2hex(random_bytes(4));
        foreach (['var/cache', 'var/log', 'data/raw', 'data/migrations', 'public/images/units'] as $dir) {
            mkdir($this->root . '/' . $dir, 0o777, true);
        }
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    public function testMissingDbUserPointsToEnvExample(): void
    {
        $r = $this->find($this->diagnose([]), 'DB_USER is not set');
        self::assertSame(Status::Fail, $r->status);
        self::assertStringContainsString('Copy .env.example', (string) $r->hint);
    }

    public function testUnreachableDatabaseFailsAndSkipsAccountCheck(): void
    {
        $results = $this->diagnose(['DB_USER' => 'x', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '1', 'AUTH_PROVIDER' => 'local']);
        self::assertSame(Status::Fail, $this->find($results, 'Cannot connect')->status);
        self::assertSame(Status::Warn, $this->find($results, 'Local accounts not checked')->status);
    }

    public function testUnknownProviderFails(): void
    {
        self::assertSame(Status::Fail, $this->find($this->diagnose(['AUTH_PROVIDER' => 'ldap']), 'not supported')->status);
    }

    public function testDiscordReportsMissingValuesAndBadServerIds(): void
    {
        $results = $this->diagnose([
            'AUTH_PROVIDER'          => 'discord',
            'DISCORD_CLIENT_ID'      => '123',
            'DISCORD_REDIRECT_URI'   => 'https://lib.example/auth/callback',
            'DISCORD_ALLOWED_GUILDS' => '123456789012345678, my-server',
        ]);
        self::assertSame(Status::Fail, $this->find($results, 'Not set: DISCORD_CLIENT_SECRET')->status);
        self::assertSame(Status::Fail, $this->find($results, 'Not a Discord server ID: my-server')->status);
    }

    public function testCompleteDiscordConfigPasses(): void
    {
        $results = $this->diagnose([
            'AUTH_PROVIDER'          => 'discord',
            'DISCORD_CLIENT_ID'      => '123',
            'DISCORD_CLIENT_SECRET'  => 'secret',
            'DISCORD_REDIRECT_URI'   => 'https://lib.example/auth/callback',
            'DISCORD_ALLOWED_GUILDS' => '123456789012345678,234567890123456789',
        ]);
        self::assertSame(Status::Ok, $this->find($results, 'Discord configured, 2 allowed servers')->status);
        self::assertSame([], $this->withStatus($results, Status::Fail, 'Login'));
    }

    public function testRedirectUriMustPointToCallback(): void
    {
        $results = $this->diagnose([
            'MM_BASE_URL'            => 'https://chat.example.org',
            'MM_OAUTH_CLIENT_ID'     => 'id',
            'MM_OAUTH_CLIENT_SECRET' => 'secret',
            'MM_OAUTH_REDIRECT_URI'  => 'http://lib.example/callback',
        ]);
        self::assertSame(Status::Fail, $this->find($results, 'must end in /auth/callback')->status);
    }

    public function testCrawlerWarnsAboutOldHostAndLowDelay(): void
    {
        $results = $this->diagnose(['MUL_BASE_URL' => 'https://masterunitlist.info', 'MUL_REQUEST_DELAY_MS' => '1000']);
        self::assertSame(Status::Warn, $this->find($results, 'old masterunitlist.info')->status);
        self::assertSame(Status::Warn, $this->find($results, 'below the minimum')->status);
    }

    public function testMissingVarDirectoryFails(): void
    {
        rmdir($this->root . '/var/cache');
        self::assertSame(Status::Fail, $this->find($this->diagnose([]), 'var/cache is missing')->status);
    }

    public function testPendingMigrationIsReported(): void
    {
        touch($this->root . '/data/migrations/PropelMigration_1_not_applied.php');
        $result = $this->find($this->diagnose($this->dbEnv()), 'migrations not applied');
        self::assertSame(Status::Fail, $result->status);
        self::assertStringContainsString('1 of 1', $result->message);
    }

    public function testLocalAccountIsCounted(): void
    {
        Bootstrap::initPropel();
        $name = 'phpunit_' . bin2hex(random_bytes(4));
        (new UserRepository())->createLocal($name, 'doctor-pass');
        try {
            $result = $this->find($this->diagnose($this->dbEnv() + ['AUTH_PROVIDER' => 'local']), 'local account');
            self::assertSame(Status::Ok, $result->status);
        } finally {
            UsersQuery::create()->filterByUsername($name)->delete();
        }
    }

    public function testCommandFailsWhenAProblemIsFound(): void
    {
        $tester = new CommandTester(new DoctorCommand($this->dbEnv() + ['AUTH_PROVIDER' => 'ldap', 'APP_DEBUG' => 'false'], $this->root));
        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertMatchesRegularExpression('/, 1 problem$/m', $tester->getDisplay());
    }

    /**
     * @param  array<string, string> $env
     * @return list<CheckResult>
     */
    private function diagnose(array $env): array
    {
        return (new Doctor($env, $this->root))->run();
    }

    /** @param list<CheckResult> $results */
    private function find(array $results, string $needle): CheckResult
    {
        foreach ($results as $r) {
            if (str_contains($r->message, $needle)) {
                return $r;
            }
        }
        self::fail("no check result containing '{$needle}': " . implode(' | ', array_map(static fn ($r) => $r->message, $results)));
    }

    /**
     * @param  list<CheckResult> $results
     * @return list<CheckResult>
     */
    private function withStatus(array $results, Status $status, string $section): array
    {
        return array_values(array_filter($results, static fn ($r): bool => $r->status === $status && $r->section === $section));
    }

    /** @return array<string, string> */
    private function dbEnv(): array
    {
        if (!getenv('DB_USER') && file_exists(dirname(__DIR__, 2) . '/.env')) {
            \Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__, 2))->safeLoad();
        }
        $env = [];
        foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
            $env[$key] = (string) getenv($key);
        }
        return $env;
    }
}
