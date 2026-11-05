<?php
require_once __DIR__ . '/includes/bootstrap.php';
http_response_code(404); $pageTitle = 'Página não encontrada'; $robotsNoindex = true;
include ROOT . '/includes/header.php'; ?>
<div class="wrap page"><h1>Ops! Conteúdo não encontrado</h1><p>A página que você procura não existe ou foi removida.</p><p><a class="btn-offer sm" href="<?= url() ?>">Voltar ao início</a></p></div>
<?php include ROOT . '/includes/footer.php';
