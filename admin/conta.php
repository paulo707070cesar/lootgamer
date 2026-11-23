<?php
/**
 * Loot Gamer - minha conta (alterar senha).
 */

declare(strict_types=1);

require __DIR__ . '/inc/layout.php';

if (xll_is_post()) {
    xll_require_csrf();
    [$ok, $message] = xll_admin_change_password(
        (string) ($_POST['senha_atual'] ?? ''),
        (string) ($_POST['senha_nova'] ?? ''),
        (string) ($_POST['senha_confirmacao'] ?? '')
    );
    adm_redirect('conta.php', $message, $ok ? 'success' : 'error');
}

$user = xll_admin_user();
adm_header('Minha conta', 'conta');
?>

<div class="adm-card" style="max-width:620px">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('user') ?> Dados do usuário</div>
            <div class="adm-card-sub">Usuário do painel: <strong><?= e($user['usuario'] ?? '') ?></strong></div>
        </div>
    </div>

    <form method="post" action="<?= e(xll_url('admin/conta.php')) ?>" autocomplete="off">
        <?= xll_csrf_field() ?>
        <div class="adm-grid">
            <div class="adm-field adm-field-full">
                <label class="adm-label" for="senha_atual">Senha atual</label>
                <input class="adm-input" id="senha_atual" type="password" name="senha_atual" autocomplete="current-password" required>
            </div>
            <div class="adm-field">
                <label class="adm-label" for="senha_nova">Nova senha</label>
                <input class="adm-input" id="senha_nova" type="password" name="senha_nova" autocomplete="new-password" minlength="6" required>
                <span class="adm-help">Mínimo de 6 caracteres.</span>
            </div>
            <div class="adm-field">
                <label class="adm-label" for="senha_confirmacao">Confirmar nova senha</label>
                <input class="adm-input" id="senha_confirmacao" type="password" name="senha_confirmacao" autocomplete="new-password" minlength="6" required>
            </div>
        </div>
        <div class="adm-actions" style="margin-top:18px">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Alterar senha</button>
        </div>
    </form>
</div>

<?php adm_footer(); ?>
