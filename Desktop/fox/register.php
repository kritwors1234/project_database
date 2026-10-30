<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}
ensure_users_table();

$errors = [];
$username = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post('register.php');
    $username = trim((string)(isset($_POST['username']) && is_string($_POST['username']) ? $_POST['username'] : ''));
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    // username ตั้งอะไรก็ได้ (ตัวอักษร ตัวเลข สัญลักษณ์ ภาษาไทย ฯลฯ) ขอแค่ไม่ว่างและไม่เกิน 30 ตัวอักษร
    if ($username === '') {
        $errors['username'] = 'กรุณากรอกชื่อผู้ใช้';
    } elseif (str_len($username) > USERNAME_MAX_LEN) {
        $errors['username'] = 'ชื่อผู้ใช้ยาวเกิน ' . USERNAME_MAX_LEN . ' ตัวอักษร';
    }
    // รหัสผ่านตั้งอะไรก็ได้ ขอแค่ยาวอย่างน้อย 8 ตัวอักษร
    if (str_len($password) < PASSWORD_MIN_LEN) {
        $errors['password'] = 'รหัสผ่านต้องยาวอย่างน้อย ' . PASSWORD_MIN_LEN . ' ตัวอักษร';
    }

    if (!$errors) {
        try {
            $pdo = db();
            $pdo->prepare('INSERT INTO fox_users (username, password_hash) VALUES (?, ?)')
                ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $newId = (int)$pdo->lastInsertId();
            login_user($newId, $username);
            flash('success', 'สมัครสมาชิกสำเร็จ ยินดีต้อนรับ ' . $username);
            redirect('index.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {   // username ซ้ำ
                $errors['username'] = 'ชื่อผู้ใช้นี้ถูกใช้แล้ว กรุณาเลือกชื่อใหม่';
            } else {
                throw $ex;
            }
        }
    }
}

auth_header('สมัครสมาชิก');
?>
  <section class="fox-card">
    <h1 class="tab-head">สมัครสมาชิก</h1>
    <div class="fox-card-body">
      <form method="post" action="<?= e(url('register.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="username">ชื่อผู้ใช้ (Username)</label>
          <input type="text" id="username" name="username" class="form-control" maxlength="30"
                 autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus
                 value="<?= e($username) ?>">
<?php if (isset($errors['username'])): ?><div class="invalid-feedback d-block"><?= e($errors['username']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label for="password">รหัสผ่าน</label>
          <input type="password" id="password" name="password" class="form-control"
                 autocomplete="new-password" required>
<?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block"><?= e($errors['password']) ?></div><?php else: ?>
          <small class="form-text text-muted">ต้องมีอย่างน้อย <?= (int)PASSWORD_MIN_LEN ?> ตัว</small>
<?php endif; ?>
        </div>
        <div class="form-check mb-3">
          <input type="checkbox" id="show-pass" class="form-check-input">
          <label for="show-pass" class="form-check-label">แสดงรหัสผ่าน</label>
        </div>
        <button type="submit" class="btn btn-fox btn-black btn-lg btn-block">สมัครสมาชิก</button>
      </form>
      <p class="auth-switch">มีบัญชีอยู่แล้ว? <a href="<?= e(url('login.php')) ?>">เข้าสู่ระบบ</a></p>
    </div>
  </section>
<?php auth_footer();
