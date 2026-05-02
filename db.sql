-- ============================================================
-- db.sql — Esperon Dairy Farm
-- Normalized to 3NF (Third Normal Form)
-- Database: esperon_dairy_farm
--
-- ENTITY CLASSIFICATION
-- ─────────────────────
-- Strong Entities (exist independently, have their own PK):
--   Address, Worker, Cow, Customer, Orders, staff_reports
--
-- Weak Entities (depend on a strong entity, use composite PK):
--   reminders  →  depends on Worker (created_by)
--                 PK: (reminder_id, created_by)
--                 Cannot exist without a Worker row
--
-- Derived Structures (views — no stored data, computed on demand):
--   vw_order_details  →  derived from Orders + Customer + Address + Cow + Worker
--   vw_staff_reports  →  derived from staff_reports + Worker
--   vw_reminders      →  derived from reminders + Worker (creator + assignee)
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
-- [STRONG ENTITY] Address
-- Stores physical locations only.
-- 1NF: each column is atomic
-- 2NF: all non-key columns depend only on Address_ID
-- 3NF: no transitive dependencies
-- ============================================================
CREATE TABLE IF NOT EXISTS Address (
    Address_ID  INT          NOT NULL AUTO_INCREMENT,
    Address     VARCHAR(255) NOT NULL,
    CONSTRAINT pk_address PRIMARY KEY (Address_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- [STRONG ENTITY] Products
-- Dairy farm products available for purchase.
-- stock_qty decreases when a cart order is placed.
-- is_active = 0 hides the product from the shop without deleting it.
-- ============================================================
CREATE TABLE IF NOT EXISTS Products (
    product_id   INT            NOT NULL AUTO_INCREMENT,
    name         VARCHAR(150)   NOT NULL,
    description  TEXT           NULL,
    price        DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    stock_qty    INT            NOT NULL DEFAULT 0,
    unit         VARCHAR(30)    NOT NULL DEFAULT 'pcs',  -- e.g. pcs, L, kg
    image_url    VARCHAR(255)   NULL,
    is_active    TINYINT(1)     NOT NULL DEFAULT 1,
    created_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_products PRIMARY KEY (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- [STRONG ENTITY] Cart
-- One active cart per customer at a time.
-- status: 'active' while shopping, 'checked_out' after purchase.
-- ============================================================
CREATE TABLE IF NOT EXISTS Cart (
    cart_id     INT      NOT NULL AUTO_INCREMENT,
    CID         INT      NOT NULL,   -- FK → Customer
    status      ENUM('active','checked_out') NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_cart     PRIMARY KEY (cart_id),
    CONSTRAINT fk_cart_cid FOREIGN KEY (CID)
        REFERENCES Customer (CID) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- [STRONG ENTITY] CartItems
-- Line items inside a cart.
-- unit_price is a snapshot of the product price at add-to-cart time.
-- ============================================================
CREATE TABLE IF NOT EXISTS CartItems (
    item_id     INT            NOT NULL AUTO_INCREMENT,
    cart_id     INT            NOT NULL,   -- FK → Cart
    product_id  INT            NOT NULL,   -- FK → Products
    quantity    INT            NOT NULL DEFAULT 1,
    unit_price  DECIMAL(10,2)  NOT NULL DEFAULT 0.00,  -- price snapshot
    CONSTRAINT pk_cart_items      PRIMARY KEY (item_id),
    CONSTRAINT fk_ci_cart         FOREIGN KEY (cart_id)
        REFERENCES Cart (cart_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ci_product      FOREIGN KEY (product_id)
        REFERENCES Products (product_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_cart_product    UNIQUE (cart_id, product_id)  -- one row per product per cart
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for shop queries
CREATE INDEX IF NOT EXISTS idx_products_active  ON Products(is_active);
CREATE INDEX IF NOT EXISTS idx_cart_cid_status  ON Cart(CID, status);
CREATE INDEX IF NOT EXISTS idx_cart_items_cart  ON CartItems(cart_id);

-- ============================================================
-- [STRONG ENTITY] Worker
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
-- [STRONG ENTITY] Cow
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
-- [STRONG ENTITY] Customer
-- Contact_Num belongs to the customer, not the address (3NF).
-- Address_ID is a FK to Address — one address can serve many
-- customers (e.g. same barangay).
-- Relationship: Customer →(N:1)→ Address
-- ============================================================
CREATE TABLE IF NOT EXISTS Customer (
    CID           INT          NOT NULL AUTO_INCREMENT,
    Customer_Name VARCHAR(100) NOT NULL,
    Address_ID    INT          NOT NULL,   -- FK → Address
    Contact_Num   VARCHAR(20)  NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_customer  PRIMARY KEY (CID),
    CONSTRAINT fk_cust_addr FOREIGN KEY (Address_ID)
        REFERENCES Address (Address_ID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT   -- cannot delete an address while a customer uses it
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- [STRONG ENTITY] Orders
-- Relationships:
--   Orders →(N:1)→ Customer   (fk_ord_cust)
--   Orders →(N:1)→ Cow        (fk_ord_cow)
--   Orders →(N:1)→ Worker     (fk_ord_worker)
--
-- quantity_liters  — how many liters were ordered
-- unit_price       — price per liter at time of order (snapshot)
-- total_price      — GENERATED column: quantity_liters × unit_price
--                    Computed by MySQL; never inserted/updated manually
-- status           — order lifecycle
-- ============================================================
CREATE TABLE IF NOT EXISTS Orders (
    Order_ID        INT           NOT NULL AUTO_INCREMENT,
    CID             INT           NOT NULL,   -- FK → Customer
    Cow_ID          INT           NOT NULL,   -- FK → Cow
    Worker_ID       INT           NOT NULL,   -- FK → Worker
    Order_Type      VARCHAR(100)  NOT NULL,
    Order_Date      DATE          NOT NULL,
    quantity_liters DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    unit_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_price     DECIMAL(10,2) GENERATED ALWAYS AS (quantity_liters * unit_price) STORED,
    status          ENUM('pending','confirmed','delivered','cancelled') NOT NULL DEFAULT 'pending',
    notes           TEXT          NULL,
    CONSTRAINT pk_order      PRIMARY KEY (Order_ID),
    CONSTRAINT fk_ord_cust   FOREIGN KEY (CID)       REFERENCES Customer (CID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_cow    FOREIGN KEY (Cow_ID)    REFERENCES Cow (Cow_ID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_worker FOREIGN KEY (Worker_ID) REFERENCES Worker (Worker_ID)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- [WEAK ENTITY] reminders
-- Depends on Worker — a reminder cannot exist without the
-- worker who created it.
--
-- Weak entity rules applied:
--   • created_by is NOT NULL and FK → Worker ON DELETE CASCADE
--     (deleting the worker deletes all their reminders)
--   • Composite PRIMARY KEY (reminder_id, created_by) formally
--     declares the partial key (reminder_id) + identifying owner
--   • assigned_to is nullable FK → Worker ON DELETE SET NULL
--     (assigning a reminder to a worker is optional)
--
-- Relationships:
--   reminders →(N:1, identifying)→ Worker  via created_by
--   reminders →(N:1, optional)→   Worker  via assigned_to
-- ============================================================
CREATE TABLE IF NOT EXISTS reminders (
    reminder_id INT          NOT NULL AUTO_INCREMENT,
    created_by  INT          NOT NULL,   -- identifying FK → Worker (weak entity owner)
    assigned_to INT          NULL,       -- optional FK → Worker
    title       VARCHAR(255) NOT NULL,
    description TEXT         NULL,
    due_date    DATETIME     NOT NULL,
    status      ENUM('pending','completed') NOT NULL DEFAULT 'pending',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Composite PK: reminder_id alone is the partial key;
    -- created_by is the identifying attribute from the owner (Worker).
    CONSTRAINT pk_reminders PRIMARY KEY (reminder_id, created_by),

    CONSTRAINT fk_reminder_creator  FOREIGN KEY (created_by)
        REFERENCES Worker (Worker_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,   -- reminder dies when its owner worker is deleted

    CONSTRAINT fk_reminder_assignee FOREIGN KEY (assigned_to)
        REFERENCES Worker (Worker_ID)
        ON UPDATE CASCADE
        ON DELETE SET NULL   -- unassign reminder if the assigned worker is deleted
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- [STRONG ENTITY] staff_reports
-- Depends on Worker for context but is a strong entity because
-- it has business meaning independent of any single worker
-- (reports are kept for audit even if a worker is removed).
--
-- worker_name and worker_role are NOT stored here — they are
-- derived at query time via vw_staff_reports (3NF compliance).
--
-- Relationship: staff_reports →(N:1)→ Worker  via worker_id
-- ============================================================
CREATE TABLE IF NOT EXISTS staff_reports (
    report_id   INT          NOT NULL AUTO_INCREMENT,
    worker_id   INT          NOT NULL,   -- FK → Worker
    report_type VARCHAR(50)  NOT NULL DEFAULT 'Daily Report',
    title       VARCHAR(255) NOT NULL,
    content     TEXT         NOT NULL,
    status      ENUM('pending','reviewed','acknowledged') NOT NULL DEFAULT 'pending',
    admin_note  TEXT         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_reports    PRIMARY KEY (report_id),
    CONSTRAINT fk_rep_worker FOREIGN KEY (worker_id)
        REFERENCES Worker (Worker_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- [DERIVED STRUCTURE] VIEW: vw_order_details
--
-- This is NOT a stored entity — it is a derived structure.
-- Source (strong entity): Orders
-- Joined entities: Customer, Address, Cow, Worker
--
-- Purpose: expose complete order information without storing
-- any redundant fields (Customer_Name, Address, Cow name,
-- Worker name) in the Orders table itself.
-- The view is recomputed on every query from live table data.
-- ============================================================
CREATE OR REPLACE VIEW vw_order_details AS
SELECT
    -- Order identity
    o.Order_ID,
    o.Order_Type,
    o.Order_Date,
    o.quantity_liters,
    o.unit_price,
    o.total_price,
    o.status          AS Order_Status,
    o.notes           AS Order_Notes,

    -- Customer details (derived from Customer + Address via FK)
    o.CID,
    c.Customer_Name,
    c.Contact_Num,
    a.Address,

    -- Cow details (derived from Cow via FK)
    o.Cow_ID,
    cw.Cow,
    cw.Breed,
    cw.Production_Liters,

    -- Worker details (derived from Worker via FK)
    o.Worker_ID,
    w.Worker          AS Worker_Name,
    w.Worker_Role

FROM       Orders   o
JOIN       Customer c  ON o.CID       = c.CID
JOIN       Address  a  ON c.Address_ID = a.Address_ID
JOIN       Cow      cw ON o.Cow_ID    = cw.Cow_ID
JOIN       Worker   w  ON o.Worker_ID  = w.Worker_ID;

-- ============================================================
-- [DERIVED STRUCTURE] VIEW: vw_staff_reports
--
-- This is NOT a stored entity — it is a derived structure.
-- Source (strong entity): staff_reports
-- Joined entity: Worker
--
-- Purpose: expose worker_name and worker_role alongside report
-- data without storing those fields redundantly in staff_reports.
-- Satisfies 3NF — no transitive dependency in the base table.
-- ============================================================
CREATE OR REPLACE VIEW vw_staff_reports AS
SELECT
    -- Report fields (from strong entity staff_reports)
    r.report_id,
    r.report_type,
    r.title,
    r.content,
    r.status,
    r.admin_note,
    r.created_at,
    r.updated_at,

    -- Worker identity (FK stored in staff_reports)
    r.worker_id,

    -- Worker details (derived via JOIN — not stored in staff_reports)
    w.Worker      AS worker_name,
    w.Worker_Role AS worker_role

FROM staff_reports r
JOIN Worker w ON r.worker_id = w.Worker_ID;

-- ============================================================
-- [DERIVED STRUCTURE] VIEW: vw_reminders
--
-- This is NOT a stored entity — it is a derived structure.
-- Source (weak entity): reminders
-- Joined entity: Worker (twice — creator and assignee)
--
-- Purpose: resolve created_by and assigned_to integer FKs to
-- human-readable worker names without storing names in reminders.
-- ============================================================
CREATE OR REPLACE VIEW vw_reminders AS
SELECT
    -- Reminder fields (from weak entity reminders)
    r.reminder_id,
    r.title,
    r.description,
    r.due_date,
    r.status,
    r.created_at,

    -- Creator (identifying owner — NOT NULL FK)
    r.created_by,
    creator.Worker        AS created_by_name,
    creator.Worker_Role   AS created_by_role,

    -- Assignee (optional FK — may be NULL)
    r.assigned_to,
    assignee.Worker       AS assigned_to_name

FROM      reminders r
JOIN      Worker creator  ON r.created_by  = creator.Worker_ID
LEFT JOIN Worker assignee ON r.assigned_to = assignee.Worker_ID;

-- ============================================================
-- INDEXES
-- Covering indexes on the most common filter/join columns.
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_worker_name        ON Worker(Worker);
CREATE INDEX IF NOT EXISTS idx_worker_role        ON Worker(Worker_Role);
CREATE INDEX IF NOT EXISTS idx_worker_approval    ON Worker(approval_status);
CREATE INDEX IF NOT EXISTS idx_customer_address   ON Customer(Address_ID);
CREATE INDEX IF NOT EXISTS idx_customer_name      ON Customer(Customer_Name);
CREATE INDEX IF NOT EXISTS idx_orders_cid         ON Orders(CID);
CREATE INDEX IF NOT EXISTS idx_orders_cow_id      ON Orders(Cow_ID);
CREATE INDEX IF NOT EXISTS idx_orders_worker_id   ON Orders(Worker_ID);
CREATE INDEX IF NOT EXISTS idx_orders_date        ON Orders(Order_Date);
CREATE INDEX IF NOT EXISTS idx_orders_status      ON Orders(status);
CREATE INDEX IF NOT EXISTS idx_reminders_due      ON reminders(due_date);
CREATE INDEX IF NOT EXISTS idx_reminders_status   ON reminders(status);
CREATE INDEX IF NOT EXISTS idx_reminders_assignee ON reminders(assigned_to);
CREATE INDEX IF NOT EXISTS idx_reports_worker     ON staff_reports(worker_id);
CREATE INDEX IF NOT EXISTS idx_reports_status     ON staff_reports(status);
CREATE INDEX IF NOT EXISTS idx_cow_active         ON Cow(is_active);
CREATE INDEX IF NOT EXISTS idx_cow_health         ON Cow(Health_Status);

-- ============================================================
-- SAMPLE DATA
-- ============================================================
INSERT INTO Address (Address_ID, Address) VALUES
    (301, 'Casisang, Malaybalay City'),
    (302, 'San Jose, Malaybalay City')
ON DUPLICATE KEY UPDATE Address = VALUES(Address);

-- Default password is 'password' — hash generated with PHP password_hash('password', PASSWORD_DEFAULT)
INSERT INTO Worker (Worker_ID, Worker, Worker_Role, Email, Password, approval_status) VALUES
    (201, 'Mark',    'Staff', 'mark@esperon.farm',    '$2y$10$wsIsunJBcBMNSwjt6Ptz6Owr9n3bqHi9IihvpiTTz7Tmhd4wWJNLC', 'approved'),
    (202, 'Patrick', 'Admin', 'patrick@esperon.farm', '$2y$10$wsIsunJBcBMNSwjt6Ptz6Owr9n3bqHi9IihvpiTTz7Tmhd4wWJNLC', 'approved')
ON DUPLICATE KEY UPDATE
    Worker          = VALUES(Worker),
    Worker_Role     = VALUES(Worker_Role),
    Email           = VALUES(Email),
    Password        = VALUES(Password),
    approval_status = VALUES(approval_status);

INSERT INTO Cow (Cow_ID, Cow, Breed, Date_Of_Birth, Production_Liters, Health_Status) VALUES
    (101, 'Cow1', 'Holstein', '2020-03-15', 10.00, 'Healthy'),
    (102, 'Cow2', 'Jersey',   '2019-07-22', 15.00, 'Healthy')
ON DUPLICATE KEY UPDATE Cow = VALUES(Cow);

INSERT INTO Customer (CID, Customer_Name, Address_ID, Contact_Num) VALUES
    (1, 'Ana',  301, '09010000001'),
    (2, 'Juan', 302, '09020000002')
ON DUPLICATE KEY UPDATE Customer_Name = VALUES(Customer_Name);

INSERT INTO Orders (CID, Cow_ID, Worker_ID, Order_Type, Order_Date, quantity_liters, unit_price, status) VALUES
    (1, 101, 201, 'Milk',   '2026-03-26', 5.00,  50.00, 'delivered'),
    (2, 102, 202, 'Milk',   '2026-03-21', 8.00,  50.00, 'delivered'),
    (1, 102, 202, 'Cheese', '2026-04-01', 3.50,  75.00, 'delivered'),
    (1, 101, 201, 'Yogurt', '2026-04-10', 4.00,  60.00, 'confirmed'),
    (2, 101, 201, 'Butter', '2026-04-05', 6.00,  55.00, 'delivered'),
    (2, 102, 202, 'Milk',   '2026-04-15', 10.00, 50.00, 'pending'),
    (1, 102, 201, 'Milk',   '2026-04-20', 7.50,  50.00, 'confirmed')
ON DUPLICATE KEY UPDATE Order_Type = VALUES(Order_Type);

INSERT INTO reminders (created_by, assigned_to, title, description, due_date, status) VALUES
    (202, 201, 'Morning Feeding',  'Feed all cows at 6AM',          '2026-05-02 06:00:00', 'pending'),
    (202, NULL, 'Vet Checkup',     'Schedule quarterly vet visit',  '2026-05-10 09:00:00', 'pending');

-- ── Sample Products ───────────────────────────────────────
INSERT INTO Products (product_id, name, description, price, stock_qty, unit) VALUES
    (1, 'Fresh Whole Milk',    'Farm-fresh whole milk, collected daily from our healthy herd.',         55.00, 100, 'L'),
    (2, 'Aged Cheddar Cheese', 'Rich, sharp cheddar aged for 3 months. Perfect for cooking or snacking.', 180.00, 40, 'pcs'),
    (3, 'Creamy Butter',       'Pure churned butter made from fresh cream. Unsalted.',                  120.00, 60, 'pcs'),
    (4, 'Natural Yogurt',      'Thick, creamy yogurt with live cultures. No added sugar.',              75.00, 50, 'pcs'),
    (5, 'Fresh Cream',         'Heavy whipping cream, ideal for desserts and cooking.',                 90.00, 35, 'L'),
    (6, 'Skim Milk',           'Low-fat skim milk, great for health-conscious customers.',              45.00, 80, 'L'),
    (7, 'Mozzarella Cheese',   'Soft, fresh mozzarella. Perfect for pizza and salads.',                 160.00, 0,  'pcs')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================================
-- MIGRATION: Upgrade an existing live database
-- Safe to run multiple times (uses IF NOT EXISTS / IF EXISTS).
-- ============================================================

-- ── 1. Orders: add new columns ────────────────────────────────
ALTER TABLE Orders
    ADD COLUMN IF NOT EXISTS quantity_liters DECIMAL(8,2)  NOT NULL DEFAULT 0.00 AFTER Order_Date,
    ADD COLUMN IF NOT EXISTS unit_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER quantity_liters,
    ADD COLUMN IF NOT EXISTS status ENUM('pending','confirmed','delivered','cancelled')
                                             NOT NULL DEFAULT 'pending' AFTER unit_price,
    ADD COLUMN IF NOT EXISTS notes           TEXT NULL AFTER status;

-- total_price as a generated column (add only if missing)
SET @col_tp = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'Orders'
      AND COLUMN_NAME  = 'total_price'
);
SET @sql_tp = IF(@col_tp = 0,
    'ALTER TABLE Orders ADD COLUMN total_price DECIMAL(10,2) GENERATED ALWAYS AS (quantity_liters * unit_price) STORED AFTER unit_price',
    'SELECT 1'
);
PREPARE stmt_tp FROM @sql_tp; EXECUTE stmt_tp; DEALLOCATE PREPARE stmt_tp;

-- ── 2. Orders: enforce FK constraints ────────────────────────
SET @fk_oc = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'Orders' AND CONSTRAINT_NAME = 'fk_ord_cust');
SET @s_oc = IF(@fk_oc = 0,
    'ALTER TABLE Orders ADD CONSTRAINT fk_ord_cust FOREIGN KEY (CID) REFERENCES Customer (CID) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1');
PREPARE p_oc FROM @s_oc; EXECUTE p_oc; DEALLOCATE PREPARE p_oc;

SET @fk_ocw = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'Orders' AND CONSTRAINT_NAME = 'fk_ord_cow');
SET @s_ocw = IF(@fk_ocw = 0,
    'ALTER TABLE Orders ADD CONSTRAINT fk_ord_cow FOREIGN KEY (Cow_ID) REFERENCES Cow (Cow_ID) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1');
PREPARE p_ocw FROM @s_ocw; EXECUTE p_ocw; DEALLOCATE PREPARE p_ocw;

SET @fk_ow = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'Orders' AND CONSTRAINT_NAME = 'fk_ord_worker');
SET @s_ow = IF(@fk_ow = 0,
    'ALTER TABLE Orders ADD CONSTRAINT fk_ord_worker FOREIGN KEY (Worker_ID) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1');
PREPARE p_ow FROM @s_ow; EXECUTE p_ow; DEALLOCATE PREPARE p_ow;

-- ── 3. Customer: add FK to Address ───────────────────────────
SET @fk_ca = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND CONSTRAINT_NAME = 'fk_cust_addr');
SET @s_ca = IF(@fk_ca = 0,
    'ALTER TABLE customer ADD CONSTRAINT fk_cust_addr FOREIGN KEY (Address_ID) REFERENCES address (Address_ID) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1');
PREPARE p_ca FROM @s_ca; EXECUTE p_ca; DEALLOCATE PREPARE p_ca;

-- Customer: add created_at if missing
ALTER TABLE Customer
    ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- ── 4. Cow: add extended attributes ──────────────────────────
ALTER TABLE Cow
    ADD COLUMN IF NOT EXISTS Breed         VARCHAR(100) NULL AFTER Cow,
    ADD COLUMN IF NOT EXISTS Date_Of_Birth DATE         NULL AFTER Breed,
    ADD COLUMN IF NOT EXISTS Health_Status ENUM('Healthy','Sick','Under Treatment','Retired')
                                           NOT NULL DEFAULT 'Healthy' AFTER Production_Liters,
    ADD COLUMN IF NOT EXISTS is_active     TINYINT(1)   NOT NULL DEFAULT 1 AFTER Health_Status,
    ADD COLUMN IF NOT EXISTS notes         TEXT         NULL AFTER is_active;

-- ── 5. Worker: change Worker_Role from VARCHAR to ENUM ───────
--    Safe only if all existing values are 'Admin' or 'Staff'
ALTER TABLE Worker
    MODIFY COLUMN Worker_Role ENUM('Admin','Staff') NOT NULL DEFAULT 'Staff';

-- ── 6. reminders: ensure created_by column exists ────────────
ALTER TABLE reminders
    ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER reminder_id;

-- Backfill NULL created_by with the first Admin worker
UPDATE reminders
SET created_by = (SELECT Worker_ID FROM Worker WHERE Worker_Role = 'Admin' LIMIT 1)
WHERE created_by IS NULL;

ALTER TABLE reminders MODIFY COLUMN created_by INT NOT NULL;

-- reminders: add assigned_to column
ALTER TABLE reminders
    ADD COLUMN IF NOT EXISTS assigned_to INT NULL AFTER created_by;

-- ── 7. reminders: upgrade to composite PK (weak entity) ──────
--    Drop the old single-column PK and replace with composite PK.
--    AUTO_INCREMENT must be removed before dropping PK in MySQL.
SET @pk_rem = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME         = 'reminders'
      AND CONSTRAINT_NAME    = 'pk_reminders'
      AND CONSTRAINT_TYPE    = 'PRIMARY KEY');

-- Only migrate if the PK is still the old single-column one
SET @pk_cols = (
    SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA  = DATABASE()
      AND TABLE_NAME    = 'reminders'
      AND CONSTRAINT_NAME = 'PRIMARY'
);
-- If PK has only 1 column, upgrade it to composite
SET @sql_pk = IF(@pk_cols = 1,
    'ALTER TABLE reminders MODIFY reminder_id INT NOT NULL, DROP PRIMARY KEY, ADD CONSTRAINT pk_reminders PRIMARY KEY (reminder_id, created_by)',
    'SELECT 1'
);
PREPARE stmt_pk FROM @sql_pk; EXECUTE stmt_pk; DEALLOCATE PREPARE stmt_pk;

-- Restore AUTO_INCREMENT on reminder_id after PK change
ALTER TABLE reminders MODIFY reminder_id INT NOT NULL AUTO_INCREMENT;

-- ── 8. reminders: enforce FK constraints ─────────────────────
SET @fk_rc = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'reminders' AND CONSTRAINT_NAME = 'fk_reminder_creator');
SET @s_rc = IF(@fk_rc = 0,
    'ALTER TABLE reminders ADD CONSTRAINT fk_reminder_creator FOREIGN KEY (created_by) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE',
    'SELECT 1');
PREPARE p_rc FROM @s_rc; EXECUTE p_rc; DEALLOCATE PREPARE p_rc;

SET @fk_ra = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'reminders' AND CONSTRAINT_NAME = 'fk_reminder_assignee');
SET @s_ra = IF(@fk_ra = 0,
    'ALTER TABLE reminders ADD CONSTRAINT fk_reminder_assignee FOREIGN KEY (assigned_to) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1');
PREPARE p_ra FROM @s_ra; EXECUTE p_ra; DEALLOCATE PREPARE p_ra;

-- ── 9. staff_reports: enforce FK constraint ──────────────────
SET @fk_sr = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_reports' AND CONSTRAINT_NAME = 'fk_rep_worker');
SET @s_sr = IF(@fk_sr = 0,
    'ALTER TABLE staff_reports ADD CONSTRAINT fk_rep_worker FOREIGN KEY (worker_id) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE',
    'SELECT 1');
PREPARE p_sr FROM @s_sr; EXECUTE p_sr; DEALLOCATE PREPARE p_sr;

-- ── 10. Recreate views to reflect all changes ─────────────────
-- (Views are replaced at the top of this script on fresh install;
--  run these manually if upgrading an existing database.)

CREATE OR REPLACE VIEW vw_order_details AS
SELECT
    o.Order_ID,
    o.Order_Type,
    o.Order_Date,
    o.quantity_liters,
    o.unit_price,
    o.total_price,
    o.status          AS Order_Status,
    o.notes           AS Order_Notes,
    o.CID,
    c.Customer_Name,
    c.Contact_Num,
    a.Address,
    o.Cow_ID,
    cw.Cow,
    cw.Breed,
    cw.Production_Liters,
    o.Worker_ID,
    w.Worker          AS Worker_Name,
    w.Worker_Role
FROM       Orders   o
JOIN       Customer c  ON o.CID       = c.CID
JOIN       Address  a  ON c.Address_ID = a.Address_ID
JOIN       Cow      cw ON o.Cow_ID    = cw.Cow_ID
JOIN       Worker   w  ON o.Worker_ID  = w.Worker_ID;

CREATE OR REPLACE VIEW vw_staff_reports AS
SELECT
    r.report_id,
    r.report_type,
    r.title,
    r.content,
    r.status,
    r.admin_note,
    r.created_at,
    r.updated_at,
    r.worker_id,
    w.Worker      AS worker_name,
    w.Worker_Role AS worker_role
FROM staff_reports r
JOIN Worker w ON r.worker_id = w.Worker_ID;

CREATE OR REPLACE VIEW vw_reminders AS
SELECT
    r.reminder_id,
    r.title,
    r.description,
    r.due_date,
    r.status,
    r.created_at,
    r.created_by,
    creator.Worker        AS created_by_name,
    creator.Worker_Role   AS created_by_role,
    r.assigned_to,
    assignee.Worker       AS assigned_to_name
FROM      reminders r
JOIN      Worker creator  ON r.created_by  = creator.Worker_ID
LEFT JOIN Worker assignee ON r.assigned_to = assignee.Worker_ID;

-- ============================================================
-- END OF SCRIPT
-- ============================================================
