-- Migration 005: a formation may hold the same unit more than once
-- (e.g. two Atlas AS7-D in one lance). The composite unique key goes; the FK
-- on formation_id needs its own index before the unique one can be dropped.

ALTER TABLE formation_units
    ADD INDEX idx_fu_formation (formation_id, sort_order),
    DROP INDEX uq_formation_unit;
