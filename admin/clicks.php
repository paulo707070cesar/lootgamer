<?php
$title = 'Cliques nos links de afiliados'; require __DIR__ . '/_head.php';
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-d', strtotime('-30 day'));
$to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
$pr = [$from . ' 00:00:00', $to . ' 23:59:59'];
$tot = (int)q('SELECT COUNT(*) FROM clicks WHERE clicked_at BETWEEN ? AND ?', $pr)->fetchColumn();
$byP = q('SELECT p.id,p.name,COUNT(*) c FROM clicks k JOIN products p ON p.id=k.product_id WHERE k.clicked_at BETWEEN ? AND ? GROUP BY p.id ORDER BY c DESC LIMIT 30', $pr)->fetchAll();
$byS = q('SELECT COALESCE(s.name,"Sem loja") name,COUNT(*) c FROM clicks k LEFT JOIN affiliate_stores s ON s.id=k.store_id WHERE k.clicked_at BETWEEN ? AND ? GROUP BY s.id ORDER BY c DESC', $pr)->fetchAll();
$byD = q('SELECT DATE(clicked_at) d,COUNT(*) c FROM clicks WHERE clicked_at BETWEEN ? AND ? GROUP BY d ORDER BY d DESC LIMIT 60', $pr)->fetchAll();
?>
<form class="bar" method="get">De <input type="date" name="from" value="<?= e($from) ?>"> até <input type="date" name="to" value="<?= e($to) ?>"><button class="btn">Aplicar</button><b class="tot">Total no período: <?= $tot ?></b></form>
<div class="cols"><section class="box"><h2>Por produto</h2><table><?php foreach ($byP as $r): ?><tr><td><?= e($r['name']) ?></td><td class="r"><?= $r['c'] ?></td></tr><?php endforeach; if (!$byP) echo '<tr><td>Sem dados.</td></tr>'; ?></table></section>
<section class="box"><h2>Por loja</h2><table><?php foreach ($byS as $r): ?><tr><td><?= e($r['name']) ?></td><td class="r"><?= $r['c'] ?></td></tr><?php endforeach; if (!$byS) echo '<tr><td>Sem dados.</td></tr>'; ?></table></section>
<section class="box"><h2>Por dia</h2><table><?php foreach ($byD as $r): ?><tr><td><?= e(date('d/m/Y', strtotime($r['d']))) ?></td><td class="r"><?= $r['c'] ?></td></tr><?php endforeach; if (!$byD) echo '<tr><td>Sem dados.</td></tr>'; ?></table></section></div>
<?php require __DIR__ . '/_foot.php';
