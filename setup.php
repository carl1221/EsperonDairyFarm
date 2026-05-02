<?php
// ============================================================
// setup.php — Esperon Dairy Farm
// Run this ONCE after cloning or pulling the repo.
// Applies all missing database changes safely.
// Safe to run multiple times — uses IF NOT EXISTS / ON DUPLICATE KEY.
//
// Usage: http://localhost/EsperonDairyFarm/setup.php
//        OR: php setup.php (from project root)
// ============================================================

// ── Output helper ─────────────────────────────────────────
$isCli = php_sapi_name() === 'cli';
function out(string $msg, string $type = 'ok'): void {
    global $isCli;
    $icons = ['ok' => '✓', 'skip' => '–', 'err' => '✗', 'head' => '══'];
    $icon  = $icons[$type] ?? '·';
    if ($isCli) {
        echo "$icon $msg\n";
    } else {
        $colors = ['ok'=>'#27ae60','skip'=>'#8a7f72','err'=>'#c0392b','head'=>'#2980b9'];
        $color  = $colors[$type] ?? '#2a1f15';
        echo "<div style='font-family:monospace;font-size:0.9rem;color:{$color};padding:2px 0;'>$icon $msg</div>";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Esperon Setup</title></head><body style='background:#faf6f0;padding:32px;max-width:700px;margin:0 auto;font-family:Lato,sans-serif;'>";
    echo "<h2 style='font-family:Playfair Display,serif;color:#2a1f15;'>🐄 Esperon Dairy Farm — Setup</h2>";
    echo "<div style='background:rgba(255,255,255,0.6);border:1px solid #e8dfd2;border-radius:12px;padding:20px 24px;'>";
}

// ── DB Connection ──────────────────────────────────────────
require_once __DIR__ . '/dairy_farm_backend/config/database.php';
try {
    $db = getConnection();
    out("Database connection OK (esperon_dairy_farm)");
} catch (Exception $e) {
    out("Cannot connect to database: " . $e->getMessage(), 'err');
    if (!$isCli) echo "</div></body></html>";
    exit(1);
}

function run(PDO $db, string $label, string $sql): void {
    try {
        $db->exec($sql);
        out($label);
    } catch (PDOException $e) {
        // 1060=dup col, 1061=dup key, 1050=table exists, 1091=can't drop
        if (in_array($e->errorInfo[1], [1060,1061,1050,1091,1068], true)) {
            out("$label (already exists)", 'skip');
        } else {
            out("$label — " . $e->getMessage(), 'err');
        }
    }
}

out("", 'head');
out("STEP 1 — Core tables", 'head');

// ── Address ────────────────────────────────────────────────
run($db, "Table: Address", "CREATE TABLE IF NOT EXISTS Address (
    Address_ID INT NOT NULL AUTO_INCREMENT,
    Address    VARCHAR(255) NOT NULL,
    CONSTRAINT pk_address PRIMARY KEY (Address_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── Worker ─────────────────────────────────────────────────
run($db, "Table: Worker", "CREATE TABLE IF NOT EXISTS Worker (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── Cow ────────────────────────────────────────────────────
run($db, "Table: Cow", "CREATE TABLE IF NOT EXISTS Cow (
    Cow_ID             INT           NOT NULL AUTO_INCREMENT,
    Cow                VARCHAR(100)  NOT NULL,
    Breed              VARCHAR(100)  NULL,
    Date_Of_Birth      DATE          NULL,
    Production_Liters  DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    Health_Status      ENUM('Healthy','Sick','Under Treatment','Retired') NOT NULL DEFAULT 'Healthy',
    is_active          TINYINT(1)    NOT NULL DEFAULT 1,
    notes              TEXT          NULL,
    CONSTRAINT pk_cow PRIMARY KEY (Cow_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── Customer ───────────────────────────────────────────────
run($db, "Table: Customer", "CREATE TABLE IF NOT EXISTS Customer (
    CID           INT          NOT NULL AUTO_INCREMENT,
    Customer_Name VARCHAR(100) NOT NULL,
    Address_ID    INT          NOT NULL,
    Contact_Num   VARCHAR(20)  NOT NULL,
    Password      VARCHAR(255) NOT NULL DEFAULT '',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_customer  PRIMARY KEY (CID),
    CONSTRAINT fk_cust_addr FOREIGN KEY (Address_ID)
        REFERENCES Address (Address_ID) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── Orders ─────────────────────────────────────────────────
run($db, "Table: Orders", "CREATE TABLE IF NOT EXISTS Orders (
    Order_ID        INT           NOT NULL AUTO_INCREMENT,
    CID             INT           NOT NULL,
    Cow_ID          INT           NOT NULL,
    Worker_ID       INT           NOT NULL,
    Order_Type      VARCHAR(100)  NOT NULL,
    Order_Date      DATE          NOT NULL,
    quantity_liters DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    unit_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_price     DECIMAL(10,2) GENERATED ALWAYS AS (quantity_liters * unit_price) STORED,
    status          ENUM('pending','confirmed','delivered','cancelled') NOT NULL DEFAULT 'pending',
    notes           TEXT          NULL,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_order      PRIMARY KEY (Order_ID),
    CONSTRAINT fk_ord_cust   FOREIGN KEY (CID)       REFERENCES Customer (CID)     ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_cow    FOREIGN KEY (Cow_ID)    REFERENCES Cow (Cow_ID)       ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ord_worker FOREIGN KEY (Worker_ID) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── reminders ──────────────────────────────────────────────
run($db, "Table: reminders", "CREATE TABLE IF NOT EXISTS reminders (
    reminder_id INT          NOT NULL AUTO_INCREMENT,
    created_by  INT          NOT NULL,
    assigned_to INT          NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT         NULL,
    due_date    DATETIME     NOT NULL,
    status      ENUM('pending','completed') NOT NULL DEFAULT 'pending',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_reminders         PRIMARY KEY (reminder_id, created_by),
    CONSTRAINT fk_reminder_creator  FOREIGN KEY (created_by)  REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_reminder_assignee FOREIGN KEY (assigned_to) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── staff_reports ──────────────────────────────────────────
run($db, "Table: staff_reports", "CREATE TABLE IF NOT EXISTS staff_reports (
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
    CONSTRAINT fk_rep_worker FOREIGN KEY (worker_id) REFERENCES Worker (Worker_ID) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── Products ───────────────────────────────────────────────
run($db, "Table: Products", "CREATE TABLE IF NOT EXISTS Products (
    product_id  INT            NOT NULL AUTO_INCREMENT,
    name        VARCHAR(150)   NOT NULL,
    description TEXT           NULL,
    price       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    stock_qty   INT            NOT NULL DEFAULT 0,
    unit        VARCHAR(30)    NOT NULL DEFAULT 'pcs',
    image_url   VARCHAR(255)   NULL,
    is_active   TINYINT(1)     NOT NULL DEFAULT 1,
    created_at  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_products PRIMARY KEY (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── Cart ───────────────────────────────────────────────────
run($db, "Table: Cart", "CREATE TABLE IF NOT EXISTS Cart (
    cart_id    INT      NOT NULL AUTO_INCREMENT,
    CID        INT      NOT NULL,
    status     ENUM('active','checked_out') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_cart     PRIMARY KEY (cart_id),
    CONSTRAINT fk_cart_cid FOREIGN KEY (CID) REFERENCES Customer (CID) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── CartItems ──────────────────────────────────────────────
run($db, "Table: CartItems", "CREATE TABLE IF NOT EXISTS CartItems (
    item_id    INT            NOT NULL AUTO_INCREMENT,
    cart_id    INT            NOT NULL,
    product_id INT            NOT NULL,
    quantity   INT            NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    CONSTRAINT pk_cart_items   PRIMARY KEY (item_id),
    CONSTRAINT fk_ci_cart      FOREIGN KEY (cart_id)    REFERENCES Cart (cart_id)         ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ci_product   FOREIGN KEY (product_id) REFERENCES Products (product_id)  ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_cart_product UNIQUE (cart_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── notes ──────────────────────────────────────────────────
run($db, "Table: notes", "CREATE TABLE IF NOT EXISTS notes (
    note_id    INT          NOT NULL AUTO_INCREMENT,
    author_id  INT          NOT NULL,
    author     VARCHAR(100) NOT NULL,
    text       TEXT         NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_notes PRIMARY KEY (note_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── production_logs ────────────────────────────────────────
run($db, "Table: production_logs", "CREATE TABLE IF NOT EXISTS production_logs (
    log_id      INT           NOT NULL AUTO_INCREMENT,
    cow_id      INT           NOT NULL,
    log_date    DATE          NOT NULL,
    liters      DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    notes       TEXT          NULL,
    recorded_by INT           NOT NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_prod_logs PRIMARY KEY (log_id),
    CONSTRAINT uq_cow_date  UNIQUE (cow_id, log_date),
    CONSTRAINT fk_pl_cow    FOREIGN KEY (cow_id)      REFERENCES Cow (Cow_ID)       ON DELETE CASCADE,
    CONSTRAINT fk_pl_worker FOREIGN KEY (recorded_by) REFERENCES Worker (Worker_ID) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

out("", 'head');
out("STEP 2 — Column migrations (safe, skips if already exists)", 'head');

// Add columns that may be missing on older installs
$colMigrations = [
    ["Customer", "Password",   "ALTER TABLE Customer ADD COLUMN IF NOT EXISTS Password VARCHAR(255) NOT NULL DEFAULT '' AFTER Contact_Num"],
    ["Customer", "created_at", "ALTER TABLE Customer ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"],
    ["Orders",   "updated_at", "ALTER TABLE Orders   ADD COLUMN IF NOT EXISTS updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER notes"],
    ["Cow",      "Breed",      "ALTER TABLE Cow ADD COLUMN IF NOT EXISTS Breed VARCHAR(100) NULL AFTER Cow"],
    ["Cow",      "Date_Of_Birth","ALTER TABLE Cow ADD COLUMN IF NOT EXISTS Date_Of_Birth DATE NULL AFTER Breed"],
    ["Cow",      "Health_Status","ALTER TABLE Cow ADD COLUMN IF NOT EXISTS Health_Status ENUM('Healthy','Sick','Under Treatment','Retired') NOT NULL DEFAULT 'Healthy' AFTER Production_Liters"],
    ["Cow",      "is_active",  "ALTER TABLE Cow ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER Health_Status"],
    ["Cow",      "notes",      "ALTER TABLE Cow ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER is_active"],
    ["Orders",   "quantity_liters","ALTER TABLE Orders ADD COLUMN IF NOT EXISTS quantity_liters DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER Order_Date"],
    ["Orders",   "unit_price", "ALTER TABLE Orders ADD COLUMN IF NOT EXISTS unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER quantity_liters"],
    ["Orders",   "status",     "ALTER TABLE Orders ADD COLUMN IF NOT EXISTS status ENUM('pending','confirmed','delivered','cancelled') NOT NULL DEFAULT 'pending' AFTER unit_price"],
    ["Orders",   "notes",      "ALTER TABLE Orders ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER status"],
];

foreach ($colMigrations as [$table, $col, $sql]) {
    run($db, "Column: $table.$col", $sql);
}

// Worker_Role ENUM migration
run($db, "Worker.Worker_Role → ENUM", "ALTER TABLE Worker MODIFY COLUMN Worker_Role ENUM('Admin','Staff') NOT NULL DEFAULT 'Staff'");

out("", 'head');
out("STEP 3 — Views", 'head');

$views = [
    "vw_order_details" => "CREATE OR REPLACE VIEW vw_order_details AS
        SELECT o.Order_ID, o.Order_Type, o.Order_Date, o.quantity_liters, o.unit_price,
               o.total_price, o.status AS Order_Status, o.notes AS Order_Notes, o.updated_at AS Order_Updated,
               o.CID, c.Customer_Name, c.Contact_Num, a.Address,
               o.Cow_ID, cw.Cow, cw.Breed, cw.Production_Liters,
               o.Worker_ID, w.Worker AS Worker_Name, w.Worker_Role
        FROM Orders o
        JOIN Customer c ON o.CID=c.CID JOIN Address a ON c.Address_ID=a.Address_ID
        JOIN Cow cw ON o.Cow_ID=cw.Cow_ID JOIN Worker w ON o.Worker_ID=w.Worker_ID",

    "vw_staff_reports" => "CREATE OR REPLACE VIEW vw_staff_reports AS
        SELECT r.report_id, r.report_type, r.title, r.content, r.status, r.admin_note,
               r.created_at, r.updated_at, r.worker_id, w.Worker AS worker_name, w.Worker_Role AS worker_role
        FROM staff_reports r JOIN Worker w ON r.worker_id=w.Worker_ID",

    "vw_reminders" => "CREATE OR REPLACE VIEW vw_reminders AS
        SELECT r.reminder_id, r.title, r.description, r.due_date, r.status, r.created_at,
               r.created_by, creator.Worker AS created_by_name, creator.Worker_Role AS created_by_role,
               r.assigned_to, assignee.Worker AS assigned_to_name
        FROM reminders r
        JOIN Worker creator ON r.created_by=creator.Worker_ID
        LEFT JOIN Worker assignee ON r.assigned_to=assignee.Worker_ID",
];

foreach ($views as $name => $sql) {
    run($db, "View: $name", $sql);
}

out("", 'head');
out("STEP 4 — Indexes", 'head');

$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_worker_role       ON Worker(Worker_Role)",
    "CREATE INDEX IF NOT EXISTS idx_worker_approval   ON Worker(approval_status)",
    "CREATE INDEX IF NOT EXISTS idx_customer_address  ON Customer(Address_ID)",
    "CREATE INDEX IF NOT EXISTS idx_orders_cid        ON Orders(CID)",
    "CREATE INDEX IF NOT EXISTS idx_orders_cow_id     ON Orders(Cow_ID)",
    "CREATE INDEX IF NOT EXISTS idx_orders_worker_id  ON Orders(Worker_ID)",
    "CREATE INDEX IF NOT EXISTS idx_orders_date       ON Orders(Order_Date)",
    "CREATE INDEX IF NOT EXISTS idx_orders_status     ON Orders(status)",
    "CREATE INDEX IF NOT EXISTS idx_reminders_due     ON reminders(due_date)",
    "CREATE INDEX IF NOT EXISTS idx_reminders_status  ON reminders(status)",
    "CREATE INDEX IF NOT EXISTS idx_reminders_assignee ON reminders(assigned_to)",
    "CREATE INDEX IF NOT EXISTS idx_reports_worker    ON staff_reports(worker_id)",
    "CREATE INDEX IF NOT EXISTS idx_cow_active        ON Cow(is_active)",
    "CREATE INDEX IF NOT EXISTS idx_cow_health        ON Cow(Health_Status)",
    "CREATE INDEX IF NOT EXISTS idx_products_active   ON Products(is_active)",
    "CREATE INDEX IF NOT EXISTS idx_cart_cid_status   ON Cart(CID, status)",
    "CREATE INDEX IF NOT EXISTS idx_cart_items_cart   ON CartItems(cart_id)",
    "CREATE INDEX IF NOT EXISTS idx_notes_created     ON notes(created_at)",
    "CREATE INDEX IF NOT EXISTS idx_prod_logs_date    ON production_logs(log_date)",
];
foreach ($indexes as $sql) {
    try { $db->exec($sql); } catch (PDOException $e) { /* ignore */ }
}
out("All indexes applied");

out("", 'head');
out("STEP 5 — Sample data", 'head');

// Workers (password = 'password')
$db->exec("INSERT INTO Address (Address_ID, Address) VALUES
    (301,'Casisang, Malaybalay City'),(302,'San Jose, Malaybalay City')
    ON DUPLICATE KEY UPDATE Address=VALUES(Address)");
out("Addresses seeded");

$db->exec("INSERT INTO Worker (Worker_ID, Worker, Worker_Role, Email, Password, approval_status) VALUES
    (201,'Mark','Staff','mark@esperon.farm','\$2y\$10\$wsIsunJBcBMNSwjt6Ptz6Owr9n3bqHi9IihvpiTTz7Tmhd4wWJNLC','approved'),
    (202,'Patrick','Admin','patrick@esperon.farm','\$2y\$10\$wsIsunJBcBMNSwjt6Ptz6Owr9n3bqHi9IihvpiTTz7Tmhd4wWJNLC','approved')
    ON DUPLICATE KEY UPDATE Worker=VALUES(Worker), Password=VALUES(Password), approval_status=VALUES(approval_status)");
out("Workers seeded (password: password)");

$db->exec("INSERT INTO Cow (Cow_ID, Cow, Breed, Date_Of_Birth, Production_Liters, Health_Status) VALUES
    (101,'Cow1','Holstein','2020-03-15',10.00,'Healthy'),
    (102,'Cow2','Jersey','2019-07-22',15.00,'Healthy')
    ON DUPLICATE KEY UPDATE Cow=VALUES(Cow)");
out("Cows seeded");

// Customers (password = 'Password1')
$custPw = password_hash('Password1', PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO Customer (CID, Customer_Name, Address_ID, Contact_Num, Password) VALUES
    (1,'Ana',301,'09010000001',?),(2,'Juan',302,'09020000002',?)
    ON DUPLICATE KEY UPDATE Customer_Name=VALUES(Customer_Name), Password=VALUES(Password)");
$stmt->execute([$custPw, $custPw]);
out("Customers seeded (password: Password1)");

// Products
$db->exec("INSERT INTO Products (product_id, name, description, price, stock_qty, unit) VALUES
    (1,'Fresh Whole Milk','Farm-fresh whole milk, collected daily.',55.00,100,'L'),
    (2,'Aged Cheddar Cheese','Rich, sharp cheddar aged for 3 months.',180.00,40,'pcs'),
    (3,'Creamy Butter','Pure churned butter. Unsalted.',120.00,60,'pcs'),
    (4,'Natural Yogurt','Thick yogurt with live cultures. No added sugar.',75.00,50,'pcs'),
    (5,'Fresh Cream','Heavy whipping cream for desserts and cooking.',90.00,35,'L'),
    (6,'Skim Milk','Low-fat skim milk for health-conscious customers.',45.00,80,'L'),
    (7,'Mozzarella Cheese','Soft, fresh mozzarella. Perfect for pizza and salads.',160.00,0,'pcs')
    ON DUPLICATE KEY UPDATE name=VALUES(name)");
out("Products seeded");

out("", 'head');
out("SETUP COMPLETE ✓", 'head');
out("");
out("Login credentials:");
out("  Admin:    Patrick / password");
out("  Staff:    Mark    / password");
out("  Customer: Ana     / Password1");
out("  Customer: Juan    / Password1");
out("");
out("DELETE this file after setup: rm setup.php");

if (!$isCli) {
    echo "</div>";
    echo "<p style='margin-top:20px;font-size:0.85rem;color:#c0392b;font-weight:600;'>⚠️ Delete setup.php after running it — it should not be publicly accessible.</p>";
    echo "</body></html>";
}
