<?php
require __DIR__ . '/includes/bootstrap.php';

// ออกจากระบบด้วย POST + CSRF เท่านั้น
require_post('index.php');
logout_user();
flash('success', 'ออกจากระบบเรียบร้อยแล้ว');
redirect('login.php');
