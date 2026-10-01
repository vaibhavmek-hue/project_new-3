-- =========================================================
-- Migration: app operations list
-- ---------------------------------------------------------
-- Adds the app_operations table so the "Report Details" screen
-- and the full client report can include an "App - Operations
-- List" section, the same way Dashboard - Operations List and
-- Website - Operations List already work.
--
-- Safe to run on an existing database - CREATE TABLE IF NOT
-- EXISTS, so re-running it is a no-op.
-- =========================================================

CREATE TABLE IF NOT EXISTS app_operations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    screen       VARCHAR(255) NOT NULL,
    section      VARCHAR(255) NOT NULL,
    operation    TEXT NOT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
