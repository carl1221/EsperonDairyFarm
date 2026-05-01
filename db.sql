-- ============================================================
-- db.sql — Esperon Dairy Farm
-- Complete database setup (all migrations merged)
-- Run this ONCE on a fresh install in phpMyAdmin or terminal:
--   mysql -u root < db.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS esperon_dairy_farm
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE esperon_dairy_farm;

-- ============================================================
-- TABLE: Address
-- ============================================================
CREATE TABLE IF NOT EXISTS Address (
    Address_ID  INT           NOT NULL AUTO_INCREMENT,
    Address     VARCHAR(100)  NOT NULL,
    Contact_Num VARCHAR(20)   NOT NULL,
    CONSTRAINT pk_address PRIMARY KEY (Address_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: Worker
-- ============================================================
CREATE TABLE IF NOT EXISTS Worker (
    Worker_ID       INT           NOT NULL AUTO_INCREMENT,
    Worker          VARCHAR(100)  NOT NULL,
    Worker_Role     VARCHAR(50)   NOT NULL,
    Email           VARCHAR(150)  NULL,
    Avatar          VARCHAR(255)  NULL,
    Password        VARCHAR(255)  NOT NULL DEFAULT '',
    approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    last_heartbeat  DATETIME      NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_worker       PRIMARY KEY (Worker_ID),
    CONSTRAINT uq_worker_email UNIQUE (Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: Cow
-- ============================================================
CREATE TABLE IF NOT EXISTS Cow (
    Cow_ID     INT          NOT NULL AUTO_INCREMENT,
    Cow        VARCHAR(50)  NOT NULL,
    Production VARCHAR(20)  NOT NULL,
    CONSTRAINT pk_cow PRIMARY KEY (Cow_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: Customer
-- ============================================================
CREATE TABLE IF NOT EXISTS Customer (
    CID           INT          NOT NULL AUTO_INCREMENT,
    Customer_Name VARCHAR(100) NOT NULL,
    Address_ID    INT          NOT NULL,
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
    CONSTRAINT fk_ord_cust   FOREIGN KEY (CID)       REFERENCES Customer (CID)       ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_cow    FOREIGN KEY (Cow_ID)    REFERENCES Cow (Cow_ID)         ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_worker FOREIGN KEY (Worker_ID) REFERENCES Worker (Worker_ID)   ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: Reminders
-- ============================================================
CREATE TABLE IF NOT EXISTS reminders (
    reminder_id INT          NOT NULL AUTO_INCREMENT,
    title       VARCHAR(255) NOT NULL,
    description TEXT         NULL,
    due_date    DATETIME     NOT NULL,
    status      ENUM('pending','completed') NOT NULL DEFAULT 'pending',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_reminders PRIMARY KEY (reminder_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: staff_reports
-- ============================================================
CREATE TABLE IF NOT EXISTS staff_reports (
    report_id   INT          NOT NULL AUTO_INCREMENT,
    worker_id   INT          NOT NULL,
    worker_name VARCHAR(100) NOT NULL,
    report_type VARCHAR(50)  NOT NULL DEFAULT 'Daily Report',
    title       VARCHAR(255) NOT NULL,
    content     TEXT         NOT NULL,
    status      ENUM('pending','reviewed','acknowledged') NOT NULL DEFAULT 'pending',
    admin_note  TEXT         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_reports PRIMARY KEY (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- VIEW: vw_order_details
-- ============================================================
CREATE OR REPLACE VIEW vw_order_details AS
SELECT
    o.Order_ID,
    o.CID,
    c.Customer_Name,
    a.Address,
    a.Contact_Num,
    o.Order_Type,
    o.Order_Date,
    o.Cow_ID,
    cw.Cow,
    cw.Production,
    o.Worker_ID,
    w.Worker,
    w.Worker_Role
FROM Orders o
JOIN Customer c  ON o.CID       = c.CID
JOIN Address  a  ON c.Address_ID = a.Address_ID
JOIN Cow      cw ON o.Cow_ID    = cw.Cow_ID
JOIN Worker   w  ON o.Worker_ID  = w.Worker_ID;

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

-- ============================================================
-- SAMPLE DATA
-- ============================================================
INSERT INTO Address (Address_ID, Address, Contact_Num) VALUES
    (301, 'Casisang', '901'),
    (302, 'San Jose',  '902')
ON DUPLICATE KEY UPDATE Address=VALUES(Address);

-- Default password is 'password' (bcrypt hash)
INSERT INTO Worker (Worker_ID, Worker, Worker_Role, Email, Password, approval_status) VALUES
    (201, 'Mark',    'Staff', 'mark@esperon.farm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'approved'),
    (202, 'Patrick', 'Admin', 'carl@esperon.farm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'approved')
ON DUPLICATE KEY UPDATE Worker=VALUES(Worker);

INSERT INTO Cow (Cow_ID, Cow, Production) VALUES
    (101, 'Cow1', '10L'),
    (102, 'Cow2', '15L')
ON DUPLICATE KEY UPDATE Cow=VALUES(Cow);

INSERT INTO Customer (CID, Customer_Name, Address_ID) VALUES
    (1, 'Ana',  301),
    (2, 'Juan', 302)
ON DUPLICATE KEY UPDATE Customer_Name=VALUES(Customer_Name);

INSERT INTO Orders (CID, Cow_ID, Worker_ID, Order_Type, Order_Date) VALUES
    (1, 101, 201, 'Milk', '2026-03-26'),
    (2, 102, 202, 'Milk', '2026-03-21');

-- ============================================================
-- END OF SCRIPT
-- ============================================================
