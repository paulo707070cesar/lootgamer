<?php
require_once __DIR__ . '/../includes/auth.php';
$me = require_admin();

$id = max(0, (int)($_GET['id'] ?? 0));
$p = $id ? q('SELECT * FROM products WHERE id = ?', [$id])->fetch() : null;
if ($id && !$p) {
    flash('err', 'Produto não encontrado.');
    redirect('products.php');
}

$categories = q('SELECT id, name FROM categories ORDER BY is_main DESC, sort_order, name')->fetchAll();
$stores = q('SELECT id, name FROM affiliate_stores ORDER BY is_active DESC, name')->fetchAll();

$defaults = [
    'category_id' => '',
    'store_id' => '',
    'name' => '',
    'slug' => '',
    'short_description' => '',
    'description' => '',
    'brand' => '',
    'model' => '',
    'price' => '',
    'old_price' => '',
    'promo_text' => '',
    'image_path' => '',
    'image_url' => '',
    'affiliate_url' => '',
    'is_featured' => 0,
    'is_promo' => 0,
    'is_recommended' => 0,
    'status' => 'rascunho',
    'sort_order' => 0,
    'seo_title' => '',
    'meta_description' => '',
];
$d = $p ? array_merge($defaults, $p) : $defaults;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $f = [];
    foreach (['name', 'slug', 'short_description', 'description', 'brand', 'model', 'promo_text', 'image_url', 'affiliate_url', 'seo_title', 'meta_description'] as $key) {
        $f[$key] = trim((string)($_POST[$key] ?? ''));
    }

    $f['category_id'] = (int)($_POST['category_id'] ?? 0);
    $f['store_id'] = (int)($_POST['store_id'] ?? 0);
    $f['price'] = parse_money((string)($_POST['price'] ?? ''));
    $f['old_price'] = parse_money((string)($_POST['old_price'] ?? ''));
    $f['status'] = in_array($_POST['status'] ?? '', ['rascunho', 'publicado', 'inativo'], true) ? $_POST['status'] : 'rascunho';
    $f['sort_order'] = (int)($_POST['sort_order'] ?? 0);
    $f['is_featured'] = !empty($_POST['is_featured']) ? 1 : 0;
    $f['is_promo'] = !empty($_POST['is_promo']) ? 1 : 0;
    $f['is_recommended'] = !empty($_POST['is_recommended']) ? 1 : 0;

    if ($f['name'] === '' || mb_strlen($f['name']) > 190) $errors[] = 'Informe o nome do produto (até 190 caracteres).';
    if (mb_strlen($f['short_description']) > 300) $errors[] = 'A descrição curta deve ter no máximo 300 caracteres.';
    if (mb_strlen($f['brand']) > 80 || mb_strlen($f['model']) > 80) $errors[] = 'Marca e modelo devem ter no máximo 80 caracteres.';
    if (mb_strlen($f['promo_text']) > 60) $errors[] = 'O texto promocional deve ter no máximo 60 caracteres.';
    if (mb_strlen($f['seo_title']) > 160) $errors[] = 'O título SEO deve ter no máximo 160 caracteres.';
    if (mb_strlen($f['meta_description']) > 255) $errors[] = 'A meta description deve ter no máximo 255 caracteres.';
    if ($f['image_url'] !== '' && !valid_http_url($f['image_url'], true)) $errors[] = 'O link da imagem deve ser uma URL HTTPS válida.';
    if ($f['affiliate_url'] !== '' && !valid_http_url($f['affiliate_url'])) $errors[] = 'Informe um link de afiliado válido (HTTP ou HTTPS).';

    $categoryIds = array_map('intval', array_column($categories, 'id'));
    $storeIds = array_map('intval', array_column($stores, 'id'));
    if ($f['category_id'] && !in_array($f['category_id'], $categoryIds, true)) $errors[] = 'A categoria selecionada não existe.';
    if ($f['store_id'] && !in_array($f['store_id'], $storeIds, true)) $errors[] = 'A loja selecionada não existe.';

    $slugBase = slugify($f['slug'] !== '' ? $f['slug'] : $f['name']);
    $slug = unique_slug('products', $slugBase, $id);

    $newImage = null;
    if (!$errors) {
        try {
            $newImage = upload_image('image_file', 'products');
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $oldImage = $p['image_path'] ?? null;
        $imagePath = $oldImage;
        $imageUrl = $f['image_url'] !== '' ? $f['image_url'] : null;

        if ($newImage) {
            $imagePath = $newImage;
        } elseif (!empty($_POST['remove_image'])) {
            $imagePath = null;
            $imageUrl = null;
        } elseif ($imageUrl !== '') {
            // Quando uma URL nova é informada, ela passa a ser a imagem principal.
            $imagePath = null;
        }

        $values = [
            $f['category_id'] ?: null,
            $f['store_id'] ?: null,
            $f['name'],
            $slug,
            $f['short_description'] ?: null,
            $f['description'] ?: null,
            $f['brand'] ?: null,
            $f['model'] ?: null,
            $f['price'],
            $f['old_price'],
            $f['promo_text'] ?: null,
            $imagePath,
            $imageUrl,
            $f['affiliate_url'] ?: null,
            $f['is_featured'],
            $f['is_promo'],
            $f['is_recommended'],
            $f['status'],
            $f['sort_order'],
            $f['seo_title'] ?: null,
            $f['meta_description'] ?: null,
        ];

        try {
            if ($id) {
                $sql = 'UPDATE products SET category_id=?, store_id=?, name=?, slug=?, short_description=?, description=?, brand=?, model=?, price=?, old_price=?, promo_text=?, image_path=?, image_url=?, affiliate_url=?, is_featured=?, is_promo=?, is_recommended=?, status=?, sort_order=?, seo_title=?, meta_description=? WHERE id=?';
                q($sql, array_merge($values, [$id]));
                $action = 'produto_editado';
                $message = 'Produto atualizado com sucesso.';
            } else {
                $sql = 'INSERT INTO products (category_id,store_id,name,slug,short_description,description,brand,model,price,old_price,promo_text,image_path,image_url,affiliate_url,is_featured,is_promo,is_recommended,status,sort_order,seo_title,meta_description) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
                q($sql, $values);
                $id = (int)db()->lastInsertId();
                $action = 'produto_criado';
                $message = 'Produto criado com sucesso.';
            }

            if ($oldImage && $oldImage !== $imagePath) delete_upload_if_unused($oldImage);
            log_admin($action, $f['name'] . ' #' . $id);
            flash('ok', $message);
            redirect('product-edit.php?id=' . $id);
        } catch (Throwable $ex) {
            error_log('product-edit: ' . $ex->getMessage());
            $errors[] = 'Não foi possível salvar o produto. Confira os dados e tente novamente.';
        }
    }

    $d = array_merge($d, $f, [
        'slug' => $slug,
        'image_path' => $newImage ?: ($p['image_path'] ?? ''),
        'image_url' => $f['image_url'],
    ]);
}

