-- ============================================================
-- esperon_approval_online.sql
-- Adds approval_status, last_heartbeat, and created_at columns
-- to the Worker table for the User Signup Approval System
-- and Online Staff Monitoring features.
-- ============================================================

-- Add columns only if they don't exist
ALTER TABLE Worker 
  ADD COLUMN IF NOT EXISTS approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  ADD COLUMN IF NOT EXISTS last_heartbeat DATETIME NULL,
  ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Approve ALL existing workers (run this even if already run — safe to repeat)
UPDATE Worker SET approval_status = 'approved';

-- Index for approval queries
CREATE INDEX IF NOT EXISTS idx_worker_approval ON Worker(approval_status);

-- ============================================================
-- QUICK FIX: If staff are locked out, run this line manually:
-- UPDATE Worker SET approval_status = 'approved';
-- ============================================================
