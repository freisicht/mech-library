<?php

declare(strict_types=1);

use App\Database\SqlMigration;

final class PropelMigration_1777311067_force_builder extends SqlMigration
{
    public string $comment = 'armies, formations, formation_units';

    protected function up(): string
    {
        return <<<'SQL'
-- Migration 002: force builder tables (Army → Formation → formation_units)
-- Idempotent via IF NOT EXISTS guards.


CREATE TABLE IF NOT EXISTS armies (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name        VARCHAR(128)    NOT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS formations (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    army_id         INT UNSIGNED        NULL,
    name            VARCHAR(128)        NOT NULL,
    formation_type  VARCHAR(64)         NOT NULL DEFAULT '',
    pv_budget       SMALLINT UNSIGNED   NULL,
    is_active       TINYINT(1)          NOT NULL DEFAULT 0,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_army_id (army_id),
    KEY idx_is_active (is_active),
    CONSTRAINT fk_formations_army FOREIGN KEY (army_id) REFERENCES armies (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Only one formation may be active at a time, enforced in application code.
-- A partial unique index (WHERE is_active = 1) isn't portable to older MySQL,
-- so the single-active invariant is maintained by FormationRepository::setActive().

CREATE TABLE IF NOT EXISTS formation_units (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    formation_id    INT UNSIGNED        NOT NULL,
    unit_id         INT UNSIGNED        NOT NULL,
    sort_order      SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_formation_unit (formation_id, unit_id),
    KEY idx_fu_unit_id (unit_id),
    CONSTRAINT fk_fu_formation FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE,
    CONSTRAINT fk_fu_unit      FOREIGN KEY (unit_id)      REFERENCES units (id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }
}
