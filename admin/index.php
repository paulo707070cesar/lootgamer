<?php
/**
 * Loot Gamer - painel (dashboard).
 */

declare(strict_types=1);

require __DIR__ . '/inc/layout.php';

$stats = xll_stats();
$pdo = xll_db();
$dbVersion = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
$sections = $pdo->query("SELECT secao_home, COUNT(*) AS total FROM produtos GROUP BY secao_home ORDER BY total DESC")->fetchAll();
$recent = $pdo->query('SELECT * FROM produtos ORDER BY atualizado_em DESC, ordem ASC LIMIT 6')->fetchAll();
$history = xll_import_history(4);
$totalCategorias = $stats['categorias'];

adm_header('Painel', 'dashboard');
?>

<div class="adm-stats">
    <div class="adm-stat">
        <div class="adm-stat-label">Produtos</div>
        <div class="adm-stat-value"><?= (int) $stats['produtos'] ?></div>
        <div class="adm-stat-sub"><?= (int) $stats['com_desconto'] ?> com desconto ativo</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Categorias</div>
        <div class="adm-stat-value"><?= (int) $totalCategorias ?></div>
        <div class="adm-stat-sub"><?= (int) $stats['marketplaces'] ?> marketplaces integrados</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Desconto médio</div>
        <div class="adm-stat-value"><?= (int) round((float) $stats['desconto_medio']) ?><small>%</small></div>
        <div class="adm-stat-sub">entre os produtos com oferta</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Banco de dados</div>
        <div class="adm-stat-value" style="font-size:16px;line-height:1.4">MySQL conectado</div>
        <div class="adm-stat-sub"><?= e($dbVersion) ?></div>
    </div>
</div>

