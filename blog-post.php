<?php
require_once __DIR__ . '/includes/bootstrap.php';
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug'] ?? ''));
$p = $slug !== '' ? q('SELECT b.*, c.name AS cat_name, c.slug AS cat_slug FROM blog_posts b LEFT JOIN blog_categories c ON c.id = b.category_id WHERE b.slug = ? AND ' . blog_public_where('b'), [$slug])->fetch() : null;
if (!$p) { include __DIR__ . '/404.php'; exit; }

$siteName = setting('site_name', 'Loot Gamer');
$host = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
$url = $host . url('blog/' . $p['slug']);
$words = word_count((string)$p['content']);
$mins = reading_minutes($words);
$published = $p['published_at'] ?: $p['created_at'];
$modified = $p['updated_at'] ?: $published;
$tags = array_values(array_filter(array_map('trim', explode(',', (string)$p['tags']))));

/* SEO: títulos, descrições e canônica */
$pageTitle = $p['seo_title'] ?: $p['title'];
$metaDesc = $p['meta_description'] ?: ($p['excerpt'] ?: excerpt_from((string)$p['content'], 155));
$canonical = $p['canonical_url'] ?: $url;
$robotsNoindex = (bool)$p['robots_noindex'];
$robotsNofollow = (bool)$p['robots_nofollow'];
$ogType = 'article';
$ogTitle = $p['og_title'] ?: $pageTitle;
$ogDesc = $p['og_description'] ?: $metaDesc;
$imgSrc = !empty($p['featured_image']) ? img_src($p['featured_image']) : null;
$imgAbs = $imgSrc ? (preg_match('~^https?://~i', $imgSrc) ? $imgSrc : $host . $imgSrc) : null;
$ogImage = $p['og_image_url'] ?: $imgAbs;

$extraHead = '<meta property="article:published_time" content="' . e(date('c', strtotime($published))) . '">'
    . '<meta property="article:modified_time" content="' . e(date('c', strtotime($modified))) . '">';
if ($p['cat_name']) $extraHead .= '<meta property="article:section" content="' . e($p['cat_name']) . '">';
foreach ($tags as $t) $extraHead .= '<meta property="article:tag" content="' . e($t) . '">';

/* Dados estruturados: migalhas + artigo */
$crumbs = [['Início', $host . url()], ['Blog', $host . url('blog')]];
if ($p['cat_slug']) $crumbs[] = [$p['cat_name'], $host . url('blog/categoria/' . $p['cat_slug'])];
$crumbs[] = [$p['title'], $url];
$logoAbs = setting('logo_path') ? $host . url(setting('logo_path')) : $host . asset('images/seed/logo.png');
$article = [
    '@type' => $p['schema_type'] ?: 'BlogPosting',
    '@id' => $url . '#article',
    'headline' => mb_substr($p['title'], 0, 110),
    'description' => $metaDesc,
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
    'datePublished' => date('c', strtotime($published)),
    'dateModified' => date('c', strtotime($modified)),
    'author' => ['@type' => 'Organization', 'name' => $siteName, 'url' => $host . url()],
    'publisher' => ['@type' => 'Organization', 'name' => $siteName, 'logo' => ['@type' => 'ImageObject', 'url' => $logoAbs]],
    'inLanguage' => 'pt-BR',
    'wordCount' => $words,
];
if ($imgAbs) $article['image'] = [$imgAbs];
if ($p['cat_name']) $article['articleSection'] = $p['cat_name'];
if ($tags) $article['keywords'] = implode(', ', $tags);
$jsonLd = ['@context' => 'https://schema.org', '@graph' => [
    ['@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1]], $crumbs, array_keys($crumbs))],
    $article,
]];

/* Relacionados: mesma categoria, senão os mais recentes */
$related = q('SELECT b.*, c.name AS cat_name, c.slug AS cat_slug FROM blog_posts b LEFT JOIN blog_categories c ON c.id = b.category_id WHERE b.id <> ? AND ' . blog_public_where('b') . ' ORDER BY (b.category_id <=> ?) DESC, COALESCE(b.published_at, b.created_at) DESC LIMIT 3', [(int)$p['id'], $p['category_id']])->fetchAll();

include ROOT . '/includes/header.php';
?>
<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb"><?php foreach ($crumbs as $i => [$label, $href]): ?><?= $i ? ' › ' : '' ?><?php if ($i === count($crumbs) - 1): ?><span><?= e($label) ?></span><?php else: ?><a href="<?= e(str_replace($host, '', $href)) ?>"><?= e($label) ?></a><?php endif; ?><?php endforeach; ?></nav>
</div>
<article class="post" itemscope itemtype="https://schema.org/<?= e($p['schema_type'] ?: 'BlogPosting') ?>">
  <header class="post-hero wrap">
    <?php if ($p['cat_name']): ?><a class="post-cat" href="<?= e(url('blog/categoria/' . $p['cat_slug'])) ?>"><?= e($p['cat_name']) ?></a><?php endif; ?>
    <h1 itemprop="headline"><?= e($p['title']) ?></h1>
    <p class="post-meta">
      <time itemprop="datePublished" datetime="<?= e(date('c', strtotime($published))) ?>"><?= e(date('d/m/Y', strtotime($published))) ?></time>
      <?php if ($modified !== $published && strtotime($modified) > strtotime($published) + 86400): ?> · atualizado em <time itemprop="dateModified" datetime="<?= e(date('c', strtotime($modified))) ?>"><?= e(date('d/m/Y', strtotime($modified))) ?></time><?php endif; ?>
      · <?= $mins ?> min de leitura
    </p>
    <?php if ($p['excerpt']): ?><p class="post-lead" itemprop="description"><?= e($p['excerpt']) ?></p><?php endif; ?>
  </header>
  <?php if ($imgAbs): ?>
  <figure class="post-figure wrap"><img src="<?= e($imgSrc) ?>" alt="<?= e($p['featured_alt'] ?: $p['title']) ?>" width="1200" height="630" fetchpriority="high" itemprop="image"></figure>
  <?php endif; ?>
  <div class="wrap post-wrap">
    <div class="post-body" itemprop="articleBody"><?= blog_html((string)$p['content']) /* HTML ou texto escrito pelo administrador */ ?></div>
    <?php if ($tags): ?>
    <ul class="post-tags" aria-label="Tags"><?php foreach ($tags as $t): ?><li>#<?= e($t) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
</article>

<?php if ($related): ?>
<section class="wrap blog-related">
  <div class="sec-head"><h2>Leia também</h2><a class="pill" href="<?= e(url('blog')) ?>">Todos os artigos →</a></div>
  <div class="bgrid">
    <?php foreach ($related as $post) include ROOT . '/includes/blog-card.php'; ?>
  </div>
</section>
<?php endif; ?>
<?php include ROOT . '/includes/footer.php';
