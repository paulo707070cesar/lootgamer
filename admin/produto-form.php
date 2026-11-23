<?php
/**
 * Loot Gamer - adicionar/editar produto.
 */

declare(strict_types=1);

require __DIR__ . '/inc/layout.php';

$pdo = xll_db();
$idParam = trim((string) ($_GET['id'] ?? ''));
$duplicarParam = trim((string) ($_GET['duplicar'] ?? ''));
$novo = isset($_GET['novo']);

$isEdit = false;
$originalId = '';
$errors = [];

$categorias = $pdo->query("SELECT DISTINCT categoria FROM produtos WHERE categoria <> '' ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
$marketplaces = $pdo->query("SELECT DISTINCT marketplace FROM produtos WHERE marketplace <> '' ORDER BY marketplace")->fetchAll(PDO::FETCH_COLUMN);
$secoes = $pdo->query("SELECT DISTINCT secao_home FROM produtos WHERE secao_home <> '' ORDER BY secao_home")->fetchAll(PDO::FETCH_COLUMN);
$selos = ['MAIS VENDIDO', 'RECOMENDADO', 'SUPER DESCONTO', 'OFERTA RELÂMPAGO', 'MENOR PREÇO', 'NOVIDADE', 'EXCLUSIVO', ''];

$nextOrdem = (int) $pdo->query('SELECT COALESCE(MAX(ordem), 0) + 1 FROM produtos')->fetchColumn();

$product = [
    'id'            => bin2hex(random_bytes(12)),
    'titulo'        => '',
    'descricao'     => '',
    'categoria'     => 'Periféricos',
    'marketplace'   => 'Mercado Livre',
    'selo'          => '',
    'preco'         => 0,
    'preco_antigo'  => 0,
    'desconto'      => 0,
    'avaliacao'     => null,
    'imagem'        => '',
    'link_afiliado' => '',
    'secao_home'    => '',
    'ordem'         => $nextOrdem,
    'slug'          => '',
];

if ($idParam !== '') {
    $found = xll_product_by_id($idParam);
    if ($found) {
        $product = $found;
        $isEdit = true;
        $originalId = $found['id'];
    } else {
        xll_flash('error', 'Produto não encontrado.');
    }
} elseif ($duplicarParam !== '') {
    $found = xll_product_by_id($duplicarParam);
    if ($found) {
        $product = $found;
        $product['id'] = bin2hex(random_bytes(12));
        $product['titulo'] = $found['titulo'] . ' (cópia)';
        $product['slug'] = xll_generate_product_slug($product['titulo'], $product['id']);
    }
}

/* ---------------------------------------------------------------------- */
/* Ações POST                                                              */
/* ---------------------------------------------------------------------- */
if (xll_is_post()) {
    xll_require_csrf();
    $acao = adm_text('acao');

    if ($acao === 'excluir') {
        $id = adm_text('id');
        if ($id !== '') {
            $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = ?');
            $stmt->execute([$id]);
            adm_redirect('produtos.php', 'Produto excluído.', 'success');
        }
        adm_redirect('produtos.php', 'Produto inválido.', 'error');
    }

    if ($acao === 'salvar') {
        $postedId = adm_text('id');
        $postedId = preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $postedId) === 1 ? $postedId : bin2hex(random_bytes(12));
        $modo = adm_text('modo');
        $isEdit = $modo === 'editar';

        $price = xll_parse_price($_POST['preco'] ?? '');
        $oldPrice = xll_parse_price($_POST['preco_antigo'] ?? '');
        $discount = (int) preg_replace('/\D+/', '', (string) ($_POST['desconto'] ?? '')) ?: 0;
        if ($discount <= 0 && $oldPrice > $price && $price > 0) {
            $discount = (int) round((1 - $price / $oldPrice) * 100);
        }
        $discount = max(0, min(99, $discount));
        $rating = xll_parse_rating($_POST['avaliacao'] ?? '');

        $image = trim((string) ($_POST['imagem'] ?? ''));
        [$image, $uploadError] = adm_upload('imagem_upload', 'produto', $image);
        if ($uploadError !== '') {
            $errors[] = 'Imagem: ' . $uploadError;
        }

        $product = [
            'id'            => $postedId,
            'titulo'        => trim((string) ($_POST['titulo'] ?? '')),
            'descricao'     => trim((string) ($_POST['descricao'] ?? '')),
            'categoria'     => trim((string) ($_POST['categoria'] ?? '')),
            'marketplace'   => trim((string) ($_POST['marketplace'] ?? '')),
            'selo'          => trim((string) ($_POST['selo'] ?? '')),
            'preco'         => $price,
            'preco_antigo'  => $oldPrice,
            'desconto'      => $discount,
            'avaliacao'     => $rating,
            'imagem'        => $image,
            'link_afiliado' => trim((string) ($_POST['link_afiliado'] ?? '')),
            'secao_home'    => trim((string) ($_POST['secao_home'] ?? '')),
            'ordem'         => (int) ($_POST['ordem'] ?? 0),
            'slug'          => trim((string) ($_POST['slug'] ?? '')),
        ];

        if ($product['titulo'] === '') {
            $errors[] = 'Informe o título do produto.';
        }
        if ($product['preco'] <= 0) {
            $errors[] = 'Informe um preço válido (ex.: 199,90).';
        }
        if ($product['slug'] === '') {
            $product['slug'] = xll_generate_product_slug($product['titulo'], $product['id']);
        }
        /* Garante slug único (usado na URL amigável /produto/{slug}). */
        $slug = $product['slug'];
        $suffix = 2;
        do {
            $slugCheck = $pdo->prepare('SELECT id FROM produtos WHERE slug = ? AND id <> ? LIMIT 1');
            $slugCheck->execute([$slug, $product['id']]);
            if (!$slugCheck->fetchColumn()) {
                break;
            }
            $slug = $product['slug'] . '-' . $suffix;
            $suffix++;
        } while ($suffix < 50);
        $product['slug'] = $slug;

        /* Se é um produto novo, o ID não pode existir. */
        if (!$isEdit) {
            $check = $pdo->prepare('SELECT id FROM produtos WHERE id = ? LIMIT 1');
            $check->execute([$product['id']]);
            if ($check->fetchColumn()) {
                $product['id'] = bin2hex(random_bytes(12));
            }
        } else {
            $check = $pdo->prepare('SELECT id FROM produtos WHERE id = ? LIMIT 1');
            $check->execute([$product['id']]);
            if (!$check->fetchColumn()) {
                /* O ID original foi alterado: move o registro. */
                $pdo->prepare('UPDATE produtos SET id = ? WHERE id = ?')->execute([$product['id'], $originalId]);
            }
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                'INSERT INTO produtos
                    (id, titulo, descricao, categoria, marketplace, selo, preco, preco_antigo, desconto, avaliacao,
                     imagem, link_afiliado, secao_home, ordem, slug)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    titulo = VALUES(titulo),
                    descricao = VALUES(descricao),
                    categoria = VALUES(categoria),
                    marketplace = VALUES(marketplace),
                    selo = VALUES(selo),
                    preco = VALUES(preco),
                    preco_antigo = VALUES(preco_antigo),
                    desconto = VALUES(desconto),
                    avaliacao = VALUES(avaliacao),
                    imagem = VALUES(imagem),
                    link_afiliado = VALUES(link_afiliado),
                    secao_home = VALUES(secao_home),
                    ordem = VALUES(ordem),
                    slug = VALUES(slug)'
            );
            $stmt->execute([
                $product['id'],
                $product['titulo'],
                $product['descricao'],
                $product['categoria'],
                $product['marketplace'],
                $product['selo'],
                $product['preco'],
                $product['preco_antigo'],
                $product['desconto'],
                $product['avaliacao'],
                $product['imagem'],
                $product['link_afiliado'],
                $product['secao_home'],
                $product['ordem'],
                $product['slug'],
            ]);

            if (!empty($_POST['continuar'])) {
                xll_flash('success', 'Produto salvo. Adicione outro abaixo.');
                xll_redirect('admin/produto-form.php?novo=1');
            }
            adm_redirect('produtos.php', 'Produto salvo com sucesso.');
        }
    }
}

