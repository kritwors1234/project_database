<?php
/** API สำหรับหน้า Cashier (ค้นหาสินค้า + จัดการตะกร้า) ตอบเป็น JSON */
require __DIR__ . '/includes/bootstrap.php';
require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function api_out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** ตอบ error พร้อมสถานะตะกร้าปัจจุบัน (ให้หน้าเว็บวาดตะกร้าให้ตรงกับเซิร์ฟเวอร์) */
function api_error(string $message, int $code = 200): void
{
    api_out(['status' => 'error', 'message' => $message, 'cart' => cart_payload()], $code);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = '';
if ($method === 'POST') {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
} else {
    $action = isset($_GET['action']) ? (string)$_GET['action'] : '';
}

$pdo = db();

/* ---------- ค้นหาสินค้า (GET) ---------- */
if ($action === 'search') {
    $q = get_str('q');
    if ($q === '') {
        api_out(['products' => []]);
    }
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $st = $pdo->prepare(
        'SELECT p.id, p.name, p.sku, p.selling_price, p.quantity,
                c.name AS category_name, u.symbol AS unit_symbol
           FROM dashboard_product p
           JOIN dashboard_category c ON c.id = p.category_id
           LEFT JOIN dashboard_unit u ON u.id = p.unit_id
          WHERE p.quantity > 0 AND p.is_deleted = 0 AND p.user_id = ?
            AND (p.name LIKE ? OR p.sku LIKE ? OR c.name LIKE ?)
          ORDER BY p.name
          LIMIT 15'
    );
    $st->execute([uid(), $like, $like, $like]);
    $out = [];
    foreach ($st->fetchAll() as $p) {
        $out[] = [
            'id'       => (int)$p['id'],
            'name'     => $p['name'],
            'sku'      => $p['sku'],
            'price'    => (float)$p['selling_price'],
            'quantity' => (int)$p['quantity'],
            'category' => $p['category_name'],
            'unit'     => $p['unit_symbol'] === null ? '' : $p['unit_symbol'],
        ];
    }
    api_out(['products' => $out]);
}

/* ---------- ต่อไปนี้ต้องเป็น POST + CSRF ---------- */
if ($method !== 'POST') {
    api_error('Invalid request', 405);
}
if (!csrf_valid()) {
    api_error('คำขอไม่ถูกต้องหรือหมดอายุ กรุณารีเฟรชหน้า', 403);
}

if (!isset($_SESSION['pos_cart']) || !is_array($_SESSION['pos_cart'])) {
    $_SESSION['pos_cart'] = [];
}
$pid = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
if ($qty < 1) {
    $qty = 1;
}

function api_find_product(PDO $pdo, int $pid)
{
    $st = $pdo->prepare(
        'SELECT p.id, p.name, p.sku, p.selling_price, p.quantity, u.symbol AS unit_symbol
           FROM dashboard_product p
           LEFT JOIN dashboard_unit u ON u.id = p.unit_id
          WHERE p.id = ? AND p.is_deleted = 0 AND p.user_id = ?'
    );
    $st->execute([$pid, uid()]);
    return $st->fetch();
}

switch ($action) {
    case 'add':
        $p = api_find_product($pdo, $pid);
        if (!$p) {
            api_error('Product not found');
        }
        $have = isset($_SESSION['pos_cart'][$pid]) ? (int)$_SESSION['pos_cart'][$pid]['quantity'] : 0;
        $new = $have + $qty;
        if ((int)$p['quantity'] < $new) {
            api_error('Stock ไม่พอ! เหลือ ' . (int)$p['quantity'] . ' ชิ้น');
        }
        if ($have > 0) {
            $_SESSION['pos_cart'][$pid]['quantity'] = $new;
        } else {
            $_SESSION['pos_cart'][$pid] = [
                'id'       => (int)$p['id'],
                'name'     => $p['name'],
                'sku'      => $p['sku'],
                'price'    => (float)$p['selling_price'],
                'quantity' => $qty,
                'unit'     => $p['unit_symbol'] === null ? '' : $p['unit_symbol'],
            ];
        }
        api_out(['status' => 'success', 'message' => $p['name'] . ' เพิ่มลงตะกร้าแล้ว', 'cart' => cart_payload()]);

    case 'update':
        if (!isset($_SESSION['pos_cart'][$pid])) {
            api_error('Item not found');
        }
        $p = api_find_product($pdo, $pid);
        if (!$p) {
            api_error('Product not found');
        }
        if ((int)$p['quantity'] < $qty) {
            api_error('Stock ไม่พอ! เหลือ ' . (int)$p['quantity'] . ' ชิ้น');
        }
        $_SESSION['pos_cart'][$pid]['quantity'] = $qty;
        api_out(['status' => 'success', 'cart' => cart_payload()]);

    case 'remove':
        if (!isset($_SESSION['pos_cart'][$pid])) {
            api_error('Item not found');
        }
        unset($_SESSION['pos_cart'][$pid]);
        api_out(['status' => 'success', 'cart' => cart_payload()]);

    case 'clear':
        $_SESSION['pos_cart'] = [];
        api_out(['status' => 'success', 'cart' => cart_payload()]);

    default:
        api_error('Invalid request', 400);
}
