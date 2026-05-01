-- ============================================================
-- db.sql — Esperon Dairy Farm
-- Normalized to 3NF (Third Normal Form)
-- Run this ONCE on a fresh install:
--   mysql -u root < db.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS esperon_dairy_farm
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE esperon_dairy_farm;

-- ============================================================
-- TABLE: Address
-- Stores physical locations only (no contact info — that
-- belongs to the Customer who lives at the address).
-- 1NF: each column is atomic
-- 2NF: Address and Barangay depend only on Address_ID
-- 3NF: no transitive dependencies
-- ============================================================
CREATE TABLE IF NOT EXISTS Address (
    Address_ID  INT          NOT NULL AUTO_INCREMENT,
    Address     VARCHAR(100) NOT NULL,
    CONSTRAINT pk_address PRIMARY KEY (Address_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: Worker
-- Stores all system users (Admin and Staff).
-- approval_status, last_heartbeat, created_at support the
-- approval and online-monitoring features.
-- ============================================================
CREATE TABLE IF NOT EXISTS Worker (
    Worker_ID       INT          NOT NULL AUTO_INCREMENT,
    Worker          VARCHAR(100) NOT NULL,
    Worker_Role     VARCHAR(50)  NOT NULL,
    Email           VARCHAR(150) NULL,
    Avatar          VARCHAR(255) NULL,
    Password        VARCHAR(255) NOT NULL DEFAULT '',
    approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    last_heartbeat  DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_worker       PRIMARY KEY (Worker_ID),
    CONSTRAINT uq_worker_email UNIQUE (Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: Cow
-- Production is split into value + unit for 1NF atomicity.
-- e.g. instead of '10L', store production_liters = 10
-- ============================================================
CREATE TABLE IF NOT EXISTS Cow (
    Cow_ID             INT           NOT NULL AUTO_INCREMENT,
    Cow                VARCHAR(50)   NOT NULL,
    Production_Liters  DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    CONSTRAINT pk_cow PRIMARY KEY (Cow_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: Customer
-- Contact_Num moved here from Address (it belongs to the
-- customer, not the location — 3NF fix).
-- ============================================================
CREATE TABLE IF NOT EXISTS Customer (
    CID           INT          NOT NULL AUTO_INCREMENT,
    Customer_Name VARCHAR(100) NOT NULL,
    Address_ID    INT          NOT NULL,
    Contact_Num   VARCHAR(20)  NOT NULL,
    CONSTRAINT pk_customer  PRIMARY KEY (CID),
    CONSTRAINT fk_cust_addr FOREIGN KEY (Address_ID)
        REFERENCES Address (Address_ID)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: Orders
-- ============================================================
CREATE TABLE IF NOT EXISTS Orders (
    Order_ID   INT          NOT NULL AUTO_INCREMENT,
    CID        INT          NOT NULL,
    Cow_ID     INT          NOT NULL,
    Worker_ID  INT          NOT NULL,
    Order_Type VARCHAR(100) NOT NULL,
    Order_Date DATE         NOT NULL,
    CONSTRAINT pk_order      PRIMARY KEY (Order_ID),
    CONSTRAINT fk_ord_cust   FOREIGN KEY (CID)       REFERENCES Customer (CID)     ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_cow    FOREIGN KEY (Cow_ID)    REFERENCES Cow (Cow_ID)       ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_worker FOREIGN KEY (Worker_ID) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: reminders
-- Weak entity — depends on Worker (created_by).
-- A reminder has no meaning without the worker who created it.
-- ============================================================
CREATE TABLE IF NOT EXISTS reminders (
    reminder_id INT          NOT NULL AUTO_INCREMENT,
    created_by  INT          NOT NULL,              -- FK → Worker (weak entity dependency)
    title       VARCHAR(255) NOT NULL,
    description TEXT         NULL,
    due_date    DATETIME     NOT NULL,
    status      ENUM('pending','completed') NOT NULL DEFAULT 'pending',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_reminders        PRIMARY KEY (reminder_id),
    CONSTRAINT fk_reminder_worker  FOREIGN KEY (created_by)
        REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: staff_reports
-- Weak entity — depends on Worker (worker_id).
-- A report has no meaning without the staff member who wrote it.
-- ============================================================
CREATE TABLE IF NOT EXISTS staff_reports (
    report_id   INT         NOT NULL AUTO_INCREMENT,
    worker_id   INT         NOT NULL,              -- FK → Worker (weak entity)
    report_type VARCHAR(50) NOT NULL DEFAULT 'Daily Report',
    title       VARCHAR(255) NOT NULL,
    content     TEXT        NOT NULL,
    status      ENUM('pending','reviewed','acknowledged') NOT NULL DEFAULT 'pending',
    admin_note  TEXT        NULL,
    created_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_reports    PRIMARY KEY (report_id),
    CONSTRAINT fk_rep_worker FOREIGN KEY (worker_id) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- VIEW: vw_order_details
-- Updated to use Production_Liters and Customer.Contact_Num
-- ============================================================
CREATE OR REPLACE VIEW vw_order_details AS
SELECT
    o.Order_ID,
    o.CID,
    c.Customer_Name,
    a.Address,
    c.Contact_Num,
    o.Order_Type,
    o.Order_Date,
    o.Cow_ID,
    cw.Cow,
    CONCAT(cw.Production_Liters, 'L') AS Production,
    o.Worker_ID,
    w.Worker,
    w.Worker_Role
FROM Orders o
JOIN Customer c  ON o.CID       = c.CID
JOIN Address  a  ON c.Address_ID = a.Address_ID
JOIN Cow      cw ON o.Cow_ID    = cw.Cow_ID
JOIN Worker   w  ON o.Worker_ID  = w.Worker_ID;

-- ============================================================
-- VIEW: vw_staff_reports
-- Joins worker name so APIs don't need to store it redundantly
-- ============================================================
CREATE OR REPLACE VIEW vw_staff_reports AS
SELECT
    r.report_id,
    r.worker_id,
    w.Worker      AS worker_name,
    w.Worker_Role AS worker_role,
    r.report_type,
    r.title,
    r.content,
    r.status,
    r.admin_note,
    r.created_at,
    r.updated_at
FROM staff_reports r
JOIN Worker w ON r.worker_id = w.Worker_ID;

-- ============================================================
-- INDEXES
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_worker_name       ON Worker(Worker);
CREATE INDEX IF NOT EXISTS idx_worker_role       ON Worker(Worker_Role);
CREATE INDEX IF NOT EXISTS idx_worker_approval   ON Worker(approval_status);
CREATE INDEX IF NOT EXISTS idx_customer_address  ON Customer(Address_ID);
CREATE INDEX IF NOT EXISTS idx_customer_name     ON Customer(Customer_Name);
CREATE INDEX IF NOT EXISTS idx_orders_cid        ON Orders(CID);
CREATE INDEX IF NOT EXISTS idx_orders_cow_id     ON Orders(Cow_ID);
CREATE INDEX IF NOT EXISTS idx_orders_worker_id  ON Orders(Worker_ID);
CREATE INDEX IF NOT EXISTS idx_orders_date       ON Orders(Order_Date);
CREATE INDEX IF NOT EXISTS idx_reminders_due     ON reminders(due_date);
CREATE INDEX IF NOT EXISTS idx_reminders_status  ON reminders(status);
CREATE INDEX IF NOT EXISTS idx_reports_worker    ON staff_reports(worker_id);
CREATE INDEX IF NOT EXISTS idx_reports_status    ON staff_reports(status);

-- ============================================================
-- SAMPLE DATA
-- ============================================================
INSERT INTO Address (Address_ID, Address) VALUES
    (301, 'Casisang'),
    (302, 'San Jose')
ON DUPLICATE KEY UPDATE Address = VALUES(Address);

-- Default password is 'password' (bcrypt hash)
INSERT INTO Worker (Worker_ID, Worker, Worker_Role, Email, Password, approval_status) VALUES
    (201, 'Mark',    'Staff', 'mark@esperon.farm',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'approved'),
    (202, 'Patrick', 'Admin', 'patrick@esperon.farm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'approved')
ON DUPLICATE KEY UPDATE Worker = VALUES(Worker);

INSERT INTO Cow (Cow_ID, Cow, Production_Liters) VALUES
    (101, 'Cow1', 10.00),
    (102, 'Cow2', 15.00)
ON DUPLICATE KEY UPDATE Cow = VALUES(Cow);

INSERT INTO Customer (CID, Customer_Name, Address_ID, Contact_Num) VALUES
    (1, 'Ana',  301, '09010000001'),
    (2, 'Juan', 302, '09020000002')
ON DUPLICATE KEY UPDATE Customer_Name = VALUES(Customer_Name);

INSERT INTO Orders (CID, Cow_ID, Worker_ID, Order_Type, Order_Date) VALUES
    (1, 101, 201, 'Milk', '2026-03-26'),
    (2, 102, 202, 'Milk', '2026-03-21');

-- ============================================================
-- MIGRATION: Fix existing live database
-- Run these if upgrading from an older version.
-- Safe to run multiple times (uses IF NOT EXISTS / IF EXISTS).
-- ============================================================

-- 1. Add created_by to reminders (links reminders → Worker)
ALTER TABLE reminders
    ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER reminder_id;

-- Set existing reminders to the first Admin worker
UPDATE reminders SET created_by = (
    SELECT Worker_ID FROM Worker WHERE Worker_Role = 'Admin' LIMIT 1
) WHERE created_by IS NULL;

-- Make it NOT NULL and add FK
ALTER TABLE reminders MODIFY COLUMN created_by INT NOT NULL;

-- Add FK only if it doesn't exist
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'reminders'
    AND CONSTRAINT_NAME = 'fk_reminder_worker'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE reminders ADD CONSTRAINT fk_reminder_worker FOREIGN KEY (created_by) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Add FK on staff_reports → Worker if missing
SET @fk2 = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'staff_reports'
    AND CONSTRAINT_NAME = 'fk_rep_worker'
);
SET @sql2 = IF(@fk2 = 0,
    'ALTER TABLE staff_reports ADD CONSTRAINT fk_rep_worker FOREIGN KEY (worker_id) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- 3. Add FK on customer → address if missing
SET @fk3 = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'customer'
    AND CONSTRAINT_NAME = 'fk_cust_addr'
);
SET @sql3 = IF(@fk3 = 0,
    'ALTER TABLE customer ADD CONSTRAINT fk_cust_addr FOREIGN KEY (Address_ID) REFERENCES address (Address_ID) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1'
);
PREPARE stmt3 FROM @sql3; EXECUTE stmt3; DEALLOCATE PREPARE stmt3;

-- ============================================================
-- END OF SCRIPT
-- ============================================================
