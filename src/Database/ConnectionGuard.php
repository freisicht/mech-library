<?php

declare(strict_types=1);

namespace App\Database;

use Propel\Runtime\Propel;

/**
 * Restores the database connection for long-running commands.
 *
 * A MySQL restart leaves a long-lived process holding a dead connection, and
 * every later query fails. After an error the guard probes the connection
 * and, if it is gone, drops it so Propel opens a new one, waiting a little
 * while in case the server is still starting.
 */
final class ConnectionGuard
{
    /** @var callable(): void */
    private $probe;
    /** @var callable(): void */
    private $reset;
    /** @var callable(int): void */
    private $sleep;

    /**
     * @param callable(): void|null $probe throws when the connection is unusable
     * @param callable(): void|null $reset drops the current connection
     * @param callable(int): void|null $sleep seconds
     */
    public function __construct(
        ?callable $probe = null,
        ?callable $reset = null,
        ?callable $sleep = null,
        private readonly int $attempts = 6,
        private readonly int $waitSeconds = 10,
    ) {
        $this->probe = $probe ?? static function (): void {
            Propel::getConnection()->query('SELECT 1');
        };
        $this->reset = $reset ?? static function (): void {
            Propel::getServiceContainer()->closeConnections();
        };
        $this->sleep = $sleep ?? static function (int $s): void {
            sleep($s);
        };
    }

    /** True when the connection had been lost and is usable again. */
    public function recover(): bool
    {
        if ($this->works()) {
            return false;
        }
        for ($i = 0; $i < $this->attempts; $i++) {
            ($this->reset)();
            if ($this->works()) {
                return true;
            }
            ($this->sleep)($this->waitSeconds);
        }
        return false;
    }

    /** @phpstan-impure */
    private function works(): bool
    {
        try {
            ($this->probe)();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
