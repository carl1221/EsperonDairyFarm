-- ============================================================
-- esperon_normalize.sql
-- Normalization migration: add AUTO_INCREMENT to all PKs
--
-- Run ONCE in phpMyAdmin or terminal:
--   mysql -u root esperon_dairy_farm < esperon_normalize.sql
-- ============================================================

USE esperon_dairy_farm;

-- Add AUTO_INCREMENT to Cow, Customer, Address primary keys
-- (Worker_ID was already fixed in esperon_fix_worker_autoinc.sql)
ALTER TABLE Cow      MODIFY Cow_ID     INT NOT NULL AUTO_INCREMENT;
ALTER TABLE Customer MODIFY CID        INT NOT NULL AUTO_INCREMENT;
ALTER TABLE Address  MODIFY Address_ID INT NOT NULL AUTO_INCREMENT;

-- ============================================================
-- END OF MIGRATION
-- ============================================================
