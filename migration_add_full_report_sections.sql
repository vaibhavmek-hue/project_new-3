-- =========================================================
-- Migration: full report-section tables
-- ---------------------------------------------------------
-- Adds the extra per-project tables needed so the "Download
-- Report" PDF can include every section that appears in a
-- full client report: team members, Figma design pages,
-- website/dashboard page lists with live links, dashboard &
-- website operations checklists, database tables, the API
-- list, and a changes/suggestions/modifications log.
--
-- Safe to run on an existing database - every statement is
-- CREATE TABLE IF NOT EXISTS, so re-running it is a no-op.
-- =========================================================

-- Team members credited on the report (e.g. "Sr. Web Developer")
CREATE TABLE IF NOT EXISTS project_team_members (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT NOT NULL,
    name        VARCHAR(255) NOT NULL,
    role        VARCHAR(255) NOT NULL,
    sort_order  INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Figma design work list ("Figma Design Work" table in the sample)
CREATE TABLE IF NOT EXISTS figma_pages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    page_name    VARCHAR(255) NOT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Website pages that are live, with their real URL
-- ("Website Pages - Design and developed" table in the sample)
CREATE TABLE IF NOT EXISTS website_page_links (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    page_name    VARCHAR(255) NOT NULL,
    page_link    VARCHAR(500) DEFAULT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Dashboard pages that have been designed ("Dashboard Pages - Designed")
CREATE TABLE IF NOT EXISTS dashboard_pages_designed (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    page_name    VARCHAR(255) NOT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Dashboard pages that are live, with their real URL ("Dashboard Pages - Developed")
CREATE TABLE IF NOT EXISTS dashboard_page_links (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    page_name    VARCHAR(255) NOT NULL,
    page_link    VARCHAR(500) DEFAULT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Dashboard operations / functionality checklist
CREATE TABLE IF NOT EXISTS dashboard_operations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    page         VARCHAR(255) NOT NULL,
    section      VARCHAR(255) NOT NULL,
    operation    TEXT NOT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Website operations / functionality checklist
CREATE TABLE IF NOT EXISTS website_operations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    section      VARCHAR(255) NOT NULL,
    sub_section  VARCHAR(255) NOT NULL,
    operation    TEXT NOT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Database tables list
CREATE TABLE IF NOT EXISTS database_tables (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    table_name   VARCHAR(255) NOT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- API creation & integration list
CREATE TABLE IF NOT EXISTS api_list (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    project_id      INT NOT NULL,
    api_name        VARCHAR(255) NOT NULL,
    created_flag    TINYINT(1) DEFAULT 1,
    integrated_flag TINYINT(1) DEFAULT 1,
    status          VARCHAR(100) DEFAULT 'Completed',
    created_date    DATE NOT NULL,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Changes / Suggestions / Modifications log
CREATE TABLE IF NOT EXISTS changes_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT NOT NULL,
    change_date DATE NOT NULL,
    page        VARCHAR(255) NOT NULL,
    section     VARCHAR(255) NOT NULL,
    note        TEXT NOT NULL,
    sort_order  INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
