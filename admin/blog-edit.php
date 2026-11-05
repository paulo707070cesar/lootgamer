<?php
require_once __DIR__ . '/../includes/auth.php'; $me = require_admin();
$id = (int)($_GET['id'] ?? 0);
$p = $id ? q('SELECT * FROM blog_posts WHERE id = ?', [$id])->fetch() : null;
if ($id && !$p) { flash('err', 'Artigo não encontrado.'); redirect('blog.php'); }
$cats = q('SELECT id, name FROM blog_categories ORDER BY sort_order, name')->fetchAll();
$d = $p ?: ['title' => '', 'slug' => '', 'excerpt' => '', 'content' => '', 'featured_image' => '', 'featured_alt' => '', 'category_id' => '', 'tags' => '', 'status' => 'rascunho', 'published_at' => '', 'focus_keyword' => '', 'seo_title' => '', 'meta_description' => '', 'canonical_url' => '', 'robots_noindex' => 0, 'robots_nofollow' => 0, 'og_title' => '', 'og_description' => '', 'og_image_url' => '', 'schema_type' => 'BlogPosting'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = [];
    foreach (['title', 'slug', 'excerpt', 'featured_alt', 'tags', 'focus_keyword', 'seo_title', 'meta_description', 'canonical_url', 'og_title', 'og_description', 'og_image_url'] as $k) $f[$k] = trim((string)($_POST[$k] ?? ''));
    $f['content'] = trim((string)($_POST['content'] ?? ''));
    $f['category_id'] = (int)($_POST['category_id'] ?? 0) ?: null;
    $f['status'] = in_array($_POST['status'] ?? '', ['rascunho', 'publicado'], true) ? $_POST['status'] : 'rascunho';
    $f['schema_type'] = in_array($_POST['schema_type'] ?? '', ['BlogPosting', 'Article', 'NewsArticle'], true) ? $_POST['schema_type'] : 'BlogPosting';
    $f['robots_noindex'] = !empty($_POST['robots_noindex']) ? 1 : 0;
    $f['robots_nofollow'] = !empty($_POST['robots_nofollow']) ? 1 : 0;

    $pubRaw = trim((string)($_POST['published_at'] ?? ''));
    $pubDt = null;
    if ($pubRaw !== '') {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $pubRaw) ?: DateTime::createFromFormat('Y-m-d H:i', $pubRaw);
        if ($dt) $pubDt = $dt->format('Y-m-d H:i:s'); else $errors[] = 'Data de publicação inválida.';
    }
    if ($f['status'] === 'publicado' && !$pubDt) $pubDt = date('Y-m-d H:i:s');

    if ($f['title'] === '' || mb_strlen($f['title']) > 190) $errors[] = 'Informe o título do artigo (até 190 caracteres).';
    if ($f['status'] === 'publicado' && $f['content'] === '') $errors[] = 'Para publicar, escreva o conteúdo do artigo (ou salve como rascunho).';
    if (mb_strlen($f['seo_title']) > 160) $errors[] = 'Título SEO: máximo 160 caracteres.';
    if (mb_strlen($f['meta_description']) > 255) $errors[] = 'Meta description: máximo 255 caracteres.';
    if (mb_strlen($f['focus_keyword']) > 120) $errors[] = 'Palavra-chave foco: máximo 120 caracteres.';
    if (mb_strlen($f['excerpt']) > 500) $errors[] = 'Resumo: máximo 500 caracteres.';
    if (mb_strlen($f['tags']) > 255) $errors[] = 'Tags: máximo 255 caracteres.';
    if ($f['canonical_url'] !== '' && !valid_http_url($f['canonical_url'])) $errors[] = 'URL canônica inválida (use http:// ou https://).';
    if ($f['og_image_url'] !== '' && !valid_http_url($f['og_image_url'], true)) $errors[] = 'Imagem para redes sociais: use uma URL HTTPS.';
    if (mb_strlen($f['og_title']) > 160 || mb_strlen($f['og_description']) > 255) $errors[] = 'Título ou descrição para redes sociais excede o limite.';

    $newImg = null;
    if (!$errors) { try { $newImg = upload_image('featured_file', 'blog'); } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); } }

    if (!$errors) {
        $slugBase = slugify($f['slug'] !== '' ? $f['slug'] : $f['title']);
        $slug = unique_slug('blog_posts', $slugBase, $id);
        $oldImg = $p['featured_image'] ?? null;
        $img = $oldImg;
        if ($newImg) $img = $newImg; elseif (!empty($_POST['remove_image'])) $img = null;
        $vals = [$f['title'], $slug, $f['excerpt'] ?: null, $f['content'] ?: null, $img, $f['featured_alt'] ?: null, $f['category_id'], $f['tags'] ?: null, $f['status'], $pubDt, $f['focus_keyword'] ?: null, $f['seo_title'] ?: null, $f['meta_description'] ?: null, $f['canonical_url'] ?: null, $f['robots_noindex'], $f['robots_nofollow'], $f['og_title'] ?: null, $f['og_description'] ?: null, $f['og_image_url'] ?: null, $f['schema_type']];
        $cols = ['title', 'slug', 'excerpt', 'content', 'featured_image', 'featured_alt', 'category_id', 'tags', 'status', 'published_at', 'focus_keyword', 'seo_title', 'meta_description', 'canonical_url', 'robots_noindex', 'robots_nofollow', 'og_title', 'og_description', 'og_image_url', 'schema_type'];
        if ($id) {
            q('UPDATE blog_posts SET ' . implode('=?, ', $cols) . '=? WHERE id = ?', array_merge($vals, [$id]));
        } else {
            q('INSERT INTO blog_posts (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')', $vals);
            $id = (int)db()->lastInsertId();
        }
        if ($oldImg && $oldImg !== $img) delete_upload_if_unused($oldImg);
        log_admin($p ? 'blog_editado' : 'blog_criado', $f['title']);
        flash('ok', 'Artigo salvo.');
        redirect('blog-edit.php?id=' . $id);
    }
    // mantém os valores digitados quando há erro
    $d = array_merge($d, $f, ['published_at' => $pubRaw, 'category_id' => $f['category_id'] ?? '']);
    if ($p) $d['featured_image'] = $p['featured_image'];
}

