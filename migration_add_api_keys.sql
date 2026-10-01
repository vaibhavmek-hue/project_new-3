-- =============================================
-- Migration: API key authentication for external access
-- Run this once against your existing
-- project_work_report_generator database.
-- =============================================

-- -------------------------------------------
-- Table: api_keys
-- One row per generated key. The plaintext key is shown to the user
-- exactly once (at generation time) and is never stored — only a
-- SHA-256 hash of it, the same principle as password_verify() for
-- user logins in login.php.
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS api_keys (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    label        VARCHAR(255) NOT NULL,
    key_prefix   VARCHAR(20) NOT NULL,      -- first few chars, shown in the UI so a key can be recognised without revealing it
    key_hash     CHAR(64) NOT NULL,         -- sha256(raw key), hex-encoded
    scopes       VARCHAR(100) NOT NULL DEFAULT 'read,write',
    status       ENUM('Active','Revoked') DEFAULT 'Active',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL DEFAULT NULL,
    revoked_at   TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uniq_key_hash (key_hash),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -------------------------------------------
-- Table: api_request_log
-- Lightweight per-key request log, used to enforce a rolling rate
-- limit and to power the "last used" / usage info shown in the UI.
-- Old rows are pruned automatically by common/api_auth.php so this
-- table never grows unbounded.
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS api_request_log (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    api_key_id    INT NOT NULL,
    endpoint      VARCHAR(500) DEFAULT NULL,
    requested_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE,
    INDEX idx_key_time (api_key_id, requested_at)
);
