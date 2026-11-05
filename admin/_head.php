<?php
require_once __DIR__ . '/../includes/auth.php';
$me = require_admin();
$cur = basename($_SERVER['SCRIPT_NAME']);
$menu = ['dashboard.php' => '📊 Dashboard', 'products.php' => '🎮 Produtos', 'categories.php' => '🗂️ Categorias', 'stores.php' => '🏬 Lojas/Afiliados', 'banners.php' => '🖼️ Banners', 'clicks.php' => '🖱️ Cliques', 'pages.php' => '📄 Páginas', 'blog.php' => '✍️ Blog', 'messages.php' => '✉️ Mensagens', 'newsletter.php' => '📰 Newsletter', 'settings.php' => '⚙️ Configurações', 'logs.php' => '🧾 Logs'];
header('X-Robots-Tag: noindex, nofollow');
?><!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?= e($title ?? 'Admin') ?> · Loot Gamer</title><link rel="stylesheet" href="<?= asset('css/admin.css') ?>?v=2"></head><body>
<aside class="side"><div class="logo">🎮 Loot Gamer<small>Painel</small></div>
<nav><?php foreach ($menu as $f => $l): ?><a class="<?= $cur === $f ? 'on' : '' ?>" href="<?= url('admin/' . $f) ?>"><?= $l ?></a><?php endforeach; ?></nav>
<div class="who"><?= e($me['name']) ?><br><a href="<?= url() ?>" target="_blank">Ver site ↗</a> · <a href="<?= url('admin/logout.php') ?>">Sair</a></div></aside>
<main class="adm"><header class="adm-top"><h1><?= e($title ?? '') ?></h1><?= $actions ?? '' ?></header>
<?php foreach (flashes() as [$t, $m]): ?><div class="alert alert-<?= e($t) ?>"><?= e($m) ?></div><?php endforeach; ?>
