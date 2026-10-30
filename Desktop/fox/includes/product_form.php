<?php
/** ตรวจสอบข้อมูลฟอร์มสินค้า คืนค่า [ข้อมูลที่ทำความสะอาดแล้ว, รายการ error] */
function product_validate(array $in, int $editId = 0): array
{
    $pdo = db();
    $errors = [];

    $d = [
        'name'          => trim((string)(isset($in['name']) ? $in['name'] : '')),
        'category_id'   => trim((string)(isset($in['category_id']) ? $in['category_id'] : '')),
        'unit_id'       => trim((string)(isset($in['unit_id']) ? $in['unit_id'] : '')),
        'sku'           => trim((string)(isset($in['sku']) ? $in['sku'] : '')),
        'quantity'      => trim((string)(isset($in['quantity']) ? $in['quantity'] : '0')),
        'cost_price'    => str_replace(',', '', trim((string)(isset($in['cost_price']) ? $in['cost_price'] : '0'))),
        'selling_price' => str_replace(',', '', trim((string)(isset($in['selling_price']) ? $in['selling_price'] : '0'))),
    ];

    if ($d['name'] === '') {
        $errors['name'] = 'กรุณากรอกชื่อสินค้า';
    } elseif (str_len($d['name']) > 200) {
        $errors['name'] = 'ชื่อสินค้ายาวเกิน 200 ตัวอักษร';
    }

    if ($d['category_id'] === '' || !ctype_digit($d['category_id'])) {
        $errors['category_id'] = 'กรุณาเลือกหมวดหมู่';
    } else {
        $st = $pdo->prepare('SELECT 1 FROM dashboard_category WHERE id = ? AND user_id = ?');
        $st->execute([(int)$d['category_id'], uid()]);
        if (!$st->fetchColumn()) {
            $errors['category_id'] = 'ไม่พบหมวดหมู่ที่เลือก';
        }
    }

    if ($d['unit_id'] !== '') {
        if (!ctype_digit($d['unit_id'])) {
            $errors['unit_id'] = 'หน่วยนับไม่ถูกต้อง';
        } else {
            $st = $pdo->prepare('SELECT 1 FROM dashboard_unit WHERE id = ? AND user_id = ?');
            $st->execute([(int)$d['unit_id'], uid()]);
            if (!$st->fetchColumn()) {
                $errors['unit_id'] = 'ไม่พบหน่วยนับที่เลือก';
            }
        }
    }

    if ($d['sku'] === '') {
        $errors['sku'] = 'กรุณากรอก SKU';
    } elseif (str_len($d['sku']) > 50) {
        $errors['sku'] = 'SKU ยาวเกิน 50 ตัวอักษร';
    } else {
        $st = $pdo->prepare('SELECT 1 FROM dashboard_product WHERE sku = ? AND id <> ? AND user_id = ?');
        $st->execute([$d['sku'], $editId, uid()]);
        if ($st->fetchColumn()) {
            $errors['sku'] = 'SKU นี้ถูกใช้แล้ว';
        }
    }

    if (!preg_match('/^\d{1,9}$/', $d['quantity']) || (int)$d['quantity'] > 2147483647) {
        $errors['quantity'] = 'จำนวนต้องเป็นเลขจำนวนเต็มตั้งแต่ 0 ขึ้นไป';
    }
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $d['cost_price'])) {
        $errors['cost_price'] = 'ราคาทุนไม่ถูกต้อง (เช่น 25 หรือ 25.50)';
    }
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $d['selling_price'])) {
        $errors['selling_price'] = 'ราคาขายไม่ถูกต้อง (เช่น 39 หรือ 39.50)';
    }

    return [$d, $errors];
}

