<?php
/* ===================== ทั่วไป ===================== */

/** escape ข้อความก่อนแสดงใน HTML */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** สร้าง URL ภายในเว็บ (อิง BASE_URL ใน config.php) */
function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function str_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

/** อ่านค่า GET ที่เป็นข้อความ (กัน array) */
function get_str(string $key): string
{
    return (isset($_GET[$key]) && is_string($_GET[$key])) ? trim($_GET[$key]) : '';
}

function baht($n, int $dec = 2): string
{
    return '฿' . number_format((float)$n, $dec);
}

function fmt_dt($s, string $fmt = 'd/m/Y H:i'): string
{
    if (!$s) {
        return '-';
    }
    $t = strtotime((string)$s);
    return $t ? date($fmt, $t) : '-';
}

/* ===================== ข้อความแจ้งเตือน ===================== */

/** $type: success | danger | warning | info */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_take(): array
{
    $f = isset($_SESSION['flash']) ? $_SESSION['flash'] : [];
    unset($_SESSION['flash']);
    return $f;
}

/* ===================== ความปลอดภัย ===================== */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $t = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    return is_string($t) && $t !== '' && hash_equals(csrf_token(), $t);
}

/** ใช้ในหน้าที่รับเฉพาะ POST */
function require_post(string $backTo = 'index.php'): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        redirect($backTo);
    }
    if (!csrf_valid()) {
        http_response_code(400);
        exit('คำขอไม่ถูกต้องหรือหมดอายุ (CSRF) กรุณากลับไปรีเฟรชหน้าแล้วลองใหม่');
    }
}

/* ===================== สถิติ (Dashboard) ===================== */

function get_stats(): array
{
    $pdo = db();

    $st = $pdo->prepare(
        'SELECT COALESCE(SUM(total_amount), 0) AS amount, COUNT(*) AS cnt
           FROM pos_possale WHERE is_cancelled = 0 AND user_id = ?'
    );
    $st->execute([uid()]);
    $s = $st->fetch();

    // หมายเหตุ: ตามโค้ดเดิม นับรวมสินค้าที่ถูกลบแบบซ่อนด้วย
    // ถ้าต้องการไม่นับ ให้เพิ่ม "WHERE is_deleted = 0" ท้าย query นี้
    $st2 = $pdo->prepare(
        'SELECT COALESCE(SUM(quantity), 0) AS pieces,
                COALESCE(SUM(cost_price * quantity), 0) AS cost_stock,
                COALESCE(SUM((selling_price - cost_price) * quantity), 0) AS profit_stock
           FROM dashboard_product WHERE user_id = ?'
    );
    $st2->execute([uid()]);
    $p = $st2->fetch();

    return [
        'sales_amount'  => (float)$s['amount'],
        'orders'        => (int)$s['cnt'],
        'pieces'        => (int)$p['pieces'],
        'cost_stock'    => (float)$p['cost_stock'],
        'profit_stock'  => (float)$p['profit_stock'],
    ];
}

/* ===================== ตะกร้า POS (เก็บใน session) ===================== */

function cart_payload(): array
{
    $items = [];
    $total = 0.0;
    $cart = isset($_SESSION['pos_cart']) && is_array($_SESSION['pos_cart']) ? $_SESSION['pos_cart'] : [];
    foreach ($cart as $it) {
        $line = round((float)$it['price'] * (int)$it['quantity'], 2);
        $items[] = [
            'id'       => (int)$it['id'],
            'name'     => (string)$it['name'],
            'sku'      => (string)$it['sku'],
            'price'    => (float)$it['price'],
            'quantity' => (int)$it['quantity'],
            'total'    => $line,
            'unit'     => (string)$it['unit'],
        ];
        $total += $line;
    }
    return ['items' => $items, 'count' => count($items), 'total' => round($total, 2)];
}
