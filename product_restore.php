<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();
require_post('products.php');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$st = db()->prepare('UPDATE dashboard_product SET is_deleted = 0, deleted_at = NULL WHERE id = ? AND is_deleted = 1 AND user_id = ?');
$st->execute([$id, uid()]);

flash($st->rowCount() ? 'success' : 'warning', $st->rowCount() ? 'กู้คืนสินค้าเรียบร้อยแล้ว' : 'ไม่พบสินค้าที่ต้องกู้คืน');
redirect('products.php');
