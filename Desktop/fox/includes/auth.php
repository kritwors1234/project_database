<?php
/**
 * ระบบสมาชิก: สมัครด้วย username + รหัสผ่าน
 * ใช้ตาราง fox_users (แยกจากตาราง users ของโปรเจกต์ php เพื่อไม่ให้ชนกัน)
 */

const USERNAME_MAX_LEN = 30;   // ตามขนาดคอลัมน์ username ในฐานข้อมูล
const PASSWORD_MIN_LEN = 8;
// แฮชปลอม ใช้ตรวจรหัสผ่านเมื่อไม่พบ username เพื่อให้เวลาตอบใกล้เคียงกัน
const DUMMY_HASH = '$2y$10$abcdefghijklmnopqrstuuabcdefghijklmnopqrstuvwxyzABCDE';

/** สร้างตาราง fox_users ถ้ายังไม่มี (เรียกเฉพาะหน้า login/register) */
function ensure_users_table(): void
{
    db()->exec(
        'CREATE TABLE IF NOT EXISTS fox_users (
           id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
           username      VARCHAR(30)  NOT NULL,
           password_hash VARCHAR(255) NOT NULL,
           created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (id),
           UNIQUE KEY uq_fox_users_username (username)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/** id ของผู้ใช้ที่ล็อกอินอยู่ (ใช้กรองข้อมูลทุก query) */
function uid(): int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

function current_username(): string
{
    return isset($_SESSION['username']) ? (string)$_SESSION['username'] : '';
}

/** ทุกหน้าที่ต้องล็อกอินเรียกฟังก์ชันนี้ */
function require_login(): void
{
    if (is_logged_in()) {
        if (empty($_SESSION['schema_ok'])) {
            ensure_multiuser_schema();
            $_SESSION['schema_ok'] = 1;
        }
        return;
    }
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'api.php') {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'หมดเวลาการใช้งาน กรุณาเข้าสู่ระบบใหม่'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    redirect('login.php');
}

function login_user(int $id, string $username): void
{
    session_regenerate_id(true);
    unset($_SESSION['csrf']);
    $_SESSION['user_id']  = $id;
    $_SESSION['username'] = $username;
}

function logout_user(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

/**
 * ทำให้ข้อมูลแยกตามผู้ใช้: เพิ่มคอลัมน์ user_id ในตารางข้อมูล (ทำครั้งเดียว ข้ามถ้ามีแล้ว)
 * ข้อมูลเดิมที่มีอยู่ก่อนหน้านี้จะตกเป็นของผู้ใช้คนแรก (fox_users.id ต่ำสุด)
 */
function ensure_multiuser_schema(): void
{
    $pdo = db();
    $tables = ['dashboard_category', 'dashboard_unit', 'dashboard_product', 'pos_possale'];
    foreach ($tables as $t) {
        $has = $pdo->query("SHOW COLUMNS FROM `$t` LIKE 'user_id'")->fetch();
        if (!$has) {
            $pdo->exec("ALTER TABLE `$t` ADD COLUMN user_id BIGINT UNSIGNED NULL DEFAULT NULL, ADD KEY idx_{$t}_user (user_id)");
            $pdo->exec("UPDATE `$t` SET user_id = (SELECT MIN(id) FROM fox_users) WHERE user_id IS NULL");
        }
    }
    // SKU ซ้ำกันได้ถ้าคนละผู้ใช้ (เดิมห้ามซ้ำทั้งระบบ)
    $old = $pdo->query("SHOW INDEX FROM dashboard_product WHERE Key_name = 'uq_product_sku'")->fetch();
    if ($old) {
        $pdo->exec('ALTER TABLE dashboard_product DROP INDEX uq_product_sku');
    }
    $new = $pdo->query("SHOW INDEX FROM dashboard_product WHERE Key_name = 'uq_product_user_sku'")->fetch();
    if (!$new) {
        $pdo->exec('ALTER TABLE dashboard_product ADD UNIQUE KEY uq_product_user_sku (user_id, sku)');
    }
}
