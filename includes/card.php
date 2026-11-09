<?php
$pct = discount_pct($p);
$badge = $pct ? '-' . $pct . '%' : '';

// Todos os elementos do card levam à página individual do produto.
// O redirecionamento para a loja afiliada acontece somente no botão
// de oferta exibido dentro de produto.php.
$goUrl = url('produto/' . (string)$p['slug']);

// Usa a mesma função das demais páginas: aceita imagem local, URL externa
// e aplica o placeholder quando o produto não possui imagem válida.
$imgSrc = product_img($p);
?>
<article class="card">
  <a class="card-img" href="<?= e($goUrl) ?>" title="Ver detalhes de <?= e($p['name']) ?>">
    <?php if ($badge): ?><span class="badge-off"><?= e($badge) ?></span><?php endif; ?>
    <img src="<?= e($imgSrc) ?>" alt="<?= e($p['name']) ?>" loading="lazy" data-fallback>
  </a>
  <div class="card-body">
    <h3 class="card-title"><a href="<?= e($goUrl) ?>"><?= e($p['name']) ?></a></h3>
    <div class="card-price">
      <?php if (!empty($p['old_price'])): ?><s><?= money($p['old_price']) ?></s><?php endif; ?>
      <strong><?= money($p['price']) ?></strong>
    </div>
    <a class="btn-offer" href="<?= e($goUrl) ?>">🔎 Ver detalhes</a>
  </div>
</article>