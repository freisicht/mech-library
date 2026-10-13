<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Auth\UserRepository;
use App\Bootstrap;
use App\Command\UserAddCommand;
use App\Command\UserGroupsCommand;
use App\Command\UserPasswordCommand;
use App\Model\UsersQuery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class UserCommandsTest extends TestCase
{
    private UserRepository $users;

    private string $username;

    protected function setUp(): void
    {
        if (!isset($_ENV['DB_USER']) && !getenv('DB_USER') && file_exists(dirname(__DIR__, 2) . '/.env')) {
            \Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__, 2))->safeLoad();
        }
        Bootstrap::initPropel();
        $this->users    = new UserRepository();
        $this->username = 'phpunit_' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        UsersQuery::create()->filterByUsername($this->username)->delete();
    }

    public function testAddCreatesUserWithGroups(): void
    {
        $tester = $this->tester(new UserAddCommand());
        $tester->setInputs(['secret-pass', 'secret-pass']);
        $code = $tester->execute(['username' => $this->username, '--group' => ['tuesday', ' tuesday ', 'league']]);

        self::assertSame(Command::SUCCESS, $code, $tester->getDisplay());
        $user = $this->users->findLocal($this->username);
        self::assertNotNull($user);
        self::assertTrue(password_verify('secret-pass', (string) $user->getPasswordHash()));
        self::assertSame(['tuesday', 'league'], $this->users->groups($user));
    }

    public function testAddRejectsMismatchAndShortPasswords(): void
    {
        $tester = $this->tester(new UserAddCommand());
        $tester->setInputs(['secret-pass', 'other-pass']);
        self::assertSame(Command::FAILURE, $tester->execute(['username' => $this->username]));

        $tester->setInputs(['short', 'short']);
        self::assertSame(Command::FAILURE, $tester->execute(['username' => $this->username]));
        self::assertNull($this->users->findLocal($this->username));
    }

    public function testAddRejectsDuplicateUsername(): void
    {
        $this->users->createLocal($this->username, 'secret-pass');
        $tester = $this->tester(new UserAddCommand());
        self::assertSame(Command::FAILURE, $tester->execute(['username' => strtoupper($this->username)]));
        self::assertStringContainsString('already exists', $tester->getDisplay());
    }

    public function testPasswordChangesHash(): void
    {
        $this->users->createLocal($this->username, 'secret-pass');
        $tester = $this->tester(new UserPasswordCommand());
        $tester->setInputs(['new-secret-pass', 'new-secret-pass']);
        self::assertSame(Command::SUCCESS, $tester->execute(['username' => $this->username]));

        $user = $this->users->findLocal($this->username);
        self::assertNotNull($user);
        self::assertTrue(password_verify('new-secret-pass', (string) $user->getPasswordHash()));
    }

    public function testGroupsShowSetAndClear(): void
    {
        $this->users->createLocal($this->username, 'secret-pass', ['tuesday']);
        $tester = $this->tester(new UserGroupsCommand());

        $tester->execute(['username' => $this->username]);
        self::assertStringContainsString('tuesday', $tester->getDisplay());

        $tester->execute(['username' => $this->username, 'groups' => ['league', 'campaign']]);
        $user = $this->users->findLocal($this->username);
        self::assertNotNull($user);
        self::assertSame(['league', 'campaign'], $this->users->groups($user));

        $tester->execute(['username' => $this->username, '--clear' => true]);
        $user = $this->users->findLocal($this->username);
        self::assertNotNull($user);
        self::assertSame([], $this->users->groups($user));
    }

    private function tester(Command $command): CommandTester
    {
        $app = new Application();
        $app->add($command);
        return new CommandTester($app->find((string) $command->getName()));
    }
}
