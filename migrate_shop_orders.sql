-- Run this on your local esperondairyfarm database in phpMyAdmin SQL tab
-- This makes Cow_ID optional so shop orders (no cow) can be stored in Orders

ALTER TABLE Orders MODIFY Cow_ID INT NULL;

INSERT IGNORE INTO OrderTypes (type_name) VALUES ('Shop Purchase');

-- Update the view to handle NULL Cow_ID (shop orders have no cow)
CREATE OR REPLACE VIEW vw_order_details AS
SELECT
    o.Order_ID,
    ot.type_name                                    AS Order_Type,
    o.Order_Date,
    o.quantity_liters,
    o.unit_price,
    o.total_price,
    o.status                                        AS Order_Status,
    o.notes                                         AS Order_Notes,
    o.updated_at                                    AS Order_Updated,
    o.CID,
    c.Customer_Name,
    c.Contact_Num,
    a.Address,
    o.Cow_ID,
    COALESCE(cw.Cow, '—')                          AS Cow,
    COALESCE(cw.Breed, '—')                        AS Breed,
    COALESCE(cw.Production_Liters, 0)              AS Production_Liters,
    o.Worker_ID,
    w.Worker                                        AS Worker_Name,
    w.Worker_Role
FROM       Orders     o
JOIN       OrderTypes ot ON o.type_id     = ot.type_id
JOIN       Customer   c  ON o.CID         = c.CID
JOIN       Address    a  ON c.Address_ID  = a.Address_ID
LEFT JOIN  Cow        cw ON o.Cow_ID      = cw.Cow_ID
JOIN       Worker     w  ON o.Worker_ID   = w.Worker_ID;
