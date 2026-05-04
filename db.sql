-- ============================================================
-- db.sql â€” Esperon Dairy Farm
-- Normalized to 3NF (Third Normal Form)
-- Database: esperon_dairy_farm
--
-- ENTITY CLASSIFICATION
-- â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
-- Strong Entities (exist independently, have their own PK):
--   Address, Worker, Cow, Customer, Orders, staff_reports
--
-- Weak Entities (depend on a strong entity, use composite PK):
--   reminders  â†’  depends on Worker (created_by)
--                 PK: (reminder_id, created_by)
--                 Cannot exist without a Worker row
--
-- Derived Structures (views â€” no stored data, computed on demand):
--   vw_order_details  â†’  derived from Orders + Customer + Address + Cow + Worker
--   vw_staff_reports  â†’  derived from staff_reports + Worker
--   vw_reminders      â†’  derived from reminders + Worker (creator + assignee)
--
-- Run on a fresh install:
--   mysql -u root < db.sql
--
-- Run on an existing database (upgrade):
--   See the MIGRATION section at the bottom.
-- ============================================================

-- CREATE DATABASE and USE removed for shared hosting compatibility
-- Import this file directly into your existing database via phpMyAdmin

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
-- Address_ID is a FK to Address â€” one address can serve many
-- customers (e.g. same barangay).
-- Relationship: Customer â†’(N:1)â†’ Address
-- ============================================================
CREATE TABLE IF NOT EXISTS Customer (
    CID           INT          NOT NULL AUTO_INCREMENT,
    Customer_Name VARCHAR(100) NOT NULL,
    Email         VARCHAR(150) NULL,                 -- for login and password reset
    Address_ID    INT          NOT NULL,   -- FK â†’ Address
    Contact_Num   VARCHAR(20)  NOT NULL,
    Password      VARCHAR(255) NOT NULL DEFAULT '',  -- bcrypt hash; set on signup
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_customer       PRIMARY KEY (CID),
    CONSTRAINT uq_customer_email UNIQUE (Email),
    CONSTRAINT fk_cust_addr      FOREIGN KEY (Address_ID)
        REFERENCES Address (Address_ID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT   -- cannot delete an address while a customer uses it
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
    CID         INT      NOT NULL,   -- FK â†’ Customer
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
    cart_id     INT            NOT NULL,   -- FK â†’ Cart
    product_id  INT            NOT NULL,   -- FK â†’ Products
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
CREATE INDEX idx_products_active  ON Products(is_active);
CREATE INDEX idx_cart_cid_status  ON Cart(CID, status);
CREATE INDEX idx_cart_items_cart  ON CartItems(cart_id);

-- ============================================================
-- [LOOKUP TABLE] OrderTypes
-- Normalizes Order_Type out of Orders (was a free-text VARCHAR).
-- 3NF: removes the partial dependency on a non-key attribute.
-- ============================================================
CREATE TABLE IF NOT EXISTS OrderTypes (
    type_id   INT         NOT NULL AUTO_INCREMENT,
    type_name VARCHAR(100) NOT NULL,
    CONSTRAINT pk_order_types PRIMARY KEY (type_id),
    CONSTRAINT uq_order_type  UNIQUE (type_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO OrderTypes (type_name) VALUES
    ('Milk Delivery'),
    ('Cheese Order'),
    ('Butter Order'),
    ('Yogurt Order'),
    ('Cream Order'),
    ('Custom Order')
ON DUPLICATE KEY UPDATE type_name = VALUES(type_name);

-- ============================================================
-- [STRONG ENTITY] Orders
-- Relationships:
--   Orders â†’(N:1)â†’ Customer   (fk_ord_cust)
--   Orders â†’(N:1)â†’ Cow        (fk_ord_cow)
--   Orders â†’(N:1)â†’ Worker     (fk_ord_worker)
--
-- quantity_liters  â€” how many liters were ordered
-- unit_price       â€” price per liter at time of order (snapshot)
-- total_price      â€” GENERATED column: quantity_liters Ã— unit_price
--                    Computed by MySQL; never inserted/updated manually
-- status           â€” order lifecycle
-- ============================================================
CREATE TABLE IF NOT EXISTS Orders (
    Order_ID        INT           NOT NULL AUTO_INCREMENT,
    CID             INT           NOT NULL,   -- FK â†’ Customer
    Cow_ID          INT           NOT NULL,   -- FK â†’ Cow
    Worker_ID       INT           NOT NULL,   -- FK â†’ Worker
    type_id         INT           NOT NULL,   -- FK â†’ OrderTypes (replaces free-text Order_Type)
    Order_Date      DATE          NOT NULL,
    quantity_liters DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    unit_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_price     DECIMAL(10,2) GENERATED ALWAYS AS (quantity_liters * unit_price) STORED,
    status          ENUM('pending','confirmed','delivered','cancelled') NOT NULL DEFAULT 'pending',
    notes           TEXT          NULL,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_order      PRIMARY KEY (Order_ID),
    CONSTRAINT fk_ord_cust   FOREIGN KEY (CID)       REFERENCES Customer (CID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_cow    FOREIGN KEY (Cow_ID)    REFERENCES Cow (Cow_ID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_worker FOREIGN KEY (Worker_ID) REFERENCES Worker (Worker_ID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_type   FOREIGN KEY (type_id)   REFERENCES OrderTypes (type_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- [WEAK ENTITY] reminders
-- Depends on Worker â€” a reminder cannot exist without the
-- worker who created it.
--
-- Weak entity rules applied:
--   â€¢ created_by is NOT NULL and FK â†’ Worker ON DELETE CASCADE
--     (deleting the worker deletes all their reminders)
--   â€¢ Composite PRIMARY KEY (reminder_id, created_by) formally
--     declares the partial key (reminder_id) + identifying owner
--   â€¢ assigned_to is nullable FK â†’ Worker ON DELETE SET NULL
--     (assigning a reminder to a worker is optional)
--
-- Relationships:
--   reminders â†’(N:1, identifying)â†’ Worker  via created_by
--   reminders â†’(N:1, optional)â†’   Worker  via assigned_to
-- ============================================================
CREATE TABLE IF NOT EXISTS reminders (
    reminder_id INT          NOT NULL AUTO_INCREMENT,
    created_by  INT          NOT NULL,   -- identifying FK â†’ Worker (weak entity owner)
    assigned_to INT          NULL,       -- optional FK â†’ Worker
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
-- worker_name and worker_role are NOT stored here â€” they are
-- derived at query time via vw_staff_reports (3NF compliance).
--
-- Relationship: staff_reports â†’(N:1)â†’ Worker  via worker_id
-- ============================================================
CREATE TABLE IF NOT EXISTS staff_reports (
    report_id   INT          NOT NULL AUTO_INCREMENT,
    worker_id   INT          NOT NULL,   -- FK â†’ Worker
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
-- This is NOT a stored entity â€” it is a derived structure.
-- Source (strong entity): Orders
-- Joined entities: Customer, Address, Cow, Worker
--
-- Purpose: expose complete order information without storing
-- any redundant fields (Customer_Name, Address, Cow name,
-- Worker name) in the Orders table itself.
-- The view is recomputed on every query from live table data.
-- ============================================================

-- ============================================================
-- [DERIVED STRUCTURE] VIEW: vw_staff_reports
--
-- This is NOT a stored entity â€” it is a derived structure.
-- Source (strong entity): staff_reports
-- Joined entity: Worker
--
-- Purpose: expose worker_name and worker_role alongside report
-- data without storing those fields redundantly in staff_reports.
-- Satisfies 3NF â€” no transitive dependency in the base table.
-- ============================================================

-- ============================================================
-- [DERIVED STRUCTURE] VIEW: vw_reminders
--
-- This is NOT a stored entity â€” it is a derived structure.
-- Source (weak entity): reminders
-- Joined entity: Worker (twice â€” creator and assignee)
--
-- Purpose: resolve created_by and assigned_to integer FKs to
-- human-readable worker names without storing names in reminders.
-- ============================================================

-- ============================================================
-- [STRONG ENTITY] notes
-- Persistent announcements/notes shared across all staff.
-- author_id FK â†’ Worker satisfies 3NF â€” author name is derived
-- at query time via JOIN, not stored redundantly here.
--
-- category    â€” classifies the note (General, Health, Feeding, etc.)
-- entity_type â€” optional polymorphic link to a related entity
--               ('Cow', 'Order', 'Customer', 'Worker', or NULL for general)
-- entity_id   â€” the PK of the linked entity row (NULL when entity_type is NULL)
-- updated_at  â€” audit trail for edits
--
-- Relationships:
--   notes â†’(N:1)â†’ Worker  via author_id  (who wrote it)
--   notes â†’(N:1)â†’ Cow     via entity_id  (when entity_type = 'Cow')
--   notes â†’(N:1)â†’ Orders  via entity_id  (when entity_type = 'Order')
--   notes â†’(N:1)â†’ Customer via entity_id (when entity_type = 'Customer')
-- ============================================================
CREATE TABLE IF NOT EXISTS notes (
    note_id     INT          NOT NULL AUTO_INCREMENT,
    author_id   INT          NOT NULL,   -- FK â†’ Worker
    text        TEXT         NOT NULL,
    category    ENUM('General','Health','Feeding','Maintenance','Finance','Other')
                             NOT NULL DEFAULT 'General',
    entity_type ENUM('Cow','Order','Customer','Worker') NULL DEFAULT NULL,
    entity_id   INT          NULL DEFAULT NULL,   -- FK to the entity identified by entity_type
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_notes       PRIMARY KEY (note_id),
    CONSTRAINT fk_note_author FOREIGN KEY (author_id)
        REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- entity_type + entity_id index for fast "notes about Cow #5" queries
CREATE INDEX idx_notes_entity  ON notes(entity_type, entity_id);
CREATE INDEX idx_notes_created ON notes(created_at);
CREATE INDEX idx_notes_category ON notes(category);

-- ============================================================
-- [DERIVED STRUCTURE] VIEW: vw_notes
-- Resolves author_id FK to worker name â€” satisfies 3NF by not
-- storing the author name redundantly in the notes table.
-- Also exposes category, entity_type, entity_id, and updated_at.
-- ============================================================

-- ============================================================
-- INDEXES
-- Covering indexes on the most common filter/join columns.
-- ============================================================
CREATE INDEX idx_worker_name        ON Worker(Worker);
CREATE INDEX idx_worker_role        ON Worker(Worker_Role);
CREATE INDEX idx_worker_approval    ON Worker(approval_status);
CREATE INDEX idx_customer_address   ON Customer(Address_ID);
CREATE INDEX idx_customer_name      ON Customer(Customer_Name);
CREATE INDEX idx_orders_cid         ON Orders(CID);
CREATE INDEX idx_orders_cow_id      ON Orders(Cow_ID);
CREATE INDEX idx_orders_worker_id   ON Orders(Worker_ID);
CREATE INDEX idx_orders_date        ON Orders(Order_Date);
CREATE INDEX idx_orders_status      ON Orders(status);
CREATE INDEX idx_reminders_due      ON reminders(due_date);
CREATE INDEX idx_reminders_status   ON reminders(status);
CREATE INDEX idx_reminders_assignee ON reminders(assigned_to);
CREATE INDEX idx_reports_worker     ON staff_reports(worker_id);
CREATE INDEX idx_reports_status     ON staff_reports(status);
CREATE INDEX idx_cow_active         ON Cow(is_active);
CREATE INDEX idx_cow_health         ON Cow(Health_Status);

-- ============================================================
-- SAMPLE DATA
-- ============================================================
INSERT INTO Address (Address_ID, Address) VALUES
    (301, 'Casisang, Malaybalay City'),
    (302, 'San Jose, Malaybalay City')
ON DUPLICATE KEY UPDATE Address = VALUES(Address);

-- Default password is 'password' â€” hash generated with PHP password_hash('password', PASSWORD_DEFAULT)
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

-- Default customer password is 'Password1' â€” admin should change via Customers > ðŸ”‘ Password
INSERT INTO Customer (CID, Customer_Name, Email, Address_ID, Contact_Num, Password) VALUES
    (1, 'Ana',  'ana@esperon.farm',  301, '09010000001', '$2y$10$EFHPolUb1knDjjLE3e9jq.60aKM0QVoukG87pbn8OYu0WOSz4Wx7m'),
    (2, 'Juan', 'juan@esperon.farm', 302, '09020000002', '$2y$10$EFHPolUb1knDjjLE3e9jq.60aKM0QVoukG87pbn8OYu0WOSz4Wx7m')
ON DUPLICATE KEY UPDATE Customer_Name = VALUES(Customer_Name), Password = VALUES(Password);

-- Orders are created by staff and customers through the system â€” no sample data.

INSERT INTO reminders (created_by, assigned_to, title, description, due_date, status) VALUES
    (202, 201, 'Morning Feeding',  'Feed all cows at 6AM',          '2026-05-02 06:00:00', 'pending'),
    (202, NULL, 'Vet Checkup',     'Schedule quarterly vet visit',  '2026-05-10 09:00:00', 'pending');

-- â”€â”€ Sample Products â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
-- [STRONG ENTITY] production_logs
-- Daily milk production per cow â€” enables trend tracking.
-- Composite unique key prevents duplicate entries per cow per day.
-- ============================================================
CREATE TABLE IF NOT EXISTS production_logs (
    log_id      INT           NOT NULL AUTO_INCREMENT,
    cow_id      INT           NOT NULL,
    log_date    DATE          NOT NULL,
    liters      DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    notes       TEXT          NULL,
    recorded_by INT           NOT NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_prod_logs PRIMARY KEY (log_id),
    CONSTRAINT uq_cow_date  UNIQUE (cow_id, log_date),
    CONSTRAINT fk_pl_cow    FOREIGN KEY (cow_id)
        REFERENCES Cow (Cow_ID) ON DELETE CASCADE,
    CONSTRAINT fk_pl_worker FOREIGN KEY (recorded_by)
        REFERENCES Worker (Worker_ID) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_prod_logs_date ON production_logs(log_date);

-- ============================================================
-- [STRONG ENTITY] product_reviews
-- Customers can leave one review per product.
-- is_verified_purchase = 1 when the customer has actually bought it.
-- ============================================================
CREATE TABLE IF NOT EXISTS product_reviews (
    review_id            INT           NOT NULL AUTO_INCREMENT,
    product_id           INT           NOT NULL,
    CID                  INT           NOT NULL,
    rating               TINYINT       NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title                VARCHAR(150)  NULL,
    comment              TEXT          NULL,
    is_verified_purchase TINYINT(1)    NOT NULL DEFAULT 0,
    created_at           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_reviews         PRIMARY KEY (review_id),
    CONSTRAINT uq_review_per_cust UNIQUE (product_id, CID),
    CONSTRAINT fk_rev_product     FOREIGN KEY (product_id) REFERENCES Products (product_id) ON DELETE CASCADE,
    CONSTRAINT fk_rev_customer    FOREIGN KEY (CID)        REFERENCES Customer (CID)        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_reviews_product ON product_reviews(product_id);
CREATE INDEX idx_reviews_cid     ON product_reviews(CID);

-- ============================================================
-- END OF SCRIPT
-- ============================================================
