<?php
require_once __DIR__ . '/../config.php';

date_default_timezone_set(APP_TIMEZONE);

if (session_status() === PHP_SESSION_NONE) {
    session_name('fox_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

set_exception_handler(function ($ex) {
    error_log('[FoxInventory] ' . get_class($ex) . ': ' . $ex->getMessage()
        . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    $isApi = (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'api.php');
    if ($isApi) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดของระบบ'], JSON_UNESCAPED_UNICODE);
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>Error</title>'
           . '<div style="font-family:sans-serif;max-width:640px;margin:15vh auto;padding:0 1rem">'
           . '<h2>เกิดข้อผิดพลาดของระบบ</h2>'
           . '<p>ลองรีเฟรชหน้านี้ ถ้ายังไม่หายให้ตรวจสอบการตั้งค่าใน <code>config.php</code></p>';
        if (APP_DEBUG) {
            echo '<pre style="white-space:pre-wrap;background:#f3f3f3;padding:1rem">'
               . htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
        }
        echo '</div>';
    }
    exit;
});