$pageTitle = $isEdit ? 'Editar produto' : 'Adicionar produto';
adm_header($pageTitle, $isEdit ? 'produtos' : 'novo');
?>

<?php if ($errors): ?>
    <div class="adm-flash adm-flash-error">
        <div style="flex:1">
            <?php foreach ($errors as $error): ?>
                <div>• <?= e($error) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(xll_url('admin/produto-form.php')) ?>" enctype="multipart/form-data">
    <?= xll_csrf_field() ?>
    <input type="hidden" name="acao" value="salvar">
    <input type="hidden" name="modo" value="<?= $isEdit ? 'editar' : 'novo' ?>">
    <input type="hidden" name="id" value="<?= e($product['id']) ?>">
    <input type="hidden" name="original_id" value="<?= e($originalId) ?>">

    <div class="adm-card">
        <div class="adm-form-head" style="margin-bottom:18px">
            <div>
                <h1><?= $isEdit ? 'Editar produto' : 'Adicionar produto' ?></h1>
                <div class="adm-card-sub">
                    <?= $isEdit ? 'Atualize os dados da oferta. Campos iguais aos da tabela/CSV.' : 'Preencha os campos abaixo. O produto aparece no site assim que salvar.' ?>
                </div>
            </div>
            <div class="adm-actions">
                <?php if ($isEdit): ?>
                    <a class="adm-btn adm-btn-secondary" href="<?= e(xll_product_url($product)) ?>" target="_blank" rel="noopener"><?= adm_icon('eye') ?> Ver no site</a>
                <?php endif; ?>
                <a class="adm-btn adm-btn-secondary" href="<?= e(xll_url('admin/produtos.php')) ?>">Voltar</a>
                <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar produto</button>
            </div>
        </div>

        <div class="adm-grid">
            <div class="adm-field adm-field-full">
                <label class="adm-label" for="titulo">Título <span class="req">*</span></label>
                <input class="adm-input" id="titulo" type="text" name="titulo" value="<?= e($product['titulo']) ?>" required maxlength="255" placeholder="Ex.: Teclado Gamer Mecânico RGB">
            </div>

            <div class="adm-field adm-field-full">
                <label class="adm-label" for="descricao">Descrição</label>
                <textarea class="adm-textarea" id="descricao" name="descricao" rows="5" placeholder="Descrição completa do produto..."><?= e($product['descricao']) ?></textarea>
                <span class="adm-help">Texto exibido na página do produto. Aceita quebras de linha.</span>
            </div>

            <div class="adm-field">
                <label class="adm-label" for="categoria">Categoria</label>
                <input class="adm-input" id="categoria" type="text" name="categoria" value="<?= e($product['categoria']) ?>" list="lista-categorias" placeholder="Periféricos">
                <datalist id="lista-categorias">
                    <?php foreach ($categorias as $value): ?><option value="<?= e($value) ?>"><?php endforeach; ?>
                </datalist>
            </div>

            <div class="adm-field">
                <label class="adm-label" for="marketplace">Marketplace</label>
                <input class="adm-input" id="marketplace" type="text" name="marketplace" value="<?= e($product['marketplace']) ?>" list="lista-marketplaces" placeholder="Mercado Livre">
                <datalist id="lista-marketplaces">
                    <?php foreach ($marketplaces as $value): ?><option value="<?= e($value) ?>"><?php endforeach; ?>
                </datalist>
            </div>

            <div class="adm-field">
                <label class="adm-label" for="selo">Selo</label>
                <input class="adm-input" id="selo" type="text" name="selo" value="<?= e($product['selo']) ?>" list="lista-selos" placeholder="MAIS VENDIDO">
                <datalist id="lista-selos">
                    <?php foreach ($selos as $value): ?>
                        <?php if ($value !== ''): ?><option value="<?= e($value) ?>"><?php endif; ?>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="adm-field">
                <label class="adm-label" for="avaliacao">Avaliação (0 a 5)</label>
                <input class="adm-input" id="avaliacao" type="number" name="avaliacao" min="0" max="5" step="0.1" value="<?= $product['avaliacao'] !== null ? e(number_format((float) $product['avaliacao'], 1, '.', '')) : '' ?>" placeholder="4,9">
            </div>
        </div>
    </div>

    <div class="adm-card">
        <div class="adm-card-head">
            <div><div class="adm-card-title"><?= adm_icon('tag') ?> Preços e oferta</div></div>
        </div>
        <div class="adm-grid adm-grid-4">
            <div class="adm-field">
                <label class="adm-label" for="preco">Preço <span class="req">*</span></label>
                <input class="adm-input" id="preco" type="text" name="preco" value="<?= $product['preco'] > 0 ? e(xll_price_to_csv($product['preco'])) : '' ?>" placeholder="199,90" data-auto-discount-price>
                <span class="adm-help">Formato brasileiro: 1.234,56</span>
            </div>
            <div class="adm-field">
                <label class="adm-label" for="preco_antigo">Preço antigo</label>
                <input class="adm-input" id="preco_antigo" type="text" name="preco_antigo" value="<?= $product['preco_antigo'] > 0 ? e(xll_price_to_csv($product['preco_antigo'])) : '' ?>" placeholder="249,90" data-auto-discount-old>
            </div>
            <div class="adm-field">
                <label class="adm-label" for="desconto">Desconto (%)</label>
                <input class="adm-input" id="desconto" type="number" name="desconto" min="0" max="99" value="<?= (int) $product['desconto'] ?>" data-auto-discount-target>
                <span class="adm-help">Calculado automaticamente pelo preço antigo.</span>
            </div>
            <div class="adm-field">
                <label class="adm-label" for="link_afiliado">Link de afiliado</label>
                <input class="adm-input" id="link_afiliado" type="text" name="link_afiliado" value="<?= e($product['link_afiliado']) ?>" placeholder="https://produto.mercadolivre.com.br/...">
                <span class="adm-help">Destino do botão COMPRAR (target _blank, rel sponsored).</span>
            </div>
        </div>
    </div>

    <div class="adm-card">
        <div class="adm-card-head">
            <div><div class="adm-card-title"><?= adm_icon('image') ?> Imagem do produto</div></div>
        </div>
        <div class="adm-grid">
            <div class="adm-field adm-field-full">
                <div class="adm-image-field">
                    <div class="adm-image-preview" style="width:200px;height:120px">
                        <?php if ($product['imagem'] !== ''): ?>
                            <img src="<?= e(xll_media_url($product['imagem'])) ?>" alt="">
                        <?php else: ?>
                            Nenhuma imagem
                        <?php endif; ?>
                    </div>
                    <div class="adm-image-fields">
                        <label class="adm-label" for="imagem">URL da imagem</label>
                        <input class="adm-input" id="imagem" type="text" name="imagem" value="<?= e($product['imagem']) ?>" placeholder="https://... ou assets/img/...">
                        <label class="adm-label">Ou envie uma imagem</label>
                        <input class="adm-file" type="file" name="imagem_upload" accept="image/*" data-preview="1">
                        <span class="adm-help">Recomendado: imagem quadrada (500×500) com fundo branco. JPG, PNG, WEBP, GIF, AVIF ou SVG.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="adm-card">
        <div class="adm-card-head">
            <div><div class="adm-card-title"><?= adm_icon('box') ?> Organização</div></div>
        </div>
        <div class="adm-grid adm-grid-4">
            <div class="adm-field">
                <label class="adm-label" for="secao_home">Seção da home</label>
                <select class="adm-select" id="secao_home" name="secao_home">
                    <option value="" <?= $product['secao_home'] === '' ? 'selected' : '' ?>>— Não exibir na home —</option>
                    <?php foreach (['Destaque', 'Populares', 'Mais vendidos'] as $value): ?>
                        <option value="<?= e($value) ?>" <?= $product['secao_home'] === $value ? 'selected' : '' ?>><?= e($value) ?></option>
                    <?php endforeach; ?>
                    <?php if ($product['secao_home'] !== '' && !in_array($product['secao_home'], ['Destaque', 'Populares', 'Mais vendidos'], true)): ?>
                        <option value="<?= e($product['secao_home']) ?>" selected><?= e($product['secao_home']) ?></option>
                    <?php endif; ?>
                </select>
                <span class="adm-help">Controla em qual seção da home o produto aparece.</span>
            </div>
            <div class="adm-field">
                <label class="adm-label" for="ordem">Ordem</label>
                <input class="adm-input" id="ordem" type="number" name="ordem" value="<?= (int) $product['ordem'] ?>">
                <span class="adm-help">Menor número aparece primeiro.</span>
            </div>
            <div class="adm-field">
                <label class="adm-label" for="slug">Slug</label>
                <input class="adm-input" id="slug" type="text" name="slug" value="<?= e($product['slug']) ?>" placeholder="gerado automaticamente">
                <span class="adm-help">URL amigável: /produto/<?= e($product['slug'] !== '' ? $product['slug'] : 'slug') ?></span>
            </div>
            <div class="adm-field">
                <label class="adm-label">ID do produto</label>
                <input class="adm-input" type="text" value="<?= e($product['id']) ?>" readonly>
                <span class="adm-help">Gerado automaticamente e usado como chave da tabela.</span>
            </div>
        </div>
    </div>

    <div class="adm-actions" style="justify-content:flex-end">
        <?php if ($isEdit): ?>
            <button type="button" class="adm-btn adm-btn-danger" onclick="if(confirm('Excluir este produto? Essa ação não pode ser desfeita.')){document.getElementById('form-excluir').submit();}">Excluir produto</button>
        <?php endif; ?>
        <a class="adm-btn adm-btn-secondary" href="<?= e(xll_url('admin/produtos.php')) ?>">Cancelar</a>
        <button type="submit" name="continuar" value="1" class="adm-btn adm-btn-secondary">Salvar e adicionar outro</button>
        <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar produto</button>
    </div>
</form>

<?php if ($isEdit): ?>
<form method="post" action="<?= e(xll_url('admin/produto-form.php')) ?>" id="form-excluir" style="display:none">
    <?= xll_csrf_field() ?>
    <input type="hidden" name="acao" value="excluir">
    <input type="hidden" name="id" value="<?= e($product['id']) ?>">
</form>
<?php endif; ?>

<?php adm_footer(); ?>
