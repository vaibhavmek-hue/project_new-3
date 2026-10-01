-- =========================================================
-- Migration: split website pages into Designed/Developed,
-- add App Pages - Designed/Developed
-- ---------------------------------------------------------
-- Previously "Website Pages (Design & Developed)" was a single
-- report section backed by website_page_links. This adds
-- website_pages_designed so Website Pages can be tracked the
-- same way Dashboard Pages already are: a "Designed" list and
-- a separate "Developed" list (with live links). website_page_links
-- itself is unchanged and becomes the "Website Pages - Developed"
-- table.
--
-- It also adds app_pages_designed and app_page_links, so App
-- Pages get their own "Designed" / "Developed" sections, mirroring
-- Dashboard and Website.
--
-- Safe to run on an existing database - CREATE TABLE IF NOT
-- EXISTS, so re-running it is a no-op. No existing data is
-- touched or moved.
-- =========================================================

CREATE TABLE IF NOT EXISTS website_pages_designed (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    page_name    VARCHAR(255) NOT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS app_pages_designed (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    page_name    VARCHAR(255) NOT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS app_page_links (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    page_name    VARCHAR(255) NOT NULL,
    page_link    VARCHAR(500) DEFAULT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
