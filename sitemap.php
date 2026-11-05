<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
$host = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
$u = [[url(), null]];
foreach (['ofertas', 'novidades', 'lojas', 'contato', 'sobre', 'privacidade', 'termos', 'cookies', 'divulgacao-afiliados', 'blog'] as $s) $u[] = [url($s), null];
foreach (q('SELECT slug FROM categories WHERE is_active = 1')->fetchAll() as $r) $u[] = [url('categoria/' . $r['slug']), null];
foreach (q("SELECT slug, updated_at FROM products WHERE status = 'publicado'")->fetchAll() as $r) $u[] = [url('produto/' . $r['slug']), substr($r['updated_at'], 0, 10)];
foreach (q('SELECT slug FROM blog_categories')->fetchAll() as $r) $u[] = [url('blog/categoria/' . $r['slug']), null];
// Artigos publicados (agendados não entram) e sem noindex
foreach (q('SELECT slug, updated_at FROM blog_posts WHERE robots_noindex = 0 AND ' . blog_public_where('blog_posts') . ' ORDER BY published_at DESC')->fetchAll() as $r) $u[] = [url('blog/' . $r['slug']), substr($r['updated_at'], 0, 10)];
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($u as [$l, $m]) echo '<url><loc>' . htmlspecialchars($host . $l, ENT_XML1) . '</loc>' . ($m ? '<lastmod>' . $m . '</lastmod>' : '') . "</url>\n";
echo '</urlset>';
