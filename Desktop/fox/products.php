<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/product_form.php';
require_login();

$pdo = db();
$form = ['name' => '', 'category_id' => '', 'unit_id' => '', 'sku' => '',
         'quantity' => '0', 'cost_price' => '0', 'selling_price' => '0'];
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post('products.php');
    list($form, $errors) = product_validate($_POST, 0);
    if (!$errors) {
        try {
            $st = $pdo->prepare(
                'INSERT INTO dashboard_product
                   (name, category_id, unit_id, quantity, cost_price, selling_price, description, sku, user_id)
                 VALUES (?, ?, ?, ?, ?, ?, \'\', ?, ?)'
            );
            $st->execute([
                $form['name'],
                (int)$form['category_id'],
                $form['unit_id'] === '' ? null : (int)$form['unit_id'],
                (int)$form['quantity'],
                number_format((float)$form['cost_price'], 2, '.', ''),
                number_format((float)$form['selling_price'], 2, '.', ''),
                $form['sku'],
                uid(),
            ]);
            flash('success', 'เพิ่มสินค้า "' . $form['name'] . '" เรียบร้อยแล้ว');
            redirect('products.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() !== '23000') {
                throw $ex;
            }
            $errors['sku'] = 'SKU นี้ถูกใช้แล้ว';
        }
    }
}

$listSt = $pdo->prepare(
    'SELECT p.*, c.name AS category_name, u.symbol AS unit_symbol
       FROM dashboard_product p
       JOIN dashboard_category c ON c.id = p.category_id
       LEFT JOIN dashboard_unit u ON u.id = p.unit_id
      WHERE p.user_id = ?
      ORDER BY p.is_deleted ASC, p.updated_at DESC, p.id DESC'
);
$listSt->execute([uid()]);
$products = $listSt->fetchAll();

page_header('Products', 'products', 'Products');
?>
<section class="fox-card mb-4">
  <h2 class="tab-head">Products List</h2>
  <div class="fox-card-body p-0">
    <div class="table-responsive">
      <table class="table fox-table mb-0">
        <thead>
          <tr>
            <th>#</th><th>Name</th><th>Category</th><th>Unit</th><th>Cost</th>
            <th>Sell</th><th>Profit</th><th>%</th><th>Qty</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
<?php if (!$products): ?>
          <tr><td colspan="10" class="text-center empty-note">No products available.</td></tr>
<?php endif; ?>
<?php foreach ($products as $i => $p):
    $cost = (float)$p['cost_price'];
    $sell = (float)$p['selling_price'];
    $profit = $sell - $cost;
    $margin = $cost == 0.0 ? 0.0 : ($profit / $cost) * 100;
    $isDeleted = (int)$p['is_deleted'] === 1;
    $tone = function ($v) { return $v > 0 ? 'badge-fox-green' : ($v < 0 ? 'badge-fox-red' : 'badge-fox-gray'); };
?>
          <tr<?= $isDeleted ? ' class="row-deleted"' : '' ?>>
            <td><?= $i + 1 ?></td>
            <td>
              <?= e($p['name']) ?>
              <?php if ($isDeleted): ?><span class="badge badge-fox-gray">Deleted</span><?php endif; ?>
              <div class="sku-note"><?= e($p['sku']) ?></div>
            </td>
            <td><?= e($p['category_name']) ?></td>
            <td>
<?php if ($p['unit_symbol'] !== null): ?>
              <span class="badge badge-fox-blue"><?= e($p['unit_symbol']) ?></span>
<?php else: ?>
              <span class="badge badge-fox-gray">-</span>
<?php endif; ?>
            </td>
            <td><?= e(baht($cost)) ?></td>
            <td><?= e(baht($sell)) ?></td>
            <td><span class="badge <?= $tone($profit) ?>"><?= $profit > 0 ? '+' : '' ?><?= e(baht($profit)) ?></span></td>
            <td><span class="badge <?= $tone($margin) ?>"><?= e(number_format($margin, 1)) ?>%</span></td>
            <td><?= e(number_format((int)$p['quantity'])) ?></td>
            <td class="text-nowrap">
              <a class="btn btn-fox btn-sm btn-yellow" href="<?= e(url('product_edit.php?id=' . (int)$p['id'])) ?>">Edit</a>
<?php if (!$isDeleted): ?>
              <form method="post" action="<?= e(url('product_delete.php')) ?>" class="d-inline"
                    onsubmit="return confirm('Delete this product?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button type="submit" class="btn btn-fox btn-sm btn-red">Delete</button>
              </form>
<?php else: ?>
              <form method="post" action="<?= e(url('product_restore.php')) ?>" class="d-inline"
                    onsubmit="return confirm('Restore this product?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button type="submit" class="btn btn-fox btn-sm btn-green">Restore</button>
              </form>
<?php endif; ?>
            </td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="fox-card">
  <h2 class="tab-head">Add Product</h2>
  <div class="fox-card-body">
    <?php product_form($form, $errors, false); ?>
  </div>
</section>
<?php page_footer(); ?>
