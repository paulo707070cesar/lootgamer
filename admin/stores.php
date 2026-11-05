<?php
require_once __DIR__ . '/../includes/auth.php'; $me = require_admin();
$id = (int)($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $pid = (int)($_POST['id'] ?? 0); $old = $pid ? q('SELECT * FROM affiliate_stores WHERE id=?', [$pid])->fetch() : null;
    if (($_POST['action'] ?? '') === 'delete' && $old) { q('DELETE FROM affiliate_stores WHERE id=?', [$pid]); delete_upload_if_unused($old['logo_path']); log_admin('loja_excluida', $old['name']); flash('ok', 'Loja excluída.'); redirect('stores.php'); }
    $name = trim((string)$_POST['name']); if ($name === '') { flash('err', 'Informe o nome da loja.'); redirect('stores.php'); }
    foreach (['official_url', 'signup_url'] as $k) { $u = trim((string)$_POST[$k]); if ($u !== '' && !valid_http_url($u)) { flash('err', 'URL inválida em ' . $k); redirect('stores.php' . ($pid ? '?id=' . $pid : '')); } }
    try { $np = upload_image('logo_file', 'settings'); } catch (RuntimeException $ex) { flash('err', $ex->getMessage()); redirect('stores.php'); }
    $v = [$name, $np ?: ($old['logo_path'] ?? null), trim((string)$_POST['official_url']) ?: null, trim((string)$_POST['signup_url']) ?: null, trim((string)$_POST['program_code']) ?: null, trim((string)$_POST['notes']) ?: null, !empty($_POST['is_active']) ? 1 : 0];
    if ($pid) { q('UPDATE affiliate_stores SET name=?,logo_path=?,official_url=?,signup_url=?,program_code=?,notes=?,is_active=? WHERE id=?', array_merge($v, [$pid])); if ($np && $old['logo_path']) delete_upload_if_unused($old['logo_path']); }
    else q('INSERT INTO affiliate_stores (name,logo_path,official_url,signup_url,program_code,notes,is_active) VALUES (?,?,?,?,?,?,?)', $v);
    log_admin('loja_salva', $name); flash('ok', 'Loja salva.'); redirect('stores.php');
}
$e = $id ? q('SELECT * FROM affiliate_stores WHERE id=?', [$id])->fetch() : null;
$d = $e ?: ['name' => '', 'logo_path' => '', 'official_url' => '', 'signup_url' => '', 'program_code' => '', 'notes' => '', 'is_active' => 1];
$rows = q('SELECT s.*, (SELECT COUNT(*) FROM products p WHERE p.store_id=s.id) n FROM affiliate_stores s ORDER BY name')->fetchAll();
$title = 'Lojas e programas de afiliados'; require __DIR__ . '/_head.php'; ?>
<div class="cols2"><section class="box scroll"><table class="list"><thead><tr><th>Loja</th><th>Programa</th><th>Produtos</th><th>Ativa</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><a href="?id=<?= $r['id'] ?>"><b><?= e($r['name']) ?></b></a></td><td><?= e($r['program_code']) ?></td><td><?= (int)$r['n'] ?></td><td><?= $r['is_active'] ? 'Sim' : 'Não' ?></td>
<td class="acts"><form method="post" class="inline" data-confirm="Excluir esta loja? Os produtos ficam sem loja."><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="mini danger">Excluir</button></form></td></tr><?php endforeach; if (!$rows) echo '<tr><td colspan="5">Nenhuma loja cadastrada.</td></tr>'; ?></tbody></table></section>
<section class="box"><h2><?= $e ? 'Editar loja' : 'Nova loja' ?></h2><form method="post" enctype="multipart/form-data" class="formgrid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>">
<label>Nome da loja *<input name="name" required value="<?= e($d['name']) ?>"></label><label>Logotipo<input type="file" name="logo_file" accept="image/*"></label>
<label>URL oficial<input type="url" name="official_url" value="<?= e($d['official_url']) ?>"></label><label>URL de cadastro no programa de afiliados<input type="url" name="signup_url" value="<?= e($d['signup_url']) ?>"></label>
<label>Identificação interna do programa<input name="program_code" value="<?= e($d['program_code']) ?>"></label><label>Observações<textarea name="notes" rows="3"><?= e($d['notes']) ?></textarea></label>
<label class="chk"><input type="checkbox" name="is_active" value="1" <?= $d['is_active'] ? 'checked' : '' ?>> Ativa</label>
<div class="actionsbar"><button class="btn">💾 Salvar</button><?php if ($e): ?><a class="btn ghost" href="stores.php">Nova</a><?php endif; ?></div></form></section></div>
<?php require __DIR__ . '/_foot.php';
