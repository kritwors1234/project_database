<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$stats = get_stats();

// ยอดขายแยกตามหมวดหมู่ (เฉพาะบิลที่ไม่ถูกยกเลิก)
$rowSt = db()->prepare(
    'SELECT c.name AS label, SUM(i.total_price) AS amount
       FROM pos_possaleitem i
       JOIN pos_possale s ON s.id = i.sale_id AND s.is_cancelled = 0
       JOIN dashboard_product p ON p.id = i.product_id
       JOIN dashboard_category c ON c.id = p.category_id
     WHERE s.user_id = ?
      GROUP BY c.id, c.name
      ORDER BY amount DESC'
);
$rowSt->execute([uid()]);
$rows = $rowSt->fetchAll();

$labels  = [];
$amounts = [];
foreach ($rows as $r) {
    $labels[]  = $r['label'];
    $amounts[] = round((float)$r['amount'], 2);
}
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

page_header('Dashboard', 'dashboard', 'Dashboard', true);
?>
<div class="row">
  <div class="col-lg-4 mb-4">
    <section class="fox-card h-100">
      <h2 class="tab-head">Information</h2>
      <div class="fox-card-body">
        <div class="info-line">
          <span class="info-label">Total Stock Cost:</span>
          <span class="info-value"><?= e(baht($stats['cost_stock'])) ?></span>
        </div>
        <div class="info-line">
          <span class="info-label">Expected Profit:</span>
          <span class="info-value"><?= e(baht($stats['profit_stock'])) ?></span>
        </div>
      </div>
    </section>
  </div>

  <div class="col-lg-8 mb-4">
    <section class="fox-card h-100">
      <h2 class="tab-head">Statistics</h2>
      <div class="fox-card-body">
        <div class="row">
          <div class="col-md-4 mb-2">
            <a class="stat-box" href="<?= e(url('summary.php')) ?>">
              <span class="stat-label">Summary</span>
              <span class="stat-value"><?= e(baht($stats['sales_amount'])) ?></span>
            </a>
          </div>
          <div class="col-md-4 mb-2">
            <a class="stat-box" href="<?= e(url('products.php')) ?>">
              <span class="stat-label">Products</span>
              <span class="stat-value"><?= e(number_format($stats['pieces'])) ?> Items</span>
            </a>
          </div>
          <div class="col-md-4 mb-2">
            <a class="stat-box" href="<?= e(url('history.php')) ?>">
              <span class="stat-label">Orders</span>
              <span class="stat-value"><?= e(number_format($stats['orders'])) ?></span>
            </a>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<div class="row">
  <div class="col-lg-6 mb-4">
    <section class="fox-card h-100">
      <h2 class="tab-head">Sales Share by Category</h2>
      <div class="fox-card-body">
<?php if ($labels): ?>
        <div class="chart-box"><canvas id="shareChart"></canvas></div>
<?php else: ?>
        <p class="empty-note">No sales data yet.</p>
<?php endif; ?>
      </div>
    </section>
  </div>
  <div class="col-lg-6 mb-4">
    <section class="fox-card h-100">
      <h2 class="tab-head">Sales Volume by Category</h2>
      <div class="fox-card-body">
<?php if ($labels): ?>
        <div class="chart-box"><canvas id="volumeChart"></canvas></div>
<?php else: ?>
        <p class="empty-note">No sales data yet.</p>
<?php endif; ?>
      </div>
    </section>
  </div>
</div>

<?php if ($labels): ?>
<script id="chart-labels" type="application/json"><?= json_encode($labels, $jsonFlags) ?></script>
<script id="chart-amounts" type="application/json"><?= json_encode($amounts) ?></script>
<script>
(function () {
  var labels  = JSON.parse(document.getElementById('chart-labels').textContent);
  var amounts = JSON.parse(document.getElementById('chart-amounts').textContent);

  // สีชุดแรกตามภาพตัวอย่าง ที่เหลือสร้างอัตโนมัติ
  var base = ['#4f86f7', '#a78bfa', '#f6b26b', '#ffe066', '#7bc67e', '#f28b82', '#5fc9d6', '#c792ea'];
  var colors = labels.map(function (_, i) {
    return i < base.length ? base[i] : 'hsl(' + Math.floor((i * 137.508) % 360) + ', 65%, 60%)';
  });
  var total = amounts.reduce(function (a, b) { return a + b; }, 0) || 1;
  var fontFamily = "'Poppins', 'Noto Sans Thai', sans-serif";
  Chart.defaults.global.defaultFontFamily = fontFamily;

  function money(v) { return '฿' + Number(v).toLocaleString('en-US'); }

  // เขียนตัวเลขเหนือแท่งกราฟ
  Chart.plugins.register({
    afterDatasetsDraw: function (chart) {
      if (chart.config.type !== 'bar') { return; }
      var ctx = chart.ctx;
      chart.data.datasets.forEach(function (ds, di) {
        chart.getDatasetMeta(di).data.forEach(function (bar, i) {
          ctx.save();
          ctx.fillStyle = '#333';
          ctx.font = '11px ' + fontFamily;
          ctx.textAlign = 'center';
          ctx.fillText(Number(ds.data[i]).toLocaleString('en-US'), bar._model.x, bar._model.y - 6);
          ctx.restore();
        });
      });
    }
  });

  var legendByCategory = {
    position: 'top',
    labels: {
      boxWidth: 10,
      generateLabels: function (chart) {
        return chart.data.labels.map(function (l, i) {
          return { text: l, fillStyle: colors[i], strokeStyle: colors[i], index: i };
        });
      }
    },
    onClick: function () {}
  };

  new Chart(document.getElementById('shareChart'), {
    type: 'doughnut',
    data: { labels: labels, datasets: [{ data: amounts, backgroundColor: colors, borderWidth: 2 }] },
    options: {
      responsive: true, maintainAspectRatio: false, cutoutPercentage: 50,
      legend: { position: 'top', labels: { boxWidth: 10 } },
      tooltips: { callbacks: { label: function (t, d) {
        var v = Number(d.datasets[0].data[t.index]);
        return ' ' + d.labels[t.index] + ': ' + money(v) + ' (' + (v * 100 / total).toFixed(1) + '%)';
      } } }
    }
  });

  new Chart(document.getElementById('volumeChart'), {
    type: 'bar',
    data: { labels: labels, datasets: [{ label: 'Total Sales (฿)', data: amounts, backgroundColor: colors }] },
    options: {
      responsive: true, maintainAspectRatio: false,
      layout: { padding: { top: 18 } },
      legend: legendByCategory,
      tooltips: { callbacks: { label: function (t) { return ' ' + money(t.yLabel); } } },
      scales: { yAxes: [{ ticks: { beginAtZero: true, callback: function (v) { return money(v); } } }] }
    }
  });
})();
</script>
<?php endif; ?>
<?php page_footer(); ?>
