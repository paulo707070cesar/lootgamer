<?php
require_once __DIR__ . '/includes/bootstrap.php';
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug'] ?? ''));
$pg = q('SELECT * FROM pages WHERE slug = ? AND is_active = 1', [$slug])->fetch();
if (!$pg) { include __DIR__ . '/404.php'; exit; }
$pageTitle = $pg['seo_title'] ?: $pg['title']; $metaDesc = $pg['meta_description'] ?: $pg['title'];
include ROOT . '/includes/header.php'; ?>
<div class="wrap"><nav class="crumbs"><a href="<?= url() ?>">Início</a> › <span><?= e($pg['title']) ?></span></nav>
<article class="page"><h1><?= e($pg['title']) ?></h1><div class="page-body"><?= $pg['content'] /* HTML escrito pelo administrador */ ?></div></article></div>
<?php include ROOT . '/includes/footer.php';
