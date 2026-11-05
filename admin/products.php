<?php
require_once __DIR__ . '/../includes/auth.php'; $me = require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)($_POST['id'] ?? 0); $a = $_POST['action'] ?? '';
    $p = q('SELECT * FROM products WHERE id = ?', [$id])->fetch();
    if ($p) {
        if ($a === 'delete') {
            $imgs = q('SELECT image_path FROM product_images WHERE product_id = ?', [$id])->fetchAll(PDO::FETCH_COLUMN);
            q('DELETE FROM products WHERE id = ?', [$id]);
            delete_upload_if_unused($p['image_path']); foreach ($imgs as $i) delete_upload_if_unused($i);
            log_admin('produto_excluido', $p['name']); flash('ok', 'Produto excluído.');
        } elseif ($a === 'duplicate') {
            $slug = unique_slug('products', $p['slug'] . '-copia');
            q("INSERT INTO products (category_id,store_id,name,slug,short_description,description,brand,model,price,old_price,promo_text,image_path,image_url,affiliate_url,is_featured,is_promo,is_recommended,status,sort_order,seo_title,meta_description) SELECT category_id,store_id,CONCAT(name,' (cópia)'),?,short_description,description,brand,model,price,old_price,promo_text,image_path,image_url,affiliate_url,is_featured,is_promo,is_recommended,'rascunho',sort_order,seo_title,meta_description FROM products WHERE id = ?", [$slug, $id]);
            log_admin('produto_duplicado', $p['name']); flash('ok', 'Produto duplicado como rascunho.');
        } elseif (in_array($a, ['publicado', 'inativo', 'rascunho'], true)) {
            q('UPDATE products SET status = ? WHERE id = ?', [$a, $id]); log_admin('produto_status', $p['name'] . ' → ' . $a); flash('ok', 'Status atualizado.');
        }
    }
    redirect($_SERVER['REQUEST_URI']);
}
$title = 'Produtos'; $actions = '<a class="btn" href="product-edit.php">+ Novo produto</a>';
require __DIR__ . '/_head.php';
$s = trim($_GET['s'] ?? ''); $st = $_GET['status'] ?? ''; $cat = (int)($_GET['cat'] ?? 0);
$w = ['1=1']; $pr = [];
if ($s !== '') { $w[] = '(p.name LIKE ? OR p.brand LIKE ? OR p.model LIKE ?)'; $l = '%' . $s . '%'; array_push($pr, $l, $l, $l); }
if (in_array($st, ['rascunho', 'publicado', 'inativo'], true)) { $w[] = 'p.status = ?'; $pr[] = $st; }
if ($cat) { $w[] = 'p.category_id = ?'; $pr[] = $cat; }
$pg = max(1, (int)($_GET['p'] ?? 1)); $per = 20;
$tot = (int)q('SELECT COUNT(*) FROM products p WHERE ' . implode(' AND ', $w), $pr)->fetchColumn();
$rows = q('SELECT p.*, c.name cname, s.name sname, (SELECT COUNT(*) FROM clicks k WHERE k.product_id=p.id) clk FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN affiliate_stores s ON s.id=p.store_id WHERE ' . implode(' AND ', $w) . ' ORDER BY p.id DESC LIMIT ' . $per . ' OFFSET ' . (($pg - 1) * $per), $pr)->fetchAll();
$cats = q('SELECT id,name FROM categories ORDER BY name')->fetchAll();
?>
<form class="bar" method="get"><input name="s" placeholder="Buscar produto..." value="<?= e($s) ?>">
<select name="status"><option value="">Todos os status</option><?php foreach (['publicado', 'rascunho', 'inativo'] as $x): ?><option <?= $st === $x ? 'selected' : '' ?>><?= $x ?></option><?php endforeach; ?></select>
<select name="cat"><option value="">Todas as categorias</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><button class="btn">Filtrar</button></form>
<div class="box scroll"><table class="list"><thead><tr><th></th><th>Produto</th><th>Categoria</th><th>Loja</th><th>Preço</th><th>Status</th><th>Cliques</th><th>Ações</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
<td><img class="thumb" src="<?= e(product_img($r)) ?>" alt="" loading="lazy"></td>
<td><a href="product-edit.php?id=<?= $r['id'] ?>"><b><?= e($r['name']) ?></b></a><br><small><?= $r['is_featured'] ? '★ destaque ' : '' ?><?= $r['is_promo'] ? '% promo ' : '' ?><?= $r['is_recommended'] ? '👍 recomendado' : '' ?></small></td>
<td><?= e($r['cname']) ?></td><td><?= e($r['sname']) ?></td><td><?= $r['price'] ? money($r['price']) : '—' ?></td>
<td><span class="st st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td><td><?= (int)$r['clk'] ?></td>
<td class="acts"><a class="mini" href="product-edit.php?id=<?= $r['id'] ?>">Editar</a>
<?php foreach ([['duplicate', 'Duplicar', ''], [$r['status'] === 'publicado' ? 'inativo' : 'publicado', $r['status'] === 'publicado' ? 'Desativar' : 'Publicar', ''], ['delete', 'Excluir', 'Excluir este produto? Esta ação não pode ser desfeita.']] as [$a, $l, $cf]): ?>
<form method="post" class="inline" <?= $cf ? 'data-confirm="' . e($cf) . '"' : '' ?>><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="action" value="<?= e($a) ?>"><button class="mini <?= $a === 'delete' ? 'danger' : '' ?>"><?= $l ?></button></form>
<?php endforeach; ?></td></tr><?php endforeach; if (!$rows) echo '<tr><td colspan="8">Nenhum produto.</td></tr>'; ?>
</tbody></table></div>
<?php $pages = (int)ceil($tot / $per); if ($pages > 1): ?><div class="pager"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="<?= $i === $pg ? 'on' : '' ?>" href="?<?= e(http_build_query(array_merge($_GET, ['p' => $i]))) ?>"><?= $i ?></a><?php endfor; ?></div><?php endif;
require __DIR__ . '/_foot.php';
