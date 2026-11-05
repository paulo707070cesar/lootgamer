<?php
$title = 'Logs administrativos'; require __DIR__ . '/_head.php';
$rows = q('SELECT l.*, u.name FROM admin_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 200')->fetchAll(); ?>
<section class="box scroll"><table class="list"><thead><tr><th>Data</th><th>Usuário</th><th>Ação</th><th>Detalhes</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['created_at']) ?></td><td><?= e($r['name']) ?></td><td><?= e($r['action']) ?></td><td><?= e($r['details']) ?></td></tr><?php endforeach; ?></tbody></table></section>
<?php require __DIR__ . '/_foot.php';
