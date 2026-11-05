<?php
require_once __DIR__ . '/includes/bootstrap.php';
$catSlug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['cat'] ?? ''));
$cat = null;
if ($catSlug !== '') {
    $cat = q('SELECT * FROM blog_categories WHERE slug = ?', [$catSlug])->fetch();
    if (!$cat) { include __DIR__ . '/404.php'; exit; }
}
$search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 9;

$where = [blog_public_where('b')]; $params = [];
if ($cat) { $where[] = 'b.category_id = ?'; $params[] = (int)$cat['id']; }
if ($search !== '') { $where[] = '(b.title LIKE ? OR b.excerpt LIKE ?)'; $params[] = '%' . $search . '%'; $params[] = '%' . $search . '%'; }
$wsql = implode(' AND ', $where);

$total = (int)q("SELECT COUNT(*) FROM blog_posts b WHERE $wsql", $params)->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
if ($page > $pages) { include __DIR__ . '/404.php'; exit; }
$posts = q("SELECT b.*, c.name AS cat_name, c.slug AS cat_slug FROM blog_posts b LEFT JOIN blog_categories c ON c.id = b.category_id WHERE $wsql ORDER BY COALESCE(b.published_at, b.created_at) DESC, b.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params)->fetchAll();

$basePath = $cat ? 'blog/categoria/' . $cat['slug'] : 'blog';
$host = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
$pageUrl = fn(int $n) => $host . url($basePath) . ($n > 1 ? '?page=' . $n : '');

if ($cat) {
    $pageTitle = $cat['seo_title'] ?: ('Blog: ' . $cat['name']);
    $metaDesc = $cat['meta_description'] ?: ($cat['description'] ?: ('Artigos sobre ' . $cat['name'] . ' no blog.'));
    $heading = $cat['name'];
    $intro = $cat['description'] ?: '';
} else {
    $pageTitle = 'Blog de games, consoles e tecnologia gamer';
    $metaDesc = 'Guias, comparativos e dicas sobre games, consoles, acessórios e PC gamer para você comprar melhor.';
    $heading = 'Blog';
    $intro = 'Guias, comparativos e dicas para você escolher melhor seus games e acessórios.';
}
if ($page > 1) $pageTitle .= ' — Página ' . $page;
$canonical = $pageUrl($page);
$robotsNoindex = $search !== '';

$extraHead = '';
if ($page > 1) $extraHead .= '<link rel="prev" href="' . e($pageUrl($page - 1)) . '">';
if ($page < $pages) $extraHead .= '<link rel="next" href="' . e($pageUrl($page + 1)) . '">';

$crumbs = [['Início', $host . url()], ['Blog', $host . url('blog')]];
if ($cat) $crumbs[] = [$cat['name'], $host . url('blog/categoria/' . $cat['slug'])];
$jsonLd = ['@context' => 'https://schema.org', '@graph' => [
    ['@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1]], $crumbs, array_keys($crumbs))],
    ['@type' => 'CollectionPage', '@id' => $canonical . '#collection', 'url' => $canonical, 'name' => $heading, 'description' => $metaDesc, 'inLanguage' => 'pt-BR'],
]];

$cats = q('SELECT c.*, (SELECT COUNT(*) FROM blog_posts b WHERE b.category_id = c.id AND ' . blog_public_where('b') . ') AS total FROM blog_categories c ORDER BY c.sort_order, c.name')->fetchAll();
$showingQuery = $search !== '';
include ROOT . '/includes/header.php';
?>
<section class="blog-hero">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><?php foreach ($crumbs as $i => [$label, $href]): ?><?= $i ? ' › ' : '' ?><?php if ($i === count($crumbs) - 1): ?><span><?= e($label) ?></span><?php else: ?><a href="<?= e(str_replace($host, '', $href)) ?>"><?= e($label) ?></a><?php endif; ?><?php endforeach; ?></nav>
    <h1><?= e($heading) ?></h1>
    <p class="blog-intro"><?= e($intro) ?></p>
    <form class="blog-search" action="<?= url($basePath) ?>" method="get" role="search">
      <input type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar artigos..." aria-label="Buscar artigos">
      <button type="submit">Buscar</button>
    </form>
    <div class="blog-cats">
      <a class="<?= !$cat ? 'on' : '' ?>" href="<?= url('blog') ?>">Todos</a>
      <?php foreach ($cats as $c): if ((int)$c['total'] === 0 && (!$cat || (int)$cat['id'] !== (int)$c['id'])) continue; ?>
        <a class="<?= $cat && (int)$cat['id'] === (int)$c['id'] ? 'on' : '' ?>" href="<?= url('blog/categoria/' . $c['slug']) ?>"><?= e($c['name']) ?> <small><?= (int)$c['total'] ?></small></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="wrap blog-list">
  <?php if ($cat && $cat['description'] && $page === 1): ?><p class="blog-cat-desc"><?= e($cat['description']) ?></p><?php endif; ?>
  <?php if (!$posts): ?>
    <p class="blog-empty">Nenhum artigo encontrado<?= $showingQuery ? ' para "' . e($search) . '"' : '' ?>. <a href="<?= url('blog') ?>">Ver todos os artigos</a>.</p>
  <?php else: ?>
  <div class="bgrid">
    <?php foreach ($posts as $post) include ROOT . '/includes/blog-card.php'; ?>
  </div>
  <?php endif; ?>

  <?php if ($pages > 1): ?>
  <nav class="bpager" aria-label="Paginação">
    <?php if ($page > 1): ?><a href="<?= e(url($basePath) . '?page=' . ($page - 1) . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" rel="prev">← Anteriores</a><?php endif; ?>
    <span>Página <?= $page ?> de <?= $pages ?></span>
    <?php if ($page < $pages): ?><a href="<?= e(url($basePath) . '?page=' . ($page + 1) . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" rel="next">Próximos →</a><?php endif; ?>
  </nav>
  <?php endif; ?>
</section>
<?php include ROOT . '/includes/footer.php';
