
<?php
require_once __DIR__ . '/bootstrap.php';

$siteName = setting('site_name', 'Loot Gamer');
$pageTitle = $pageTitle ?? '';
$metaDesc = $metaDesc ?? setting(
    'site_description',
    'Ofertas e achadinhos da net em games, consoles, acessórios gamer, PC e tecnologia.'
);

/*
|--------------------------------------------------------------------------
| URLs CANÔNICAS - SEO
|--------------------------------------------------------------------------
| Página inicial:
| https://lootgamer.xll.com.br/
|
| Categoria Games:
| https://lootgamer.xll.com.br/categoria/jogos-digitais
|
| Remove parâmetros e mantém HTTPS.
|--------------------------------------------------------------------------
*/

$canonical = $canonical ?? '';

if ($canonical === '') {
    $baseUrl = 'https://lootgamer.xll.com.br';

    $requestPath = parse_url(
        $_SERVER['REQUEST_URI'] ?? '/',
        PHP_URL_PATH
    ) ?: '/';

    // Página inicial
    if ($requestPath === '/' || $requestPath === '/index.php') {
        $canonical = $baseUrl . '/';

    // Categoria Games
    } elseif (
        $requestPath === '/categoria/jogos-digitais' ||
        $requestPath === '/categoria/jogos-digitais/'
    ) {
        $canonical = $baseUrl . '/categoria/jogos-digitais';

    // Demais páginas
    } else {
        $requestPath = rtrim($requestPath, '/');

        $canonical = $baseUrl . (
            $requestPath !== '' ? $requestPath : '/'
        );
    }
}

$ogImage = $ogImage ?? ('https://lootgamer.xll.com.br' . (setting('logo_path') ? url(setting('logo_path')) : asset('images/seed/logo.png')));

$allCats = q(
    'SELECT id,name,slug
     FROM categories
     WHERE is_active = 1 AND is_main = 1
     ORDER BY sort_order, name'
)->fetchAll();

$logo = setting('logo_path')
    ? url(setting('logo_path'))
    : asset('images/seed/logo.png');

$navMap = [
    ['jogos-digitais', 'sports_esports', 'Games'],
    ['consoles', 'gamepad', 'Consoles'],
    ['acessorios', 'headset', 'Acessórios'],
    ['pc-gamer', 'desktop_windows', 'PC Gamer'],
    ['gift-cards', 'card_giftcard', 'Gift Cards']
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= e(page_title($pageTitle)) ?></title>

<meta name="description" content="<?= e($metaDesc) ?>">

<!-- URL CANÔNICA -->
<link rel="canonical" href="<?= e($canonical) ?>">

<!-- OPEN GRAPH -->
<meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
<meta property="og:title" content="<?= e($ogTitle ?? page_title($pageTitle)) ?>">
<meta property="og:description" content="<?= e($ogDesc ?? $metaDesc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">

<?php if ($ogImage): ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php endif; ?>

<!-- TWITTER -->
<meta name="twitter:card" content="<?= $ogImage ? 'summary_large_image' : 'summary' ?>">

<!-- FAVICON -->
<?php
$faviconPath = setting('favicon_path');

if ($faviconPath !== ''):
    $faviconUrl = url($faviconPath);
?>
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<?php endif; ?>

<!-- SCHEMA WEBSITE -->
<?php
$siteSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => $siteName,
    'alternateName' => 'Loot Gamer',
    'url' => 'https://lootgamer.xll.com.br/'
];
?>

<script type="application/ld+json"><?=
    json_encode(
        $siteSchema,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_HEX_TAG
    )
?></script>

<?php
$organizationSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $siteName,
    'url' => 'https://lootgamer.xll.com.br/',
    'logo' => 'https://lootgamer.xll.com.br' . (setting('logo_path') ? url(setting('logo_path')) : asset('images/seed/logo.png'))
];
?>
<script type="application/ld+json"><?= json_encode($organizationSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>

<!-- ROBOTS -->
<?php if (!empty($robotsNoindex) || !empty($robotsNofollow)): ?>
<meta name="robots" content="<?=
    !empty($robotsNoindex) ? 'noindex' : 'index'
?>,<?=
    !empty($robotsNofollow) ? 'nofollow' : 'follow'
?>">
<?php endif; ?>

<meta name="theme-color" content="#10131b">

<!-- FONTES + ÍCONES (Material Symbols) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap">

<!-- CSS -->
<link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=7">
<link rel="stylesheet" href="<?= asset('css/theme-stitch.css') ?>?v=19">
<?php $__c=__DIR__.'/../assets/css/theme-admin.css'; if(is_file($__c)){echo '<style id="lg-cores-painel">'.file_get_contents($__c).'</style>';} ?>

<?php
// Fallback BreadcrumbList for public pages that do not already provide one.
// Custom product/category/blog schemas are preserved without duplication.
$schemaHasBreadcrumb = !empty($jsonLd) && str_contains(
    json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '',
    'BreadcrumbList'
);
if (!$schemaHasBreadcrumb) {
    $path = trim((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: ''), '/');
    $crumbItems = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => 'https://lootgamer.xll.com.br/']];
    if ($path !== '' && $path !== 'index.php') {
        $parts = array_values(array_filter(explode('/', $path)));
        $built = '';
        foreach ($parts as $index => $part) {
            $built .= '/' . $part;
            $label = ucwords(str_replace(['-', '_'], ' ', rawurldecode($part)));
            $crumbItems[] = ['@type' => 'ListItem', 'position' => count($crumbItems) + 1, 'name' => $label, 'item' => 'https://lootgamer.xll.com.br' . $built];
        }
    }
    $fallbackBreadcrumb = ['@type' => 'BreadcrumbList', 'itemListElement' => $crumbItems];
    if (!empty($jsonLd['@graph']) && is_array($jsonLd['@graph'])) {
        $jsonLd['@graph'][] = $fallbackBreadcrumb;
    } elseif (!empty($jsonLd)) {
        $jsonLd = ['@context' => 'https://schema.org', '@graph' => [$jsonLd, $fallbackBreadcrumb]];
    } else {
        $jsonLd = ['@context' => 'https://schema.org', '@graph' => [$fallbackBreadcrumb]];
    }
}
?>
<!-- SCHEMA EXTRA -->
<?php if (!empty($jsonLd)): ?>
<script type="application/ld+json"><?=
    json_encode(
        $jsonLd,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_HEX_TAG
    )
