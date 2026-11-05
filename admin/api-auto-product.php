<?php
/**
 * API Automática - Loot Gamer Products (Cards Home)
 * FINAL - com download de imagem local + preço antes/promo
 * Local: public_html/lootgamer/admin/api-auto-product.php
 */

declare(strict_types=1);
const API_SECRET_TOKEN = 'L00tG4m3r_Xll_2026_9xP4_k7s!';

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$providedToken = $_SERVER['HTTP_X_API_TOKEN'] ?? $_GET['token'] ?? '';
$isApiCall = hash_equals(API_SECRET_TOKEN, $providedToken);
if (!$isApiCall) {
    $me = require_admin();
}

header('Content-Type: application/json; charset=utf-8');

function prod_slugify(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'produto';
}

function prod_download_image(string $url, string $name): ?string {
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) return null;
    try {
        $pathInfo = parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($pathInfo, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) $ext = 'jpg';
        $dir = __DIR__ . '/../uploads/products/' . date('Y/m');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = prod_slugify(substr($name,0,30)).'-'.uniqid().'.'.$ext;
        $fullPath = $dir.'/'.$filename;
        $ctx = stream_context_create(['http'=>['timeout'=>15,'header'=>"User-Agent: Mozilla/5.0"]]);
        $binary = @file_get_contents($url, false, $ctx);
        if (!$binary || strlen($binary) < 1000) return null;
        file_put_contents($fullPath, $binary);
        return 'uploads/products/' . date('Y/m') . '/' . $filename;
    } catch(Throwable $e) { return null; }
}

// DEBUG e FIX via GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['debug'])) {
    if ($_GET['debug'] === 'fix') {
        try {
            q("UPDATE products SET status='publicado' WHERE status='' OR status='published' OR status IS NULL OR status=''");
            q("UPDATE products SET is_featured=1 WHERE id >= 15");
            $total = q("SELECT COUNT(*) as c FROM products")->fetch()['c'];
            echo json_encode(['ok'=>true,'msg'=>'corrigido para publicado + featured','total'=>$total], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        } catch(Throwable $e){ echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'line'=>$e->getLine()]); }
        exit;
    }
    if ($_GET['debug'] === 'fix2') {
        try {
            q("DELETE FROM products WHERE id >= 15");
            echo json_encode(['ok'=>true,'msg'=>'apagados produtos teste >=15']);
        } catch(Throwable $e){ echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
        exit;
    }
    try {
        $total = q("SELECT COUNT(*) as c FROM products")->fetch()['c'];
        $rows = q("SELECT id, name, slug, price, old_price, promo_text, image_path, image_url, affiliate_url, is_featured, status, created_at FROM products ORDER BY id DESC LIMIT 20")->fetchAll();
        $stores = q("SELECT id, name FROM affiliate_stores LIMIT 10")->fetchAll();
        echo json_encode(['ok'=>true,'total'=>$total,'last20'=>$rows,'stores'=>$stores], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    } catch(Throwable $e){ echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'line'=>$e->getLine()]); }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Use POST']);
    exit;
}

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!$data) throw new Exception('JSON invalido');

    $name = trim($data['name'] ?? $data['nome_produto'] ?? '');
    if ($name === '') throw new Exception('name obrigatorio');

    $slugBase = prod_slugify($data['slug'] ?? $name);
    $slug = $slugBase; $i=2;
    while (true) {
        $ex = q("SELECT id FROM products WHERE slug=?", [$slug])->fetch();
        if (!$ex) break;
        $slug = $slugBase.'-'.$i++;
    }

    $price = floatval($data['price'] ?? $data['preco_promocao'] ?? $data['preco'] ?? 0);
    $old_price = floatval($data['old_price'] ?? $data['preco_antes'] ?? 0);
    if ($old_price <= $price) $old_price = $price * 1.30; // 30% desconto se não tiver old

    $affiliate = $data['affiliate_url'] ?? $data['link_afiliado_ml'] ?? '';
    $image_raw = $data['image_url'] ?? $data['link_imagem_produto'] ?? $data['imagem'] ?? '';
    $blog_slug = $data['blog_slug'] ?? '';
    $category_id = intval($data['category_id'] ?? 3);

    $final_url = $blog_slug ? '/blog/'.$blog_slug : $affiliate;
    if ($blog_slug && $final_url !== '' && $final_url[0] !== '/') $final_url = '/blog/'.$final_url;

    $promo = $old_price > $price ? round(100 - ($price*100/$old_price)).'% OFF' : 'Oferta';

    // Baixa imagem local
    $localPath = prod_download_image($image_raw, $name);
    $image_path = $localPath ? $localPath : $image_raw;
    $image_url = $localPath ? $localPath : $image_raw;

    $storeRow = q("SELECT id FROM affiliate_stores LIMIT 1")->fetch();
    $store_id = $storeRow ? $storeRow['id'] : null;

    // 21 ? + NOW(),NOW() = 23 colunas
    $sql = "INSERT INTO products (name, slug, short_description, description, category_id, brand, model, price, old_price, promo_text, image_path, image_url, affiliate_url, store_id, is_featured, is_promo, is_recommended, status, sort_order, seo_title, meta_description, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())";

    q($sql, [
        $name,
        $slug,
        substr($data['short_description'] ?? $name, 0, 255),
        $data['description'] ?? $name,
        $category_id,
        $data['brand'] ?? '【entity-Sony¦canonical_name=Sony】',
        $data['model'] ?? '',
        $price,
        $old_price,
        $promo,
        $image_path,
        $image_url,
        $final_url,
        $store_id,
        1, 1, 1, 'publicado', 0,
        $name.' em oferta | Loot Gamer',
        substr($name.' - melhor preço no Loot Gamer', 0, 160)
    ]);

    $newId = (int)db()->lastInsertId();
    log_admin('produto_criado_api', $name.' #'.$newId);

    echo json_encode([
        'ok'=>true,
        'id'=>$newId,
        'slug'=>$slug,
        'url'=>'/produto/'.$slug,
        'final_url'=>$final_url,
        'image_path'=>$image_path,
        'price'=>$price,
        'old_price'=>$old_price,
        'store_id'=>$store_id
    ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

} catch(Throwable $e){
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'file'=>basename($e->getFile()),'line'=>$e->getLine()]);
}