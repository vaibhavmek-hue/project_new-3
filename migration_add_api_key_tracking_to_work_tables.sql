-- =============================================
-- Migration: track which API key touched each work item
-- Run this AFTER migration_add_api_keys.sql.
-- Safe to re-run - each line is a no-op if already applied.
-- =============================================

ALTER TABLE website_work
    ADD COLUMN IF NOT EXISTS last_api_key_id INT NULL AFTER end_date,
    ADD COLUMN IF NOT EXISTS last_api_touched_at TIMESTAMP NULL DEFAULT NULL AFTER last_api_key_id;

ALTER TABLE app_work
    ADD COLUMN IF NOT EXISTS last_api_key_id INT NULL AFTER end_date,
    ADD COLUMN IF NOT EXISTS last_api_touched_at TIMESTAMP NULL DEFAULT NULL AFTER last_api_key_id;

ALTER TABLE dashboard_work
    ADD COLUMN IF NOT EXISTS last_api_key_id INT NULL AFTER end_date,
    ADD COLUMN IF NOT EXISTS last_api_touched_at TIMESTAMP NULL DEFAULT NULL AFTER last_api_key_id;

-- Foreign keys are added separately (and guarded) so this migration
-- doesn't error out if run twice - MySQL has no "ADD CONSTRAINT IF NOT
-- EXISTS", so we check information_schema first.
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_website_work_api_key'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE website_work ADD CONSTRAINT fk_website_work_api_key FOREIGN KEY (last_api_key_id) REFERENCES api_keys(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_app_work_api_key'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE app_work ADD CONSTRAINT fk_app_work_api_key FOREIGN KEY (last_api_key_id) REFERENCES api_keys(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_dashboard_work_api_key'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE dashboard_work ADD CONSTRAINT fk_dashboard_work_api_key FOREIGN KEY (last_api_key_id) REFERENCES api_keys(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
