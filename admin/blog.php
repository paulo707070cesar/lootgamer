<?php
require_once __DIR__ . '/../includes/auth.php'; $me = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'cat_save') {
        $cid = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) { flash('err', 'Informe o nome da categoria (até 120 caracteres).'); redirect('blog.php'); }
        $slug = unique_slug('blog_categories', slugify($name), $cid);
        $seoT = trim((string)($_POST['seo_title'] ?? '')) ?: null;
        $seoD = trim((string)($_POST['meta_description'] ?? '')) ?: null;
        $desc = trim((string)($_POST['description'] ?? '')) ?: null;
        if ($cid) {
            q('UPDATE blog_categories SET name=?, slug=?, description=?, seo_title=?, meta_description=? WHERE id=?', [$name, $slug, $desc, $seoT, $seoD, $cid]);
        } else {
            q('INSERT INTO blog_categories (name, slug, description, seo_title, meta_description) VALUES (?,?,?,?,?)', [$name, $slug, $desc, $seoT, $seoD]);
        }
        log_admin('blog_categoria', $name); flash('ok', 'Categoria salva.'); redirect('blog.php');
    }
    if ($act === 'cat_delete') {
        q('DELETE FROM blog_categories WHERE id = ?', [(int)$_POST['id']]);
        log_admin('blog_categoria_excluida', (string)$_POST['id']); flash('ok', 'Categoria excluída (artigos ficam sem categoria).'); redirect('blog.php');
    }
    if ($act === 'delete') {
        $pid = (int)($_POST['id'] ?? 0);
        $img = q('SELECT featured_image FROM blog_posts WHERE id = ?', [$pid])->fetchColumn();
        q('DELETE FROM blog_posts WHERE id = ?', [$pid]);
        if ($img) delete_upload_if_unused($img);
        log_admin('blog_excluido', (string)$pid); flash('ok', 'Artigo excluído.'); redirect('blog.php');
    }
}

$filter = in_array($_GET['status'] ?? '', ['publicado', 'rascunho'], true) ? $_GET['status'] : '';
$search = trim((string)($_GET['q'] ?? ''));
$where = []; $params = [];
if ($filter) { $where[] = 'b.status = ?'; $params[] = $filter; }
if ($search !== '') { $where[] = '(b.title LIKE ? OR b.slug LIKE ?)'; $params[] = '%' . $search . '%'; $params[] = '%' . $search . '%'; }
$sql = 'SELECT b.*, c.name AS cat_name FROM blog_posts b LEFT JOIN blog_categories c ON c.id = b.category_id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY COALESCE(b.published_at, b.created_at) DESC, b.id DESC LIMIT 200';
$posts = q($sql, $params)->fetchAll();
$cats = q('SELECT c.*, (SELECT COUNT(*) FROM blog_posts b WHERE b.category_id = c.id) AS total FROM blog_categories c ORDER BY c.sort_order, c.name')->fetchAll();
$editCat = null;
if (!empty($_GET['cat'])) foreach ($cats as $c) if ((int)$c['id'] === (int)$_GET['cat']) $editCat = $c;

$actions = '<a class="btn" href="' . e(url('admin/blog-edit.php')) . '">+ Novo artigo</a>';
$title = 'Blog'; require __DIR__ . '/_head.php'; ?>
<div class="cols2">
  <section class="box">
    <form method="get" class="blog-filters">
      <input type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar por título ou slug">
      <select name="status"><option value="">Todos os status</option><option value="publicado" <?= $filter === 'publicado' ? 'selected' : '' ?>>Publicados</option><option value="rascunho" <?= $filter === 'rascunho' ? 'selected' : '' ?>>Rascunhos</option></select>
      <button class="mini">Filtrar</button>
    </form>
    <div class="scroll"><table class="list">
      <thead><tr><th>Artigo</th><th>Categoria</th><th>Status</th><th>Publicação</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($posts as $p): $future = $p['status'] === 'publicado' && $p['published_at'] && strtotime($p['published_at']) > time(); ?>
        <tr>
          <td><a href="<?= e(url('admin/blog-edit.php?id=' . $p['id'])) ?>"><b><?= e($p['title']) ?></b></a><br><small>/blog/<?= e($p['slug']) ?></small></td>
          <td><?= e($p['cat_name'] ?: '—') ?></td>
          <td><?php if ($p['status'] === 'publicado'): ?><span class="st st-publicado"><?= $future ? 'agendado' : 'publicado' ?></span><?php else: ?><span class="st st-rascunho">rascunho</span><?php endif; ?></td>
          <td><small><?= $p['published_at'] ? e(date('d/m/Y H:i', strtotime($p['published_at']))) : '—' ?></small></td>
          <td class="acts">
            <?php if ($p['status'] === 'publicado' && !$future): ?><a class="mini" href="<?= e(url('blog/' . $p['slug'])) ?>" target="_blank">Ver</a><?php endif; ?>
            <a class="mini" href="<?= e(url('admin/blog-edit.php?id=' . $p['id'])) ?>">Editar</a>
            <form method="post" class="inline" data-confirm="Excluir este artigo definitivamente?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="mini danger">Excluir</button></form>
          </td>
        </tr>
      <?php endforeach; if (!$posts): ?><tr><td colspan="5"><p>Nenhum artigo encontrado.</p></td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </section>

  <section class="box">
    <h2><?= $editCat ? 'Editar categoria' : 'Nova categoria' ?></h2>
    <form method="post" class="formgrid">
      <?= csrf_field() ?><input type="hidden" name="action" value="cat_save"><input type="hidden" name="id" value="<?= (int)($editCat['id'] ?? 0) ?>">
      <label>Nome<input name="name" required maxlength="120" value="<?= e($editCat['name'] ?? '') ?>"></label>
      <label>Descrição<textarea name="description" rows="3"><?= e($editCat['description'] ?? '') ?></textarea></label>
      <label>Título SEO<input name="seo_title" maxlength="160" value="<?= e($editCat['seo_title'] ?? '') ?>"></label>
      <label>Meta description<input name="meta_description" maxlength="255" value="<?= e($editCat['meta_description'] ?? '') ?>"></label>
      <div class="actionsbar"><button class="btn">💾 Salvar categoria</button><?php if ($editCat): ?><a class="btn ghost" href="<?= e(url('admin/blog.php')) ?>">Cancelar</a><?php endif; ?></div>
    </form>
    <h2 style="margin-top:18px">Categorias</h2>
    <table class="list"><?php foreach ($cats as $c): ?>
      <tr><td><a href="?cat=<?= (int)$c['id'] ?>"><b><?= e($c['name']) ?></b></a><br><small>/blog/categoria/<?= e($c['slug']) ?> · <?= (int)$c['total'] ?> artigo(s)</small></td>
      <td class="acts"><form method="post" class="inline" data-confirm="Excluir a categoria? Os artigos continuam publicados, sem categoria."><?= csrf_field() ?><input type="hidden" name="action" value="cat_delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="mini danger">Excluir</button></form></td></tr>
    <?php endforeach; if (!$cats): ?><tr><td>Nenhuma categoria ainda.</td></tr><?php endif; ?></table>
  </section>
</div>
<?php require __DIR__ . '/_foot.php';
