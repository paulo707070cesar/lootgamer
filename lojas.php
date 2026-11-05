<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Lojas e parceiros'; $metaDesc = 'Lojas e programas de afiliados divulgados neste site.';
$stores = q("SELECT s.*, (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id AND p.status='publicado') AS total FROM affiliate_stores s WHERE s.is_active = 1 ORDER BY s.name")->fetchAll();
include ROOT . '/includes/header.php'; ?>
<div class="wrap"><nav class="crumbs"><a href="<?= url() ?>">Início</a> › <span>Lojas</span></nav>
<div class="sec-head"><h1>Lojas e Parceiros</h1></div>
<p class="intro">Divulgamos produtos de lojas parceiras e podemos receber comissão por compras feitas pelos links. Veja a <a href="<?= url('divulgacao-afiliados') ?>">divulgação de afiliados</a>.</p>
<div class="stores"><?php foreach ($stores as $s): ?>
  <div class="store">
    <?php if ($s['logo_path']): ?><img src="<?= e(url($s['logo_path'])) ?>" alt="<?= e($s['name']) ?>" loading="lazy" data-fallback><?php endif; ?>
    <h3><?= e($s['name']) ?></h3><p><?= (int)$s['total'] ?> produto(s) neste site</p>
    <?php if ($s['official_url'] && valid_http_url($s['official_url'])): ?><a href="<?= e($s['official_url']) ?>" target="_blank" rel="nofollow noopener">Site oficial</a><?php endif; ?>
    <a href="<?= url('busca?loja=' . (int)$s['id'] . '&q=') ?>" hidden></a>
  </div><?php endforeach; ?>
  <?php if (!$stores): ?><p class="empty">Nenhuma loja cadastrada ainda.</p><?php endif; ?></div></div>
<?php include ROOT . '/includes/footer.php';
