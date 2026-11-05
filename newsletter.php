<?php
require_once __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url());
csrf_check();
$back = url();
if (!empty($_POST['website'])) redirect($back);
$email = trim((string)($_POST['email'] ?? ''));
$src = mb_substr(preg_replace('/[^a-z0-9_\-]/i', '', (string)($_POST['source'] ?? 'site')), 0, 60);
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) { flash('err', 'Informe um e-mail válido.'); redirect($back); }
if (empty($_POST['consent'])) { flash('err', 'É necessário marcar o consentimento para se inscrever.'); redirect($back); }
if (time() - ($_SESSION['last_news'] ?? 0) < 10) { flash('err', 'Aguarde alguns segundos.'); redirect($back); }
$_SESSION['last_news'] = time();
$ex = q('SELECT id FROM newsletter_subscribers WHERE email = ?', [$email])->fetch();
if ($ex) q('UPDATE newsletter_subscribers SET is_active = 1, unsubscribed_at = NULL, consent_at = NOW(), consent_source = ? WHERE id = ?', [$src, $ex['id']]);
else q('INSERT INTO newsletter_subscribers (email, consent_at, consent_source, token) VALUES (?, NOW(), ?, ?)', [$email, $src, bin2hex(random_bytes(20))]);
flash('ok', 'Inscrição realizada! Você pode cancelar quando quiser.');
redirect($back);
