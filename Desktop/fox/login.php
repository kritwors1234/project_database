<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}
ensure_users_table();

$error = '';
$username = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post('login.php');
    $username = trim((string)(isset($_POST['username']) && is_string($_POST['username']) ? $_POST['username'] : ''));
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    $st = db()->prepare('SELECT id, username, password_hash FROM fox_users WHERE username = ? LIMIT 1');
    $st->execute([$username]);
    $user = $st->fetch();

    $ok = password_verify($password, $user ? $user['password_hash'] : DUMMY_HASH) && $user;
    if ($ok) {
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            db()->prepare('UPDATE fox_users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        login_user((int)$user['id'], $user['username']);
        redirect('index.php');
    }
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
}

auth_header('เข้าสู่ระบบ');
?>
  <section class="fox-card">
    <h1 class="tab-head">เข้าสู่ระบบ</h1>
    <div class="fox-card-body">
<?php if ($error !== ''): ?>
      <div class="alert alert-danger py-2" role="alert"><?= e($error) ?></div>
<?php endif; ?>
      <form method="post" action="<?= e(url('login.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="username">ชื่อผู้ใช้ (Username)</label>
          <input type="text" id="username" name="username" class="form-control" maxlength="30"
                 autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus
                 value="<?= e($username) ?>">
        </div>
        <div class="form-group">
          <label for="password">รหัสผ่าน</label>
          <input type="password" id="password" name="password" class="form-control"
                 autocomplete="current-password" required>
        </div>
        <div class="form-check mb-3">
          <input type="checkbox" id="show-pass" class="form-check-input">
          <label for="show-pass" class="form-check-label">แสดงรหัสผ่าน</label>
        </div>
        <button type="submit" class="btn btn-fox btn-black btn-lg btn-block">เข้าสู่ระบบ</button>
      </form>
      <p class="auth-switch">ยังไม่มีบัญชี? <a href="<?= e(url('register.php')) ?>">สมัครสมาชิก</a></p>
    </div>
  </section>
<?php auth_footer();