<div class="adm-grid" style="grid-template-columns:1.35fr 0.65fr">
    <div class="adm-card">
        <div class="adm-card-head">
            <div>
                <div class="adm-card-title">Ações rápidas</div>
                <div class="adm-card-sub">Tudo o que você precisa para atualizar o site.</div>
            </div>
        </div>
        <div class="adm-actions">
            <a class="adm-btn" href="<?= e(xll_url('admin/produto-form.php')) ?>"><?= adm_icon('plus') ?> Adicionar produto</a>
            <a class="adm-btn adm-btn-secondary" href="<?= e(xll_url('admin/home.php')) ?>"><?= adm_icon('home') ?> Personalizar home</a>
            <a class="adm-btn adm-btn-secondary" href="<?= e(xll_url('admin/importar.php')) ?>"><?= adm_icon('import') ?> Importar CSV</a>
            <a class="adm-btn adm-btn-secondary" href="<?= e(xll_url('admin/aparencia.php')) ?>"><?= adm_icon('palette') ?> Cores e fontes</a>
        </div>

        <div style="margin-top:22px">
            <div class="adm-label" style="margin-bottom:10px">Produtos por seção da home</div>
            <div class="adm-actions" style="gap:8px">
                <?php foreach ($sections as $section): ?>
                    <?php if ((string) $section['secao_home'] === '') { continue; } ?>
                    <span class="adm-badge adm-badge-green"><?= e($section['secao_home']) ?> · <?= (int) $section['total'] ?></span>
                <?php endforeach; ?>
                <?php if (!$sections): ?>
                    <span class="adm-help">Nenhum produto cadastrado ainda.</span>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top:22px">
            <div class="adm-label" style="margin-bottom:10px">Últimos produtos atualizados</div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th></th><th>Produto</th><th>Preço</th><th>Seção</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $product): ?>
                            <?php $p = xll_normalize_product($product); ?>
                            <tr>
                                <td><img class="thumb" src="<?= e(xll_media_url($p['imagem'])) ?>" alt="" loading="lazy"></td>
                                <td class="cell-title"><strong><?= e($p['titulo']) ?></strong><small><?= e($p['categoria']) ?></small></td>
                                <td class="price"><?= e(xll_format_price($p['preco'])) ?></td>
                                <td><span class="adm-badge adm-badge-muted"><?= e($p['secao_home'] !== '' ? $p['secao_home'] : '—') ?></span></td>
                                <td><a class="adm-btn adm-btn-secondary adm-btn-sm" href="<?= e(xll_url('admin/produto-form.php', ['id' => $p['id']])) ?>">Editar</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$recent): ?>
                            <tr><td colspan="5" class="adm-empty">Nenhum produto cadastrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:20px">
        <?php $clickStats = xll_click_stats(); $unreadMessages = xll_unread_messages(); ?>
        <div class="adm-card">
            <div class="adm-card-head">
                <div><div class="adm-card-title">Engajamento</div></div>
                <a class="adm-btn adm-btn-secondary adm-btn-sm" href="<?= e(xll_url('admin/analytics.php')) ?>">Analytics</a>
            </div>
            <dl class="adm-kv">
                <dt>Cliques hoje</dt><dd><?= (int) $clickStats['hoje'] ?></dd>
                <dt>Cliques (7 dias)</dt><dd><?= (int) $clickStats['ultimos7'] ?></dd>
                <dt>Cliques totais</dt><dd><?= (int) $clickStats['total'] ?></dd>
                <dt>Mensagens novas</dt><dd>
                    <a href="<?= e(xll_url('admin/mensagens.php', ['filtro' => 'nao-lidas'])) ?>" style="color:<?= $unreadMessages > 0 ? '#00e676' : 'inherit' ?>">
                        <?= (int) $unreadMessages ?> não lida(s)
                    </a>
                </dd>
            </dl>
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <div><div class="adm-card-title">Status do site</div></div>
            </div>
            <dl class="adm-kv">
                <dt>Site</dt><dd><?= e((string) xll_setting('site_name', 'Loot Gamer')) ?></dd>
                <dt>Instalado em</dt><dd><?= e((string) xll_setting('instalado_em', '—')) ?></dd>
                <dt>PHP</dt><dd><?= e(PHP_VERSION) ?></dd>
                <dt>MySQL</dt><dd><?= e($dbVersion) ?></dd>
                <dt>Banco</dt><dd><?= e(xll_db_config()['database']) ?></dd>
                <dt>Uploads</dt><dd><?= xll_dir_writable(XLL_UPLOAD_DIR) ? 'gravável' : '<span style="color:#ff9b73">sem permissão</span>' ?></dd>
            </dl>
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <div><div class="adm-card-title">Últimas importações</div></div>
                <a class="adm-btn adm-btn-secondary adm-btn-sm" href="<?= e(xll_url('admin/importar.php')) ?>">Abrir</a>
            </div>
            <?php if ($history): ?>
                <?php foreach ($history as $item): ?>
                    <div style="padding:9px 0;border-bottom:1px solid rgba(255,255,255,.05)">
                        <div style="font-size:12.5px;color:#fff"><?= e($item['arquivo']) ?></div>
                        <div class="adm-help">
                            <?= e(date('d/m/Y H:i', strtotime((string) $item['criado_em']))) ?> ·
                            <?= e($item['modo'] === 'replace' ? 'substituição' : 'mescla') ?> ·
                            <?= (int) $item['inseridos'] ?> novos, <?= (int) $item['atualizados'] ?> atualizados
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="adm-help">Nenhuma importação realizada ainda. O catálogo inicial veio do CSV de exemplo.</p>
            <?php endif; ?>
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <div><div class="adm-card-title">Dicas</div></div>
            </div>
            <ul class="adm-help" style="margin:0;padding-left:18px;line-height:1.8">
                <li>Alterou produtos no CSV? Use <strong>Importar / Exportar</strong> para atualizar a tabela.</li>
                <li>A home é montada automaticamente com os produtos que têm <strong>Seção da home</strong> preenchida.</li>
                <li>Personalize banners, cards, ícones, cores e fontes em <strong>Página inicial</strong> e <strong>Aparência</strong>.</li>
                <li>Troque sua senha em <strong>Minha conta</strong>.</li>
            </ul>
        </div>
    </div>
</div>

<?php adm_footer(); ?>
