-- =========================================================
-- Migration: add editable content fields to project_reports
-- ---------------------------------------------------------
-- Run this once against an EXISTING database (one that was
-- created before this change). A brand new install doesn't
-- need this -- database.sql already includes these columns.
--
-- If a column already exists, MySQL will show a "Duplicate
-- column name" error for that line -- that's fine, it just
-- means it's already applied; skip that line and run the rest.
-- =========================================================

ALTER TABLE project_reports ADD COLUMN custom_subject VARCHAR(255) DEFAULT NULL AFTER report_date;
ALTER TABLE project_reports ADD COLUMN custom_intro   TEXT DEFAULT NULL AFTER custom_subject;
ALTER TABLE project_reports ADD COLUMN custom_closing TEXT DEFAULT NULL AFTER custom_intro;
