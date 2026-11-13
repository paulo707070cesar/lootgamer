<?php
/**
 * Listagem com filtros/ordenação/paginação.
 * Espera: $listTitle, $baseWhere (string), $baseParams (array), $listUrl, $defaultOrder, $breadcrumb(array), $intro(optional)
 */
$perPage = 15;
$page = max(1, (int)($_GET['p'] ?? 1));
$where = $baseWhere; $params = $baseParams;
$min = parse_money($_GET['min'] ?? ''); $max = parse_money($_GET['max'] ?? '');
$store = (int)($_GET['loja'] ?? 0);
if ($min !== null) { $where .= ($where ? ' AND ' : '') . 'p.price >= ?'; $params[] = $min; }
if ($max !== null) { $where .= ($where ? ' AND ' : '') . 'p.price <= ?'; $params[] = $max; }
if ($store) { $where .= ($where ? ' AND ' : '') . 'p.store_id = ?'; $params[] = $store; }
$orders = [
    'novos' => 'p.created_at DESC, p.id DESC',
    'menor' => 'p.price IS NULL, p.price ASC',
    'maior' => 'p.price DESC',
    'popular' => '(SELECT COUNT(*) FROM clicks k WHERE k.product_id = p.id) DESC, p.id DESC',
    'padrao' => $defaultOrder,
];
$ord = $_GET['ordem'] ?? 'padrao'; if (!isset($orders[$ord])) $ord = 'padrao';
$total = (int)q("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.status='publicado'" . ($where ? " AND $where" : ''), $params)->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage)); if ($page > $pages) $page = $pages;
$items = products_where($where, $params, $orders[$ord], $perPage, ($page - 1) * $perPage);
$storesList = q("SELECT DISTINCT s.id, s.name FROM affiliate_stores s JOIN products p ON p.store_id = s.id WHERE p.status='publicado' AND s.is_active = 1 ORDER BY s.name")->fetchAll();
include ROOT . '/includes/header.php';
$qs = function (array $over = []) { return http_build_query(array_filter(array_merge($_GET, $over), fn($v) => $v !== '' && $v !== null)); };
?>
<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= url() ?>">Início</a><?php foreach ($breadcrumb as $b): ?> › <?= $b[1] ? '<a href="' . e($b[1]) . '">' . e($b[0]) . '</a>' : '<span>' . e($b[0]) . '</span>' ?><?php endforeach; ?></nav>
  <div class="sec-head"><h1><?= e($listTitle) ?></h1><span class="count"><?= $total ?> produto<?= $total === 1 ? '' : 's' ?></span></div>
  <?php if (!empty($intro)): ?><p class="intro"><?= e($intro) ?></p><?php endif; ?>
  <form class="filters" method="get" id="filters">
    <?php foreach ($_GET as $k => $v) if (in_array($k, ['q', 'slug'], true)) echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">'; ?>
    <label>Ordenar<select name="ordem" data-auto><option value="padrao">Relevância</option><option value="novos" <?= $ord === 'novos' ? 'selected' : '' ?>>Novidades</option><option value="popular" <?= $ord === 'popular' ? 'selected' : '' ?>>Popularidade</option><option value="menor" <?= $ord === 'menor' ? 'selected' : '' ?>>Menor preço</option><option value="maior" <?= $ord === 'maior' ? 'selected' : '' ?>>Maior preço</option></select></label>
    <label>Loja<select name="loja" data-auto><option value="">Todas</option><?php foreach ($storesList as $s): ?><option value="<?= $s['id'] ?>" <?= $store === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></label>
    <label>Preço mín.<input name="min" inputmode="decimal" value="<?= e($_GET['min'] ?? '') ?>" placeholder="0,00"></label>
    <label>Preço máx.<input name="max" inputmode="decimal" value="<?= e($_GET['max'] ?? '') ?>" placeholder="9999,00"></label>
    <button class="btn-offer sm" type="submit">Filtrar</button>
  </form>
  <?php if ($items): ?>
    <div class="grid"><?php foreach ($items as $p) include ROOT . '/includes/card.php'; ?></div>
    <?php if ($pages > 1): ?><nav class="pager" aria-label="Paginação">
      <?php if ($page > 1): ?><a rel="prev" href="?<?= e($qs(['p' => $page - 1])) ?>">‹ Anterior</a><?php endif; ?>
      <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?><a class="<?= $i === $page ? 'on' : '' ?>" href="?<?= e($qs(['p' => $i])) ?>"><?= $i ?></a><?php endfor; ?>
      <?php if ($page < $pages): ?><a rel="next" href="?<?= e($qs(['p' => $page + 1])) ?>">Próxima ›</a><?php endif; ?>
    </nav><?php endif; ?>
  <?php else: ?><p class="empty">Nenhum produto encontrado.</p><?php endif; ?>
</div>
<?php include ROOT . '/includes/footer.php';
