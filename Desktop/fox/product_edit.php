<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/product_form.php';
require_login();

$pdo = db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$st = $pdo->prepare('SELECT * FROM dashboard_product WHERE id = ? AND user_id = ?');
$st->execute([$id, uid()]);
$product = $st->fetch();
if (!$product) {
    flash('danger', 'ไม่พบสินค้านี้');
    redirect('products.php');
}

$form = [
    'name'          => $product['name'],
    'category_id'   => (string)$product['category_id'],
    'unit_id'       => $product['unit_id'] === null ? '' : (string)$product['unit_id'],
    'sku'           => $product['sku'],
    'quantity'      => (string)$product['quantity'],
    'cost_price'    => number_format((float)$product['cost_price'], 2, '.', ''),
    'selling_price' => number_format((float)$product['selling_price'], 2, '.', ''),
];
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post('product_edit.php?id=' . $id);
    list($form, $errors) = product_validate($_POST, $id);
    if (!$errors) {
        try {
            $up = $pdo->prepare(
                'UPDATE dashboard_product
                    SET name = ?, category_id = ?, unit_id = ?, quantity = ?,
                        cost_price = ?, selling_price = ?, sku = ?
                  WHERE id = ? AND user_id = ?'
            );
            $up->execute([
                $form['name'],
                (int)$form['category_id'],
                $form['unit_id'] === '' ? null : (int)$form['unit_id'],
                (int)$form['quantity'],
                number_format((float)$form['cost_price'], 2, '.', ''),
                number_format((float)$form['selling_price'], 2, '.', ''),
                $form['sku'],
                $id,
                uid(),
            ]);
            flash('success', 'แก้ไขสินค้า "' . $form['name'] . '" เรียบร้อยแล้ว');
            redirect('products.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() !== '23000') {
                throw $ex;
            }
            $errors['sku'] = 'SKU นี้ถูกใช้แล้ว';
        }
    }
}

page_header('Edit Product', 'products', 'Products');
?>
<section class="fox-card">
  <h2 class="tab-head">Edit Product</h2>
  <div class="fox-card-body">
    <?php product_form($form, $errors, true, $id); ?>
  </div>
</section>
<?php page_footer(); ?>
