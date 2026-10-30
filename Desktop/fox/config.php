<?php
/**
 * FoxInventory - ตั้งค่า
 * ค่าฐานข้อมูลแก้ที่ config/db.php (แก้แค่ DB_PASS ก็พอ)
 */
require_once __DIR__ . '/config/db.php';

// ----- เว็บ -----
// ตรวจหาโฟลเดอร์ที่วางเว็บให้อัตโนมัติ (วางที่ root หรือโฟลเดอร์ย่อยก็ใช้ได้โดยไม่ต้องแก้)
// ถ้าต้องการกำหนดเอง ให้ใส่เช่น '/fox' (ไม่ต้องมี / ท้าย)
$__fox_base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE_URL', $__fox_base);
unset($__fox_base);

define('APP_NAME', 'FoxInventory');
define('APP_TIMEZONE', 'Asia/Bangkok');
define('DB_TIME_OFFSET', '+07:00');   // ตรงกับ Asia/Bangkok

// true = แสดงข้อความ error จริงบนหน้าเว็บ (ใช้ตอนแก้ปัญหาเท่านั้น แล้วปิดกลับเป็น false)
define('APP_DEBUG', false);
