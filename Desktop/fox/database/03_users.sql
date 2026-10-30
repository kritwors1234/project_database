-- ตารางสมาชิกของ FoxInventory (ไม่บังคับ: หน้า login/register จะสร้างให้อัตโนมัติถ้ายังไม่มี)
-- ใช้ชื่อ fox_users เพื่อไม่ชนกับตาราง users ของโปรเจกต์ php ในฐานข้อมูลเดียวกัน
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS fox_users (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(30)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_fox_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
