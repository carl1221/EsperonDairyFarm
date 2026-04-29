-- ============================================================
-- esperon_customer_portal.sql
-- Migration: add customer login support
--
-- Run ONCE in phpMyAdmin or terminal:
--   mysql -u root esperon_dairy_farm < esperon_customer_portal.sql
-- ============================================================

USE esperon_dairy_farm;

-- Add login columns to Customer table
ALTER TABLE Customer
  ADD COLUMN IF NOT EXISTS Email    VARCHAR(150) NULL AFTER Customer_Name,
  ADD COLUMN IF NOT EXISTS Password VARCHAR(255) NULL AFTER Email,
  ADD COLUMN IF NOT EXISTS Phone    VARCHAR(20)  NULL AFTER Password;

-- Unique email constraint
ALTER TABLE Customer
  ADD CONSTRAINT IF NOT EXISTS uq_customer_email UNIQUE (Email);

-- Seed sample customer credentials (password = 'password')
UPDATE Customer
  SET Email    = 'ana@esperon.farm',
      Password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
      Phone    = '09011111111'
  WHERE CID = 1;

UPDATE Customer
  SET Email    = 'juan@esperon.farm',
      Password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
      Phone    = '09022222222'
  WHERE CID = 2;

-- ============================================================
-- END OF MIGRATION
-- ============================================================