?></script>
<?php endif; ?>

<!-- ANALYTICS -->
<?php if (setting('analytics_id')): ?>
<meta name="lg-analytics" content="<?= e(
    preg_replace(
        '/[^A-Za-z0-9\-]/',
        '',
        setting('analytics_id')
    )
) ?>">
<?php endif; ?>

<?= $extraHead ?? '' ?>

</head>
<body>

<header class="site-header">

  <div class="wrap hd-top">

    <button
        class="menu-toggle"
        id="menuToggle"
        aria-label="Abrir menu"
        aria-expanded="false"
    ><span class="material-symbols-outlined" aria-hidden="true">menu</span></button>

    <a class="brand" href="<?= url() ?>" aria-label="<?= e($siteName) ?>">
      <img
          src="<?= e($logo) ?>"
          alt="<?= e($siteName) ?>"
          width="150"
          height="52"
      >
    </a>

    <div class="tagline">
      <span class="tg-ico"><span class="material-symbols-outlined" aria-hidden="true">shopping_bag</span></span>
      <span>Ofertas e<br>Achadinhos da Net</span>
    </div>

    <form
        class="search"
        action="<?= url('busca') ?>"
        method="get"
        role="search"
    >
      <input
          type="search"
          name="q"
          id="searchInput"
          placeholder="Busque por games, consoles, acessórios..."
          value="<?= e($_GET['q'] ?? '') ?>"
          autocomplete="off"
          aria-label="Buscar produtos"
      >

      <button type="submit" aria-label="Buscar"><span class="material-symbols-outlined" aria-hidden="true">search</span></button>

      <div class="suggest" id="suggestBox" hidden></div>
    </form>

    <div class="hd-icons">
      <a href="<?= url('lojas') ?>">
        <span><span class="material-symbols-outlined" aria-hidden="true">storefront</span></span>Lojas
      </a>

      <a href="<?= url('contato') ?>">
        <span><span class="material-symbols-outlined" aria-hidden="true">mail</span></span>Contato
      </a>
    </div>

  </div>

  <div class="wrap hd-nav" id="mainNav">

    <div class="allcats">

      <button
          class="btn-allcats"
          id="allcatsBtn"
          aria-expanded="false"
      ><span class="material-symbols-outlined" aria-hidden="true">menu</span> Todas as Categorias</button>

      <ul class="allcats-list" id="allcatsList" hidden>
        <?php foreach ($allCats as $c): ?>
        <li>
          <a href="<?= url('categoria/' . $c['slug']) ?>">
            <?= e($c['name']) ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>

    </div>

    <nav class="navbar" aria-label="Principal">

      <?php foreach ($navMap as [$slug, $ico, $label]): ?>
      <a href="<?= url('categoria/' . $slug) ?>">
        <span class="material-symbols-outlined"><?= e($ico) ?></span><?= e($label) ?>
      </a>
      <?php endforeach; ?>

      <a href="<?= url('ofertas') ?>">
        <span><span class="material-symbols-outlined" aria-hidden="true">sell</span></span>Ofertas
      </a>

      <a href="<?= url('blog') ?>">
        <span><span class="material-symbols-outlined" aria-hidden="true">article</span></span>Blog
      </a>

      <a href="<?= url('novidades') ?>">
        <span><span class="material-symbols-outlined" aria-hidden="true">auto_awesome</span></span>Novidades
      </a>

    </nav>

  </div>

</header>

<main id="conteudo">

<?php foreach (flashes() as [$t, $m]): ?>
<div class="wrap">
  <div class="alert alert-<?= e($t) ?>">
    <?= e($m) ?>
  </div>
</div>
<?php endforeach; ?>
