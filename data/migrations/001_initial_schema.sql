-- Migration 001: initial schema
-- Run once. Idempotent via IF NOT EXISTS / IF NOT EXISTS guards.


-- -------------------------------------------------------
-- Lookup tables (seeded from MUL index pages)
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS unit_types (
    id        SMALLINT UNSIGNED NOT NULL,
    name      VARCHAR(64)       NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS technologies (
    id        SMALLINT UNSIGNED NOT NULL,
    name      VARCHAR(64)       NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    id        SMALLINT UNSIGNED NOT NULL,
    name      VARCHAR(64)       NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS factions (
    id        SMALLINT UNSIGNED NOT NULL,
    name      VARCHAR(128)      NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eras (
    id        SMALLINT UNSIGNED NOT NULL,
    name      VARCHAR(128)      NOT NULL,
    start_year SMALLINT         NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Core unit table
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS units (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    mul_id                  INT UNSIGNED    NOT NULL,   -- MUL Id field; upsert key
    name                    VARCHAR(255)    NOT NULL,
    class                   VARCHAR(128)    NOT NULL DEFAULT '',
    variant                 VARCHAR(128)    NOT NULL DEFAULT '',
    tonnage                 TINYINT UNSIGNED NOT NULL DEFAULT 0,
    battle_value            INT UNSIGNED    NULL,
    cost                    BIGINT UNSIGNED NULL,
    rules                   VARCHAR(32)     NOT NULL DEFAULT '',
    date_introduced         VARCHAR(16)     NULL,       -- raw string from API (e.g. "2776")
    era_id                  SMALLINT UNSIGNED NULL,
    era_start               SMALLINT        NULL,
    image_url               VARCHAR(512)    NULL,
    is_published            TINYINT(1)      NOT NULL DEFAULT 1,
    mul_release             DECIMAL(4,1)    NULL,
    type_id                 SMALLINT UNSIGNED NULL,
    technology_id           SMALLINT UNSIGNED NULL,
    role_id                 SMALLINT UNSIGNED NULL,
    -- Alpha Strike fields
    bf_type                 VARCHAR(8)      NULL,
    bf_size                 TINYINT UNSIGNED NULL,
    bf_move                 VARCHAR(16)     NULL,       -- raw string e.g. "8\"j"
    bf_tmm                  TINYINT         NULL,
    bf_armor                SMALLINT UNSIGNED NULL,
    bf_structure            SMALLINT UNSIGNED NULL,
    bf_threshold            TINYINT UNSIGNED NULL,
    bf_damage_short         TINYINT UNSIGNED NULL,
    bf_damage_short_min     TINYINT(1)      NOT NULL DEFAULT 0,
    bf_damage_medium        TINYINT UNSIGNED NULL,
    bf_damage_medium_min    TINYINT(1)      NOT NULL DEFAULT 0,
    bf_damage_long          TINYINT UNSIGNED NULL,
    bf_damage_long_min      TINYINT(1)      NOT NULL DEFAULT 0,
    bf_damage_extreme       TINYINT UNSIGNED NULL,
    -- sic: mirrors API typo "BFDamageExtemeMin"
    bf_damage_extreme_min   TINYINT(1)      NOT NULL DEFAULT 0,
    bf_overheat             TINYINT UNSIGNED NULL,
    bf_point_value          SMALLINT UNSIGNED NULL,
    bf_abilities            TEXT            NULL,       -- raw comma-separated string from API
    -- housekeeping
    image_path              VARCHAR(512)    NULL,       -- local relative path once downloaded
    image_hash              CHAR(64)        NULL,       -- SHA-256 of image file
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mul_id (mul_id),
    KEY idx_tonnage (tonnage),
    KEY idx_bf_point_value (bf_point_value),
    KEY idx_type_id (type_id),
    KEY idx_role_id (role_id),
    KEY idx_technology_id (technology_id),
    CONSTRAINT fk_units_type       FOREIGN KEY (type_id)       REFERENCES unit_types (id),
    CONSTRAINT fk_units_technology FOREIGN KEY (technology_id) REFERENCES technologies (id),
    CONSTRAINT fk_units_role       FOREIGN KEY (role_id)       REFERENCES roles (id),
    CONSTRAINT fk_units_era        FOREIGN KEY (era_id)        REFERENCES eras (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Parsed abilities side-table
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS unit_abilities (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    unit_id     INT UNSIGNED    NOT NULL,
    tag         VARCHAR(32)     NOT NULL,   -- e.g. "AC", "IF", "CASE", "LRM"
    `short`     TINYINT UNSIGNED NULL,      -- per-range value or NULL
    `medium`    TINYINT UNSIGNED NULL,
    `long`      TINYINT UNSIGNED NULL,
    value       VARCHAR(16)     NULL,       -- single numeric rating or composite (e.g. "3/2")
    raw         VARCHAR(64)     NOT NULL,   -- original token e.g. "AC2/2/-"
    PRIMARY KEY (id),
    KEY idx_ua_unit_id (unit_id),
    KEY idx_ua_tag (tag),
    CONSTRAINT fk_ua_unit FOREIGN KEY (unit_id) REFERENCES units (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
