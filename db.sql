-- ============================================================
-- db.sql - Esperon Dairy Farm
-- Compatible with shared hosting (no CREATE DATABASE, no VIEWs)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Address
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Address (
    Address_ID  INT          NOT NULL AUTO_INCREMENT,
    Address     VARCHAR(255) NOT NULL,
    CONSTRAINT pk_address PRIMARY KEY (Address_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Worker
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- Cow
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- Customer
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Customer (
    CID           INT          NOT NULL AUTO_INCREMENT,
    Customer_Name VARCHAR(100) NOT NULL,
    Email         VARCHAR(150) NULL,
    Address_ID    INT          NOT NULL,
    Contact_Num   VARCHAR(20)  NOT NULL,
    Password      VARCHAR(255) NOT NULL DEFAULT '',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_customer       PRIMARY KEY (CID),
    CONSTRAINT uq_customer_email UNIQUE (Email),
    CONSTRAINT fk_cust_addr      FOREIGN KEY (Address_ID)
        REFERENCES Address (Address_ID)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Products
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Products (
    product_id   INT            NOT NULL AUTO_INCREMENT,
    name         VARCHAR(150)   NOT NULL,
    description  TEXT           NULL,
    price        DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    stock_qty    INT            NOT NULL DEFAULT 0,
    unit         VARCHAR(30)    NOT NULL DEFAULT 'pcs',
    image_url    VARCHAR(255)   NULL,
    is_active    TINYINT(1)     NOT NULL DEFAULT 1,
    created_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_products PRIMARY KEY (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Cart
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Cart (
    cart_id     INT      NOT NULL AUTO_INCREMENT,
    CID         INT      NOT NULL,
    status      ENUM('active','checked_out') NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_cart     PRIMARY KEY (cart_id),
    CONSTRAINT fk_cart_cid FOREIGN KEY (CID)
        REFERENCES Customer (CID) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- CartItems
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS CartItems (
    item_id     INT            NOT NULL AUTO_INCREMENT,
    cart_id     INT            NOT NULL,
    product_id  INT            NOT NULL,
    quantity    INT            NOT NULL DEFAULT 1,
    unit_price  DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    CONSTRAINT pk_cart_items   PRIMARY KEY (item_id),
    CONSTRAINT fk_ci_cart      FOREIGN KEY (cart_id)
        REFERENCES Cart (cart_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ci_product   FOREIGN KEY (product_id)
        REFERENCES Products (product_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_cart_product UNIQUE (cart_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- OrderTypes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS OrderTypes (
    type_id   INT          NOT NULL AUTO_INCREMENT,
    type_name VARCHAR(100) NOT NULL,
    CONSTRAINT pk_order_types PRIMARY KEY (type_id),
    CONSTRAINT uq_order_type  UNIQUE (type_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Orders
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Orders (
    Order_ID        INT           NOT NULL AUTO_INCREMENT,
    CID             INT           NOT NULL,
    Cow_ID          INT           NOT NULL,
    Worker_ID       INT           NOT NULL,
    type_id         INT           NOT NULL,
    Order_Date      DATE          NOT NULL,
    quantity_liters DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    unit_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_price     DECIMAL(10,2) GENERATED ALWAYS AS (quantity_liters * unit_price) STORED,
    status          ENUM('pending','confirmed','delivered','cancelled') NOT NULL DEFAULT 'pending',
    notes           TEXT          NULL,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_order      PRIMARY KEY (Order_ID),
    CONSTRAINT fk_ord_cust   FOREIGN KEY (CID)       REFERENCES Customer  (CID)       ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_cow    FOREIGN KEY (Cow_ID)    REFERENCES Cow       (Cow_ID)    ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_worker FOREIGN KEY (Worker_ID) REFERENCES Worker    (Worker_ID) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_type   FOREIGN KEY (type_id)   REFERENCES OrderTypes(type_id)   ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- reminders
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reminders (
    reminder_id INT          NOT NULL AUTO_INCREMENT,
    created_by  INT          NOT NULL,
    assigned_to INT          NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT         NULL,
    due_date    DATETIME     NOT NULL,
    status      ENUM('pending','completed') NOT NULL DEFAULT 'pending',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_reminders         PRIMARY KEY (reminder_id, created_by),
    CONSTRAINT fk_reminder_creator  FOREIGN KEY (created_by)
        REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_reminder_assignee FOREIGN KEY (assigned_to)
        REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- staff_reports
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- notes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notes (
    note_id     INT          NOT NULL AUTO_INCREMENT,
    author_id   INT          NOT NULL,
    text        TEXT         NOT NULL,
    category    ENUM('General','Health','Feeding','Maintenance','Finance','Other') NOT NULL DEFAULT 'General',
    entity_type ENUM('Cow','Order','Customer','Worker') NULL DEFAULT NULL,
    entity_id   INT          NULL DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_notes       PRIMARY KEY (note_id),
    CONSTRAINT fk_note_author FOREIGN KEY (author_id)
        REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- production_logs
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- product_reviews
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- Indexes
-- ------------------------------------------------------------
CREATE INDEX idx_worker_name        ON Worker(Worker);
CREATE INDEX idx_worker_role        ON Worker(Worker_Role);
CREATE INDEX idx_worker_approval    ON Worker(approval_status);
CREATE INDEX idx_customer_address   ON Customer(Address_ID);
CREATE INDEX idx_customer_name      ON Customer(Customer_Name);
CREATE INDEX idx_products_active    ON Products(is_active);
CREATE INDEX idx_cart_cid_status    ON Cart(CID, status);
CREATE INDEX idx_cart_items_cart    ON CartItems(cart_id);
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
CREATE INDEX idx_notes_entity       ON notes(entity_type, entity_id);
CREATE INDEX idx_notes_created      ON notes(created_at);
CREATE INDEX idx_notes_category     ON notes(category);
CREATE INDEX idx_prod_logs_date     ON production_logs(log_date);
CREATE INDEX idx_reviews_product    ON product_reviews(product_id);
CREATE INDEX idx_reviews_cid        ON product_reviews(CID);

-- ------------------------------------------------------------
-- Seed Data
-- ------------------------------------------------------------
INSERT INTO OrderTypes (type_name) VALUES
    ('Milk Delivery'),
    ('Cheese Order'),
    ('Butter Order'),
    ('Yogurt Order'),
    ('Cream Order'),
    ('Custom Order')
ON DUPLICATE KEY UPDATE type_name = VALUES(type_name);

INSERT INTO Address (Address_ID, Address) VALUES
    (301, 'Casisang, Malaybalay City'),
    (302, 'San Jose, Malaybalay City')
ON DUPLICATE KEY UPDATE Address = VALUES(Address);

-- Default password: 'password'
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

-- Default customer password: 'Password1'
INSERT INTO Customer (CID, Customer_Name, Email, Address_ID, Contact_Num, Password) VALUES
    (1, 'Ana',  'ana@esperon.farm',  301, '09010000001', '$2y$10$EFHPolUb1knDjjLE3e9jq.60aKM0QVoukG87pbn8OYu0WOSz4Wx7m'),
    (2, 'Juan', 'juan@esperon.farm', 302, '09020000002', '$2y$10$EFHPolUb1knDjjLE3e9jq.60aKM0QVoukG87pbn8OYu0WOSz4Wx7m')
ON DUPLICATE KEY UPDATE Customer_Name = VALUES(Customer_Name), Password = VALUES(Password);

INSERT INTO reminders (created_by, assigned_to, title, description, due_date, status) VALUES
    (202, 201, 'Morning Feeding',  'Feed all cows at 6AM',         '2026-05-02 06:00:00', 'pending'),
    (202, NULL, 'Vet Checkup',     'Schedule quarterly vet visit', '2026-05-10 09:00:00', 'pending');

INSERT INTO Products (product_id, name, description, price, stock_qty, unit) VALUES
    (1, 'Fresh Whole Milk',    'Farm-fresh whole milk, collected daily from our healthy herd.',            55.00, 100, 'L'),
    (2, 'Aged Cheddar Cheese', 'Rich, sharp cheddar aged for 3 months. Perfect for cooking or snacking.', 180.00, 40, 'pcs'),
    (3, 'Creamy Butter',       'Pure churned butter made from fresh cream. Unsalted.',                    120.00, 60, 'pcs'),
    (4, 'Natural Yogurt',      'Thick, creamy yogurt with live cultures. No added sugar.',                 75.00, 50, 'pcs'),
    (5, 'Fresh Cream',         'Heavy whipping cream, ideal for desserts and cooking.',                    90.00, 35, 'L'),
    (6, 'Skim Milk',           'Low-fat skim milk, great for health-conscious customers.',                 45.00, 80, 'L'),
    (7, 'Mozzarella Cheese',   'Soft, fresh mozzarella. Perfect for pizza and salads.',                   160.00,  0, 'pcs')
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END
-- ============================================================
