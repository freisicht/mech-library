<?php

declare(strict_types=1);

use App\Database\SqlMigration;

final class PropelMigration_1791885854_formation_unit_duplicates extends SqlMigration
{
    public string $comment = 'same unit may appear twice in a formation';

    protected function up(): string
    {
        return <<<'SQL'
-- Migration 005: a formation may hold the same unit more than once
-- (e.g. two Atlas AS7-D in one lance). The composite unique key goes, the FK
-- on formation_id needs its own index before the unique one can be dropped.

ALTER TABLE formation_units
    ADD INDEX idx_fu_formation (formation_id, sort_order),
    DROP INDEX uq_formation_unit;
SQL;
    }
}
