-- ============================================================
-- esperon_add_email.sql
-- Migration: add Email column to the Worker table.
--
-- Run ONCE in phpMyAdmin or terminal:
--   mysql -u root esperon_dairy_farm < esperon_add_email.sql
-- ============================================================

USE esperon_dairy_farm;

-- Add Email column (unique, nullable for existing rows)
ALTER TABLE Worker
    ADD COLUMN Email VARCHAR(150) NULL AFTER Worker_Role;

-- Add a unique index so no two accounts share the same email
ALTER TABLE Worker
    ADD CONSTRAINT uq_worker_email UNIQUE (Email);

-- Update the two sample workers with placeholder emails
-- (change these to real addresses before you use the system)
UPDATE Worker SET Email = 'mark@esperon.farm'  WHERE Worker_ID = 201;
UPDATE Worker SET Email = 'carl@esperon.farm'  WHERE Worker_ID = 202;

-- Once you have test data, make the column NOT NULL if preferred:
-- ALTER TABLE Worker MODIFY COLUMN Email VARCHAR(150) NOT NULL;

-- ============================================================
-- END OF MIGRATION
-- ============================================================
