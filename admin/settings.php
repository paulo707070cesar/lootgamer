<?php
require_once __DIR__ . '/../includes/auth.php'; $me = require_admin();
$text = ['site_name' => 'Nome do site', 'site_description' => 'Descrição institucional', 'contact_email' => 'E-mail de contato', 'whatsapp' => 'WhatsApp comercial (opcional)', 'social_youtube' => 'YouTube (URL)', 'social_instagram' => 'Instagram (URL)', 'social_tiktok' => 'TikTok (URL)', 'social_facebook' => 'Facebook (URL)', 'social_x' => 'X/Twitter (URL)', 'copyright' => 'Texto de direitos autorais', 'affiliate_notice' => 'Aviso de afiliados (rodapé)', 'legal_info' => 'Informações legais (razão social, CNPJ etc.)', 'analytics_id' => 'Google Analytics (ex.: G-XXXXXXX)'];
$secs = ['categorias' => 'Categorias', 'destaques' => 'Ofertas em destaque', 'jogos' => 'Jogos mais procurados', 'giftcards' => 'Gift cards', 'populares' => 'Mais populares', 'novidades' => 'Novidades', 'consoles' => 'Consoles e acessórios', 'perifericos' => 'Periféricos e computadores', 'recomendados' => 'Recomendados', 'achadinhos' => 'Achadinhos da Net', 'ultimos' => 'Últimos cadastrados'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($text as $k => $_) {
        $v = trim((string)($_POST[$k] ?? ''));
        if (strpos($k, 'social_') === 0 && $v !== '' && !valid_http_url($v, true)) { flash('err', 'URL inválida em ' . $text[$k]); redirect('settings.php'); }
        if ($k === 'contact_email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) { flash('err', 'E-mail de contato inválido.'); redirect('settings.php'); }
        if ($k === 'analytics_id' && $v !== '' && !preg_match('/^[A-Za-z0-9\-]{4,30}$/', $v)) { flash('err', 'ID do Analytics inválido.'); redirect('settings.php'); }
        set_setting($k, $v);
    }
    foreach ($secs as $k => $_) set_setting('sec_' . $k, !empty($_POST['sec_' . $k]) ? '1' : '0');
    foreach (['logo_path' => 'logo_file', 'favicon_path' => 'favicon_file'] as $k => $f) {
        try { $np = upload_image($f, 'settings'); if ($np) { $old = setting($k); set_setting($k, $np); delete_upload_if_unused($old ?: null); } } catch (RuntimeException $ex) { flash('err', $ex->getMessage()); }
    }
    log_admin('configuracoes_salvas'); flash('ok', 'Configurações salvas.'); redirect('settings.php');
}
$title = 'Configurações gerais'; require __DIR__ . '/_head.php'; ?>
<form method="post" enctype="multipart/form-data" class="box formgrid"><?= csrf_field() ?>
<?php foreach ($text as $k => $l): ?><label><?= e($l) ?><?php if (in_array($k, ['site_description', 'affiliate_notice', 'legal_info'], true)): ?><textarea name="<?= $k ?>" rows="3"><?= e(setting($k)) ?></textarea><?php else: ?><input name="<?= $k ?>" value="<?= e(setting($k)) ?>"><?php endif; ?></label><?php endforeach; ?>
<div class="f2"><label>Logo<input type="file" name="logo_file" accept="image/*"><?php if (setting('logo_path')): ?><img class="thumb wide" src="<?= e(url(setting('logo_path'))) ?>" alt=""><?php endif; ?></label>
<label>Favicon<input type="file" name="favicon_file" accept="image/*"><?php if (setting('favicon_path')): ?><img class="thumb" src="<?= e(url(setting('favicon_path'))) ?>" alt=""><?php endif; ?></label></div>
<fieldset class="imgbox"><legend>Seções exibidas na página inicial</legend><div class="f4"><?php foreach ($secs as $k => $l): ?><label class="chk"><input type="checkbox" name="sec_<?= $k ?>" value="1" <?= section_on($k) ? 'checked' : '' ?>> <?= e($l) ?></label><?php endforeach; ?></div></fieldset>
<div class="actionsbar"><button class="btn">💾 Salvar configurações</button></div></form>
<?php require __DIR__ . '/_foot.php';
