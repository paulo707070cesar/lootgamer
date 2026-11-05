<?php
require_once __DIR__ . '/../includes/auth.php';
if (!empty($_SESSION['uid'])) log_admin('logout');
$_SESSION = []; session_destroy();
redirect(url('admin/index.php'));
