<?php

declare(strict_types=1);

namespace App\Tests\Database;

use App\Bootstrap;
use App\Database\ConnectionGuard;
use PHPUnit\Framework\TestCase;
use Propel\Runtime\Propel;
use RuntimeException;

final class ConnectionGuardTest extends TestCase
{
    public function testHealthyConnectionIsLeftAlone(): void
    {
        $resets = 0;
        $guard  = new ConnectionGuard(
            static function (): void {
            },
            static function () use (&$resets): void {
                $resets++;
            },
        );

        self::assertFalse($guard->recover());
        self::assertSame(0, $resets);
    }

    public function testLostConnectionIsReopenedAfterWaiting(): void
    {
        $server = new \stdClass();
        $server->alive = false;
        $resets = 0;
        $slept  = [];
        $guard  = new ConnectionGuard(
            static function () use ($server): void {
                if ($server->alive !== true) {
                    throw new RuntimeException('MySQL server has gone away');
                }
            },
            static function () use (&$resets, $server): void {
                // The server comes back on the second reset.
                $server->alive = ++$resets >= 2;
            },
            static function (int $s) use (&$slept): void {
                $slept[] = $s;
            },
        );

        self::assertTrue($guard->recover());
        self::assertSame(2, $resets);
        self::assertSame([10], $slept);
    }

    public function testGivesUpWhenTheServerStaysDown(): void
    {
        $resets = 0;
        $guard  = new ConnectionGuard(
            static function (): void {
                throw new RuntimeException('Connection refused');
            },
            static function () use (&$resets): void {
                $resets++;
            },
            static function (int $_s): void {
            },
            attempts: 3,
        );

        self::assertFalse($guard->recover());
        self::assertSame(3, $resets);
    }

    public function testDefaultResetMakesPropelOpenAFreshConnection(): void
    {
        if (!isset($_ENV['DB_USER']) && !getenv('DB_USER')) {
            \Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__, 2))->safeLoad();
        }
        Bootstrap::initPropel();
        $before = Propel::getConnection();
        $before->query('SELECT 1');

        Propel::getServiceContainer()->closeConnections();
        $after = Propel::getConnection();

        self::assertNotSame($before, $after);
        self::assertSame('1', (string) $after->query('SELECT 1')->fetchColumn());
    }
}
