<?php
require_once __DIR__ . '/../includes/auth.php'; $me = require_admin();
$id = (int)($_GET['id'] ?? 0);
$positions = ['principal' => 'Banner principal', 'secundario' => 'Banner secundário', 'entre_categorias' => 'Entre categorias', 'entre_produtos' => 'Entre produtos', 'jogos_digitais' => 'Jogos digitais', 'rodape' => 'Promocional no rodapé'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $pid = (int)($_POST['id'] ?? 0); $old = $pid ? q('SELECT * FROM banners WHERE id=?', [$pid])->fetch() : null;
    if (($_POST['action'] ?? '') === 'delete' && $old) { q('DELETE FROM banners WHERE id=?', [$pid]); delete_upload_if_unused($old['image_path']); log_admin('banner_excluido', $old['internal_title']); flash('ok', 'Banner excluído.'); redirect('banners.php'); }
    $t = trim((string)$_POST['internal_title']); $iu = trim((string)$_POST['image_url']); $lk = trim((string)$_POST['link_url']);
    if ($t === '') { flash('err', 'Informe o título interno.'); redirect('banners.php'); }
    if ($iu !== '' && !valid_http_url($iu, true)) { flash('err', 'URL da imagem deve ser HTTPS válida.'); redirect('banners.php'); }
    if ($lk !== '' && !valid_http_url($lk) && $lk[0] !== '/') { flash('err', 'Link de destino inválido.'); redirect('banners.php'); }
    try { $np = upload_image('image_file', 'banners'); } catch (RuntimeException $ex) { flash('err', $ex->getMessage()); redirect('banners.php'); }
    $pos = array_key_exists($_POST['position'] ?? '', $positions) ? $_POST['position'] : 'principal';
    $dt = fn($k) => ($x = trim((string)($_POST[$k] ?? ''))) !== '' ? str_replace('T', ' ', $x) . ':00' : null;
    $v = [$t, trim((string)$_POST['main_text']) ?: null, trim((string)$_POST['secondary_text']) ?: null, $np ?: ($old['image_path'] ?? null), $iu ?: null, $lk ?: null, trim((string)$_POST['button_text']) ?: null, $pos, $dt('starts_at'), $dt('ends_at'), (int)$_POST['sort_order'], !empty($_POST['is_active']) ? 1 : 0];
    if ($pid) { q('UPDATE banners SET internal_title=?,main_text=?,secondary_text=?,image_path=?,image_url=?,link_url=?,button_text=?,position=?,starts_at=?,ends_at=?,sort_order=?,is_active=? WHERE id=?', array_merge($v, [$pid])); if ($np && $old['image_path']) delete_upload_if_unused($old['image_path']); }
    else q('INSERT INTO banners (internal_title,main_text,secondary_text,image_path,image_url,link_url,button_text,position,starts_at,ends_at,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', $v);
    log_admin('banner_salvo', $t); flash('ok', 'Banner salvo.'); redirect('banners.php');
}
$e = $id ? q('SELECT * FROM banners WHERE id=?', [$id])->fetch() : null;
$d = $e ?: ['internal_title' => '', 'main_text' => '', 'secondary_text' => '', 'image_path' => '', 'image_url' => '', 'link_url' => '', 'button_text' => '', 'position' => 'principal', 'starts_at' => '', 'ends_at' => '', 'sort_order' => 0, 'is_active' => 1];
$rows = q('SELECT * FROM banners ORDER BY position, sort_order, id DESC')->fetchAll();
$dtl = fn($x) => $x ? str_replace(' ', 'T', substr($x, 0, 16)) : '';
$title = 'Banners'; require __DIR__ . '/_head.php'; ?>
<div class="cols2"><section class="box scroll"><table class="list"><thead><tr><th></th><th>Banner</th><th>Posição</th><th>Período</th><th>Ativo</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><img class="thumb wide" src="<?= e(img_src($r['image_path'], $r['image_url'])) ?>" alt=""></td><td><a href="?id=<?= $r['id'] ?>"><b><?= e($r['internal_title']) ?></b></a></td><td><?= e($positions[$r['position']]) ?></td><td><small><?= $r['starts_at'] ? e(substr($r['starts_at'], 0, 16)) : '—' ?> → <?= $r['ends_at'] ? e(substr($r['ends_at'], 0, 16)) : '—' ?></small></td><td><?= $r['is_active'] ? 'Sim' : 'Não' ?></td>
<td class="acts"><form method="post" class="inline" data-confirm="Excluir este banner?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="mini danger">Excluir</button></form></td></tr><?php endforeach; ?></tbody></table></section>
<section class="box"><h2><?= $e ? 'Editar banner' : 'Novo banner' ?></h2><form method="post" enctype="multipart/form-data" class="formgrid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>">
<label>Título interno *<input name="internal_title" required value="<?= e($d['internal_title']) ?>"></label>
<label>Texto principal (opcional)<input name="main_text" value="<?= e($d['main_text']) ?>"></label><label>Texto secundário<input name="secondary_text" value="<?= e($d['secondary_text']) ?>"></label>
<label>Imagem (upload)<input type="file" name="image_file" id="imgFile" accept="image/*"></label><label>…ou URL HTTPS da imagem<input type="url" name="image_url" id="imgUrl" value="<?= e($d['image_url']) ?>"></label>
<div class="preview"><img id="imgPrev" src="<?= e(img_src($d['image_path'], $d['image_url'])) ?>" alt=""></div>
<label>Link de destino<input name="link_url" value="<?= e($d['link_url']) ?>" placeholder="https://… ou /ofertas"></label><label>Texto do botão<input name="button_text" value="<?= e($d['button_text']) ?>"></label>
<label>Posição<select name="position"><?php foreach ($positions as $k => $l): ?><option value="<?= $k ?>" <?= $d['position'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
<div class="f2"><label>Início<input type="datetime-local" name="starts_at" value="<?= e($dtl($d['starts_at'])) ?>"></label><label>Término<input type="datetime-local" name="ends_at" value="<?= e($dtl($d['ends_at'])) ?>"></label></div>
<div class="f2"><label>Ordem<input type="number" name="sort_order" value="<?= (int)$d['sort_order'] ?>"></label><label class="chk"><input type="checkbox" name="is_active" value="1" <?= $d['is_active'] ? 'checked' : '' ?>> Ativo</label></div>
<div class="actionsbar"><button class="btn">💾 Salvar</button><?php if ($e): ?><a class="btn ghost" href="banners.php">Novo</a><?php endif; ?></div></form></section></div>
<?php require __DIR__ . '/_foot.php';
