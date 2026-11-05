<?php
/**
 * API Automática - Loot Gamer Blog
 * Local: public_html/lootgamer/admin/api-auto-blog.php
 * includes fica em: public_html/lootgamer/includes/
 */

declare(strict_types=1);

// === TROQUE AQUI SEU TOKEN SECRETO ===
const API_SECRET_TOKEN = 'L00tG4m3r_Xll_2026_9xP4_k7s!';

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$providedToken = $_SERVER['HTTP_X_API_TOKEN'] ?? $_GET['token'] ?? '';
$isApiCall = hash_equals(API_SECRET_TOKEN, $providedToken);

if (!$isApiCall) {
    $me = require_admin();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['debug'])) {
    $total = q("SELECT COUNT(*) as c FROM blog_posts")->fetch()['c'];
    $rows = q("SELECT id, title, slug, status, published_at FROM blog_posts ORDER BY id DESC LIMIT 15")->fetchAll();
    echo json_encode(['ok'=>true,'total'=>$total,'last15'=>$rows], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Use POST']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
    exit;
}

function api_slugify(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'artigo';
}

function api_unique_slug(string $base, int $excludeId = 0): string {
    $slug = $base; $i = 2;
    while (true) {
        $exists = q('SELECT id FROM blog_posts WHERE slug = ?' . ($excludeId ? ' AND id != ?' : ''), $excludeId ? [$slug, $excludeId] : [$slug])->fetch();
        if (!$exists) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function api_save_image_from_base64(string $base64, string $alt = ''): ?string {
    if (strpos($base64, ',') !== false) {
        $base64 = explode(',', $base64, 2)[1];
    }
    $binary = base64_decode($base64, true);
    if (!$binary) throw new RuntimeException('Base64 inválido');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($binary);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Tipo não permitido: '.$mime);
    $ext = $allowed[$mime];
    $dir = __DIR__ . '/../uploads/blog/' . date('Y/m');
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = api_slugify(substr($alt ?: 'loot-gamer', 0, 30)) . '-' . uniqid() . '.' . $ext;
    file_put_contents($dir.'/'.$filename, $binary);
    return 'uploads/blog/' . date('Y/m') . '/' . $filename;
}

function api_save_image_from_url(string $url): ?string {
    $binary = @file_get_contents($url);
    if (!$binary) throw new RuntimeException('Não baixou imagem da URL');
    return api_save_image_from_base64(base64_encode($binary), basename(parse_url($url, PHP_URL_PATH)));
}

$f = [];
foreach (['title','slug','excerpt','featured_alt','tags','focus_keyword','seo_title','meta_description','canonical_url','og_title','og_description','og_image_url'] as $k) {
    $f[$k] = trim((string)($data[$k] ?? ''));
}
$f['content'] = trim((string)($data['content'] ?? ''));
$f['category_id'] = isset($data['category_id']) ? (int)$data['category_id'] : null;
if (!$f['category_id'] && !empty($data['category_name'])) {
    $cat = q('SELECT id FROM blog_categories WHERE name = ? OR slug = ? LIMIT 1', [$data['category_name'], api_slugify($data['category_name'])])->fetch();
    if ($cat) $f['category_id'] = (int)$cat['id'];
}
$f['status'] = in_array($data['status'] ?? '', ['rascunho','publicado'], true) ? $data['status'] : 'publicado';
$f['schema_type'] = in_array($data['schema_type'] ?? '', ['BlogPosting','Article','NewsArticle'], true) ? $data['schema_type'] : 'BlogPosting';
$f['robots_noindex'] = !empty($data['robots_noindex']) ? 1 : 0;
$f['robots_nofollow'] = !empty($data['robots_nofollow']) ? 1 : 0;

$pubRaw = trim((string)($data['published_at'] ?? ''));
$pubDt = null;
if ($pubRaw !== '') {
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $pubRaw) ?: DateTime::createFromFormat('Y-m-d H:i:s', $pubRaw);
    if ($dt) $pubDt = $dt->format('Y-m-d H:i:s');
}
if ($f['status'] === 'publicado' && !$pubDt) $pubDt = date('Y-m-d H:i:s');

$errors = [];
if ($f['title'] === '' || mb_strlen($f['title']) > 190) $errors[] = 'Título obrigatório até 190';
if ($f['status'] === 'publicado' && $f['content'] === '') $errors[] = 'Conteúdo obrigatório';
if ($errors) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'errors'=>$errors]); exit;
}

if ($f['seo_title'] === '') $f['seo_title'] = mb_substr($f['title'], 0, 60);
if ($f['meta_description'] === '') $f['meta_description'] = mb_substr($f['excerpt'] ?: strip_tags($f['content']), 0, 155);
if ($f['excerpt'] === '') $f['excerpt'] = mb_substr($f['meta_description'], 0, 500);
if ($f['og_title'] === '') $f['og_title'] = $f['seo_title'];
if ($f['og_description'] === '') $f['og_description'] = $f['meta_description'];

$imgPath = null;
try {
    if (!empty($data['featured_image_base64'])) {
        $imgPath = api_save_image_from_base64($data['featured_image_base64'], $f['featured_alt'] ?: $f['title']);
    } elseif (!empty($data['featured_image_url'])) {
        $imgPath = api_save_image_from_url($data['featured_image_url']);
    }
} catch (RuntimeException $ex) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>'Imagem: '.$ex->getMessage()]); exit;
}

$slugBase = api_slugify($f['slug'] !== '' ? $f['slug'] : $f['title']);
$slug = api_unique_slug($slugBase);

$cols = ['title','slug','excerpt','content','featured_image','featured_alt','category_id','tags','status','published_at','focus_keyword','seo_title','meta_description','canonical_url','robots_noindex','robots_nofollow','og_title','og_description','og_image_url','schema_type'];
$vals = [$f['title'],$slug,$f['excerpt']?:null,$f['content']?:null,$imgPath,$f['featured_alt']?:null,$f['category_id'],$f['tags']?:null,$f['status'],$pubDt,$f['focus_keyword']?:null,$f['seo_title']?:null,$f['meta_description']?:null,$f['canonical_url']?:null,$f['robots_noindex'],$f['robots_nofollow'],$f['og_title']?:null,$f['og_description']?:null,$f['og_image_url']?:null,$f['schema_type']];

q('INSERT INTO blog_posts ('.implode(',', $cols).') VALUES ('.implode(',', array_fill(0,count($cols),'?')).')', $vals);
$newId = (int)db()->lastInsertId();
log_admin('blog_criado_api', $f['title'].' #'.$newId);

echo json_encode([
    'ok'=>true,'id'=>$newId,'slug'=>$slug,
    'url'=>url('blog/'.$slug),
    'edit_url'=>url('admin/blog-edit.php?id='.$newId),
    'featured_image'=>$imgPath ? url($imgPath) : null,
    'seo'=>['seo_title'=>$f['seo_title'],'meta_description'=>$f['meta_description'],'focus_keyword'=>$f['focus_keyword']]
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);