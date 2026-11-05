<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Contato'; $metaDesc = 'Fale com a equipe.';
$errors = []; $old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($old as $k => $_) $old[$k] = trim((string)($_POST[$k] ?? ''));
    if (!empty($_POST['website'])) { redirect(url('contato')); } // honeypot
    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 120) $errors[] = 'Informe seu nome.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 190) $errors[] = 'Informe um e-mail válido.';
    if (mb_strlen($old['message']) < 10 || mb_strlen($old['message']) > 3000) $errors[] = 'A mensagem deve ter entre 10 e 3000 caracteres.';
    if (empty($_POST['consent'])) $errors[] = 'É necessário concordar com o tratamento dos dados para responder sua mensagem.';
    $last = $_SESSION['last_contact'] ?? 0;
    if (!$errors && time() - $last < 30) $errors[] = 'Aguarde alguns segundos antes de enviar outra mensagem.';
    if (!$errors) {
        q('INSERT INTO contact_messages (name,email,subject,message,consent_at) VALUES (?,?,?,?,NOW())', [$old['name'], $old['email'], mb_substr($old['subject'], 0, 160), $old['message']]);
        $_SESSION['last_contact'] = time();
        flash('ok', 'Mensagem enviada! Responderemos assim que possível.');
        redirect(url('contato'));
    }
}
include ROOT . '/includes/header.php'; ?>
<div class="wrap"><nav class="crumbs"><a href="<?= url() ?>">Início</a> › <span>Contato</span></nav>
<article class="page narrow"><h1>Contato</h1>
<?php if (setting('contact_email')): ?><p>Ou escreva para <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a>.</p><?php endif; ?>
<?php foreach ($errors as $er): ?><div class="alert alert-err"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" class="form"><?= csrf_field() ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
  <label>Nome<input name="name" required maxlength="120" value="<?= e($old['name']) ?>"></label>
  <label>E-mail<input type="email" name="email" required maxlength="190" value="<?= e($old['email']) ?>"></label>
  <label>Assunto<input name="subject" maxlength="160" value="<?= e($old['subject']) ?>"></label>
  <label>Mensagem<textarea name="message" rows="6" required maxlength="3000"><?= e($old['message']) ?></textarea></label>
  <label class="consent"><input type="checkbox" name="consent" value="1" required> Concordo com o uso dos meus dados para responder esta mensagem, conforme a <a href="<?= url('privacidade') ?>">Política de Privacidade</a>.</label>
  <button class="btn-offer" type="submit">Enviar mensagem</button></form></article></div>
<?php include ROOT . '/includes/footer.php';
