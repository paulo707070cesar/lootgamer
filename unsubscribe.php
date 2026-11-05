<?php
require_once __DIR__ . '/includes/bootstrap.php';
$t = (string)($_GET['token'] ?? '');
$done = false;
if (preg_match('/^[a-f0-9]{40}$/', $t)) { $done = q('UPDATE newsletter_subscribers SET is_active = 0, unsubscribed_at = NOW() WHERE token = ? AND is_active = 1', [$t])->rowCount() > 0; }
$pageTitle = 'Cancelar inscrição'; $robotsNoindex = true;
include ROOT . '/includes/header.php'; ?>
<div class="wrap page narrow"><h1>Cancelar inscrição</h1><p><?= $done ? 'Sua inscrição foi cancelada.' : 'Link inválido ou inscrição já cancelada.' ?></p></div>
<?php include ROOT . '/includes/footer.php';
