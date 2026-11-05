<?php
require_once __DIR__ . '/../includes/auth.php'; $me = require_admin();
$id = (int)($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $a = $_POST['action'] ?? 'save'; $pid = (int)($_POST['id'] ?? 0);
    if ($a === 'delete' && $pid) {
        $c = q('SELECT * FROM categories WHERE id = ?', [$pid])->fetch();
        if ($c) { q('DELETE FROM categories WHERE id = ?', [$pid]); delete_upload_if_unused($c['image_path']); log_admin('categoria_excluida', $c['name']); flash('ok', 'Categoria excluída (produtos ficam sem categoria).'); }
        redirect('categories.php');
    }
    $name = trim((string)($_POST['name'] ?? '')); $old = $pid ? q('SELECT * FROM categories WHERE id = ?', [$pid])->fetch() : null;
    if ($name === '') { flash('err', 'Informe o nome.'); redirect('categories.php' . ($pid ? '?id=' . $pid : '')); }
    try { $np = upload_image('image_file', 'categories'); } catch (RuntimeException $ex) { flash('err', $ex->getMessage()); redirect('categories.php' . ($pid ? '?id=' . $pid : '')); }
    $slug = unique_slug('categories', slugify(trim((string)($_POST['slug'] ?? '')) ?: $name), $pid);
    $parent = (int)($_POST['parent_id'] ?? 0) ?: null; if ($parent === $pid) $parent = null;
    $path = $np ?: ($old['image_path'] ?? null);
    $v = [$parent, $name, $slug, trim((string)$_POST['description']) ?: null, $path, !empty($_POST['is_main']) ? 1 : 0, (int)$_POST['sort_order'], !empty($_POST['is_active']) ? 1 : 0, trim((string)$_POST['seo_title']) ?: null, trim((string)$_POST['meta_description']) ?: null];
    if ($pid) { q('UPDATE categories SET parent_id=?,name=?,slug=?,description=?,image_path=?,is_main=?,sort_order=?,is_active=?,seo_title=?,meta_description=? WHERE id=?', array_merge($v, [$pid])); if ($np && $old['image_path']) delete_upload_if_unused($old['image_path']); }
    else q('INSERT INTO categories (parent_id,name,slug,description,image_path,is_main,sort_order,is_active,seo_title,meta_description) VALUES (?,?,?,?,?,?,?,?,?,?)', $v);
    log_admin('categoria_salva', $name); flash('ok', 'Categoria salva.'); redirect('categories.php');
}
$e = $id ? q('SELECT * FROM categories WHERE id = ?', [$id])->fetch() : null;
$d = $e ?: ['name' => '', 'slug' => '', 'description' => '', 'image_path' => '', 'parent_id' => '', 'is_main' => 1, 'sort_order' => 0, 'is_active' => 1, 'seo_title' => '', 'meta_description' => ''];
$rows = q('SELECT c.*, p.name pname, (SELECT COUNT(*) FROM products x WHERE x.category_id=c.id) n FROM categories c LEFT JOIN categories p ON p.id=c.parent_id ORDER BY c.sort_order, c.name')->fetchAll();
$title = 'Categorias'; require __DIR__ . '/_head.php'; ?>
<div class="cols2"><section class="box scroll"><table class="list"><thead><tr><th></th><th>Nome</th><th>Pai</th><th>Ordem</th><th>Prod.</th><th>Ativa</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><img class="thumb sm" src="<?= e(img_src($r['image_path'])) ?>" alt=""></td><td><a href="?id=<?= $r['id'] ?>"><b><?= e($r['name']) ?></b></a><br><small>/<?= e($r['slug']) ?><?= $r['is_main'] ? ' · principal' : '' ?></small></td><td><?= e($r['pname']) ?></td><td><?= (int)$r['sort_order'] ?></td><td><?= (int)$r['n'] ?></td><td><?= $r['is_active'] ? 'Sim' : 'Não' ?></td>
<td class="acts"><a class="mini" href="?id=<?= $r['id'] ?>">Editar</a><form method="post" class="inline" data-confirm="Excluir esta categoria?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="mini danger">Excluir</button></form></td></tr><?php endforeach; ?></tbody></table></section>
<section class="box"><h2><?= $e ? 'Editar categoria' : 'Nova categoria' ?></h2>
<form method="post" enctype="multipart/form-data" class="formgrid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>">
<label>Nome *<input name="name" required value="<?= e($d['name']) ?>"></label><label>Slug<input name="slug" value="<?= e($d['slug']) ?>"></label>
<label>Descrição<textarea name="description" rows="2"><?= e($d['description']) ?></textarea></label>
<label>Imagem/ícone<input type="file" name="image_file" accept="image/*" id="imgFile"></label><div class="preview"><img id="imgPrev" src="<?= e(img_src($d['image_path'])) ?>" alt=""></div>
<label>Categoria pai<select name="parent_id"><option value="">— nenhuma —</option><?php foreach ($rows as $r) if ((int)$r['id'] !== $id): ?><option value="<?= $r['id'] ?>" <?= (int)$d['parent_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endif; ?></select></label>
<div class="f3"><label class="chk"><input type="checkbox" name="is_main" value="1" <?= $d['is_main'] ? 'checked' : '' ?>> Principal (menu/home)</label><label class="chk"><input type="checkbox" name="is_active" value="1" <?= $d['is_active'] ? 'checked' : '' ?>> Ativa</label><label>Ordem<input type="number" name="sort_order" value="<?= (int)$d['sort_order'] ?>"></label></div>
<label>Título SEO<input name="seo_title" value="<?= e($d['seo_title']) ?>"></label><label>Meta description<input name="meta_description" value="<?= e($d['meta_description']) ?>"></label>
<div class="actionsbar"><button class="btn">💾 Salvar</button><?php if ($e): ?><a class="btn ghost" href="categories.php">Nova</a><?php endif; ?></div></form></section></div>
<?php require __DIR__ . '/_foot.php';
