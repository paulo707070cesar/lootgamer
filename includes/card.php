<?php
$pct = discount_pct($p);
$badge = $pct ? '-' . $pct . '%' : '';
$affUrl = trim((string)($p['affiliate_url'] ?? ''));
$isBlog = strpos($affUrl, '/blog/') !== false;
$goUrl = $isBlog ? $affUrl : url('ir/' . (int)$p['id']);
$btnText = $isBlog ? '🔍 Veja nossa análise' : '🛒 Confira Oferta';
$btnAttr = $isBlog ? '' : 'rel="sponsored nofollow noopener" target="_blank"';

// Usa a mesma função das demais páginas: aceita imagem local, URL externa
// e aplica o placeholder quando o produto não possui imagem válida.
$imgSrc = product_img($p);
?>
<article class="card">
  <a class="card-img" href="<?= e($goUrl) ?>">
    <?php if ($badge): ?><span class="badge-off"><?= e($badge) ?></span><?php endif; ?>
    <img src="<?= e($imgSrc) ?>" alt="<?= e($p['name']) ?>" loading="lazy" data-fallback>
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