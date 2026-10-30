<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo = db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$st = $pdo->prepare('SELECT * FROM pos_possale WHERE id = ? AND user_id = ?');
$st->execute([$id, uid()]);
$sale = $st->fetch();
if (!$sale) {
    flash('danger', 'ไม่พบใบเสร็จนี้');
    redirect('history.php');
}

$it = $pdo->prepare(
    'SELECT i.quantity, i.unit_price, i.total_price, p.name
       FROM pos_possaleitem i
       JOIN dashboard_product p ON p.id = i.product_id
      WHERE i.sale_id = ?
      ORDER BY i.id'
);
$it->execute([$id]);
$items = $it->fetchAll();
$cancelled = (int)$sale['is_cancelled'] === 1;

page_header('Receipt ' . $sale['sale_no'], 'orders', 'Receipt');
?>
<div class="row">
  <div class="col-md-8 col-lg-6 mx-auto">
    <section class="fox-card receipt-card">
      <h2 class="tab-head no-print">Receipt</h2>
      <div class="fox-card-body">
        <div class="receipt-title">SALE NO.</div>
        <div class="receipt-no"><?= e($sale['sale_no']) ?></div>

<?php if ($cancelled): ?>
        <div class="alert alert-danger text-center">
          This receipt has been cancelled<?= $sale['cancelled_at'] ? ' (' . e(fmt_dt($sale['cancelled_at'], 'd/m/Y H:i')) . ')' : '' ?>
        </div>
<?php endif; ?>

        <table class="table table-sm receipt-table">
          <thead><tr><th>PRODUCT</th><th class="text-center">QTY</th><th class="text-right">PRICE</th></tr></thead>
          <tbody>
<?php foreach ($items as $row): ?>
            <tr>
              <td><?= e($row['name']) ?></td>
              <td class="text-center"><?= (int)$row['quantity'] ?></td>
              <td class="text-right"><?= e(baht($row['total_price'], 0)) ?></td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>

        <div class="receipt-total">
          <span>Total:</span>
          <strong><?= e(baht($sale['total_amount'], 0)) ?></strong>
        </div>

        <p class="receipt-meta">
          Payment: Cash<br>
          Time: <?= e(fmt_dt($sale['created_at'], 'd/m/Y H:i:s')) ?>
        </p>

        <div class="text-center no-print">
          <a href="<?= e(url('cashier.php')) ?>" class="btn btn-fox btn-black">Cashier</a>
          <button type="button" class="btn btn-fox btn-blue" onclick="window.print()">Print</button>
<?php if (!$cancelled): ?>
          <form method="post" action="<?= e(url('cancel_receipt.php')) ?>" class="d-inline"
                onsubmit="return confirm('Confirm cancellation? Stock will be returned.');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$sale['id'] ?>">
            <button type="submit" class="btn btn-fox btn-red">Cancel Receipt</button>
          </form>
<?php endif; ?>
        </div>
      </div>
    </section>
  </div>
</div>
<?php page_footer(); ?>
