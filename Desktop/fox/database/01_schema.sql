-- =====================================================================
-- FoxInventory : โครงสร้างฐานข้อมูล (MySQL 5.7+ / MariaDB 10.3+)
-- วิธีใช้: phpMyAdmin > เลือกฐานข้อมูลของคุณทางซ้าย > แท็บ Import > เลือกไฟล์นี้
-- ใช้กับฐานข้อมูลที่ "ว่างเปล่า" เท่านั้น (ถ้ามีตารางชื่อซ้ำ จะขึ้น error และไม่ทับของเดิม)
-- เวลาทั้งหมดเก็บเป็นเวลาไทย (UTC+7)
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';

-- หมวดหมู่สินค้า
CREATE TABLE dashboard_category (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(200) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- หน่วยนับ
CREATE TABLE dashboard_unit (
  id      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name    VARCHAR(50) NOT NULL,
  symbol  VARCHAR(10) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- สินค้า (is_deleted = ลบแบบซ่อน กู้คืนได้)
CREATE TABLE dashboard_product (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name           VARCHAR(200) NOT NULL,
  category_id    BIGINT UNSIGNED NOT NULL,
  unit_id        BIGINT UNSIGNED NULL DEFAULT NULL,
  quantity       INT NOT NULL DEFAULT 0,
  cost_price     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  selling_price  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  description    TEXT NOT NULL,
  sku            VARCHAR(50) NOT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  is_deleted     TINYINT(1) NOT NULL DEFAULT 0,
  deleted_at     DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_product_sku (sku),
  KEY idx_product_category (category_id),
  KEY idx_product_unit (unit_id),
  KEY idx_product_deleted (is_deleted),
  CONSTRAINT fk_product_category FOREIGN KEY (category_id)
    REFERENCES dashboard_category (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_product_unit FOREIGN KEY (unit_id)
    REFERENCES dashboard_unit (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ใบขาย (บิล)
CREATE TABLE pos_possale (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sale_no       VARCHAR(50) NOT NULL,
  total_amount  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  is_cancelled  TINYINT(1) NOT NULL DEFAULT 0,
  cancelled_at  DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sale_no (sale_no),
  KEY idx_sale_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- รายการสินค้าในบิล
CREATE TABLE pos_possaleitem (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sale_id      BIGINT UNSIGNED NOT NULL,
  product_id   BIGINT UNSIGNED NOT NULL,
  quantity     INT NOT NULL DEFAULT 1,
  unit_price   DECIMAL(10,2) NOT NULL,
  total_price  DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_item_sale (sale_id),
  KEY idx_item_product (product_id),
  CONSTRAINT fk_item_sale FOREIGN KEY (sale_id)
    REFERENCES pos_possale (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_item_product FOREIGN KEY (product_id)
    REFERENCES dashboard_product (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
