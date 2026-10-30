<?php
/** เชื่อมต่อฐานข้อมูล (PDO) - เชื่อมต่อครั้งเดียวต่อหนึ่งคำขอ */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('เซิร์ฟเวอร์ไม่ได้เปิดใช้ pdo_mysql');
        }
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // ให้ NOW() / CURRENT_TIMESTAMP เป็นเวลาไทยเสมอ ไม่ว่า server ตั้งเขตเวลาไว้อย่างไร
        $pdo->exec("SET time_zone = '" . DB_TIME_OFFSET . "'");
    }
    return $pdo;
}
