<?php
/**
 * โครงหน้าเว็บ (ส่วนหัว/ท้าย)
 * $active: dashboard | products | cashier | orders | summary
 */

/** <head> ที่ใช้ร่วมกันทุกหน้า (รวมหน้า login/register) */
function render_head(string $title, bool $withChart = false): void
{
    ?><!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= e($title) ?> | <?= e(APP_NAME) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700&family=Poppins:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>?v=2">
<?php if ($withChart): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<?php endif; ?>
</head>
<?php
}

/** หน้าแม่ของแต่ละหน้า (ใช้กับปุ่มย้อนกลับ) หน้า Dashboard ไม่มีปุ่ม */
function default_back_url(): string
{
    $map = [
        'products.php'        => 'index.php',
        'cashier.php'         => 'index.php',
        'history.php'         => 'index.php',
        'summary.php'         => 'index.php',
        'categories.php'      => 'products.php',
        'units.php'           => 'products.php',
        'product_edit.php'    => 'products.php',
        'receipt.php'         => 'history.php',
    ];
    $page = basename($_SERVER['SCRIPT_NAME'] ?? '');
    return isset($map[$page]) ? $map[$page] : '';
}

function page_header(string $title, string $active = '', string $heading = '', bool $withChart = false, ?string $back = null): void
{
    $nav = [
        'dashboard' => ['Dashboard', 'index.php'],
        'products'  => ['Products',  'products.php'],
        'cashier'   => ['Cashier',   'cashier.php'],
        'orders'    => ['Orders',    'history.php'],
        'summary'   => ['Summary',   'summary.php'],
    ];
    if ($back === null) {
        $back = default_back_url();
    }
    render_head($title, $withChart);
    ?>
<body>
<nav class="navbar navbar-expand-lg navbar-dark fox-nav no-print">
  <div class="container">
    <a class="navbar-brand" href="<?= e(url('index.php')) ?>"><?= e(APP_NAME) ?></a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#foxNav"
            aria-controls="foxNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="foxNav">
      <ul class="navbar-nav mr-auto">
<?php foreach ($nav as $key => $item): ?>
        <li class="nav-item<?= $key === $active ? ' active' : '' ?>">
          <a class="nav-link" href="<?= e(url($item[1])) ?>"><?= e($item[0]) ?></a>
        </li>
<?php endforeach; ?>
      </ul>
<?php if (is_logged_in()): ?>
      <form method="post" action="<?= e(url('logout.php')) ?>" class="form-inline nav-user">
        <?= csrf_field() ?>
        <span class="navbar-text mr-lg-3"><?= e(current_username()) ?></span>
        <button type="submit" class="btn btn-sm btn-outline-light">ออกจากระบบ</button>
      </form>
<?php endif; ?>
    </div>
  </div>
</nav>

<main class="container py-4">
<?php foreach (flash_take() as $f): ?>
  <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show no-print" role="alert">
    <?= e($f['message']) ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  </div>
<?php endforeach; ?>
<?php if ($heading !== '' || $back !== ''): ?>
  <div class="page-top no-print">
<?php if ($back !== ''): ?>
    <a href="<?= e(url($back)) ?>" class="back-btn" aria-label="ย้อนกลับ" title="ย้อนกลับ">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.6"
           stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>
    </a>
<?php endif; ?>
<?php if ($heading !== ''): ?>
    <h1 class="page-pill"><?= e($heading) ?></h1>
<?php endif; ?>
  </div>
<?php endif; ?>
<?php
}

function page_footer(): void
{
    ?></main>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"
        integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
</body>
</html>
<?php
}

/** หน้า login / register (ไม่มีเมนู) */
function auth_header(string $title): void
{
    render_head($title);
    ?>
<body class="auth-body">
<main class="auth-wrap">
  <div class="auth-brand"><?= e(APP_NAME) ?></div>
<?php foreach (flash_take() as $f): ?>
  <div class="alert alert-<?= e($f['type']) ?> auth-alert" role="alert"><?= e($f['message']) ?></div>
<?php endforeach; ?>
<?php
}

function auth_footer(): void
{
    ?></main>
<script>
  // ปุ่มแสดง/ซ่อนรหัสผ่าน
  (function () {
    var cb = document.getElementById('show-pass');
    var pw = document.getElementById('password');
    if (cb && pw) cb.addEventListener('change', function () { pw.type = cb.checked ? 'text' : 'password'; });
  })();
</script>
</body>
</html>
<?php
}
