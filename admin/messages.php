<?php
require_once __DIR__ . '/../includes/auth.php'; $me = require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)$_POST['id'];
    if (($_POST['action'] ?? '') === 'delete') { q('DELETE FROM contact_messages WHERE id=?', [$id]); flash('ok', 'Mensagem excluída.'); }
    else { q('UPDATE contact_messages SET is_answered = 1 - is_answered WHERE id=?', [$id]); flash('ok', 'Status atualizado.'); }
    redirect('messages.php');
}
$rows = q('SELECT * FROM contact_messages ORDER BY is_answered, id DESC LIMIT 200')->fetchAll();
$title = 'Mensagens de contato'; require __DIR__ . '/_head.php'; ?>
<?php foreach ($rows as $r): ?><section class="box msg <?= $r['is_answered'] ? 'done' : '' ?>"><div class="msg-h"><b><?= e($r['name']) ?></b> &lt;<a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a>&gt; · <small><?= e($r['created_at']) ?></small> <span class="st st-<?= $r['is_answered'] ? 'publicado' : 'rascunho' ?>"><?= $r['is_answered'] ? 'respondida' : 'pendente' ?></span></div>
<?php if ($r['subject']): ?><p><b><?= e($r['subject']) ?></b></p><?php endif; ?><p><?= nl2br(e($r['message'])) ?></p>
<form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="mini"><?= $r['is_answered'] ? 'Marcar como pendente' : 'Marcar como respondida' ?></button></form>
<form method="post" class="inline" data-confirm="Excluir mensagem?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="mini danger">Excluir</button></form></section><?php endforeach; if (!$rows) echo '<p>Nenhuma mensagem.</p>';
require __DIR__ . '/_foot.php';
