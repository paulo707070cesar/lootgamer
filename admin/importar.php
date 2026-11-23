<?php
/**
 * Loot Gamer - importar / exportar a tabela de produtos (CSV).
 */

declare(strict_types=1);

require __DIR__ . '/inc/layout.php';

$preview = null;
$importError = '';

/* ---------------------------------------------------------------------- */
/* Exportações (download)                                                  */
/* ---------------------------------------------------------------------- */
$export = (string) ($_GET['export'] ?? '');
if ($export !== '') {
    $stamp = date('Ymd-His');
    if ($export === 'csv') {
        xll_send_download('produtos-' . $stamp . '.csv', xll_export_csv());
    }
    if ($export === 'json') {
        xll_send_download('produtos-' . $stamp . '.json', xll_export_json(), 'application/json; charset=UTF-8');
    }
    if ($export === 'modelo') {
        xll_send_download('modelo-produtos.csv', xll_csv_model());
    }
    xll_redirect('admin/importar.php');
}

/* ---------------------------------------------------------------------- */
/* Importação                                                              */
/* ---------------------------------------------------------------------- */
if (xll_is_post()) {
    xll_require_csrf();
    $acao = adm_text('acao');

    if ($acao === 'analisar') {
        if (!isset($_FILES['csv']) || !is_array($_FILES['csv'])) {
            $importError = 'Selecione um arquivo CSV.';
        } else {
            try {
                $path = xll_save_uploaded_csv($_FILES['csv']);
                $preview = xll_import_preview($path);
                $preview['path'] = $path;
                $preview['name'] = (string) ($_FILES['csv']['name'] ?? basename($path));
                $_SESSION['_xll_import'] = ['path' => $path, 'name' => $preview['name']];
            } catch (Throwable $error) {
                $importError = $error->getMessage();
            }
        }
    }

    if ($acao === 'confirmar') {
        $saved = $_SESSION['_xll_import'] ?? null;
        $path = is_array($saved) ? (string) ($saved['path'] ?? '') : '';
        $real = realpath($path);
        $allowed = realpath(XLL_ROOT . '/data/imports');
        if (!$real || !$allowed || !str_starts_with($real, $allowed) || !is_file($real)) {
            adm_redirect('importar.php', 'Nenhum arquivo analisado. Envie o CSV novamente.', 'error');
        }
        if (empty($_POST['confirmar'])) {
            adm_redirect('importar.php', 'Marque a caixa de confirmação para importar.', 'error');
        }
        $mode = adm_text('modo') === 'replace' ? 'replace' : 'merge';

        try {
            if ($mode === 'replace') {
                if (!is_dir(XLL_BACKUP_DIR)) {
                    @mkdir(XLL_BACKUP_DIR, 0775, true);
                }
                $backupName = 'produtos-backup-' . date('Ymd-His') . '.csv';
                @file_put_contents(XLL_BACKUP_DIR . '/' . $backupName, xll_export_csv());
            }
            $result = xll_import_csv($real, $mode, (string) ($saved['name'] ?? basename($real)));
            unset($_SESSION['_xll_import']);
            $message = 'Importação concluída: ' . $result['inseridos'] . ' inseridos, '
                . $result['atualizados'] . ' atualizados'
                . ($result['removidos'] > 0 ? ', ' . $result['removidos'] . ' removidos' : '')
                . (count($result['erros']) > 0 ? '. ' . count($result['erros']) . ' linha(s) com aviso.' : '.');
            adm_redirect('importar.php', $message, 'success');
        } catch (Throwable $error) {
            $importError = 'Falha na importação: ' . $error->getMessage();
        }
    }

    if ($acao === 'cancelar') {
        unset($_SESSION['_xll_import']);
        adm_redirect('importar.php', 'Importação cancelada.', 'warning');
    }
}

