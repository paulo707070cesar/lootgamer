<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    session_name('lootgamer_sid');
    session_start();
}
define('ROOT', dirname(__DIR__));
$__doc = rtrim(str_replace('\\', '/', (string)($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$__base = str_replace($__doc, '', str_replace('\\', '/', ROOT));
define('BASE', rtrim($__base, '/'));
$__cfgFile = ROOT . '/config/config.php';
$GLOBALS['CFG'] = is_file($__cfgFile) ? require $__cfgFile : null;
ini_set('log_errors', '1');
ini_set('error_log', ROOT . '/logs/php-error.log');
ini_set('display_errors', (!empty($GLOBALS['CFG']['debug'])) ? '1' : '0');
date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $c = $GLOBALS['CFG'];
    if (!$c) { header('Location: ' . BASE . '/install.php'); exit; }
    try {
        $pdo = new PDO("mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4", $c['db_user'], $c['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '-03:00'");
    } catch (Throwable $e) {
        error_log('DB: ' . $e->getMessage());
        http_response_code(500);
        exit('Serviço temporariamente indisponível.');
    }
    return $pdo;
}
function q(string $sql, array $p = []): PDOStatement { $s = db()->prepare($sql); $s->execute($p); return $s; }
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return BASE . '/' . ltrim($path, '/'); }
function asset(string $p): string { return url('assets/' . ltrim($p, '/')); }
function redirect(string $to): void { header('Location: ' . $to); exit; }

function settings(): array {
    static $s = null;
    if ($s === null) {
        $s = [];
        try { foreach (q('SELECT setting_key, setting_value FROM site_settings')->fetchAll() as $r) $s[$r['setting_key']] = $r['setting_value']; } catch (Throwable $e) {}
    }
    return $s;
}
function setting(string $k, string $d = ''): string { $s = settings(); return isset($s[$k]) && $s[$k] !== '' ? $s[$k] : $d; }
function set_setting(string $k, string $v): void {
    q('INSERT INTO site_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [$k, $v]);
}

/* CSRF */
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) { http_response_code(419); exit('Sessão expirada. Volte e tente novamente.'); }
}
/* Flash */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

/* Formatação */
function money($v): string { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function parse_money(?string $s): ?string {
    $s = trim((string)$s); if ($s === '') return null;
    $s = preg_replace('/[^\d,\.]/', '', $s);
    if (strpos($s, ',') !== false) $s = str_replace('.', '', $s);
    $s = str_replace(',', '.', $s);
    return is_numeric($s) && (float)$s >= 0 ? number_format((float)$s, 2, '.', '') : null;
}
function discount_pct(array $p): ?int {
    if (!empty($p['price']) && !empty($p['old_price']) && (float)$p['old_price'] > (float)$p['price'] && (float)$p['price'] > 0)
        return (int)round((1 - (float)$p['price'] / (float)$p['old_price']) * 100);
    return null;
}
function slugify(string $s): string {
    $s = mb_strtolower($s);
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'item';
}
function unique_slug(string $table, string $base, int $ignoreId = 0): string {
    $slug = $base; $i = 2;
    while (q("SELECT id FROM $table WHERE slug = ? AND id <> ?", [$slug, $ignoreId])->fetch()) $slug = $base . '-' . $i++;
    return $slug;
}
function valid_http_url(?string $u, bool $httpsOnly = false): bool {
    if (!$u || strlen($u) > 2000) return false;
    $p = parse_url($u);
    if (!$p || empty($p['host']) || empty($p['scheme'])) return false;
    $ok = $httpsOnly ? ['https'] : ['http', 'https'];
    return in_array(strtolower($p['scheme']), $ok, true) && filter_var($u, FILTER_VALIDATE_URL) !== false;
}

/* Imagens */
function img_src(?string $path, ?string $url = null): string {
    $path = trim((string)$path);
    // Alguns registros antigos guardam a URL completa no campo image_path.
    // Não transforme https://... em /https://..., pois isso quebra a imagem.
    if ($path !== '') {
        if (preg_match('~^https://~i', $path) && valid_http_url($path, true)) return $path;
        // Caminhos locais devem ser relativos à raiz pública do projeto.
        if (preg_match('~^https?://~i', $path)) $path = '';
        else return url($path);
    }
    if ($url && valid_http_url(trim($url), true)) return trim($url);
    return asset('images/placeholder.svg');
}
function product_img(array $p): string { return img_src($p['image_path'] ?? null, $p['image_url'] ?? null); }
function upload_image(string $field, string $dir): ?string {
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Falha no upload da imagem.');
    if ($f['size'] > 3 * 1024 * 1024) throw new RuntimeException('Imagem maior que 3 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($map[$mime])) throw new RuntimeException('Formato inválido. Use JPG, PNG, WEBP ou GIF.');
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) throw new RuntimeException('Extensão inválida.');
    if (!@getimagesize($f['tmp_name'])) throw new RuntimeException('Arquivo de imagem inválido.');
    if (!in_array($dir, ['products', 'banners', 'categories', 'settings', 'blog'], true)) throw new RuntimeException('Pasta inválida.');
    $name = bin2hex(random_bytes(16)) . '.' . $map[$mime];
    $dest = ROOT . "/uploads/$dir/$name";
    if (!move_uploaded_file($f['tmp_name'], $dest)) throw new RuntimeException('Não foi possível salvar a imagem.');
    @chmod($dest, 0644);
    return "uploads/$dir/$name";
}
/** Remove arquivo local apenas se nenhum outro registro o usa. */
function delete_upload_if_unused(?string $path): void {
    if (!$path || strpos($path, 'uploads/') !== 0 || strpos($path, '..') !== false) return;
    $uses = 0;
    foreach ([['products', 'image_path'], ['product_images', 'image_path'], ['banners', 'image_path'], ['categories', 'image_path'], ['affiliate_stores', 'logo_path'], ['blog_posts', 'featured_image']] as [$t, $c])
        $uses += (int)q("SELECT COUNT(*) FROM $t WHERE $c = ?", [$path])->fetchColumn();
    foreach (settings() as $v) if ($v === $path) $uses++;
    if ($uses === 0) { $f = ROOT . '/' . $path; if (is_file($f)) @unlink($f); }
}

/* Consultas públicas */
const PROD_SELECT = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug, s.name AS store_name FROM products p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN affiliate_stores s ON s.id = p.store_id';
function products_where(string $where, array $params = [], string $order = 'p.sort_order ASC, p.id DESC', int $limit = 12, int $offset = 0): array {
    $sql = PROD_SELECT . " WHERE p.status = 'publicado' " . ($where ? "AND $where " : '') . "ORDER BY $order LIMIT " . (int)$limit . ' OFFSET ' . (int)$offset;
    return q($sql, $params)->fetchAll();
}
function banners_for(string $pos): array {
    return q("SELECT * FROM banners WHERE position = ? AND is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY sort_order, id DESC", [$pos])->fetchAll();
}
function blog_public_where(string $alias = 'b'): string { return "$alias.status = 'publicado' AND ($alias.published_at IS NULL OR $alias.published_at <= NOW())"; }
/** Conteúdo com HTML de bloco é exibido como está. Texto puro: linhas em branco viram parágrafos e quebras simples viram <br>. */
function blog_html(string $c): string {
    $c = str_replace(["\r\n", "\r"], "\n", $c);
    if (preg_match('/<(p|h[1-6]|ul|ol|li|div|table|figure|blockquote|section|article)[\s>]/i', $c)) return $c;
    $out = '';
    foreach (preg_split('/\n\s*\n/', trim($c)) ?: [] as $b) {
        $b = trim($b);
        if ($b !== '') $out .= '<p>' . nl2br($b, false) . "</p>\n";
    }
    return $out;
}
function reading_minutes(int $words): int { return max(1, (int)ceil($words / 220)); }
function word_count(string $html): int { $t = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($html, ENT_QUOTES, 'UTF-8')))); return $t === '' ? 0 : count(preg_split('/\s+/u', $t)); }
function excerpt_from(string $html, int $max = 160): string { $t = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($html, ENT_QUOTES, 'UTF-8')))); return mb_strlen($t) > $max ? rtrim(mb_substr($t, 0, $max - 1)) . '…' : $t; }
function page_title(string $t): string { return $t === '' ? setting('site_name', 'Loot Gamer') : $t . ' | ' . setting('site_name', 'Loot Gamer'); }
function log_admin(string $action, string $details = ''): void {
    try { q('INSERT INTO admin_logs (user_id, action, details) VALUES (?,?,?)', [$_SESSION['uid'] ?? null, $action, mb_substr($details, 0, 250)]); } catch (Throwable $e) {}
}

