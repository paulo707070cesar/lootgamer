<?php
/**
 * Sincronização admin XLL -> tabelas do site público (lootgamer).
 * Executado a cada carregamento do painel (admin/inc/layout.php).
 */
declare(strict_types=1);

function lg_legacy_pdo(): ?PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) { return $pdo; }
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=u940098558_lootgamer;charset=utf8mb4', 'u940098558_lootgamer', 'o/;Mf:7J', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable) { return null; }
    return $pdo;
}

function lg_slug(string $txt): string
{
    $txt = iconv('UTF-8', 'ASCII//TRANSLIT', $txt) ?: $txt;
    $txt = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $txt) ?? '');
    return trim($txt, '-') ?: 'item';
}

function lg_pub_url(string $caminho): string
{
    $caminho = trim($caminho);
    if ($caminho === '' || preg_match('#^https?://#i', $caminho)) { return $caminho; }
    $caminho = ltrim($caminho, '/');
    if (str_starts_with($caminho, 'data/') || str_starts_with($caminho, 'assets/')) { return '/' . $caminho; }
    if (!str_starts_with($caminho, 'uploads/')) { $caminho = 'uploads/' . $caminho; }
    return '/' . $caminho;
}

function lg_sync_legacy(bool $forcar = false): array
{
    $rel = ['ok' => true, 'log' => [], 'erros' => []];
    $pdo = lg_legacy_pdo();
    if (!$pdo) { $rel['ok'] = false; $rel['erros'][] = 'sem conexão legada'; return $rel; }
    $xll = xll_db();

    try {
        /* 1. configurações */
        $cfg = [
            'site_name' => (string) xll_setting('site_name', 'Loot Gamer'),
            'site_description' => (string) xll_setting('meta_description', ''),
        ];
        $insSet = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($cfg as $k => $v) { if ($v !== '') { $insSet->execute([$k, $v]); } }
        $rel['log'][] = 'configurações sincronizadas';

        /* 2. categorias */
        $cats = xll_setting('categories');
        $itens = is_array($cats) && !empty($cats['items']) ? $cats['items'] : [];
        $catIdPorSlug = [];
        $catIdPorNome = [];
        $existentes = $pdo->query('SELECT id, slug, name FROM categories')->fetchAll();
        $norm = function (string $t): string {
            $t = mb_strtolower(trim($t));
            $t = preg_replace('/\bgamer\b/u', '', $t);
            return preg_replace('/[^a-z0-9]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $t) ?: $t);
        };
        $catIdPorNorm = [];
        foreach ($existentes as $c) { $catIdPorSlug[$c['slug']] = (int) $c['id']; $catIdPorNorm[$norm($c['name'])] = (int) $c['id']; }
        $ordem = 0;
        foreach ($itens as $item) {
            if (empty($item['active'])) { continue; }
            $nome = trim((string) ($item['name'] ?? ''));
            if ($nome === '') { continue; }
            $slug = lg_slug($nome);
            $chave = $norm($nome);
            if (isset($catIdPorNorm[$chave])) {
                $slug = (string) array_search($catIdPorNorm[$chave], $catIdPorSlug, true);
                $pdo->prepare('UPDATE categories SET name = ?, description = ?, sort_order = ? WHERE id = ?')
                    ->execute([$nome, (string) ($item['description'] ?? ''), $ordem, $catIdPorNorm[$chave]]);
                $catIdPorNome[mb_strtolower($nome)] = $catIdPorNorm[$chave];
            } else {
                $img = lg_pub_url((string) ($item['image'] ?? ''));
                $pdo->prepare('INSERT INTO categories (name, slug, description, image_path, is_main, is_active, sort_order) VALUES (?,?,?,?,1,1,?)')
                    ->execute([$nome, $slug, (string) ($item['description'] ?? ''), $img, $ordem]);
                $novoId = (int) $pdo->lastInsertId();
                $catIdPorSlug[$slug] = $novoId;
                $catIdPorNome[mb_strtolower($nome)] = $novoId;
            }
            $ordem++;
        }
        $rel['log'][] = 'categorias: ' . count($itens);

        /* 3. produtos */
        $prods = $xll->query('SELECT * FROM produtos ORDER BY ordem, id')->fetchAll();
        $sel = $pdo->prepare('SELECT id FROM products WHERE slug = ?');
        $upd = $pdo->prepare('UPDATE products SET category_id=?, name=?, description=?, price=?, old_price=?, affiliate_url=?, is_featured=?, is_promo=?, status="publicado", sort_order=?, updated_at=NOW() WHERE id=?');
            $slugs = [];
        $nIns = 0; $nUpd = 0;
        foreach ($prods as $p) {
            $slug = trim((string) ($p['slug'] ?? ''));
            if ($slug === '') { continue; }
            $slugs[$slug] = true;
            $catNome = mb_strtolower(trim((string) ($p['categoria'] ?? '')));
            $catId = $catIdPorNome[$catNome] ?? $catIdPorSlug[lg_slug($catNome)] ?? null;
            $preco = ((int) $p['preco']) / 100;
            $antigo = ((int) $p['preco_antigo']) / 100;
            $img = lg_pub_url((string) ($p['imagem'] ?? ''));
            $feat = ($p['secao_home'] ?? '') === 'destaques' ? 1 : 0;
            $promo = ((int) $p['desconto']) > 0 ? 1 : 0;
            $sel->execute([$slug]);
            $achou = $sel->fetch();
            if ($achou) {
                $upd->execute([$catId, $p['titulo'], (string) ($p['descricao'] ?? ''), $preco, $antigo, (string) ($p['link_afiliado'] ?? ''), $feat, $promo, (int) $p['ordem'], $achou['id']]);
                if (preg_match('#^https?://#i', $img)) { $pdo->prepare('UPDATE products SET image_url = ?, image_path = NULL WHERE id = ?')->execute([$img, $achou['id']]); }
                else { $pdo->prepare('UPDATE products SET image_path = ?, image_url = NULL WHERE id = ?')->execute([ltrim($img, '/'), $achou['id']]); }
                $nUpd++;
            } else {
                $pdo->prepare('INSERT INTO products (category_id, name, slug, short_description, description, price, old_price, image_path, affiliate_url, is_featured, is_promo, status, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,"publicado",?)')
                    ->execute([$catId, $p['titulo'], $slug, '', (string) ($p['descricao'] ?? ''), $preco, $antigo, $img, (string) ($p['link_afiliado'] ?? ''), $feat, $promo, (int) $p['ordem']]);
                $nIns++;
            }
        }
        /* desativa produtos que não existem mais no painel */
        $todos = $pdo->query('SELECT id, slug FROM products')->fetchAll();
        $desativa = $pdo->prepare('UPDATE products SET status = "inativo" WHERE id = ?');
        foreach ($todos as $t) {
            if (!isset($slugs[$t['slug']])) { $desativa->execute([$t['id']]); }
        }
        $rel['log'][] = "produtos: {$nUpd} atualizados, {$nIns} inseridos";
        $rel['produtos'] = count($prods);

        /* 4. banners do hero */
        $hero = xll_setting('hero');
        $slides = is_array($hero) && !empty($hero['slides']) ? $hero['slides'] : [];
        $validos = [];
        foreach ($slides as $s) {
            if (empty($s['active'])) { continue; }
            $img = trim((string) ($s['image'] ?? ''));
            if ($img === '') { continue; }
            $url = preg_match('#^https?://#i', $img) ? $img : 'https://lootgamer.xll.com.br/' . ltrim($img, '/');
            $relPath = preg_replace('#^https?://[^/]+/#i', '', $url);
            $validos[] = ['url' => $url, 'rel' => $relPath, 'link' => (string) ($s['link'] ?? ''), 'alt' => (string) ($s['alt'] ?? 'Banner principal')];
        }
        if ($validos) {
            $pdo->exec('DELETE FROM banners WHERE position = "principal"');
            $insB = $pdo->prepare('INSERT INTO banners (internal_title, main_text, secondary_text, image_path, image_url, link_url, button_text, position, sort_order, is_active) VALUES (?, "", "", ?, ?, ?, "", "principal", ?, 1)');
            $i = 0;
            foreach ($validos as $v) {
                $insB->execute([$v['alt'], $v['rel'], $v['url'], $v['link'], $i]);
                $i++;
            }
            $rel['log'][] = 'banners: ' . $i;
        }

        /* 5. corrige caminhos de imagens antigos (registros legados) */
        
        $pdo->exec("UPDATE products SET image_path = CONCAT('/', image_path) WHERE image_path LIKE 'uploads/%'");
        $pdo->exec("UPDATE products SET image_path = CONCAT('/', image_path) WHERE image_path LIKE 'data/%'");
        $rel['log'][] = 'imagens normalizadas';

        /* 6. cores do painel -> CSS do site publico */
        $colors = xll_setting('colors');
        $colors = is_array($colors) ? $colors : [];
        $mapaCores = ['background' => '--bg', 'card' => '--card', 'border' => '--line', 'text' => '--text', 'muted' => '--muted', 'accent' => '--neon', 'discount' => '--yellow', 'button_text' => '--btn-text', 'footer_title' => '--footer-title'];
        $vars = [];
        foreach ($mapaCores as $chave => $var) {
            $v = trim((string) ($colors[$chave] ?? ''));
            if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $v)) { $vars[$var] = $v; }
        }
        $radius = max(0, min(24, (int) ($colors['radius'] ?? 6)));
        $css = '';
        if ($vars) {
            $css = ':root{';
            foreach ($vars as $var => $v) { $css .= $var . ':' . $v . ';'; }
            $css .= '--xll-radius:' . $radius . 'px;';
            $css .= '--lg-surface:var(--bg);--lg-surface-lowest:var(--bg);--lg-surface-container:var(--card);--lg-surface-high:var(--card);--lg-outline-variant:var(--line);--lg-primary-container:var(--neon);--lg-primary:var(--neon);}';
            $css .= 'body{background-color:var(--bg)!important;color:var(--text)!important;}';
            $css .= '.site-header{background:rgba(11,14,21,.92)!important;}';
            $css .= 'h1,h2,h3,h4,h5,h6,.card-title a,.navbar a,.panel h2,.sec-head h2,.brand{color:var(--text)!important;}';
            $css .= 'p,small,.card-price s,.trust small,.site-footer p,.site-footer a,.cat span,.pill,.crumbs,.tagline span{color:var(--muted)!important;}';
            $css .= '.card-title a:hover,.site-footer a:hover,.navbar a:hover{color:var(--neon)!important;}';
            if (!empty($vars['--neon'])) { $css .= '.card-price strong,.btn-offer,.pager a.on{color:var(--neon)!important;}'; }
            @file_put_contents(XLL_ROOT . '/assets/css/theme-admin.css', $css);
            $rel['log'][] = 'cores sincronizadas';
        }
    } catch (Throwable $e) {
        $rel['ok'] = false;
        $rel['erros'][] = $e->getMessage();
    }
    return $rel;
}
