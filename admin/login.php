<?php
/**
 * Loot Gamer - login do painel.
 */

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
xll_require_installed();

if (xll_admin_logged_in()) {
    xll_redirect('admin/index.php');
}

$error = '';
$usuario = '';
if (xll_is_post()) {
    xll_require_csrf();
    $usuario = adm_login_text('usuario');
    [$ok, $message] = xll_admin_login($usuario, (string) ($_POST['senha'] ?? ''));
    if ($ok) {
        xll_flash('success', $message);
        $next = (string) ($_SESSION['_xll_admin_next'] ?? '');
        unset($_SESSION['_xll_admin_next']);
        if ($next !== '' && str_contains($next, '/admin/') && !str_contains($next, 'login.php')) {
            header('Location: ' . $next);
            exit;
        }
        xll_redirect('admin/index.php');
    }
    $error = $message;
}

function adm_login_text(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

$siteName = (string) xll_setting('site_name', 'Loot Gamer');
$logo = xll_media_url((string) xll_setting('logo', 'assets/img/logo.webp'));
?><!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Entrar no painel — <?= e($siteName) ?></title>
    <link rel="icon" type="image/png" href="<?= e(xll_media_url((string) xll_setting('favicon', 'assets/img/favicon-192.png'))) ?>">
    <link rel="stylesheet" href="<?= e(xll_asset('assets/css/admin.css')) ?>">
</head>
<body class="adm-body">
<div class="adm-login-wrap">
    <div class="adm-login">
        <a class="adm-login-brand" href="<?= e(xll_url('index.php')) ?>">
            <img src="<?= e($logo) ?>" alt="<?= e($siteName) ?>">
        </a>
        <h1>Painel administrativo</h1>
        <p class="adm-login-sub">Entre para gerenciar produtos, banners e personalizar o site.</p>

        <?php if ($error !== ''): ?>
            <div class="adm-flash adm-flash-error" style="margin-bottom:16px"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(xll_url('admin/login.php')) ?>" autocomplete="on">
            <?= xll_csrf_field() ?>
            <div class="adm-field">
                <label class="adm-label" for="usuario">Usuário</label>
                <input class="adm-input" id="usuario" type="text" name="usuario" value="<?= e($usuario) ?>" autocomplete="username" required autofocus>
            </div>
            <div class="adm-field">
                <label class="adm-label" for="senha">Senha</label>
                <input class="adm-input" id="senha" type="password" name="senha" autocomplete="current-password" required>
            </div>
            <button type="submit" class="adm-btn adm-btn-block">Entrar no painel</button>
        </form>

        <div class="adm-login-foot">
            <a href="<?= e(xll_url('index.php')) ?>">← Voltar para o site</a>
            <p style="margin-top:10px">Primeiro acesso? Use o usuário e senha definidos na instalação.</p>
        </div>
    </div>
</div>
</body>
</html>
