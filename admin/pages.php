<?php
require_once __DIR__ . '/../includes/auth.php'; $me = require_admin();
$id = (int)($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $pid = (int)$_POST['id'];
    $t = trim((string)$_POST['title']); if (!$pid || $t === '') { flash('err', 'Dados inválidos.'); redirect('pages.php'); }
    q('UPDATE pages SET title=?, content=?, seo_title=?, meta_description=?, is_active=? WHERE id=?', [$t, (string)$_POST['content'], trim((string)$_POST['seo_title']) ?: null, trim((string)$_POST['meta_description']) ?: null, !empty($_POST['is_active']) ? 1 : 0, $pid]);
    log_admin('pagina_editada', $t); flash('ok', 'Página salva.'); redirect('pages.php?id=' . $pid);
}
$e = $id ? q('SELECT * FROM pages WHERE id=?', [$id])->fetch() : null;
$rows = q('SELECT * FROM pages ORDER BY title')->fetchAll();
$title = 'Páginas institucionais'; require __DIR__ . '/_head.php'; ?>
<div class="cols2"><section class="box"><table class="list"><?php foreach ($rows as $r): ?><tr><td><a href="?id=<?= $r['id'] ?>"><b><?= e($r['title']) ?></b></a><br><small>/<?= e($r['slug']) ?></small></td><td><?= $r['is_active'] ? 'ativa' : 'oculta' ?></td></tr><?php endforeach; ?></table></section>
<section class="box"><?php if ($e): ?><h2>Editar: <?= e($e['title']) ?></h2><form method="post" class="formgrid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $e['id'] ?>">
<label>Título<input name="title" required value="<?= e($e['title']) ?>"></label><label>Conteúdo (HTML permitido)<textarea name="content" rows="16"><?= e($e['content']) ?></textarea></label>
<label>Título SEO<input name="seo_title" value="<?= e($e['seo_title']) ?>"></label><label>Meta description<input name="meta_description" value="<?= e($e['meta_description']) ?>"></label>
<label class="chk"><input type="checkbox" name="is_active" value="1" <?= $e['is_active'] ? 'checked' : '' ?>> Página ativa</label><div class="actionsbar"><button class="btn">💾 Salvar</button></div></form>
<?php else: ?><p>Selecione uma página para editar.</p><?php endif; ?></section></div>
<?php require __DIR__ . '/_foot.php';
