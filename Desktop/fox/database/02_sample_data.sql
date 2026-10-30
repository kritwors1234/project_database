-- =====================================================================
-- FoxInventory : ข้อมูลตัวอย่างสำหรับทดสอบ (ไม่จำเป็นต้องนำเข้า)
-- นำเข้าหลัง 01_schema.sql และเฉพาะตอนที่ตารางยังว่างอยู่เท่านั้น
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';

INSERT INTO dashboard_category (id, name) VALUES
  (1, 'Dog'), (2, 'Cat'), (3, 'Fish'), (4, 'Bunny');

INSERT INTO dashboard_unit (id, name, symbol) VALUES
  (1, 'Piece', 'PCS'), (2, 'Kilogram', 'KG'), (3, 'Box', 'BOX'), (4, 'Bag', 'BAG');

INSERT INTO dashboard_product
  (id, name, category_id, unit_id, quantity, cost_price, selling_price, description, sku) VALUES
  (1, 'Dog Food 1 kg',        1, 2, 40,  80.00, 120.00, '', 'DOG-001'),
  (2, 'Dog Chew Toy',         1, 1, 25,  30.00,  59.00, '', 'DOG-002'),
  (3, 'Cat Food Tuna 400 g',  2, 1, 60,  25.00,  39.00, '', 'CAT-001'),
  (4, 'Cat Litter 5 L',       2, 4, 18,  90.00, 149.00, '', 'CAT-002'),
  (5, 'Fish Flakes 100 g',    3, 3, 35,  20.00,  35.00, '', 'FISH-001'),
  (6, 'Bunny Hay 500 g',      4, 4, 15,  45.00,  79.00, '', 'BUN-001');