/** แสดงฟอร์มสินค้า (ใช้ทั้งหน้าเพิ่มและแก้ไข) */
function product_form(array $d, array $errors, bool $isEdit, int $productId = 0): void
{
    $pdo = db();
    $cs = $pdo->prepare('SELECT id, name FROM dashboard_category WHERE user_id = ? ORDER BY name');
    $cs->execute([uid()]);
    $cats = $cs->fetchAll();
    $us = $pdo->prepare('SELECT id, name, symbol FROM dashboard_unit WHERE user_id = ? ORDER BY name');
    $us->execute([uid()]);
    $units = $us->fetchAll();
    $action = $isEdit ? url('product_edit.php?id=' . $productId) : url('products.php');

    $err = function ($key) use ($errors) {
        return isset($errors[$key]) ? '<div class="invalid-feedback d-block">' . e($errors[$key]) . '</div>' : '';
    };
    ?>
<form method="post" action="<?= e($action) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="form-row">
    <div class="form-group col-md-4">
      <label for="f-name">Name</label>
      <input type="text" id="f-name" name="name" class="form-control" maxlength="200" required
             value="<?= e($d['name']) ?>">
      <?= $err('name') ?>
    </div>
    <div class="form-group col-md-4">
      <label for="f-cat">Category</label>
      <select id="f-cat" name="category_id" class="form-control" required>
        <option value="">Select category</option>
<?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>"<?= (string)$c['id'] === (string)$d['category_id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
<?php endforeach; ?>
      </select>
      <?= $err('category_id') ?>
<?php if (!$cats): ?>
      <small class="form-text text-muted">ยังไม่มีหมวดหมู่ <a href="<?= e(url('categories.php')) ?>">เพิ่มหมวดหมู่ก่อน</a></small>
<?php endif; ?>
    </div>
    <div class="form-group col-md-4">
      <label for="f-unit">Unit</label>
      <select id="f-unit" name="unit_id" class="form-control">
        <option value="">-</option>
<?php foreach ($units as $u): ?>
        <option value="<?= (int)$u['id'] ?>"<?= (string)$u['id'] === (string)$d['unit_id'] ? ' selected' : '' ?>><?= e($u['name']) ?> (<?= e($u['symbol']) ?>)</option>
<?php endforeach; ?>
      </select>
      <?= $err('unit_id') ?>
    </div>
  </div>

  <div class="form-row">
    <div class="form-group col-md-4">
      <label for="f-cost">Cost Price</label>
      <input type="text" inputmode="decimal" id="f-cost" name="cost_price" class="form-control"
             value="<?= e($d['cost_price']) ?>">
      <?= $err('cost_price') ?>
    </div>
    <div class="form-group col-md-4">
      <label for="f-sell">Selling Price</label>
      <input type="text" inputmode="decimal" id="f-sell" name="selling_price" class="form-control"
             value="<?= e($d['selling_price']) ?>">
      <?= $err('selling_price') ?>
    </div>
    <div class="form-group col-md-4">
      <label for="f-sku">SKU</label>
      <input type="text" id="f-sku" name="sku" class="form-control" maxlength="50" required
             value="<?= e($d['sku']) ?>">
      <?= $err('sku') ?>
    </div>
  </div>

  <div class="form-row align-items-end">
    <div class="form-group col-md-4">
      <label for="f-qty">Quantity</label>
      <input type="text" inputmode="numeric" id="f-qty" name="quantity" class="form-control"
             value="<?= e($d['quantity']) ?>">
      <?= $err('quantity') ?>
    </div>
    <div class="form-group col-md-8">
<?php if ($isEdit): ?>
      <button type="submit" class="btn btn-fox btn-green">Update Product</button>
      <a href="<?= e(url('products.php')) ?>" class="btn btn-fox btn-gray">Cancel</a>
<?php else: ?>
      <button type="submit" class="btn btn-fox btn-green">Add Product</button>
<?php endif; ?>
      <a href="<?= e(url('categories.php')) ?>" class="btn btn-fox btn-yellow">Edit Category</a>
    </div>
  </div>
</form>
<?php
}
