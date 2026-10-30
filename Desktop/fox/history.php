<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

/** แปลง YYYY-MM-DD เป็น DateTime (คืน null ถ้ารูปแบบไม่ถูกต้อง) */
function parse_ymd(string $s)
{
    if ($s === '') {
        return null;
    }
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $d : null;
}

$from = get_str('from');
$to   = get_str('to');
$where = ['user_id = ?'];
$params = [uid()];

$dFrom = parse_ymd($from);
if ($dFrom) {
    $where[] = 'created_at >= ?';
    $params[] = $dFrom->format('Y-m-d') . ' 00:00:00';
} else {
    $from = '';
}
$dTo = parse_ymd($to);
if ($dTo) {
    $dTo->modify('+1 day');   // นับรวมทั้งวันของวันที่สิ้นสุด
    $where[] = 'created_at < ?';
    $params[] = $dTo->format('Y-m-d') . ' 00:00:00';
} else {
    $to = '';
}

$sql = 'SELECT id, sale_no, total_amount, created_at, is_cancelled FROM pos_possale'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY created_at DESC, id DESC';
$st = db()->prepare($sql);
$st->execute($params);
$sales = $st->fetchAll();

page_header('Orders', 'orders', 'Orders');
?>
<section class="fox-card">
  <h2 class="tab-head">Sales History</h2>
  <div class="fox-card-body">
    <form method="get" action="<?= e(url('history.php')) ?>" class="mb-3">
      <div class="form-row align-items-end">
        <div class="form-group col-md-4">
          <label for="d-from">From (Day Start)</label>
          <input type="date" id="d-from" name="from" class="form-control" value="<?= e($from) ?>">
        </div>
        <div class="form-group col-md-4">
          <label for="d-to">To (Day End)</label>
          <input type="date" id="d-to" name="to" class="form-control" value="<?= e($to) ?>">
        </div>
        <div class="form-group col-md-4">
          <button type="submit" class="btn btn-fox btn-black">Filter</button>
          <a href="<?= e(url('history.php')) ?>" class="btn btn-fox btn-gray">Reset</a>
        </div>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table fox-table mb-0">
        <thead><tr><th>#</th><th>Sale No</th><th>Status</th><th>Total</th><th>Time</th><th>Action</th></tr></thead>
        <tbody>
<?php if (!$sales): ?>
          <tr><td colspan="6" class="text-center empty-note">No sales history available.</td></tr>
<?php endif; ?>
<?php foreach ($sales as $i => $s): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><strong><?= e($s['sale_no']) ?></strong></td>
            <td>
<?php if ((int)$s['is_cancelled'] === 1): ?>
              <span class="badge badge-fox-red">Cancelled</span>
<?php else: ?>
              <span class="badge badge-fox-green">Normal</span>
<?php endif; ?>
            </td>
            <td><?= e(baht($s['total_amount'], 0)) ?></td>
            <td><?= e(fmt_dt($s['created_at'], 'd/m/Y H:i')) ?></td>
            <td><a class="btn btn-fox btn-sm btn-green" href="<?= e(url('receipt.php?id=' . (int)$s['id'])) ?>">View Receipt</a></td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php page_footer(); ?>