function cat_ids(string $slug): array {
    $c = q('SELECT id FROM categories WHERE slug = ?', [$slug])->fetch();
    if (!$c) return [0];
    $ids = [(int)$c['id']];
    $ids = array_merge($ids, array_map('intval', q('SELECT id FROM categories WHERE parent_id = ?', [$c['id']])->fetchAll(PDO::FETCH_COLUMN)));
    return $ids;
}
function in_clause(array $ids): string { return implode(',', array_fill(0, count($ids), '?')); }
function section_on(string $k): bool { return setting('sec_' . $k, '1') === '1'; }
function render_banner(array $b, string $cls = ''): void {
    $img = img_src($b['image_path'] ?? null, $b['image_url'] ?? null);
    $link = !empty($b['link_url']) ? $b['link_url'] : '#';
    $ext = valid_http_url($link) ? ' target="_blank" rel="sponsored nofollow noopener"' : '';
    if ($link !== '#' && !valid_http_url($link)) $link = url(ltrim($link, '/'));
    $isHero = ($b['position'] ?? '') === 'principal';
    $loading = $isHero ? 'eager' : 'lazy';
    $priority = $isHero ? ' fetchpriority="high"' : '';
    echo '<a class="banner ' . e($cls) . '" href="' . e($link) . '"' . $ext . '><img src="' . e($img) . '" alt="' . e($b['internal_title']) . '" loading="' . $loading . '"' . $priority . ' data-fallback>';
    if (!empty($b['main_text']) || !empty($b['secondary_text']) || !empty($b['button_text'])) {
        echo '<span class="banner-txt">';
        if (!empty($b['main_text'])) echo '<strong>' . e($b['main_text']) . '</strong>';
        if (!empty($b['secondary_text'])) echo '<em>' . e($b['secondary_text']) . '</em>';
        if (!empty($b['button_text'])) echo '<b class="btn-banner">' . e($b['button_text']) . '</b>';
        echo '</span>';
    }
    echo '</a>';
}
function products_section(string $title, array $items, string $more = '', string $key = ''): void {
    if (!$items) return;
    echo '<section class="wrap sec"><div class="sec-head"><h2>' . e($title) . '</h2>';
    if ($more) echo '<a class="pill" href="' . e($more) . '">Ver todos →</a>';
    echo '</div><div class="grid">';
    foreach ($items as $p) include ROOT . '/includes/card.php';
    echo '</div></section>';
}
