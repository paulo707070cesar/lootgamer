<?php
require_once __DIR__ . '/includes/bootstrap.php';
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug'] ?? ''));
$p = q(PROD_SELECT . " WHERE p.slug = ? AND p.status = 'publicado'", [$slug])->fetch();
if (!$p) { include __DIR__ . '/404.php'; exit; }
$extra = q('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$p['id']])->fetchAll();
$related = products_where('p.id <> ? AND p.category_id <=> ?', [$p['id'], $p['category_id']], 'p.sort_order, p.id DESC', 5);
$pageTitle = $p['seo_title'] ?: $p['name'];
$metaDesc = $p['meta_description'] ?: ($p['short_description'] ?: $p['name']);
$host = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
$imgAbs = product_img($p); if ($imgAbs[0] === '/') $imgAbs = $host . $imgAbs;
$ogImage = $imgAbs;
$jsonLd = ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p['name'], 'image' => [$imgAbs], 'description' => $metaDesc];
if ($p['brand']) $jsonLd['brand'] = ['@type' => 'Brand', 'name' => $p['brand']];
if ($p['price'] && $p['affiliate_url']) $jsonLd['offers'] = ['@type' => 'Offer', 'priceCurrency' => 'BRL', 'price' => $p['price'], 'url' => $host . url('produto/' . $p['slug'])];
include ROOT . '/includes/header.php';
$pct = discount_pct($p);
?>
<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= url() ?>">Início</a><?php if ($p['category_slug']): ?> › <a href="<?= url('categoria/' . $p['category_slug']) ?>"><?= e($p['category_name']) ?></a><?php endif; ?> › <span><?= e($p['name']) ?></span></nav>
  <div class="pd">
    <div class="pd-gallery">
      <div class="pd-main"><?php if ($pct): ?><span class="badge-off">-<?= $pct ?>%</span><?php endif; ?><img id="pdMain" src="<?= e(product_img($p)) ?>" alt="<?= e($p['name']) ?>" data-fallback></div>
      <?php if ($extra): ?><div class="pd-thumbs">
        <button type="button" data-src="<?= e(product_img($p)) ?>"><img src="<?= e(product_img($p)) ?>" alt="" loading="lazy"></button>
        <?php foreach ($extra as $x): $s = img_src($x['image_path'], $x['image_url']); ?><button type="button" data-src="<?= e($s) ?>"><img src="<?= e($s) ?>" alt="" loading="lazy" data-fallback></button><?php endforeach; ?>
      </div><?php endif; ?>
    </div>
    <div class="pd-info">
      <?php if ($p['promo_text']): ?><span class="badge-tag static"><?= e($p['promo_text']) ?></span><?php endif; ?>
      <h1><?= e($p['name']) ?></h1>
      <?php if ($p['brand'] || $p['model']): ?><p class="card-meta"><?= e(trim($p['brand'] . ' ' . $p['model'])) ?></p><?php endif; ?>
      <div class="card-price big">
        <?php if ($p['old_price'] && $p['price'] && (float)$p['old_price'] > (float)$p['price']): ?><s><?= money($p['old_price']) ?></s><?php endif; ?>
        <?php if ($p['price']): ?><strong><?= money($p['price']) ?></strong><?php else: ?><em>Veja o preço na loja</em><?php endif; ?>
      </div>
      <p class="note">Preço e disponibilidade podem mudar. Confirme na loja <?= $p['store_name'] ? '(' . e($p['store_name']) . ')' : 'de destino' ?> antes de comprar.</p>
      <?php if ($p['affiliate_url']): ?>
        <a class="btn-offer lg" href="<?= url('ir/' . (int)$p['id']) ?>" rel="sponsored nofollow noopener" target="_blank">🛒 Confira Oferta<?= $p['store_name'] ? ' na ' . e($p['store_name']) : '' ?></a>
        <p class="aff-note">Ao comprar por este link, o site pode receber uma comissão, sem custo adicional para você. <a href="<?= url('divulgacao-afiliados') ?>">Saiba mais</a>.</p>
      <?php else: ?><p class="note">Oferta sem link ativo no momento.</p><?php endif; ?>
      <?php if ($p['short_description']): ?><p class="pd-short"><?= e($p['short_description']) ?></p><?php endif; ?>
    </div>
  </div>
  <?php if ($p['description']): ?><section class="pd-desc"><h2>Descrição</h2><div><?= nl2br(e($p['description'])) ?></div></section><?php endif; ?>
</div>
<?php products_section('Produtos relacionados', $related); include ROOT . '/includes/footer.php';
