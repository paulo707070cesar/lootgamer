<?php
$title = 'Dashboard'; require __DIR__ . '/_head.php';
$n = fn($sql, $p = []) => (int)q($sql, $p)->fetchColumn();
$k = [
 ['Produtos cadastrados', $n('SELECT COUNT(*) FROM products')], ['Publicados', $n("SELECT COUNT(*) FROM products WHERE status='publicado'")],
 ['Inativos', $n("SELECT COUNT(*) FROM products WHERE status='inativo'")], ['Rascunhos', $n("SELECT COUNT(*) FROM products WHERE status='rascunho'")],
 ['Categorias', $n('SELECT COUNT(*) FROM categories')], ['Banners ativos', $n("SELECT COUNT(*) FROM banners WHERE is_active=1 AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW())")],
 ['Cliques (total)', $n('SELECT COUNT(*) FROM clicks')], ['Cliques (7 dias)', $n('SELECT COUNT(*) FROM clicks WHERE clicked_at > (NOW() - INTERVAL 7 DAY)')],
 ['Mensagens não respondidas', $n('SELECT COUNT(*) FROM contact_messages WHERE is_answered=0')], ['Inscritos newsletter', $n('SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active=1')],
];
$top = q('SELECT p.id,p.name,COUNT(k.id) c FROM clicks k JOIN products p ON p.id=k.product_id GROUP BY p.id ORDER BY c DESC LIMIT 8')->fetchAll();
$byStore = q('SELECT COALESCE(s.name,"Sem loja") name, COUNT(k.id) c FROM clicks k LEFT JOIN affiliate_stores s ON s.id=k.store_id GROUP BY s.id ORDER BY c DESC LIMIT 8')->fetchAll();
$days = q('SELECT DATE(clicked_at) d, COUNT(*) c FROM clicks WHERE clicked_at > (NOW() - INTERVAL 14 DAY) GROUP BY d ORDER BY d')->fetchAll(PDO::FETCH_KEY_PAIR);
$series = []; for ($i = 13; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i day")); $series[$d] = (int)($days[$d] ?? 0); }
$mx = max(1, max($series));
$recent = q('SELECT id,name,status,created_at FROM products ORDER BY id DESC LIMIT 6')->fetchAll();
?>
<div class="kpis"><?php foreach ($k as [$l, $v]): ?><div class="kpi"><b><?= $v ?></b><span><?= e($l) ?></span></div><?php endforeach; ?></div>
<div class="cols">
<section class="box"><h2>Cliques por dia (14 dias)</h2><div class="bars"><?php foreach ($series as $d => $c): ?><div title="<?= e($d) ?>: <?= $c ?>"><i style="height:<?= round($c / $mx * 100) ?>%"></i><small><?= date('d', strtotime($d)) ?></small></div><?php endforeach; ?></div></section>
<section class="box"><h2>Produtos mais clicados</h2><table><?php foreach ($top as $r): ?><tr><td><a href="product-edit.php?id=<?= $r['id'] ?>"><?= e($r['name']) ?></a></td><td class="r"><?= $r['c'] ?></td></tr><?php endforeach; if (!$top) echo '<tr><td>Sem cliques ainda.</td></tr>'; ?></table></section>
<section class="box"><h2>Cliques por loja</h2><table><?php foreach ($byStore as $r): ?><tr><td><?= e($r['name']) ?></td><td class="r"><?= $r['c'] ?></td></tr><?php endforeach; if (!$byStore) echo '<tr><td>Sem cliques ainda.</td></tr>'; ?></table></section>
<section class="box"><h2>Cadastrados recentemente</h2><table><?php foreach ($recent as $r): ?><tr><td><a href="product-edit.php?id=<?= $r['id'] ?>"><?= e($r['name']) ?></a></td><td><span class="st st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td></tr><?php endforeach; ?></table></section>
</div>
<?php require __DIR__ . '/_foot.php';
