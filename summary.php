<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

// กำไร = (ราคาขาย - ราคาทุน) x จำนวน  ตามโค้ดเดิม ใช้ราคาปัจจุบันของสินค้า (ไม่ใช่ราคา ณ วันที่ขาย)
$sumSt = db()->prepare(
    'SELECT p.id, p.name, c.name AS category_name,
            SUM(i.quantity) AS total_qty,
            SUM(i.total_price) AS total_amount,
            SUM((p.selling_price - p.cost_price) * i.quantity) AS total_profit
       FROM pos_possaleitem i
       JOIN pos_possale s ON s.id = i.sale_id AND s.is_cancelled = 0
       JOIN dashboard_product p ON p.id = i.product_id
       JOIN dashboard_category c ON c.id = p.category_id
      WHERE s.user_id = ?
      GROUP BY p.id, p.name, c.name
      ORDER BY total_profit DESC, p.name'
);
$sumSt->execute([uid()]);
$rows = $sumSt->fetchAll();

$totalSales = 0.0;
$totalProfit = 0.0;
foreach ($rows as $r) {
    $totalSales  += (float)$r['total_amount'];
    $totalProfit += (float)$r['total_profit'];
}

page_header('Summary', 'summary', 'Summary');
?>
<section class="fox-card">
  <h2 class="tab-head">Product Sales Summary</h2>
  <div class="fox-card-body">
    <div class="summary-totals">
      <div>Total Sales: <strong><?= e(baht($totalSales)) ?></strong></div>
      <div>Total Profit: <strong><?= e(baht($totalProfit)) ?></strong></div>
    </div>

    <div class="table-responsive">
      <table class="table fox-table mb-0">
        <thead>
          <tr>
            <th>#</th><th>Products</th><th>Category</th>
            <th class="text-right">Qty Sold</th><th class="text-right">Total Sales</th>
            <th class="text-right">Total Profit</th><th class="text-right">% of Total Profit</th>
          </tr>
        </thead>
        <tbody>
<?php if (!$rows): ?>
          <tr><td colspan="7" class="text-center empty-note">No sales data available.</td></tr>
<?php endif; ?>
<?php foreach ($rows as $i => $r):
    $share = $totalProfit != 0.0 ? ((float)$r['total_profit'] / $totalProfit) * 100 : 0.0;
?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= e($r['name']) ?></td>
            <td><?= e($r['category_name']) ?></td>
            <td class="text-right"><?= e(number_format((int)$r['total_qty'])) ?></td>
            <td class="text-right"><?= e(baht($r['total_amount'])) ?></td>
            <td class="text-right"><?= e(baht($r['total_profit'])) ?></td>
            <td class="text-right"><?= e(number_format($share, 1)) ?>%</td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php page_footer(); ?>
