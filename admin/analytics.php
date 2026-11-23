<?php
/**
 * Loot Gamer - analytics de cliques nos links de afiliado.
 */

declare(strict_types=1);

require __DIR__ . '/inc/layout.php';

$stats = xll_click_stats();
$daily = xll_clicks_daily(14);
$topProducts = xll_clicks_top_products(10);
$topMarketplaces = xll_clicks_top_marketplaces(6);
$recent = xll_clicks_recent(20);
$maxDaily = max(1, max(array_column($daily, 'total')));
$unread = xll_unread_messages();

adm_header('Analytics de cliques', 'analytics');
?>

<div class="adm-stats">
    <div class="adm-stat">
        <div class="adm-stat-label">Cliques totais</div>
        <div class="adm-stat-value"><?= (int) $stats['total'] ?></div>
        <div class="adm-stat-sub">desde a instalação</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Hoje</div>
        <div class="adm-stat-value"><?= (int) $stats['hoje'] ?></div>
        <div class="adm-stat-sub">cliques nas últimas 24h</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Últimos 7 dias</div>
        <div class="adm-stat-value"><?= (int) $stats['ultimos7'] ?></div>
        <div class="adm-stat-sub"><?= (int) $stats['produtos'] ?> produtos com cliques</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Mensagens</div>
        <div class="adm-stat-value"><?= (int) $unread ?></div>
        <div class="adm-stat-sub"><a href="<?= e(xll_url('admin/mensagens.php')) ?>" style="color:#00e676">não lidas — abrir caixa</a></div>
    </div>
</div>

<div class="adm-card">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('chart') ?> Cliques por dia (14 dias)</div>
            <div class="adm-card-sub">Cada clique no botão COMPRAR passa por <code>/ir/{id}</code>, é registrado e redireciona para a loja parceira.</div>
        </div>
        <a class="adm-btn adm-btn-secondary adm-btn-sm" href="<?= e(xll_url('index.php')) ?>" target="_blank" rel="noopener"><?= adm_icon('eye') ?> Ver site</a>
    </div>
    <div class="adm-chart" role="img" aria-label="Gráfico de cliques por dia">
        <?php foreach ($daily as $day): ?>
            <?php $height = max(3, (int) round(($day['total'] / $maxDaily) * 120)); ?>
            <div class="adm-chart-col" title="<?= e($day['data']) ?>: <?= (int) $day['total'] ?> clique(s)">
                <span class="adm-chart-value"><?= (int) $day['total'] ?></span>
                <span class="adm-chart-bar" style="height:<?= $height ?>px"></span>
                <span class="adm-chart-label"><?= e(date('d/m', strtotime($day['data']))) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="adm-grid" style="align-items:start">
    <div class="adm-card">
        <div class="adm-card-head">
            <div>
                <div class="adm-card-title">Produtos mais clicados</div>
                <div class="adm-card-sub">Top 10 por cliques no link de afiliado.</div>
            </div>
        </div>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead><tr><th></th><th>Produto</th><th>Marketplace</th><th>Cliques</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($topProducts as $row): ?>
                        <tr>
                            <td><img class="thumb" src="<?= e(xll_media_url((string) ($row['imagem'] ?? ''))) ?>" alt="" loading="lazy"></td>
                            <td class="cell-title"><strong><?= e($row['titulo'] ?? '(produto removido)') ?></strong><small><?= e($row['produto_id']) ?></small></td>
                            <td><?= e($row['marketplace'] ?? '—') ?></td>
                            <td><span class="adm-badge adm-badge-green"><?= (int) $row['total'] ?></span></td>
                            <td><a class="adm-btn adm-btn-secondary adm-btn-sm" href="<?= e(xll_url('admin/produto-form.php', ['id' => $row['produto_id']])) ?>">Editar</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$topProducts): ?>
                        <tr><td colspan="5" class="adm-empty">Nenhum clique registrado ainda. Os cliques aparecem aqui assim que os visitantes usarem os botões COMPRAR.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:20px">
        <div class="adm-card">
            <div class="adm-card-head">
                <div><div class="adm-card-title">Por marketplace</div></div>
            </div>
            <?php if ($topMarketplaces): ?>
                <?php foreach ($topMarketplaces as $row): ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.05)">
                        <span style="flex:1"><?= e($row['marketplace']) ?></span>
                        <span class="adm-badge adm-badge-green"><?= (int) $row['total'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="adm-help">Sem dados por enquanto.</p>
            <?php endif; ?>
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <div><div class="adm-card-title">Configuração</div></div>
            </div>
            <ul class="adm-help" style="margin:0;padding-left:18px;line-height:1.8">
                <li>O rastreamento pode ser desligado em <a href="<?= e(xll_url('admin/aparencia.php')) ?>" style="color:#00e676">Aparência → SEO e privacidade</a>.</li>
                <li>Guardamos apenas um hash do IP (sem IP completo) e o marketplace do clique.</li>
                <li>Para afiliados, use os links completos no campo <strong>Link de afiliado</strong> do produto.</li>
            </ul>
        </div>
    </div>
</div>

<div class="adm-card">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title">Cliques recentes</div>
            <div class="adm-card-sub">Últimos 20 registros.</div>
        </div>
    </div>
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead><tr><th>Data</th><th>Produto</th><th>Marketplace</th><th>Origem</th></tr></thead>
            <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td style="white-space:nowrap"><?= e(date('d/m/Y H:i', strtotime((string) $row['criado_em']))) ?></td>
                        <td class="cell-title"><strong><?= e($row['titulo'] ?? '(produto removido)') ?></strong><small><?= e($row['produto_id']) ?></small></td>
                        <td><?= e($row['marketplace'] ?: '—') ?></td>
                        <td style="max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($row['referer'] ?: 'direto') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?>
                    <tr><td colspan="4" class="adm-empty">Nenhum clique registrado ainda.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php adm_footer(); ?>
