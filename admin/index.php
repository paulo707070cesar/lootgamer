<?php
require_once __DIR__ . '/../includes/auth.php';
if (admin_user()) redirect(url('admin/dashboard.php'));
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (login_blocked()) $err = 'Muitas tentativas. Aguarde 15 minutos.';
    elseif (attempt_login(trim((string)($_POST['email'] ?? '')), (string)($_POST['password'] ?? ''))) redirect(url('admin/dashboard.php'));
    else $err = 'E-mail ou senha inválidos.';
}
?><!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Entrar · Admin</title><link rel="stylesheet" href="<?= asset('css/admin.css') ?>"></head>
<body class="login"><form method="post" class="loginbox"><h1>🎮 Loot Gamer</h1><p>Acesso administrativo</p>
<?php if ($err): ?><div class="alert alert-err"><?= e($err) ?></div><?php endif; ?><?= csrf_field() ?>
<label>E-mail<input type="email" name="email" required autofocus autocomplete="username"></label>
<label>Senha<input type="password" name="password" required autocomplete="current-password"></label>
<button class="btn" type="submit">Entrar</button></form></body></html>
