<?php
require_once __DIR__ . '/bootstrap.php';
$siteName = setting('site_name', 'Loot Gamer');
$pageTitle = $pageTitle ?? '';
$metaDesc = $metaDesc ?? setting('site_description', 'Ofertas e achadinhos da net em games, consoles, acessórios gamer, PC e tecnologia.');
$canonical = $canonical ?? ((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
$ogImage = $ogImage ?? null;
$allCats = q('SELECT id,name,slug FROM categories WHERE is_active = 1 AND is_main = 1 ORDER BY sort_order, name')->fetchAll();
$logo = setting('logo_path') ? url(setting('logo_path')) : asset('images/seed/logo.png');
$navMap = [['jogos-digitais', '🎮', 'Games'], ['consoles', '🕹️', 'Consoles'], ['acessorios', '🎧', 'Acessórios'], ['pc-gamer', '🖥️', 'PC Gamer'], ['gift-cards', '🎁', 'Gift Cards']];
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(page_title($pageTitle)) ?></title>
<meta name="description" content="<?= e($metaDesc) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
<meta property="og:title" content="<?= e($ogTitle ?? page_title($pageTitle)) ?>">
<meta property="og:description" content="<?= e($ogDesc ?? $metaDesc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<?php if ($ogImage): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
<meta name="twitter:card" content="<?= $ogImage ? 'summary_large_image' : 'summary' ?>">
<?php if (setting('favicon_path')): ?><link rel="icon" href="<?= e(url(setting('favicon_path'))) ?>"><?php endif; ?>
<?php if (!empty($robotsNoindex) || !empty($robotsNofollow)): ?><meta name="robots" content="<?= !empty($robotsNoindex) ? 'noindex' : 'index' ?>,<?= !empty($robotsNofollow) ? 'nofollow' : 'follow' ?>"><?php endif; ?>
<meta name="theme-color" content="#0a1440">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=4">
<?php if (!empty($jsonLd)): ?><script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script><?php endif; ?>
<?php if (setting('analytics_id')): ?><meta name="lg-analytics" content="<?= e(preg_replace('/[^A-Za-z0-9\-]/', '', setting('analytics_id'))) ?>"><?php endif; ?>
<?= $extraHead ?? '' ?>
</head>
<body>
<header class="site-header">
  <div class="wrap hd-top">
    <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu" aria-expanded="false">☰</button>
    <a class="brand" href="<?= url() ?>" aria-label="<?= e($siteName) ?>">
      <img src="<?= e($logo) ?>" alt="<?= e($siteName) ?>" width="150" height="52">
    </a>
    <div class="tagline"><span class="tg-ico">🛒</span><span>Ofertas e<br>Achadinhos da Net</span></div>
    <form class="search" action="<?= url('busca') ?>" method="get" role="search">
      <input type="search" name="q" id="searchInput" placeholder="Busque por games, consoles, acessórios..." value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off" aria-label="Buscar produtos">
      <button type="submit" aria-label="Buscar">🔍</button>
      <div class="suggest" id="suggestBox" hidden></div>
    </form>
    <div class="hd-icons">
      <a href="<?= url('lojas') ?>"><span>🏬</span>Lojas</a>
      <a href="<?= url('contato') ?>"><span>✉️</span>Contato</a>
    </div>
  </div>
  <div class="wrap hd-nav" id="mainNav">
    <div class="allcats">
      <button class="btn-allcats" id="allcatsBtn" aria-expanded="false">☰ Todas as Categorias</button>
      <ul class="allcats-list" id="allcatsList" hidden>
        <?php foreach ($allCats as $c): ?><li><a href="<?= url('categoria/' . $c['slug']) ?>"><?= e($c['name']) ?></a></li><?php endforeach; ?>
      </ul>
    </div>
    <nav class="navbar" aria-label="Principal">
      <?php foreach ($navMap as [$slug, $ico, $label]): ?><a href="<?= url('categoria/' . $slug) ?>"><span><?= $ico ?></span><?= e($label) ?></a><?php endforeach; ?>
      <a href="<?= url('ofertas') ?>"><span>🏷️</span>Ofertas</a>
      <a href="<?= url('blog') ?>"><span>📝</span>Blog</a>
      <a href="<?= url('novidades') ?>"><span>⭐</span>Novidades</a>
    </nav>
  </div>
</header>
<main id="conteudo">
<?php foreach (flashes() as [$t, $m]): ?><div class="wrap"><div class="alert alert-<?= e($t) ?>"><?= e($m) ?></div></div><?php endforeach; ?>