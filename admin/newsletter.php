<?php
require_once __DIR__ . '/../includes/auth.php'; $me = require_admin();
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="inscritos.csv"');
    $o = fopen('php://output', 'w'); fputcsv($o, ['email', 'consentimento_em', 'origem', 'ativo']);
    foreach (q('SELECT email,consent_at,consent_source,is_active FROM newsletter_subscribers ORDER BY id DESC')->fetchAll() as $r) fputcsv($o, [ltrim($r['email'], "=+-@\t\r"), $r['consent_at'], $r['consent_source'], $r['is_active']]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); q('DELETE FROM newsletter_subscribers WHERE id=?', [(int)$_POST['id']]); flash('ok', 'Inscrito removido.'); redirect('newsletter.php'); }
$rows = q('SELECT * FROM newsletter_subscribers ORDER BY id DESC LIMIT 500')->fetchAll();
$title = 'Newsletter'; $actions = '<a class="btn" href="?export=1">Exportar CSV</a>'; require __DIR__ . '/_head.php'; ?>
<section class="box scroll"><table class="list"><thead><tr><th>E-mail</th><th>Consentimento</th><th>Origem</th><th>Ativo</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['email']) ?></td><td><?= e($r['consent_at']) ?></td><td><?= e($r['consent_source']) ?></td><td><?= $r['is_active'] ? 'Sim' : 'Cancelado' ?></td>
<td><form method="post" class="inline" data-confirm="Remover inscrito?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="mini danger">Remover</button></form></td></tr><?php endforeach; ?></tbody></table></section>
<?php require __DIR__ . '/_foot.php';
