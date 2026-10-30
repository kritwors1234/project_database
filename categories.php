<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo = db();
$error = '';
$name = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post('categories.php');
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';

    if ($action === 'add') {
        $name = trim((string)(isset($_POST['name']) ? $_POST['name'] : ''));
        if ($name === '') {
            $error = 'กรุณากรอกชื่อหมวดหมู่';
        } elseif (str_len($name) > 200) {
            $error = 'ชื่อหมวดหมู่ยาวเกิน 200 ตัวอักษร';
        } else {
            $pdo->prepare('INSERT INTO dashboard_category (name, user_id) VALUES (?, ?)')->execute([$name, uid()]);
            flash('success', 'เพิ่มหมวดหมู่ "' . $name . '" เรียบร้อยแล้ว');
            redirect('categories.php');
        }
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $cnt = $pdo->prepare('SELECT COUNT(*) FROM dashboard_product WHERE category_id = ? AND user_id = ?');
        $cnt->execute([$id, uid()]);
        $n = (int)$cnt->fetchColumn();
        if ($n > 0) {
            flash('danger', 'ลบไม่ได้: ยังมีสินค้าใช้หมวดหมู่นี้อยู่ ' . $n . ' รายการ (รวมสินค้าที่ถูกลบแบบซ่อน) กรุณาย้ายหรือแก้ไขสินค้าก่อน');
        } else {
            $d = $pdo->prepare('DELETE FROM dashboard_category WHERE id = ? AND user_id = ?');
            $d->execute([$id, uid()]);
            flash($d->rowCount() ? 'success' : 'warning', $d->rowCount() ? 'ลบหมวดหมู่เรียบร้อยแล้ว' : 'ไม่พบหมวดหมู่นี้');
        }
        redirect('categories.php');
    }
}

$catSt = $pdo->prepare(
    'SELECT c.id, c.name, c.created_at, COUNT(p.id) AS product_count
       FROM dashboard_category c
       LEFT JOIN dashboard_product p ON p.category_id = c.id
      WHERE c.user_id = ?
      GROUP BY c.id, c.name, c.created_at
      ORDER BY c.id'
);
$catSt->execute([uid()]);
$cats = $catSt->fetchAll();

page_header('Categories', 'products', 'Categories');
?>
<div class="row">
  <div class="col-lg-4 mb-4">
    <section class="fox-card">
      <h2 class="tab-head">Add Category</h2>
      <div class="fox-card-body">
        <form method="post" action="<?= e(url('categories.php')) ?>" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <div class="form-group">
            <label for="cat-name">Category Name</label>
            <input type="text" id="cat-name" name="name" class="form-control" maxlength="200"
                   placeholder="Enter category name" required value="<?= e($name) ?>">
<?php if ($error): ?><div class="invalid-feedback d-block"><?= e($error) ?></div><?php endif; ?>
          </div>
          <button type="submit" class="btn btn-fox btn-green">Add Category</button>
          <a href="<?= e(url('units.php')) ?>" class="btn btn-fox btn-yellow">Manage Units</a>
        </form>
      </div>
    </section>
  </div>

  <div class="col-lg-8 mb-4">
    <section class="fox-card">
      <h2 class="tab-head">Categories List</h2>
      <div class="fox-card-body p-0">
        <div class="table-responsive">
          <table class="table fox-table mb-0">
            <thead><tr><th>#</th><th>Name</th><th>Products</th><th>Created</th><th>Action</th></tr></thead>
            <tbody>
<?php if (!$cats): ?>
              <tr><td colspan="5" class="text-center empty-note">No categories yet. Create one!</td></tr>
<?php endif; ?>
<?php foreach ($cats as $i => $c): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= e($c['name']) ?></td>
                <td><?= (int)$c['product_count'] ?></td>
                <td><?= e(fmt_dt($c['created_at'], 'd/m/Y')) ?></td>
                <td>
                  <form method="post" action="<?= e(url('categories.php')) ?>" class="d-inline"
                        onsubmit="return confirm('Are you sure?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
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
