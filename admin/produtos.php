<?php
/**
 * Loot Gamer - lista de produtos.
 */

declare(strict_types=1);

require __DIR__ . '/inc/layout.php';

if (xll_is_post()) {
    xll_require_csrf();
    $acao = adm_text('acao');
    $id = adm_text('id');

    if ($id !== '' && $acao === 'delete') {
        $stmt = xll_db()->prepare('DELETE FROM produtos WHERE id = ?');
        $stmt->execute([$id]);
        adm_redirect('produtos.php', 'Produto excluído.', 'success');
    }

    if ($id !== '' && $acao === 'duplicar') {
        $product = xll_product_by_id($id);
        if ($product) {
            $newId = bin2hex(random_bytes(12));
            $stmt = xll_db()->prepare(
                'INSERT INTO produtos (id, titulo, descricao, categoria, marketplace, selo, preco, preco_antigo, desconto, avaliacao, imagem, link_afiliado, secao_home, ordem, slug)
                 SELECT ?, CONCAT(titulo, \' (cópia)\'), descricao, categoria, marketplace, selo, preco, preco_antigo, desconto, avaliacao, imagem, link_afiliado, secao_home, ordem, ? FROM produtos WHERE id = ?'
            );
            $stmt->execute([$newId, $newId, $id]);
            adm_redirect('produto-form.php?id=' . urlencode($newId), 'Produto duplicado. Revise as informações.');
        }
        adm_redirect('produtos.php', 'Produto não encontrado.', 'error');
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$categoria = trim((string) ($_GET['categoria'] ?? ''));
$marketplace = trim((string) ($_GET['marketplace'] ?? ''));
$secao = trim((string) ($_GET['secao'] ?? ''));
$ordenar = trim((string) ($_GET['ordenar'] ?? 'padrao'));
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$filters = [
    'q'           => $q,
    'categoria'   => $categoria,
    'marketplace' => $marketplace,
    'secao'       => $secao,
    'ordenar'     => $ordenar,
];
$catalogo = xll_catalog($filters, 20, $pagina);
$queryBase = array_filter([
    'q'           => $q,
    'categoria'   => $categoria,
    'marketplace' => $marketplace,
    'secao'       => $secao,
    'ordenar'     => $ordenar !== 'padrao' ? $ordenar : '',
], static fn ($value) => $value !== '');

$pdo = xll_db();
$categorias = $pdo->query("SELECT DISTINCT categoria FROM produtos WHERE categoria <> '' ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
$marketplaces = $pdo->query("SELECT DISTINCT marketplace FROM produtos WHERE marketplace <> '' ORDER BY marketplace")->fetchAll(PDO::FETCH_COLUMN);
$secoes = $pdo->query("SELECT DISTINCT secao_home FROM produtos WHERE secao_home <> '' ORDER BY secao_home")->fetchAll(PDO::FETCH_COLUMN);

/**
 * Paginação do painel.
 */
function adm_paginate(int $page, int $pages, array $params): string
{
    if ($pages <= 1) {
        return '';
    }
    $html = '<div class="adm-actions" style="justify-content:center;margin-top:18px">';
    $start = max(1, $page - 2);
    $end = min($pages, $page + 2);
    if ($page > 1) {
        $html .= '<a class="adm-btn adm-btn-secondary adm-btn-sm" href="' . e(xll_url('admin/produtos.php', array_merge($params, ['pagina' => $page - 1]))) . '">‹ Anterior</a>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $class = $i === $page ? 'adm-btn adm-btn-sm' : 'adm-btn adm-btn-secondary adm-btn-sm';
        $html .= '<a class="' . $class . '" href="' . e(xll_url('admin/produtos.php', array_merge($params, ['pagina' => $i]))) . '">' . $i . '</a>';
    }
    if ($page < $pages) {
        $html .= '<a class="adm-btn adm-btn-secondary adm-btn-sm" href="' . e(xll_url('admin/produtos.php', array_merge($params, ['pagina' => $page + 1]))) . '">Próxima ›</a>';
    }
    $html .= '</div>';

    return $html;
}

adm_header('Produtos', 'produtos');
?>

<div class="adm-card">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('box') ?> Catálogo de produtos</div>
            <div class="adm-card-sub"><?= (int) $catalogo['total'] ?> produtos cadastrados. A tabela do site é atualizada imediatamente ao salvar.</div>
        </div>
        <div class="adm-actions">
            <a class="adm-btn" href="<?= e(xll_url('admin/produto-form.php')) ?>"><?= adm_icon('plus') ?> Adicionar produto</a>
            <a class="adm-btn adm-btn-secondary" href="<?= e(xll_url('admin/importar.php')) ?>"><?= adm_icon('import') ?> Importar / Exportar</a>
        </div>
    </div>

    <form method="get" action="<?= e(xll_url('admin/produtos.php')) ?>" class="adm-grid adm-grid-4" style="margin-bottom:14px">
        <div class="adm-field">
            <label class="adm-label">Buscar</label>
            <input class="adm-input" type="search" name="q" value="<?= e($q) ?>" placeholder="Título, descrição...">
        </div>
        <div class="adm-field">
            <label class="adm-label">Categoria</label>
            <select class="adm-select" name="categoria">
                <option value="">Todas</option>
                <?php foreach ($categorias as $value): ?>
                    <option value="<?= e($value) ?>" <?= $value === $categoria ? 'selected' : '' ?>><?= e($value) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="adm-field">
            <label class="adm-label">Marketplace</label>
            <select class="adm-select" name="marketplace">
                <option value="">Todos</option>
                <?php foreach ($marketplaces as $value): ?>
                    <option value="<?= e($value) ?>" <?= $value === $marketplace ? 'selected' : '' ?>><?= e($value) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="adm-field">
            <label class="adm-label">Seção da home</label>
            <select class="adm-select" name="secao">
                <option value="">Todas</option>
                <?php foreach ($secoes as $value): ?>
                    <option value="<?= e($value) ?>" <?= $value === $secao ? 'selected' : '' ?>><?= e($value) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="adm-field">
            <label class="adm-label">Ordenar</label>
            <select class="adm-select" name="ordenar">
                <option value="padrao" <?= $ordenar === 'padrao' ? 'selected' : '' ?>>Ordem do catálogo</option>
                <option value="menor-preco" <?= $ordenar === 'menor-preco' ? 'selected' : '' ?>>Menor preço</option>
                <option value="maior-preco" <?= $ordenar === 'maior-preco' ? 'selected' : '' ?>>Maior preço</option>
                <option value="maior-desconto" <?= $ordenar === 'maior-desconto' ? 'selected' : '' ?>>Maior desconto</option>
                <option value="avaliacao" <?= $ordenar === 'avaliacao' ? 'selected' : '' ?>>Melhor avaliação</option>
                <option value="nome" <?= $ordenar === 'nome' ? 'selected' : '' ?>>Nome (A-Z)</option>
            </select>
        </div>
        <div class="adm-field" style="justify-content:flex-end">
            <button type="submit" class="adm-btn">Filtrar</button>
        </div>
        <div class="adm-field" style="justify-content:flex-end">
            <a class="adm-btn adm-btn-secondary" href="<?= e(xll_url('admin/produtos.php')) ?>">Limpar</a>
        </div>
    </form>

    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Marketplace</th>
                    <th>Selo</th>
                    <th>Preço</th>
                    <th>Desc.</th>
                    <th>Seção</th>
                    <th>Ordem</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($catalogo['produtos'] as $product): ?>
                    <tr>
                        <td><img class="thumb" src="<?= e(xll_media_url($product['imagem'])) ?>" alt="" loading="lazy"></td>
                        <td class="cell-title">
                            <strong><?= e($product['titulo']) ?></strong>
                            <small><?= e($product['id']) ?></small>
                        </td>
                        <td><?= e($product['categoria']) ?></td>
                        <td><?= e($product['marketplace']) ?></td>
                        <td>
                            <?php if ($product['selo'] !== ''): ?>
                                <span class="adm-badge"><?= e($product['selo']) ?></span>
                            <?php else: ?>
                                <span class="adm-badge adm-badge-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="price"><?= e(xll_format_price($product['preco'])) ?></td>
                        <td><?= (int) $product['desconto'] > 0 ? '<span class="adm-badge adm-badge-orange">-' . (int) $product['desconto'] . '%</span>' : '—' ?></td>
                        <td><span class="adm-badge adm-badge-green"><?= e($product['secao_home'] !== '' ? $product['secao_home'] : '—') ?></span></td>
                        <td><?= (int) $product['ordem'] ?></td>
                        <td>
                            <div class="adm-row-actions">
                                <a class="adm-btn adm-btn-secondary adm-btn-sm" href="<?= e(xll_url('admin/produto-form.php', ['id' => $product['id']])) ?>">Editar</a>
                                <form method="post" action="<?= e(xll_url('admin/produtos.php')) ?>" style="display:inline" data-confirm="Excluir o produto &quot;<?= e($product['titulo']) ?>&quot;? Essa ação não pode ser desfeita.">
                                    <?= xll_csrf_field() ?>
                                    <input type="hidden" name="acao" value="delete">
                                    <input type="hidden" name="id" value="<?= e($product['id']) ?>">
                                    <button type="submit" class="adm-btn adm-btn-danger adm-btn-sm">Excluir</button>
                                </form>
                                <form method="post" action="<?= e(xll_url('admin/produtos.php')) ?>" style="display:inline">
                                    <?= xll_csrf_field() ?>
                                    <input type="hidden" name="acao" value="duplicar">
                                    <input type="hidden" name="id" value="<?= e($product['id']) ?>">
                                    <button type="submit" class="adm-btn adm-btn-secondary adm-btn-sm">Duplicar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$catalogo['produtos']): ?>
                    <tr><td colspan="10" class="adm-empty">Nenhum produto encontrado com esses filtros.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?= adm_paginate($catalogo['pagina'], $catalogo['paginas'], $queryBase) ?>
</div>

<?php adm_footer(); ?>
