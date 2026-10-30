<?php
/**
 * ตั้งค่าฐานข้อมูล (รูปแบบเดียวกับโปรเจกต์ php)
 * บน Angsila ชื่อผู้ใช้กับชื่อฐานข้อมูลมักเป็นรหัสเดียวกัน (เช่น s68160374)
 * ** ใส่รหัสผ่านฐานข้อมูลที่บรรทัด DB_PASS ด้านล่าง (ใช้ตัวเดียวกับที่โปรเจกต์ php ใช้ได้) **
 */
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 's68160374');
define('DB_USER', getenv('DB_USER') ?: 's68160374');
define('DB_PASS', getenv('DB_PASS') ?: 'C4HU2kVx');
