<?php
$pct = discount_pct($p);
$badge = $pct ? '-' . $pct . '%' : '';
$affUrl = trim((string)($p['affiliate_url'] ?? ''));
$isBlog = strpos($affUrl, '/blog/') !== false;
$goUrl = $isBlog ? $affUrl : url('ir/' . (int)$p['id']);
$btnText = $isBlog ? '🔍 Veja nossa análise' : '🛒 Confira Oferta';
$btnAttr = $isBlog ? '' : 'rel="sponsored nofollow noopener" target="_blank"';

$imgSrc = $p['image_path'] ?? $p['image_url'] ?? '';
$imgSrc = trim($imgSrc);
if (strpos($imgSrc, 'https://lootgamer.xll.com.br') === 0) {
    $imgSrc = str_replace('https://lootgamer.xll.com.br', '', $imgSrc);
}
if ($imgSrc && $imgSrc[0] !== '/') $imgSrc = '/' . $imgSrc;
if (!$imgSrc) $imgSrc = '[STRIPPED 66 bytes].webp';
?>
<article class="card">
  <a class="card-img" href="<?= e($goUrl) ?>">
    <?php if ($badge): ?><span class="badge-off"><?= e($badge) ?></span><?php endif; ?>
    <img src="<?= e($imgSrc) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
  </a>
  <div class="card-body">
    <h3 class="card-title"><a href="<?= e($goUrl) ?>"><?= e($p['name']) ?></a></h3>
    <div class="card-price">
      <?php if (!empty($p['old_price'])): ?><s><?= money($p['old_price']) ?></s><?php endif; ?>
      <strong><?= money($p['price']) ?></strong>
    </div>
    <a class="btn-offer <?= $isBlog ? 'alt' : '' ?>" href="<?= e($goUrl) ?>" <?= $btnAttr ?>><?= $btnText ?></a>
  </div>
</article>