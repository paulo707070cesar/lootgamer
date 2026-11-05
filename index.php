<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = '';
$metaDesc = setting('site_description', 'Ofertas e achadinhos da net em games, consoles, acessórios gamer, PC e tecnologia.');
include ROOT . '/includes/header.php';

/* 1. Banner principal */
$hero = banners_for('principal');
if ($hero): ?>
<section class="hero" id="hero" aria-roledescription="carrossel">
  <div class="hero-track">
    <?php foreach ($hero as $i => $b): ?><div class="hero-slide<?= $i === 0 ? ' active' : '' ?>"><?php render_banner($b); ?></div><?php endforeach; ?>
  </div>
  <?php if (count($hero) > 1): ?>
  <button class="hero-nav prev" aria-label="Anterior">‹</button><button class="hero-nav next" aria-label="Próximo">›</button>
  <div class="hero-dots"><?php foreach ($hero as $i => $_): ?><button class="<?= $i === 0 ? 'on' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button><?php endforeach; ?></div>
  <?php endif; ?>
</section>
<?php endif;

/* 3. Categorias */
if (section_on('categorias')):
$cats = q('SELECT * FROM categories WHERE is_active = 1 AND is_main = 1 ORDER BY sort_order, name LIMIT 12')->fetchAll();
if ($cats): ?>
<section class="wrap sec">
  <div class="sec-head"><h2><img class="h-ico" src="<?= asset('images/seed/ico-gamepad.png') ?>" alt="" width="32" height="28"> Compre por Categoria</h2></div>
  <div class="cats">
    <?php foreach ($cats as $i => $c): ?>
      <a class="cat t<?= $i % 10 ?>" href="<?= url('categoria/' . $c['slug']) ?>">
        <img src="<?= e(img_src($c['image_path'])) ?>" alt="" loading="lazy" data-fallback><span><?= e($c['name']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; endif;
foreach (banners_for('entre_categorias') as $b): ?><div class="wrap sec-ban"><?php render_banner($b); ?></div><?php endforeach;

/* 4. Ofertas em destaque */
if (section_on('destaques')):
$feat = products_where('p.is_featured = 1', [], 'p.sort_order ASC, p.id DESC', 5);
if ($feat): ?>
<section class="wrap sec">
  <div class="sec-head"><h2><img class="h-ico" src="<?= asset('images/seed/ico-trophy.png') ?>" alt="" width="30" height="30"> Ofertas em Destaque</h2>
    <a class="pill" href="<?= url('ofertas') ?>"><img src="<?= asset('images/seed/ico-fire.png') ?>" alt="" width="20" height="20"> Descontos Imperdíveis</a></div>
  <div class="grid g5"><?php foreach ($feat as $p) include ROOT . '/includes/card.php'; ?></div>
</section>
<?php endif; endif;

/* 7/10. Jogos + Gift Cards */
$games = section_on('jogos') ? (function () { $ids = cat_ids('jogos-digitais'); return products_where('p.category_id IN (' . in_clause($ids) . ')', $ids, 'p.sort_order ASC, p.id DESC', 5); })() : [];
$gifts = section_on('giftcards') ? (function () { $ids = cat_ids('gift-cards'); return products_where('p.category_id IN (' . in_clause($ids) . ')', $ids, 'p.sort_order ASC, p.id DESC', 4); })() : [];
if ($games || $gifts): ?>
<section class="wrap duo">
  <?php if ($games): ?>
  <div class="panel p-games">
    <h2><img class="h-ico" src="<?= asset('images/seed/ico-trophy2.png') ?>" alt="" width="30" height="26"> Jogos Mais Procurados</h2>
    <div class="tiles tg">
      <?php foreach ($games as $p): ?><a href="<?= url('produto/' . $p['slug']) ?>" title="<?= e($p['name']) ?>"><img src="<?= e(product_img($p)) ?>" alt="<?= e($p['name']) ?>" loading="lazy" data-fallback></a><?php endforeach; ?>
    </div>
  </div>
  <?php endif; if ($gifts): ?>
  <div class="panel p-gifts">
    <h2><img class="h-ico" src="<?= asset('images/seed/ico-gift.png') ?>" alt="" width="32" height="30"> Gift Cards e Créditos</h2>
    <div class="tiles tgf">
      <?php foreach ($gifts as $p): ?><a href="<?= url('produto/' . $p['slug']) ?>" title="<?= e($p['name']) ?>"><img src="<?= e(product_img($p)) ?>" alt="<?= e($p['name']) ?>" loading="lazy" data-fallback></a><?php endforeach; ?>
    </div>
    <a class="gf-more" href="<?= url('categoria/gift-cards') ?>"><span>Créditos, assinaturas e<br>muito mais!</span><b>➜</b></a>
  </div>
  <?php endif; ?>
</section>
<?php endif;
foreach (banners_for('jogos_digitais') as $b): ?><div class="wrap sec-ban"><?php render_banner($b); ?></div><?php endforeach;

/* Demais seções (todas vindas do banco e controláveis em Configurações) */
if (section_on('populares')) {
    $pop = q(PROD_SELECT . " WHERE p.status='publicado' AND p.id IN (SELECT product_id FROM (SELECT product_id FROM clicks WHERE product_id IS NOT NULL AND clicked_at > (NOW() - INTERVAL 30 DAY) GROUP BY product_id ORDER BY COUNT(*) DESC LIMIT 5) t)")->fetchAll();
    products_section('Produtos Mais Populares', $pop);
}
foreach (banners_for('entre_produtos') as $b): ?><div class="wrap sec-ban"><?php render_banner($b); ?></div><?php endforeach;
if (section_on('novidades')) products_section('Novidades e Lançamentos', products_where('', [], 'p.created_at DESC, p.id DESC', 5), url('novidades'));
if (section_on('consoles')) { $ids = cat_ids('consoles'); products_section('Consoles e Acessórios Gamer', products_where('p.category_id IN (' . in_clause($ids) . ')', $ids, 'p.sort_order, p.id DESC', 5), url('categoria/consoles')); }
if (section_on('perifericos')) { $ids = array_merge(cat_ids('pc-gamer'), cat_ids('monitores'), cat_ids('acessorios')); products_section('Periféricos e Computadores', products_where('p.category_id IN (' . in_clause($ids) . ')', $ids, 'p.sort_order, p.id DESC', 5), url('categoria/pc-gamer')); }
if (section_on('recomendados')) products_section('Produtos Recomendados', products_where('p.is_recommended = 1', [], 'p.sort_order, p.id DESC', 5));
foreach (banners_for('secundario') as $b): ?><div class="wrap sec-ban"><?php render_banner($b); ?></div><?php endforeach;
if (section_on('achadinhos')) products_section('Achadinhos da Net', products_where('p.is_promo = 1', [], 'p.sort_order, p.id DESC', 5), url('ofertas'));
if (section_on('ultimos')) products_section('Últimos Produtos Cadastrados', products_where('', [], 'p.id DESC', 5));
foreach (banners_for('rodape') as $b): ?><div class="wrap sec-ban"><?php render_banner($b); ?></div><?php endforeach;
include ROOT . '/includes/footer.php';
