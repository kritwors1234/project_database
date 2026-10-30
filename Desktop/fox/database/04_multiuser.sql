-- แยกข้อมูลตามผู้ใช้ (ไม่บังคับ: ระบบจะทำให้อัตโนมัติเมื่อล็อกอินครั้งแรก)
-- รันไฟล์นี้ "ครั้งเดียว" เท่านั้น ถ้าขึ้น error Duplicate column แปลว่าทำไปแล้ว
-- ข้อมูลเดิมทั้งหมดจะตกเป็นของผู้ใช้คนแรก (fox_users.id ต่ำสุด)

ALTER TABLE dashboard_category ADD COLUMN user_id BIGINT UNSIGNED NULL DEFAULT NULL, ADD KEY idx_dashboard_category_user (user_id);
ALTER TABLE dashboard_unit     ADD COLUMN user_id BIGINT UNSIGNED NULL DEFAULT NULL, ADD KEY idx_dashboard_unit_user (user_id);
ALTER TABLE dashboard_product  ADD COLUMN user_id BIGINT UNSIGNED NULL DEFAULT NULL, ADD KEY idx_dashboard_product_user (user_id);
ALTER TABLE pos_possale        ADD COLUMN user_id BIGINT UNSIGNED NULL DEFAULT NULL, ADD KEY idx_pos_possale_user (user_id);

UPDATE dashboard_category SET user_id = (SELECT MIN(id) FROM fox_users) WHERE user_id IS NULL;
UPDATE dashboard_unit     SET user_id = (SELECT MIN(id) FROM fox_users) WHERE user_id IS NULL;
UPDATE dashboard_product  SET user_id = (SELECT MIN(id) FROM fox_users) WHERE user_id IS NULL;
UPDATE pos_possale        SET user_id = (SELECT MIN(id) FROM fox_users) WHERE user_id IS NULL;

-- SKU ซ้ำกันได้ถ้าคนละผู้ใช้
ALTER TABLE dashboard_product DROP INDEX uq_product_sku, ADD UNIQUE KEY uq_product_user_sku (user_id, sku);
