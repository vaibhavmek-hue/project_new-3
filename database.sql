-- =============================================
-- Database: project_work_report_generator
-- Run this in phpMyAdmin or MySQL CLI
-- =============================================

CREATE DATABASE IF NOT EXISTS project_work_report_generator;
USE project_work_report_generator;

-- -------------------------------------------
-- Table: clients
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS clients (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    client_name      VARCHAR(255) NOT NULL,
    organization_name VARCHAR(255) NOT NULL,
    contact_person   VARCHAR(255),
    designation      VARCHAR(255),
    mobile           VARCHAR(20),
    email            VARCHAR(255),
    address          TEXT,
    state            VARCHAR(100),
    pin_code         VARCHAR(20),
    status           ENUM('Active','Inactive') DEFAULT 'Active',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------
-- Table: projects
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS projects (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    client_id    INT NULL,
    client_name  VARCHAR(255),
    email        VARCHAR(255),
    phone        VARCHAR(20),
    num_users    INT,
    start_date   DATE,
    end_date     DATE,
    description  TEXT,
    status       ENUM('In Progress','Completed','On Hold') DEFAULT 'In Progress',
    progress     INT DEFAULT 0,
    team_image   VARCHAR(255),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
);

-- -------------------------------------------
-- Table: app_work
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS app_work (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NULL,
    screen_name  VARCHAR(255) NOT NULL,
    status       ENUM('Designed','Developed','Pending') DEFAULT 'Pending',
    created_date DATE,
    end_date     DATE,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

-- -------------------------------------------
-- Table: dashboard_work
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS dashboard_work (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NULL,
    page_name    VARCHAR(255) NOT NULL,
    status       ENUM('Designed','Developed','Pending') DEFAULT 'Pending',
    created_date DATE,
    end_date     DATE,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

-- -------------------------------------------
-- Table: website_work
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS website_work (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NULL,
    page_name    VARCHAR(255) NOT NULL,
    status       ENUM('Designed','Developed','Pending') DEFAULT 'Pending',
    created_date DATE,
    end_date     DATE,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

-- -------------------------------------------
-- Table: technologies
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS technologies (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NULL,
    tech_type  VARCHAR(100) NOT NULL,
    name       VARCHAR(100) NOT NULL,
    version    VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

-- -------------------------------------------
-- Table: project_reports
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS project_reports (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    report_name  VARCHAR(255) NOT NULL,
    report_type  ENUM('Weekly','Monthly','Progress','Final') DEFAULT 'Progress',
    report_date  DATE,
    custom_subject VARCHAR(255) DEFAULT NULL,
    custom_intro   TEXT DEFAULT NULL,
    custom_closing TEXT DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- -------------------------------------------
-- Table: users (login.php / loginpage.php)
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    email      VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    status     ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fixed login user, baked in as a bcrypt hash so it works the same on
-- every machine as soon as this SQL file is imported. No need to run
-- setup_admin.php on new deployments.
--   Username/Email: admin@123
--   Password:       Admin@123
INSERT INTO users (name, email, password, status)
VALUES ('Admin', 'admin@123', '$2b$10$yOCKYlHeT5QFzGlzAclaGepUKux0RcspCawNhU1UyXWTRsjxuRAvS', 'Active')
ON DUPLICATE KEY UPDATE password = VALUES(password), status = 'Active';

-- -------------------------------------------
-- Safety net: if you already created this database before the
-- `team_image` column existed, this adds it without erroring out.
-- (Safe to run even on a brand new database - it will just fail
-- silently if the column is already there.)
-- -------------------------------------------
ALTER TABLE projects ADD COLUMN IF NOT EXISTS team_image VARCHAR(255) AFTER progress;

-- -------------------------------------------
-- Safety net: projects no longer collect city/country, and now
-- track an end_date alongside start_date. Safe to re-run on an
-- existing database - adds/drops only if not already applied.
-- -------------------------------------------
ALTER TABLE projects ADD COLUMN IF NOT EXISTS end_date DATE AFTER start_date;
ALTER TABLE projects DROP COLUMN IF EXISTS city;
ALTER TABLE projects DROP COLUMN IF EXISTS country;

-- -------------------------------------------
-- Safety net: clients no longer collect city/country either.
-- Safe to re-run - no-ops if the columns are already gone.
-- -------------------------------------------
ALTER TABLE clients DROP COLUMN IF EXISTS city;
ALTER TABLE clients DROP COLUMN IF EXISTS country;

-- -------------------------------------------
-- Safety net: link the tables together for anyone who already had
-- this database before client_id / project_id existed. Safe to
-- re-run - each line just no-ops if the column is already there.
-- If you are running this on an EXISTING database, run
-- migration_connect_projects.sql instead - it also adds the
-- foreign keys and backfills client_id/project_id from the old
-- text-matching columns so nothing already in your data gets lost.
-- -------------------------------------------
ALTER TABLE projects       ADD COLUMN IF NOT EXISTS client_id  INT NULL AFTER project_name;
ALTER TABLE app_work        ADD COLUMN IF NOT EXISTS project_id INT NULL AFTER id;
ALTER TABLE dashboard_work  ADD COLUMN IF NOT EXISTS project_id INT NULL AFTER id;
ALTER TABLE website_work    ADD COLUMN IF NOT EXISTS project_id INT NULL AFTER id;
ALTER TABLE technologies    ADD COLUMN IF NOT EXISTS project_id INT NULL AFTER id;

-- -------------------------------------------
-- Safety net: add End Date to the three work tables for anyone who
-- already had this database before end_date existed. Safe to re-run.
-- -------------------------------------------
ALTER TABLE app_work        ADD COLUMN IF NOT EXISTS end_date DATE NULL AFTER created_date;
ALTER TABLE dashboard_work  ADD COLUMN IF NOT EXISTS end_date DATE NULL AFTER created_date;
ALTER TABLE website_work    ADD COLUMN IF NOT EXISTS end_date DATE NULL AFTER created_date;

-- -------------------------------------------
-- Full report-section tables (team members, Figma pages, page
-- links, operations checklists, database tables, API list,
-- changes log). See migration_add_full_report_sections.sql for
-- the same statements with more detail - kept here too so a
-- brand new install gets them straight away.
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS project_team_members (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT NOT NULL,
    name        VARCHAR(255) NOT NULL,
    role        VARCHAR(255) NOT NULL,
    sort_order  INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS figma_pages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    page_name    VARCHAR(255) NOT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

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

CREATE TABLE IF NOT EXISTS website_pages_designed (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    page_name    VARCHAR(255) NOT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS dashboard_pages_designed (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    page_name    VARCHAR(255) NOT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

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

CREATE TABLE IF NOT EXISTS database_tables (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    table_name   VARCHAR(255) NOT NULL,
    created_date DATE NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

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
