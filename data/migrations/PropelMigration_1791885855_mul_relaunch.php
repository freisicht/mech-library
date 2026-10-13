<?php

declare(strict_types=1);

use App\Database\SqlMigration;

final class PropelMigration_1791885855_mul_relaunch extends SqlMigration
{
    public string $comment = 'units.mul_uid, nullable mul_id, fractional tonnage';

    protected function up(): string
    {
        return <<<'SQL'
-- Migration 006: units from the relaunched MUL (masterunitlist.battletech.com).
-- New units carry a string public id instead of the old integer id, and
-- non-Mech types need fractional (Battle Armor) and very large (DropShip) tonnage.

ALTER TABLE units
    MODIFY mul_id INT UNSIGNED NULL,
    ADD mul_uid VARCHAR(16) NULL AFTER mul_id,
    ADD UNIQUE KEY uq_mul_uid (mul_uid),
    MODIFY tonnage FLOAT NOT NULL DEFAULT 0;
SQL;
    }
}
