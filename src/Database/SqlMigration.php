<?php

declare(strict_types=1);

namespace App\Database;

use LogicException;
use Propel\Generator\Manager\MigrationManager;

/**
 * Base for the hand-written migrations in data/migrations/, run by `vendor/bin/perpl
 * migration:migrate`. Perpl calls the hooks unconditionally, so they live here once.
 */
abstract class SqlMigration
{
    public string $comment = '';

    abstract protected function up(): string;

    public function preUp(MigrationManager $manager): void
    {
    }

    public function postUp(MigrationManager $manager): void
    {
    }

    public function preDown(MigrationManager $manager): void
    {
    }

    public function postDown(MigrationManager $manager): void
    {
    }

    /** @return array<string, string> */
    public function getUpSQL(): array
    {
        return ['mech_library' => $this->up()];
    }

    /** @return array<string, string> */
    public function getDownSQL(): array
    {
        throw new LogicException('Down migrations are not supported, restore the database from a backup.');
    }
}
