-- ============================================================
-- esperon_add_reminders.sql
-- Migration: create the Reminders table.
--
-- Run ONCE in phpMyAdmin or terminal:
--   mysql -u root esperon_dairy_farm < esperon_add_reminders.sql
-- ============================================================

USE esperon_dairy_farm;

-- Create Reminders table
CREATE TABLE IF NOT EXISTS reminders (
    reminder_id  INT           NOT NULL AUTO_INCREMENT,
    title        VARCHAR(255)  NOT NULL,
    description  TEXT          NULL,
    due_date     DATETIME      NOT NULL,
    status       ENUM('pending', 'completed') NOT NULL DEFAULT 'pending',
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_reminders PRIMARY KEY (reminder_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index for sorting by due date
CREATE INDEX IF NOT EXISTS idx_reminders_due_date ON reminders(due_date);

-- Index for filtering by status
CREATE INDEX IF NOT EXISTS idx_reminders_status ON reminders(status);

-- ============================================================
-- END OF MIGRATION
-- ============================================================