if ($preview === null && isset($_SESSION['_xll_import'])) {
    $saved = $_SESSION['_xll_import'];
    $path = is_array($saved) ? (string) ($saved['path'] ?? '') : '';
    if ($path !== '' && is_file($path)) {
        try {
            $preview = xll_import_preview($path);
            $preview['path'] = $path;
            $preview['name'] = (string) ($saved['name'] ?? basename($path));
        } catch (Throwable $error) {
            $importError = $error->getMessage();
        }
    }
}

$history = xll_import_history(10);
$totalProducts = (int) xll_db()->query('SELECT COUNT(*) FROM produtos')->fetchColumn();

adm_header('Importar / Exportar', 'importar');
?>

<?php if ($importError !== ''): ?>
    <div class="adm-flash adm-flash-error"><?= e($importError) ?></div>
<?php endif; ?>

<div class="adm-grid" style="grid-template-columns:1.1fr 0.9fr;align-items:start">
    <div class="adm-card">
        <div class="adm-card-head">
            <div>
                <div class="adm-card-title"><?= adm_icon('import') ?> Importar CSV</div>
                <div class="adm-card-sub">A tabela é a mesma do site: <strong><?= $totalProducts ?></strong> produtos atualmente. Envie o CSV com as colunas abaixo.</div>
            </div>
        </div>

        <form method="post" action="<?= e(xll_url('admin/importar.php')) ?>" enctype="multipart/form-data">
            <?= xll_csrf_field() ?>
            <input type="hidden" name="acao" value="analisar">
            <div class="adm-field">
                <label class="adm-label" for="csv">Arquivo CSV</label>
                <input class="adm-file" id="csv" type="file" name="csv" accept=".csv,.txt,text/csv" required data-filename-target="#csv-name">
                <span class="adm-help" id="csv-name"></span>
                <span class="adm-help">Separador detectado automaticamente (; ou ,). UTF-8 (o Excel pode salvar em CSV separado por ponto e vírgula).</span>
            </div>
            <div class="adm-actions" style="margin-top:14px">
                <button type="submit" class="adm-btn"><?= adm_icon('import') ?> Analisar arquivo</button>
                <a class="adm-btn adm-btn-secondary" href="<?= e(xll_url('admin/importar.php?export=modelo')) ?>">Baixar modelo CSV</a>
            </div>
        </form>

        <?php if ($preview !== null): ?>
            <div style="margin-top:22px;border-top:1px solid rgba(255,255,255,.07);padding-top:18px">
                <div class="adm-card-title" style="margin-bottom:8px">Pré-visualização de <?= e($preview['name']) ?></div>
                <div class="adm-actions" style="gap:8px;margin-bottom:14px">
                    <span class="adm-badge adm-badge-green"><?= (int) $preview['total'] ?> linhas válidas</span>
                    <span class="adm-badge adm-badge-green"><?= (int) $preview['novos'] ?> novos</span>
                    <span class="adm-badge adm-badge-muted"><?= (int) $preview['atualizados'] ?> já existem</span>
                    <?php if (count($preview['erros']) > 0): ?>
                        <span class="adm-badge adm-badge-orange"><?= count($preview['erros']) ?> avisos</span>
                    <?php endif; ?>
                </div>

                <?php if (count($preview['erros']) > 0): ?>
                    <div class="adm-flash adm-flash-warning" style="margin-bottom:12px">
                        <div><?= e(implode(' ', array_slice($preview['erros'], 0, 5))) ?></div>
                    </div>
                <?php endif; ?>

                <div class="adm-table-wrap" style="margin-bottom:16px">
                    <table class="adm-table">
                        <thead><tr><th>Título</th><th>Categoria</th><th>Marketplace</th><th>Preço</th><th>Seção</th></tr></thead>
                        <tbody>
                            <?php foreach ($preview['amostra'] as $row): ?>
                                <tr>
                                    <td class="cell-title"><strong><?= e($row['titulo']) ?></strong><small><?= e($row['id']) ?></small></td>
                                    <td><?= e($row['categoria']) ?></td>
                                    <td><?= e($row['marketplace']) ?></td>
                                    <td class="price"><?= e(xll_format_price((int) $row['preco'])) ?></td>
                                    <td><?= e($row['secao_home']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <form method="post" action="<?= e(xll_url('admin/importar.php')) ?>" data-confirm="Confirmar a importação do arquivo?">
                    <?= xll_csrf_field() ?>
                    <input type="hidden" name="acao" value="confirmar">
                    <div class="adm-field">
                        <label class="adm-label">Como importar</label>
                        <label class="adm-check" style="margin-bottom:8px">
                            <input type="radio" name="modo" value="merge" checked>
                            <span><strong>Mesclar</strong> — adiciona produtos novos e atualiza os existentes pelo ID (recomendado).</span>
                        </label>
                        <label class="adm-check">
                            <input type="radio" name="modo" value="replace">
                            <span><strong>Substituir tudo</strong> — apaga a tabela atual e importa somente este arquivo. Um backup do catálogo é salvo antes.</span>
                        </label>
                    </div>
                    <label class="adm-check" style="margin-top:14px">
                        <input type="checkbox" name="confirmar" value="1" required>
                        <span>Confirmo que revisei os dados acima.</span>
                    </label>
                    <div class="adm-actions" style="margin-top:14px">
                        <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Importar agora</button>
                        <button type="submit" name="acao" value="cancelar" class="adm-btn adm-btn-secondary">Cancelar</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div style="display:flex;flex-direction:column;gap:20px">
        <div class="adm-card">
            <div class="adm-card-head">
                <div>
                    <div class="adm-card-title"><?= adm_icon('import') ?> Exportar</div>
                    <div class="adm-card-sub">Baixe a tabela no mesmo formato do CSV original.</div>
                </div>
            </div>
            <div class="adm-actions" style="flex-direction:column;align-items:stretch">
                <a class="adm-btn" href="<?= e(xll_url('admin/importar.php?export=csv')) ?>"><?= adm_icon('import') ?> Exportar CSV (Excel)</a>
                <a class="adm-btn adm-btn-secondary" href="<?= e(xll_url('admin/importar.php?export=json')) ?>">Exportar JSON (backup)</a>
                <a class="adm-btn adm-btn-secondary" href="<?= e(xll_url('admin/importar.php?export=modelo')) ?>">Baixar modelo vazio</a>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <div>
                    <div class="adm-card-title">Colunas da tabela</div>
                </div>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead><tr><th>Coluna</th><th>Descrição</th></tr></thead>
                    <tbody>
                        <?php foreach (xll_csv_columns() as $key => $label): ?>
                            <tr><td><code style="color:#9df5c6"><?= e($key) ?></code></td><td><?= e($label) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="adm-help" style="margin-top:10px">Preços usam vírgula decimal (ex.: <code>1.234,56</code>). A categoria “Seção da home” aceita Destaque, Populares ou Mais vendidos.</p>
        </div>
    </div>
</div>

<div class="adm-card">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title">Histórico de importações</div>
            <div class="adm-card-sub">Backups de substituições ficam em <code>data/backups/</code>.</div>
        </div>
    </div>
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr><th>Data</th><th>Arquivo</th><th>Modo</th><th>Linhas</th><th>Inseridos</th><th>Atualizados</th><th>Removidos</th><th>Avisos</th></tr>
            </thead>
            <tbody>
                <?php foreach ($history as $item): ?>
                    <tr>
                        <td><?= e(date('d/m/Y H:i', strtotime((string) $item['criado_em']))) ?></td>
                        <td class="cell-title"><strong><?= e($item['arquivo']) ?></strong></td>
                        <td><span class="adm-badge <?= $item['modo'] === 'replace' ? 'adm-badge-orange' : 'adm-badge-green' ?>"><?= e($item['modo']) ?></span></td>
                        <td><?= (int) $item['total'] ?></td>
                        <td><?= (int) $item['inseridos'] ?></td>
                        <td><?= (int) $item['atualizados'] ?></td>
                        <td><?= (int) $item['removidos'] ?></td>
                        <td><?= (int) $item['erros'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$history): ?>
                    <tr><td colspan="8" class="adm-empty">Nenhuma importação registrada ainda.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php adm_footer(); ?>
