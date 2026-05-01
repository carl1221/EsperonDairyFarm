-- ============================================================
-- db.sql — Esperon Dairy Farm
-- Normalized to 3NF (Third Normal Form)
-- Database: esperon_dairy_farm
--
-- Run on a fresh install:
--   mysql -u root < db.sql
--
-- Run on an existing database (upgrade):
--   See the MIGRATION section at the bottom.
-- ============================================================

CREATE DATABASE IF NOT EXISTS esperon_dairy_farm
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE esperon_dairy_farm;

-- ============================================================
-- TABLE: Address
-- Stores physical locations only.
-- 1NF: each column is atomic
-- 2NF: all columns depend only on Address_ID
-- 3NF: no transitive dependencies
-- ============================================================
CREATE TABLE IF NOT EXISTS Address (
    Address_ID  INT          NOT NULL AUTO_INCREMENT,
    Address     VARCHAR(255) NOT NULL,
    CONSTRAINT pk_address PRIMARY KEY (Address_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: Worker
-- Stores all system users (Admin and Staff).
-- Worker_Role uses ENUM to enforce valid values at DB level.
-- approval_status, last_heartbeat, created_at support the
-- approval workflow and online-monitoring features.
-- ============================================================
CREATE TABLE IF NOT EXISTS Worker (
    Worker_ID       INT          NOT NULL AUTO_INCREMENT,
    Worker          VARCHAR(100) NOT NULL,
    Worker_Role     ENUM('Admin','Staff') NOT NULL DEFAULT 'Staff',
    Email           VARCHAR(150) NULL,
    Avatar          VARCHAR(255) NULL,
    Password        VARCHAR(255) NOT NULL DEFAULT '',
    approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    last_heartbeat  DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_worker       PRIMARY KEY (Worker_ID),
    CONSTRAINT uq_worker_email UNIQUE (Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: Cow
-- Extended with breed, date of birth, health status, and
-- an active flag so retired cows are kept for historical orders.
-- Production_Liters is the average daily yield.
-- ============================================================
CREATE TABLE IF NOT EXISTS Cow (
    Cow_ID             INT           NOT NULL AUTO_INCREMENT,
    Cow                VARCHAR(100)  NOT NULL,
    Breed              VARCHAR(100)  NULL,
    Date_Of_Birth      DATE          NULL,
    Production_Liters  DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    Health_Status      ENUM('Healthy','Sick','Under Treatment','Retired') NOT NULL DEFAULT 'Healthy',
    is_active          TINYINT(1)    NOT NULL DEFAULT 1,
    notes              TEXT          NULL,
    CONSTRAINT pk_cow PRIMARY KEY (Cow_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: Customer
-- Contact_Num belongs to the customer, not the address (3NF).
-- Address_ID is a FK to Address — one address can serve many
-- customers (e.g. same barangay).
-- ============================================================
CREATE TABLE IF NOT EXISTS Customer (
    CID           INT          NOT NULL AUTO_INCREMENT,
    Customer_Name VARCHAR(100) NOT NULL,
    Address_ID    INT          NOT NULL,
    Contact_Num   VARCHAR(20)  NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_customer  PRIMARY KEY (CID),
    CONSTRAINT fk_cust_addr FOREIGN KEY (Address_ID)
        REFERENCES Address (Address_ID)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: Orders
-- quantity_liters  — how many liters were ordered
-- unit_price       — price per liter at time of order (snapshot)
-- total_price      — computed as quantity_liters × unit_price
-- status           — lifecycle of the order
-- ============================================================
CREATE TABLE IF NOT EXISTS Orders (
    Order_ID        INT             NOT NULL AUTO_INCREMENT,
    CID             INT             NOT NULL,
    Cow_ID          INT             NOT NULL,
    Worker_ID       INT             NOT NULL,
    Order_Type      VARCHAR(100)    NOT NULL,
    Order_Date      DATE            NOT NULL,
    quantity_liters DECIMAL(8,2)    NOT NULL DEFAULT 0.00,
    unit_price      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    total_price     DECIMAL(10,2)   GENERATED ALWAYS AS (quantity_liters * unit_price) STORED,
    status          ENUM('pending','confirmed','delivered','cancelled') NOT NULL DEFAULT 'pending',
    notes           TEXT            NULL,
    CONSTRAINT pk_order      PRIMARY KEY (Order_ID),
    CONSTRAINT fk_ord_cust   FOREIGN KEY (CID)       REFERENCES Customer (CID)     ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_cow    FOREIGN KEY (Cow_ID)    REFERENCES Cow (Cow_ID)       ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_worker FOREIGN KEY (Worker_ID) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: reminders
-- created_by  — the worker who created the reminder (weak entity)
-- assigned_to — the worker responsible for completing it (nullable)
--               NULL means it is a general/admin reminder
-- ============================================================
CREATE TABLE IF NOT EXISTS reminders (
    reminder_id INT          NOT NULL AUTO_INCREMENT,
    created_by  INT          NOT NULL,
    assigned_to INT          NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT         NULL,
    due_date    DATETIME     NOT NULL,
    status      ENUM('pending','completed') NOT NULL DEFAULT 'pending',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_reminders          PRIMARY KEY (reminder_id),
    CONSTRAINT fk_reminder_creator   FOREIGN KEY (created_by)
        REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_reminder_assignee  FOREIGN KEY (assigned_to)
        REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: staff_reports
-- worker_name and worker_role are NOT stored here — they are
-- derived at query time via the vw_staff_reports view (3NF).
-- ============================================================
CREATE TABLE IF NOT EXISTS staff_reports (
    report_id   INT          NOT NULL AUTO_INCREMENT,
    worker_id   INT          NOT NULL,
    report_type VARCHAR(50)  NOT NULL DEFAULT 'Daily Report',
    title       VARCHAR(255) NOT NULL,
    content     TEXT         NOT NULL,
    status      ENUM('pending','reviewed','acknowledged') NOT NULL DEFAULT 'pending',
    admin_note  TEXT         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_reports    PRIMARY KEY (report_id),
    CONSTRAINT fk_rep_worker FOREIGN KEY (worker_id)
        REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VIEW: vw_order_details
-- Joins all related tables so the API never needs raw JOINs.
-- total_price is the generated column from Orders.
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
    o.quantity_liters,
    o.unit_price,
    o.total_price,
    o.status          AS Order_Status,
    o.notes           AS Order_Notes,
    o.Cow_ID,
    cw.Cow,
    cw.Breed,
    cw.Production_Liters,
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
-- Exposes worker_name and worker_role without storing them
-- redundantly in staff_reports (satisfies 3NF).
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
-- VIEW: vw_reminders
-- Resolves both created_by and assigned_to to worker names.
-- ============================================================
CREATE OR REPLACE VIEW vw_reminders AS
SELECT
    r.reminder_id,
    r.created_by,
    creator.Worker   AS created_by_name,
    r.assigned_to,
    assignee.Worker  AS assigned_to_name,
    r.title,
    r.description,
    r.due_date,
    r.status,
    r.created_at
FROM reminders r
JOIN   Worker creator  ON r.created_by  = creator.Worker_ID
LEFT JOIN Worker assignee ON r.assigned_to = assignee.Worker_ID;

-- ============================================================
-- INDEXES
-- Covering indexes on the most common filter/join columns.
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
CREATE INDEX IF NOT EXISTS idx_orders_status     ON Orders(status);
CREATE INDEX IF NOT EXISTS idx_reminders_due     ON reminders(due_date);
CREATE INDEX IF NOT EXISTS idx_reminders_status  ON reminders(status);
CREATE INDEX IF NOT EXISTS idx_reminders_assignee ON reminders(assigned_to);
CREATE INDEX IF NOT EXISTS idx_reports_worker    ON staff_reports(worker_id);
CREATE INDEX IF NOT EXISTS idx_reports_status    ON staff_reports(status);
CREATE INDEX IF NOT EXISTS idx_cow_active        ON Cow(is_active);
CREATE INDEX IF NOT EXISTS idx_cow_health        ON Cow(Health_Status);

-- ============================================================
-- SAMPLE DATA
-- ============================================================
INSERT INTO Address (Address_ID, Address) VALUES
    (301, 'Casisang, Malaybalay City'),
    (302, 'San Jose, Malaybalay City')
ON DUPLICATE KEY UPDATE Address = VALUES(Address);

-- Default password is 'password' (bcrypt hash)
INSERT INTO Worker (Worker_ID, Worker, Worker_Role, Email, Password, approval_status) VALUES
    (201, 'Mark',    'Staff', 'mark@esperon.farm',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'approved'),
    (202, 'Patrick', 'Admin', 'patrick@esperon.farm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'approved')
ON DUPLICATE KEY UPDATE Worker = VALUES(Worker);

INSERT INTO Cow (Cow_ID, Cow, Breed, Date_Of_Birth, Production_Liters, Health_Status) VALUES
    (101, 'Cow1', 'Holstein', '2020-03-15', 10.00, 'Healthy'),
    (102, 'Cow2', 'Jersey',   '2019-07-22', 15.00, 'Healthy')
ON DUPLICATE KEY UPDATE Cow = VALUES(Cow);

INSERT INTO Customer (CID, Customer_Name, Address_ID, Contact_Num) VALUES
    (1, 'Ana',  301, '09010000001'),
    (2, 'Juan', 302, '09020000002')
ON DUPLICATE KEY UPDATE Customer_Name = VALUES(Customer_Name);

INSERT INTO Orders (CID, Cow_ID, Worker_ID, Order_Type, Order_Date, quantity_liters, unit_price, status) VALUES
    (1, 101, 201, 'Milk', '2026-03-26', 5.00, 50.00, 'delivered'),
    (2, 102, 202, 'Milk', '2026-03-21', 8.00, 50.00, 'delivered');

INSERT INTO reminders (created_by, assigned_to, title, description, due_date, status) VALUES
    (202, 201, 'Morning Feeding', 'Feed all cows at 6AM', '2026-05-02 06:00:00', 'pending'),
    (202, NULL, 'Vet Checkup', 'Schedule quarterly vet visit', '2026-05-10 09:00:00', 'pending');

-- ============================================================
-- MIGRATION: Upgrade an existing live database
-- Safe to run multiple times (uses IF NOT EXISTS / IF EXISTS).
-- ============================================================

-- 1. Orders: add quantity_liters, unit_price, total_price, status, notes
ALTER TABLE Orders
    ADD COLUMN IF NOT EXISTS quantity_liters DECIMAL(8,2)  NOT NULL DEFAULT 0.00 AFTER Order_Date,
    ADD COLUMN IF NOT EXISTS unit_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER quantity_liters,
    ADD COLUMN IF NOT EXISTS status          ENUM('pending','confirmed','delivered','cancelled') NOT NULL DEFAULT 'pending' AFTER unit_price,
    ADD COLUMN IF NOT EXISTS notes           TEXT NULL AFTER status;

-- total_price as a generated column (add only if missing)
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Orders'
    AND COLUMN_NAME = 'total_price'
);
SET @sql_gen = IF(@col_exists = 0,
    'ALTER TABLE Orders ADD COLUMN total_price DECIMAL(10,2) GENERATED ALWAYS AS (quantity_liters * unit_price) STORED AFTER unit_price',
    'SELECT 1'
);
PREPARE stmt_gen FROM @sql_gen; EXECUTE stmt_gen; DEALLOCATE PREPARE stmt_gen;

-- 2. Cow: add extended attributes
ALTER TABLE Cow
    ADD COLUMN IF NOT EXISTS Breed         VARCHAR(100) NULL AFTER Cow,
    ADD COLUMN IF NOT EXISTS Date_Of_Birth DATE         NULL AFTER Breed,
    ADD COLUMN IF NOT EXISTS Health_Status ENUM('Healthy','Sick','Under Treatment','Retired') NOT NULL DEFAULT 'Healthy' AFTER Production_Liters,
    ADD COLUMN IF NOT EXISTS is_active     TINYINT(1)   NOT NULL DEFAULT 1 AFTER Health_Status,
    ADD COLUMN IF NOT EXISTS notes         TEXT         NULL AFTER is_active;

-- 3. Worker: change Worker_Role from VARCHAR to ENUM
--    (safe only if existing values are 'Admin' or 'Staff')
ALTER TABLE Worker
    MODIFY COLUMN Worker_Role ENUM('Admin','Staff') NOT NULL DEFAULT 'Staff';

-- 4. reminders: add created_by (if upgrading from very old schema)
ALTER TABLE reminders
    ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER reminder_id;

UPDATE reminders SET created_by = (
    SELECT Worker_ID FROM Worker WHERE Worker_Role = 'Admin' LIMIT 1
) WHERE created_by IS NULL;

ALTER TABLE reminders MODIFY COLUMN created_by INT NOT NULL;

-- 5. reminders: add assigned_to FK
ALTER TABLE reminders
    ADD COLUMN IF NOT EXISTS assigned_to INT NULL AFTER created_by;

-- Add FKs only if they don't already exist
SET @fk1 = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'reminders' AND CONSTRAINT_NAME = 'fk_reminder_creator');
SET @s1 = IF(@fk1 = 0,
    'ALTER TABLE reminders ADD CONSTRAINT fk_reminder_creator FOREIGN KEY (created_by) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE',
    'SELECT 1');
PREPARE p1 FROM @s1; EXECUTE p1; DEALLOCATE PREPARE p1;

SET @fk2 = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'reminders' AND CONSTRAINT_NAME = 'fk_reminder_assignee');
SET @s2 = IF(@fk2 = 0,
    'ALTER TABLE reminders ADD CONSTRAINT fk_reminder_assignee FOREIGN KEY (assigned_to) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1');
PREPARE p2 FROM @s2; EXECUTE p2; DEALLOCATE PREPARE p2;

-- 6. staff_reports: add FK if missing
SET @fk3 = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_reports' AND CONSTRAINT_NAME = 'fk_rep_worker');
SET @s3 = IF(@fk3 = 0,
    'ALTER TABLE staff_reports ADD CONSTRAINT fk_rep_worker FOREIGN KEY (worker_id) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE',
    'SELECT 1');
PREPARE p3 FROM @s3; EXECUTE p3; DEALLOCATE PREPARE p3;

-- 7. Customer: add FK to Address if missing
SET @fk4 = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND CONSTRAINT_NAME = 'fk_cust_addr');
SET @s4 = IF(@fk4 = 0,
    'ALTER TABLE customer ADD CONSTRAINT fk_cust_addr FOREIGN KEY (Address_ID) REFERENCES address (Address_ID) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1');
PREPARE p4 FROM @s4; EXECUTE p4; DEALLOCATE PREPARE p4;

-- 8. Customer: add created_at if missing
ALTER TABLE Customer
    ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- ============================================================
-- END OF SCRIPT
-- ============================================================
