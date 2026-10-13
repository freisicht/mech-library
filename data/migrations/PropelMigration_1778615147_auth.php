<?php

declare(strict_types=1);

use App\Database\SqlMigration;

final class PropelMigration_1778615147_auth extends SqlMigration
{
    public string $comment = 'users, armies.required_mm_channel_id';

    protected function up(): string
    {
        return <<<'SQL'
-- Migration 003: Mattermost login (users) and per-army channel gate.

ALTER TABLE armies
    ADD required_mm_channel_id VARCHAR(32) NULL AFTER name;

CREATE TABLE IF NOT EXISTS users (
    id                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    mm_user_id            VARCHAR(32)   NOT NULL,
    username              VARCHAR(128)  NOT NULL,
    email                 VARCHAR(255)  NULL,
    channels_json         TEXT          NULL,
    channels_refreshed_at TIMESTAMP     NULL,
    last_login_at         TIMESTAMP     NULL,
    created_at            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE INDEX uq_mm_user_id (mm_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }
}
