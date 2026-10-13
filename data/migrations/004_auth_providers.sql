-- Migration 004: provider-neutral auth. Mattermost channels become generic
-- groups, and users can come from different providers (mattermost, local).
-- Existing rows are kept and tagged as mattermost.

ALTER TABLE users
    ADD provider VARCHAR(32) NOT NULL DEFAULT 'mattermost' AFTER id,
    CHANGE mm_user_id external_id VARCHAR(64) NOT NULL,
    ADD password_hash VARCHAR(255) NULL AFTER email,
    CHANGE channels_json groups_json TEXT NULL,
    CHANGE channels_refreshed_at groups_refreshed_at TIMESTAMP NULL,
    DROP INDEX uq_mm_user_id,
    ADD UNIQUE INDEX uq_provider_external_id (provider, external_id);

ALTER TABLE users
    ALTER provider DROP DEFAULT;

ALTER TABLE armies
    CHANGE required_mm_channel_id required_group_id VARCHAR(64) NULL;
