<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();
require_post('products.php');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$st = db()->prepare('UPDATE dashboard_product SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND is_deleted = 0 AND user_id = ?');
$st->execute([$id, uid()]);

flash($st->rowCount() ? 'success' : 'warning', $st->rowCount() ? 'ลบสินค้าเรียบร้อยแล้ว (กู้คืนได้ที่ปุ่ม Restore)' : 'ไม่พบสินค้า หรือถูกลบไปแล้ว');
redirect('products.php');
