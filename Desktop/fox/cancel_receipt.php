<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();
require_post('history.php');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$pdo = db();

try {
    $pdo->beginTransaction();

    $st = $pdo->prepare('SELECT id, sale_no, is_cancelled FROM pos_possale WHERE id = ? AND user_id = ? FOR UPDATE');
    $st->execute([$id, uid()]);
    $sale = $st->fetch();

    if (!$sale) {
        $pdo->rollBack();
        flash('danger', 'ไม่พบใบเสร็จนี้');
        redirect('history.php');
    }
    if ((int)$sale['is_cancelled'] === 1) {
        $pdo->rollBack();
        flash('warning', 'บิลนี้ถูกยกเลิกไปแล้ว');
        redirect('receipt.php?id=' . $id);
    }

    // คืนสต็อก
    $items = $pdo->prepare('SELECT product_id, quantity FROM pos_possaleitem WHERE sale_id = ? ORDER BY product_id');
    $items->execute([$id]);
    $back = $pdo->prepare('UPDATE dashboard_product SET quantity = quantity + ? WHERE id = ?');
    foreach ($items->fetchAll() as $row) {
        $back->execute([(int)$row['quantity'], (int)$row['product_id']]);
    }

    $pdo->prepare('UPDATE pos_possale SET is_cancelled = 1, cancelled_at = NOW() WHERE id = ?')->execute([$id]);
    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $ex;
}

flash('success', 'ยกเลิกบิล ' . $sale['sale_no'] . ' เรียบร้อยแล้ว');
redirect('receipt.php?id=' . $id);
