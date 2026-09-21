<?php
require __DIR__ . '/config/app.php';
$u = current_user();
if (!$u) redirect('login.php');
redirect($u['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