$pubVal = '';
if (!empty($d['published_at']) && strpos((string)$d['published_at'], 'T') === false) $pubVal = date('Y-m-d\TH:i', strtotime($d['published_at']));
else $pubVal = (string)$d['published_at'];
$featUrl = !empty($d['featured_image']) ? url($d['featured_image']) : asset('images/placeholder.svg');
$isNew = !$p;
$siteName = setting('site_name', 'Loot Gamer');
$domain = $_SERVER['HTTP_HOST'] ?? 'seusite.com.br';
$title = $isNew ? 'Novo artigo' : 'Editar artigo';
$actions = $p && $d['status'] === 'publicado' ? '<a class="btn ghost" target="_blank" href="' . e(url('blog/' . $p['slug'])) . '">Ver artigo ↗</a>' : '';
$pageScript = 'js/blog-admin.js';
require __DIR__ . '/_head.php'; ?>
<?php if ($errors): ?><div class="alert alert-err"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" id="postForm" class="blog-form"
      data-site="<?= e($siteName) ?>" data-domain="<?= e($domain) ?>" data-base="<?= e(BASE) ?>">
<?= csrf_field() ?>
<div class="blog-layout">
  <div class="blog-main">
    <section class="box">
      <label class="fld">Título do artigo
        <input name="title" id="postTitle" required maxlength="190" value="<?= e($d['title']) ?>" placeholder="Ex.: Melhores headsets gamer em 2026">
      </label>
      <label class="fld">Permalink (slug)
        <div class="slug-row"><span><?= e($domain) ?>/blog/</span><input name="slug" id="slugInput" maxlength="190" value="<?= e($d['slug']) ?>" placeholder="gerado-automaticamente"></div>
      </label>
      <label class="fld">Resumo (aparece nas listagens)
        <textarea name="excerpt" id="excerpt" rows="2" maxlength="500" placeholder="Um parágrafo curto sobre o que o leitor vai encontrar."><?= e($d['excerpt']) ?></textarea>
        <small class="hint">Também serve de meta description, caso o campo de meta description (no painel SEO) fique vazio.</small>
        <small class="count" data-count-for="excerpt" data-max="500"></small>
      </label>
    </section>

    <section class="box">
      <div class="toolbar" role="toolbar" aria-label="Formatação do conteúdo">
        <button type="button" data-tag="h2">H2</button>
        <button type="button" data-tag="h3">H3</button>
        <button type="button" data-tag="p">Parágrafo</button>
        <button type="button" data-tag="strong">Negrito</button>
        <button type="button" data-tag="ul">Lista</button>
        <button type="button" data-tag="link">Link</button>
        <button type="button" data-tag="img">Imagem</button>
      </div>
      <label class="fld">Conteúdo (HTML)
        <textarea name="content" id="content" rows="22" class="content-area"><?= e($d['content']) ?></textarea>
      </label>
      <p class="stats"><span id="wordCount">0</span> palavras · <span id="readTime">1</span> min de leitura</p>
    </section>

    <section class="box seo-box" id="seoBox">
      <div class="seo-head">
        <h2>Análise de SEO</h2>
        <span class="seo-light" id="seoLight" title="Resumo geral da análise"></span>
      </div>
      <div class="snippet">
        <h3 class="sub">Aparência no Google</h3>
        <div class="serp">
          <div class="serp-url" id="serpUrl"></div>
          <div class="serp-title" id="serpTitle"></div>
          <div class="serp-desc" id="serpDesc"></div>
        </div>
        <label class="fld">Título SEO
          <input name="seo_title" id="seoTitle" maxlength="160" value="<?= e($d['seo_title']) ?>" placeholder="Padrão: título do artigo">
          <small class="count" data-count-for="seoTitle" data-max="60" data-min="30"></small>
        </label>
        <label class="fld">Meta description
          <textarea name="meta_description" id="metaDesc" rows="3" maxlength="255" placeholder="Escreva um resumo de até 160 caracteres. Enquanto estiver vazio, usa o resumo acima."><?= e($d['meta_description']) ?></textarea>
          <span class="meta-actions"><small class="hint">Se estiver vazio, o resumo do artigo é usado automaticamente.</small> <button type="button" class="mini" id="copyExcerpt">Usar resumo</button></span>
          <small class="count" data-count-for="metaDesc" data-max="160" data-min="120"></small>
        </label>
      </div>

      <div class="seo-tabs" role="tablist">
        <button type="button" class="on" data-panel="pAnalise">Análise</button>
        <button type="button" data-panel="pSocial">Redes sociais</button>
        <button type="button" data-panel="pAvancado">Avançado</button>
      </div>

      <div class="seo-panel" id="pAnalise">
        <label class="fld">Palavra-chave foco
          <input name="focus_keyword" id="focusKw" maxlength="120" value="<?= e($d['focus_keyword']) ?>" placeholder="Ex.: headset gamer">
          <small class="hint">A palavra-chave principal pela qual você quer ser encontrado. Use uma frase curta e natural.</small>
        </label>
        <ul class="checks" id="seoChecks"></ul>
        <h3 class="sub">Legibilidade</h3>
        <ul class="checks" id="readChecks"></ul>
      </div>

      <div class="seo-panel" id="pSocial" hidden>
        <p class="hint">Deixe em branco para usar o título SEO, a meta description e a imagem destacada.</p>
        <label class="fld">Título para Facebook/WhatsApp/X
          <input name="og_title" maxlength="160" value="<?= e($d['og_title']) ?>">
        </label>
        <label class="fld">Descrição para redes sociais
          <textarea name="og_description" rows="2" maxlength="255"><?= e($d['og_description']) ?></textarea>
        </label>
        <label class="fld">URL de imagem para redes sociais (HTTPS, opcional)
          <input type="url" name="og_image_url" value="<?= e($d['og_image_url']) ?>" placeholder="https://...">
        </label>
      </div>

      <div class="seo-panel" id="pAvancado" hidden>
        <label class="fld">URL canônica (opcional)
          <input type="url" name="canonical_url" value="<?= e($d['canonical_url']) ?>" placeholder="Deixe vazio para usar a própria página">
          <small class="hint">Use somente se este conteúdo for uma cópia de outra URL.</small>
        </label>
        <label class="chk"><input type="checkbox" name="robots_noindex" value="1" id="noindex" <?= $d['robots_noindex'] ? 'checked' : '' ?>> Não indexar esta página (noindex)</label>
        <label class="chk"><input type="checkbox" name="robots_nofollow" value="1" <?= $d['robots_nofollow'] ? 'checked' : '' ?>> Não seguir os links desta página (nofollow)</label>
        <label class="fld">Tipo de dado estruturado (Schema.org)
          <select name="schema_type">
            <?php foreach (['BlogPosting' => 'Postagem de blog (BlogPosting)', 'Article' => 'Artigo (Article)', 'NewsArticle' => 'Notícia (NewsArticle)'] as $v => $l): ?>
              <option value="<?= $v ?>" <?= $d['schema_type'] === $v ? 'selected' : '' ?>><?= e($l) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
    </section>
  </div>

  <aside class="blog-side">
    <section class="box">
      <h2>Publicação</h2>
      <div class="formgrid">
        <label>Status
          <select name="status" id="status">
            <option value="rascunho" <?= $d['status'] === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
            <option value="publicado" <?= $d['status'] === 'publicado' ? 'selected' : '' ?>>Publicado</option>
          </select>
        </label>
        <label>Data de publicação
          <input type="datetime-local" name="published_at" value="<?= e($pubVal) ?>">
          <small class="hint">Data futura = agendado. Vazio = agora ao publicar.</small>
        </label>
        <label>Categoria do blog
          <select name="category_id">
            <option value="">Sem categoria</option>
            <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (string)$d['category_id'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
          <?php if (!$cats): ?>
            <small class="hint warn-txt">Nenhuma categoria do blog cadastrada. <a href="<?= e(url('admin/blog.php')) ?>">Criar categorias do blog</a> (são diferentes das categorias da loja, do menu Categorias).</small>
          <?php else: ?>
            <small class="hint"><?= count($cats) ?> categoria(s) do blog · <a href="<?= e(url('admin/blog.php')) ?>">gerenciar</a></small>
          <?php endif; ?>
        </label>
        <label>Tags (separadas por vírgula)
          <input name="tags" maxlength="255" value="<?= e($d['tags']) ?>" placeholder="games, headset, pc gamer">
        </label>
      </div>
      <div class="actionsbar" style="margin-top:14px"><button class="btn">💾 Salvar artigo</button></div>
    </section>

    <section class="box">
      <h2>Imagem destacada</h2>
      <div class="feat-prev"><img id="featPrev" src="<?= e($featUrl) ?>" alt="" data-has="<?= empty($d['featured_image']) ? 0 : 1 ?>" data-fallback></div>
      <div class="formgrid">
        <input type="file" name="featured_file" id="featFile" accept="image/jpeg,image/png,image/webp,image/gif">
        <small class="hint">JPG, PNG, WEBP ou GIF até 3 MB. Recomendado: 1200×630 px.</small>
        <label>Texto alternativo (alt)
          <input name="featured_alt" id="featAlt" maxlength="190" value="<?= e($d['featured_alt']) ?>" placeholder="Descreva a imagem">
        </label>
        <?php if (!empty($d['featured_image'])): ?>
        <label class="chk"><input type="checkbox" name="remove_image" value="1"> Remover imagem destacada</label>
        <?php endif; ?>
      </div>
    </section>
  </aside>
</div>
</form>
<?php require __DIR__ . '/_foot.php';