$isNew = !$p && !$id;
$title = $isNew ? 'Novo produto' : 'Editar produto';
$actions = $p
    ? '<a class="btn ghost" target="_blank" href="' . e(url('produto/' . $p['slug'])) . '">Ver produto ↗</a>'
    : '<a class="btn ghost" href="products.php">Voltar aos produtos</a>';
$previewSrc = ($d['image_path'] || $d['image_url']) ? product_img($d) : asset('images/placeholder.svg');
$priceValue = $d['price'] !== null && $d['price'] !== '' ? number_format((float)$d['price'], 2, '.', '') : '';
$oldPriceValue = $d['old_price'] !== null && $d['old_price'] !== '' ? number_format((float)$d['old_price'], 2, '.', '') : '';

require __DIR__ . '/_head.php';
?>
<?php if ($errors): ?>
<div class="alert alert-err"><?= implode('<br>', array_map('e', $errors)) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="blog-form">
<?= csrf_field() ?>
<div class="blog-layout">
  <div class="blog-main">
    <section class="box">
      <h2>Informações do produto</h2>
      <label class="fld">Nome do produto
        <input name="name" required maxlength="190" value="<?= e($d['name']) ?>" placeholder="Ex.: Headset Gamer sem fio">
      </label>
      <label class="fld">URL amigável (slug)
        <div class="slug-row"><span>/produto/</span><input name="slug" maxlength="210" value="<?= e($d['slug']) ?>" placeholder="gerado a partir do nome"></div>
        <small class="hint">Use letras minúsculas, números e hífens. O sistema evita URLs duplicadas.</small>
      </label>
      <label class="fld">Descrição curta
        <textarea name="short_description" rows="3" maxlength="300" placeholder="Resumo que aparece na página do produto."><?= e($d['short_description']) ?></textarea>
      </label>
      <label class="fld">Descrição completa
        <textarea name="description" rows="9" placeholder="Detalhes, características, compatibilidade e informações úteis para o comprador."><?= e($d['description']) ?></textarea>
      </label>
      <div class="f2">
        <label class="fld">Marca
          <input name="brand" maxlength="80" value="<?= e($d['brand']) ?>">
        </label>
        <label class="fld">Modelo
          <input name="model" maxlength="80" value="<?= e($d['model']) ?>">
        </label>
      </div>
    </section>

    <section class="box">
      <h2>Preço e promoção</h2>
      <div class="f3">
        <label class="fld">Preço atual (R$)
          <input type="number" name="price" min="0" max="99999999.99" step="0.01" value="<?= e($priceValue) ?>" placeholder="0,00">
        </label>
        <label class="fld">Preço anterior (R$)
          <input type="number" name="old_price" min="0" max="99999999.99" step="0.01" value="<?= e($oldPriceValue) ?>" placeholder="0,00">
        </label>
        <label class="fld">Texto promocional
          <input name="promo_text" maxlength="60" value="<?= e($d['promo_text']) ?>" placeholder="Ex.: 20% OFF">
        </label>
      </div>
      <p class="hint">O preço e a disponibilidade devem ser conferidos na loja parceira antes da compra.</p>
    </section>

    <section class="box">
      <h2>Imagem e link de afiliado</h2>
      <label class="fld">URL da imagem (HTTPS)
        <input type="url" name="image_url" value="<?= e($d['image_url']) ?>" placeholder="https://...">
      </label>
      <label class="fld">Enviar imagem do computador
        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif">
        <small class="hint">JPG, PNG, WEBP ou GIF, até 3 MB. A imagem enviada substitui a imagem principal atual.</small>
      </label>
      <?php if (!empty($d['image_path']) || !empty($d['image_url'])): ?>
      <label class="chk"><input type="checkbox" name="remove_image" value="1"> Remover imagem atual</label>
      <?php endif; ?>
      <label class="fld">Link de afiliado
        <input type="url" name="affiliate_url" value="<?= e($d['affiliate_url']) ?>" placeholder="https://loja-parceira.com/produto?...">
        <small class="hint">Este link será aberto pelo botão de oferta na página individual do produto.</small>
      </label>
    </section>

    <section class="box">
      <h2>SEO do produto</h2>
      <label class="fld">Título SEO
        <input name="seo_title" maxlength="160" value="<?= e($d['seo_title']) ?>" placeholder="Nome do produto + benefício | Loot Gamer">
      </label>
      <label class="fld">Meta description
        <textarea name="meta_description" rows="3" maxlength="255" placeholder="Resumo original e informativo da página do produto."><?= e($d['meta_description']) ?></textarea>
      </label>
    </section>
  </div>

  <aside class="blog-side">
    <section class="box">
      <h2>Publicação</h2>
      <div class="formgrid">
        <label>Categoria
          <select name="category_id">
            <option value="">Sem categoria</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (string)$d['category_id'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Loja afiliada
          <select name="store_id">
            <option value="">Sem loja vinculada</option>
            <?php foreach ($stores as $s): ?>
              <option value="<?= (int)$s['id'] ?>" <?= (string)$d['store_id'] === (string)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Status
          <select name="status">
            <?php foreach (['rascunho' => 'Rascunho', 'publicado' => 'Publicado', 'inativo' => 'Inativo'] as $v => $label): ?>
              <option value="<?= $v ?>" <?= $d['status'] === $v ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Ordem de exibição
          <input type="number" name="sort_order" value="<?= (int)$d['sort_order'] ?>">
        </label>
      </div>
      <hr style="border:0;border-top:1px solid #eef0f7;margin:14px 0">
      <div class="formgrid">
        <label class="chk"><input type="checkbox" name="is_featured" value="1" <?= !empty($d['is_featured']) ? 'checked' : '' ?>> Produto em destaque</label>
        <label class="chk"><input type="checkbox" name="is_promo" value="1" <?= !empty($d['is_promo']) ? 'checked' : '' ?>> Produto em promoção</label>
        <label class="chk"><input type="checkbox" name="is_recommended" value="1" <?= !empty($d['is_recommended']) ? 'checked' : '' ?>> Produto recomendado</label>
      </div>
      <div class="actionsbar" style="margin-top:16px">
        <button class="btn" type="submit">💾 Salvar produto</button>
        <a class="btn ghost" href="products.php">Cancelar</a>
      </div>
    </section>

    <section class="box">
      <h2>Imagem principal</h2>
      <div class="feat-prev"><img src="<?= e($previewSrc) ?>" alt="<?= e($d['name']) ?>"></div>
      <small class="hint">Pré-visualização da imagem cadastrada.</small>
    </section>
  </aside>
</div>
</form>
<?php require __DIR__ . '/_foot.php';