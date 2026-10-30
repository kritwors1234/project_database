<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();
require_post('cashier.php');

$cart = (isset($_SESSION['pos_cart']) && is_array($_SESSION['pos_cart'])) ? $_SESSION['pos_cart'] : [];
if (!$cart) {
    flash('danger', 'ตะกร้าว่าง');
    redirect('cashier.php');
}
ksort($cart);   // ล็อกแถวสินค้าตามลำดับ id เสมอ ป้องกัน deadlock เมื่อมีหลายเครื่องขายพร้อมกัน

/** ข้อผิดพลาดที่ตั้งใจแจ้งผู้ใช้ (สต็อกไม่พอ ฯลฯ) แยกจาก PDOException */
class CheckoutException extends Exception {}

$pdo = db();
$saleId = 0;
$saleNo = '';

try {
    $pdo->beginTransaction();

    // 1) ล็อกสินค้าและตรวจสต็อกจริงในฐานข้อมูล
    $sel = $pdo->prepare('SELECT id, name, quantity, is_deleted FROM dashboard_product WHERE id = ? AND user_id = ? FOR UPDATE');
    $lines = [];
    $total = 0.0;
    foreach ($cart as $pid => $it) {
        $sel->execute([(int)$pid, uid()]);
        $p = $sel->fetch();
        if (!$p || (int)$p['is_deleted'] === 1) {
            throw new CheckoutException('สินค้า "' . $it['name'] . '" ไม่มีในระบบแล้ว กรุณาลบออกจากตะกร้า');
        }
        if ((int)$p['quantity'] < (int)$it['quantity']) {
            throw new CheckoutException('สินค้า "' . $p['name'] . '" สต็อกไม่พอ (เหลือ ' . (int)$p['quantity'] . ' ชิ้น) กรุณาปรับจำนวนในตะกร้า');
        }
        $line = round((float)$it['price'] * (int)$it['quantity'], 2);
        $lines[] = ['id' => (int)$pid, 'qty' => (int)$it['quantity'], 'price' => (float)$it['price'], 'total' => $line];
        $total += $line;
    }
    $total = round($total, 2);

    // 2) สร้างบิล (เลขบิลรูปแบบเดิม POS-<timestamp> ถ้าซ้ำจะต่อท้ายเลขลำดับ)
    $base = 'POS-' . time();
    $saleNo = $base;
    $ins = $pdo->prepare('INSERT INTO pos_possale (sale_no, total_amount, user_id) VALUES (?, ?, ?)');
    for ($n = 1; $n <= 20; $n++) {
        try {
            $ins->execute([$saleNo, number_format($total, 2, '.', ''), uid()]);
            $saleId = (int)$pdo->lastInsertId();
            break;
        } catch (PDOException $ex) {
            if ($ex->getCode() !== '23000') {
                throw $ex;
            }
            $saleNo = $base . '-' . ($n + 1);
        }
    }
    if ($saleId === 0) {
        throw new CheckoutException('สร้างเลขบิลไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
    }

    // 3) บันทึกรายการ + ตัดสต็อก
    $insItem = $pdo->prepare(
        'INSERT INTO pos_possaleitem (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)'
    );
    $cut = $pdo->prepare('UPDATE dashboard_product SET quantity = quantity - ? WHERE id = ? AND user_id = ?');
    foreach ($lines as $l) {
        $insItem->execute([
            $saleId, $l['id'], $l['qty'],
            number_format($l['price'], 2, '.', ''),
            number_format($l['total'], 2, '.', ''),
        ]);
        $cut->execute([$l['qty'], $l['id'], uid()]);
    }

    $pdo->commit();
} catch (CheckoutException $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('danger', $ex->getMessage());
    redirect('cashier.php');
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $ex;
}

$_SESSION['pos_cart'] = [];
flash('success', 'Receipt เลขที่ ' . $saleNo);
redirect('receipt.php?id=' . $saleId);
