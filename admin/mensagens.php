<?php
/**
 * Loot Gamer - mensagens recebidas pelo formulário de contato.
 */

declare(strict_types=1);

require __DIR__ . '/inc/layout.php';

if (xll_is_post()) {
    xll_require_csrf();
    $acao = adm_text('acao');
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0 && $acao === 'excluir') {
        try {
            xll_db()->prepare('DELETE FROM mensagens WHERE id = ?')->execute([$id]);
            adm_redirect('mensagens.php', 'Mensagem excluída.', 'success');
        } catch (Throwable $error) {
            adm_redirect('mensagens.php', 'Não foi possível excluir: ' . $error->getMessage(), 'error');
        }
    }
    if ($id > 0 && $acao === 'marcar_lida') {
        xll_db()->prepare('UPDATE mensagens SET lido = 1 WHERE id = ?')->execute([$id]);
        adm_redirect('mensagens.php', 'Mensagem marcada como lida.', 'success');
    }
    if ($id > 0 && $acao === 'marcar_nao_lida') {
        xll_db()->prepare('UPDATE mensagens SET lido = 0 WHERE id = ?')->execute([$id]);
        adm_redirect('mensagens.php', 'Mensagem marcada como não lida.', 'success');
    }
}

$filter = adm_text('filtro') ?: (string) ($_GET['filtro'] ?? 'todas');
$filter = $filter === 'nao-lidas' ? 'nao-lidas' : 'todas';
$viewId = (int) ($_GET['ver'] ?? 0);
$viewing = $viewId > 0 ? xll_message_get($viewId) : null;
if ($viewing && (int) $viewing['lido'] === 0) {
    try {
        xll_db()->prepare('UPDATE mensagens SET lido = 1 WHERE id = ?')->execute([$viewId]);
        $viewing['lido'] = 1;
    } catch (Throwable) {
    }
}

$messages = xll_messages_list($filter, 150);
$unread = xll_unread_messages();

adm_header('Mensagens de contato', 'mensagens');
?>

<div class="adm-grid" style="grid-template-columns:1.3fr 0.7fr;align-items:start">
    <div class="adm-card">
        <div class="adm-card-head">
            <div>
                <div class="adm-card-title"><?= adm_icon('mail') ?> Caixa de entrada</div>
                <div class="adm-card-sub"><?= $unread ?> mensagem(ns) não lida(s) · <?= count($messages) ?> exibida(s).</div>
            </div>
            <div class="adm-actions">
                <a class="adm-btn <?= $filter === 'todas' ? '' : 'adm-btn-secondary' ?> adm-btn-sm" href="<?= e(xll_url('admin/mensagens.php')) ?>">Todas</a>
                <a class="adm-btn <?= $filter === 'nao-lidas' ? '' : 'adm-btn-secondary' ?> adm-btn-sm" href="<?= e(xll_url('admin/mensagens.php', ['filtro' => 'nao-lidas'])) ?>">Não lidas</a>
            </div>
        </div>

        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr><th></th><th>De</th><th>Assunto</th><th>Data</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $message): ?>
                        <tr>
                            <td>
                                <?php if ((int) $message['lido'] === 0): ?>
                                    <span class="adm-badge adm-badge-green">Nova</span>
                                <?php else: ?>
                                    <span class="adm-badge adm-badge-muted">Lida</span>
                                <?php endif; ?>
                            </td>
                            <td class="cell-title">
                                <strong><?= e($message['nome']) ?></strong>
                                <small><?= e($message['email']) ?></small>
                            </td>
                            <td><?= e($message['assunto'] !== '' ? $message['assunto'] : '—') ?></td>
                            <td style="white-space:nowrap"><?= e(date('d/m/Y H:i', strtotime((string) $message['criado_em']))) ?></td>
                            <td>
                                <a class="adm-btn adm-btn-secondary adm-btn-sm" href="<?= e(xll_url('admin/mensagens.php', ['ver' => (int) $message['id'], 'filtro' => $filter])) ?>">Abrir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$messages): ?>
                        <tr><td colspan="5" class="adm-empty">Nenhuma mensagem recebida ainda.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:20px">
        <?php if ($viewing): ?>
            <div class="adm-card">
                <div class="adm-card-head">
                    <div>
                        <div class="adm-card-title">Mensagem #<?= (int) $viewing['id'] ?></div>
                        <div class="adm-card-sub"><?= e(date('d/m/Y H:i', strtotime((string) $viewing['criado_em']))) ?></div>
                    </div>
                </div>
                <dl class="adm-kv" style="grid-template-columns:80px 1fr">
                    <dt>Nome</dt><dd><?= e($viewing['nome']) ?></dd>
                    <dt>E-mail</dt><dd><a href="mailto:<?= e($viewing['email']) ?>" style="color:#00e676"><?= e($viewing['email']) ?></a></dd>
                    <dt>Assunto</dt><dd><?= e($viewing['assunto'] !== '' ? $viewing['assunto'] : '—') ?></dd>
                </dl>
                <div style="margin-top:14px;padding:14px;border:1px solid var(--adm-border);border-radius:8px;background:#03202a;white-space:pre-line;line-height:1.6;font-size:13px"><?= e($viewing['mensagem']) ?></div>
                <div class="adm-actions" style="margin-top:16px">
                    <a class="adm-btn" href="mailto:<?= e($viewing['email']) ?>?subject=<?= e('Re: ' . ($viewing['assunto'] !== '' ? $viewing['assunto'] : 'Contato pelo site')) ?>">Responder por e-mail</a>
                    <form method="post" action="<?= e(xll_url('admin/mensagens.php')) ?>">
                        <?= xll_csrf_field() ?>
                        <input type="hidden" name="acao" value="<?= (int) $viewing['lido'] === 1 ? 'marcar_nao_lida' : 'marcar_lida' ?>">
                        <input type="hidden" name="id" value="<?= (int) $viewing['id'] ?>">
                        <button type="submit" class="adm-btn adm-btn-secondary"><?= (int) $viewing['lido'] === 1 ? 'Marcar como não lida' : 'Marcar como lida' ?></button>
                    </form>
                    <form method="post" action="<?= e(xll_url('admin/mensagens.php')) ?>" data-confirm="Excluir esta mensagem?">
                        <?= xll_csrf_field() ?>
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" value="<?= (int) $viewing['id'] ?>">
                        <button type="submit" class="adm-btn adm-btn-danger">Excluir</button>
                    </form>
                    <a class="adm-btn adm-btn-secondary" href="<?= e(xll_url('admin/mensagens.php')) ?>">Fechar</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="adm-card">
            <div class="adm-card-head">
                <div><div class="adm-card-title">Como funciona</div></div>
            </div>
            <ul class="adm-help" style="margin:0;padding-left:18px;line-height:1.8">
                <li>As mensagens enviadas em <strong>/contato</strong> aparecem aqui automaticamente.</li>
                <li>Se o servidor permitir envio de e-mail (<code>mail()</code>), uma notificação também é enviada para o destinatário configurado.</li>
                <li>Configure o e-mail de destino, assunto e ativação em <a href="<?= e(xll_url('admin/empresa.php')) ?>" style="color:#00e676">Rodapé, menu e páginas → Notificações</a>.</li>
            </ul>
        </div>
    </div>
</div>

<?php adm_footer(); ?>
