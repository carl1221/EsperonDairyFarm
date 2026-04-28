-- ============================================================
-- esperon_fix_worker_autoinc.sql
-- Fix: add AUTO_INCREMENT to Worker_ID so signup works.
--
-- Run ONCE in phpMyAdmin or terminal:
--   mysql -u root esperon_dairy_farm < esperon_fix_worker_autoinc.sql
-- ============================================================

USE esperon_dairy_farm;

ALTER TABLE Worker MODIFY Worker_ID INT NOT NULL AUTO_INCREMENT;

-- ============================================================
-- END OF MIGRATION
-- ============================================================
