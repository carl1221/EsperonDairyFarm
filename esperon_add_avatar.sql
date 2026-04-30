-- ============================================================
-- esperon_add_avatar.sql
-- Migration: add Avatar column to the Worker table.
--
-- Run ONCE in phpMyAdmin or terminal:
--   mysql -u root esperon_dairy_farm < esperon_add_avatar.sql
-- ============================================================

USE esperon_dairy_farm;

ALTER TABLE Worker
    ADD COLUMN Avatar VARCHAR(255) NULL AFTER Email;

-- ============================================================
-- END OF MIGRATION
-- ============================================================
