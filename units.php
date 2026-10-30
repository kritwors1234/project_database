<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo = db();
$errors = [];
$form = ['name' => '', 'symbol' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post('units.php');
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';

    if ($action === 'add') {
        $form['name']   = trim((string)(isset($_POST['name']) ? $_POST['name'] : ''));
        $form['symbol'] = trim((string)(isset($_POST['symbol']) ? $_POST['symbol'] : ''));
        if ($form['name'] === '') {
            $errors['name'] = 'กรุณากรอกชื่อหน่วย';
        } elseif (str_len($form['name']) > 50) {
            $errors['name'] = 'ชื่อหน่วยยาวเกิน 50 ตัวอักษร';
        }
        if ($form['symbol'] === '') {
            $errors['symbol'] = 'กรุณากรอกสัญลักษณ์';
        } elseif (str_len($form['symbol']) > 10) {
            $errors['symbol'] = 'สัญลักษณ์ยาวเกิน 10 ตัวอักษร';
        }
        if (!$errors) {
            $pdo->prepare('INSERT INTO dashboard_unit (name, symbol, user_id) VALUES (?, ?, ?)')
                ->execute([$form['name'], $form['symbol'], uid()]);
            flash('success', 'เพิ่มหน่วย "' . $form['name'] . '" เรียบร้อยแล้ว');
            redirect('units.php');
        }
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $cnt = $pdo->prepare('SELECT COUNT(*) FROM dashboard_product WHERE unit_id = ? AND user_id = ?');
        $cnt->execute([$id, uid()]);
        $n = (int)$cnt->fetchColumn();
        if ($n > 0) {
            flash('danger', 'ลบไม่ได้: ยังมีสินค้าใช้หน่วยนี้อยู่ ' . $n . ' รายการ (รวมสินค้าที่ถูกลบแบบซ่อน) กรุณาแก้ไขสินค้าก่อน');
        } else {
            $d = $pdo->prepare('DELETE FROM dashboard_unit WHERE id = ? AND user_id = ?');
            $d->execute([$id, uid()]);
            flash($d->rowCount() ? 'success' : 'warning', $d->rowCount() ? 'ลบหน่วยเรียบร้อยแล้ว' : 'ไม่พบหน่วยนี้');
        }
        redirect('units.php');
    }
}

$unitSt = $pdo->prepare(
    'SELECT u.id, u.name, u.symbol, COUNT(p.id) AS product_count
       FROM dashboard_unit u
       LEFT JOIN dashboard_product p ON p.unit_id = u.id
      WHERE u.user_id = ?
      GROUP BY u.id, u.name, u.symbol
      ORDER BY u.name'
);
$unitSt->execute([uid()]);
$units = $unitSt->fetchAll();

page_header('Units', 'products', 'Units');
?>
<div class="row">
  <div class="col-lg-4 mb-4">
    <section class="fox-card">
      <h2 class="tab-head">Add Unit</h2>
      <div class="fox-card-body">
        <form method="post" action="<?= e(url('units.php')) ?>" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <div class="form-group">
            <label for="unit-name">Unit Name</label>
            <input type="text" id="unit-name" name="name" class="form-control" maxlength="50"
                   placeholder="e.g. Box, Kilogram" required value="<?= e($form['name']) ?>">
<?php if (isset($errors['name'])): ?><div class="invalid-feedback d-block"><?= e($errors['name']) ?></div><?php endif; ?>
          </div>
          <div class="form-group">
            <label for="unit-symbol">Symbol</label>
            <input type="text" id="unit-symbol" name="symbol" class="form-control" maxlength="10"
                   placeholder="e.g. BOX, KG, PCS" required value="<?= e($form['symbol']) ?>">
<?php if (isset($errors['symbol'])): ?><div class="invalid-feedback d-block"><?= e($errors['symbol']) ?></div><?php endif; ?>
          </div>
          <button type="submit" class="btn btn-fox btn-green">Add Unit</button>
          <a href="<?= e(url('categories.php')) ?>" class="btn btn-fox btn-yellow">Manage Categories</a>
        </form>
      </div>
    </section>
  </div>

  <div class="col-lg-8 mb-4">
    <section class="fox-card">
      <h2 class="tab-head">Units List</h2>
      <div class="fox-card-body p-0">
        <div class="table-responsive">
          <table class="table fox-table mb-0">
            <thead><tr><th>#</th><th>Name</th><th>Symbol</th><th>Products</th><th>Action</th></tr></thead>
            <tbody>
<?php if (!$units): ?>
              <tr><td colspan="5" class="text-center empty-note">No units available. Add one!</td></tr>
<?php endif; ?>
<?php foreach ($units as $i => $u): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= e($u['name']) ?></td>
                <td><span class="badge badge-fox-blue"><?= e($u['symbol']) ?></span></td>
                <td><?= (int)$u['product_count'] ?></td>
                <td>
                  <form method="post" action="<?= e(url('units.php')) ?>" class="d-inline"
                        onsubmit="return confirm('Delete this unit?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-fox btn-sm btn-red">Delete</button>
                  </form>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</div>
<?php page_footer(); ?>
