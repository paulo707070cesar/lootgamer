<?php
require_once __DIR__ . '/../includes/auth.php';
$me = require_admin();

function lg_cell_col(string $ref): int {
    preg_match('/^[A-Z]+/', strtoupper($ref), $m);
    $n = 0;
    foreach (str_split($m[0] ?? 'A') as $ch) $n = $n * 26 + ord($ch) - 64;
    return $n - 1;
}
function lg_xlsx_rows(string $file): array {
    if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) {
        throw new RuntimeException('Este servidor não possui ZipArchive/SimpleXML habilitado para ler XLSX. Salve a planilha como CSV UTF-8 e envie novamente.');
    }
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) throw new RuntimeException('Não foi possível abrir o arquivo XLSX.');
    $read = static function($z, $path) {
        $v = $z->getFromName($path);
        if ($v === false) throw new RuntimeException('Planilha XLSX inválida ou incompleta.');
        return simplexml_load_string($v);
    };
    $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $wb = $read($zip, 'xl/workbook.xml');
    $rels = $read($zip, 'xl/_rels/workbook.xml.rels')->children('http://schemas.openxmlformats.org/package/2006/relationships');
    $sheetPath = '';
    $sheets = $wb->children($ns)->sheets->children($ns);
    foreach ($sheets as $sh) {
        if ((string)$sh['name'] === 'Produtos') {
            $rid = (string)$sh->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            foreach ($rels->Relationship as $rel) {
                if ((string)$rel['Id'] === $rid) {
                    $target = ltrim((string)$rel['Target'], '/');
                    $sheetPath = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                    break 2;
                }
            }
        }
    }
    if (!$sheetPath) { $zip->close(); throw new RuntimeException('Não encontrei a aba Produtos dentro do arquivo.'); }
    $shared = [];
    if ($zip->locateName('xl/sharedStrings.xml') !== false) {
        $ss = $read($zip, 'xl/sharedStrings.xml')->children($ns);
        foreach ($ss->si as $si) {
            $txt = '';
            $si->registerXPathNamespace('m', $ns);
            foreach ($si->xpath('.//m:t') ?: [] as $t) $txt .= (string)$t;
            $shared[] = $txt;
        }
    }
    $sheet = $read($zip, $sheetPath)->children($ns);
    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $line = [];
        foreach ($row->c as $cell) {
            $idx = lg_cell_col((string)$cell['r']);
            $type = (string)$cell['t'];
            $v = '';
            if ($type === 's') $v = $shared[(int)$cell->v] ?? '';
            elseif ($type === 'inlineStr') {
                $cell->registerXPathNamespace('m', $ns);
                foreach ($cell->xpath('.//m:t') ?: [] as $t) $v .= (string)$t;
            } else $v = isset($cell->v) ? (string)$cell->v : '';
            $line[$idx] = $v;
        }
        if ($line) {
            $max = max(array_keys($line));
            $ordered = [];
            for ($i=0; $i<=$max; $i++) $ordered[] = $line[$i] ?? '';
            $rows[] = $ordered;
        }
    }
    $zip->close();
    return $rows;
}
function lg_csv_rows(string $file): array {
    $fh = fopen($file, 'rb');
    if (!$fh) throw new RuntimeException('Não foi possível ler o CSV.');
    $first = fgets($fh); rewind($fh);
    $delim = substr_count((string)$first, ';') >= substr_count((string)$first, ',') ? ';' : ',';
    if (substr_count((string)$first, "\t") > substr_count((string)$first, $delim)) $delim = "\t";
    $rows = [];
    while (($r = fgetcsv($fh, 0, $delim)) !== false) $rows[] = $r;
    fclose($fh);
    if (isset($rows[0][0])) $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$rows[0][0]);
    return $rows;
}
function lg_bool($v): int {
    $s = mb_strtolower(trim((string)$v));
    return in_array($s, ['1','sim','yes','true','publicado','publicar','destaque','ativo'], true) ? 1 : 0;
}
function lg_money($v): ?string {
    $s = trim((string)$v);
    if ($s === '') return null;
    $s = preg_replace('/[^\d,.\-]/', '', $s);
    if (str_contains($s, ',')) $s = str_replace('.', '', $s);
    $s = str_replace(',', '.', $s);
    return is_numeric($s) ? number_format((float)$s, 2, '.', '') : null;
}
function lg_url_or_blank($v): string {
    $v = trim((string)$v);
    return $v !== '' && valid_http_url($v) ? $v : '';
}
function lg_map_rows(array $raw): array {
    if (count($raw) < 2) throw new RuntimeException('A aba Produtos não contém linhas de produtos.');
    $headers = array_map(static fn($v) => trim((string)$v), array_shift($raw));
    $out = [];
    foreach ($raw as $lineNo => $line) {
        $r = [];
        foreach ($headers as $i => $h) if ($h !== '') $r[$h] = $line[$i] ?? '';
        $name = trim((string)($r['Nome_Produto'] ?? ''));
        if ($name === '') continue;
        $statusRaw = mb_strtolower(trim((string)($r['Status'] ?? $r['Status_Banco'] ?? 'rascunho')));
        $status = in_array($statusRaw, ['publicar','publicado','ativo','sim'], true) ? 'publicado' : (in_array($statusRaw, ['inativo','desativado'], true) ? 'inativo' : 'rascunho');
        $imageUrl = lg_url_or_blank($r['Image_URL_Banco'] ?? '');
        if (!$imageUrl) $imageUrl = lg_url_or_blank($r['Imagem_Principal_Drive'] ?? '');
        if (!$imageUrl) $imageUrl = lg_url_or_blank($r['Link_Imagem_Principal_Mercado_Livre'] ?? '');
        $imagePath = trim((string)($r['Imagem_Path_Banco'] ?? ''));
        if (preg_match('~^https?://~i', $imagePath)) {
            if (!$imageUrl) $imageUrl = lg_url_or_blank($imagePath);
            $imagePath = '';
        }
        if ($imagePath !== '' && (str_contains($imagePath, '..') || str_starts_with($imagePath, '/'))) $imagePath = '';
        $slug = slugify(trim((string)($r['Slug'] ?? '')) ?: $name);
        $out[] = [
            'external_id' => (int)($r['ID_Banco'] ?? 0),
            'name' => mb_substr($name, 0, 190),
            'slug' => mb_substr($slug, 0, 210),
            'category' => mb_substr(trim((string)($r['Categoria'] ?? '')), 0, 120),
            'store' => mb_substr(trim((string)($r['Loja'] ?? '')), 0, 120),
            'brand' => mb_substr(trim((string)($r['Marca'] ?? '')), 0, 80),
            'model' => mb_substr(trim((string)($r['Modelo'] ?? '')), 0, 80),
            'short_description' => mb_substr(trim((string)($r['Descricao_Curta'] ?? '')), 0, 300),
            'description' => trim((string)($r['Descricao_Completa'] ?? '')),
            'price' => lg_money($r['Preco_Atual'] ?? ''),
            'old_price' => lg_money($r['Preco_Anterior'] ?? ''),
            'promo_text' => mb_substr(trim((string)($r['Texto_Promocao'] ?? '')), 0, 60),
            'image_path' => $imagePath,
            'image_url' => $imageUrl,
            'affiliate_url' => lg_url_or_blank($r['Link_Afiliado'] ?? '') ?: lg_url_or_blank($r['Link_Afiliado_Original_Banco'] ?? ''),
            'is_featured' => lg_bool($r['Destaque_Home'] ?? ''),
            'is_promo' => lg_bool($r['Em_Promocao'] ?? ''),
            'is_recommended' => lg_bool($r['Recomendado'] ?? ''),
            'status' => $status,
            'sort_order' => (int)($r['Ordem'] ?? 0),
            'seo_title' => mb_substr(trim((string)($r['Titulo_SEO'] ?? '')), 0, 160),
            'meta_description' => mb_substr(trim((string)($r['Meta_Descricao'] ?? '')), 0, 255),
        ];
    }
    if (!$out) throw new RuntimeException('Nenhum produto válido foi encontrado. Confira a coluna Nome_Produto.');
    if (count($out) > 1000) throw new RuntimeException('O limite por importação é de 1.000 produtos.');
    return $out;
}
function lg_find_or_create_category(string $name): ?int {
    if ($name === '') return null;
    $r = q('SELECT id FROM categories WHERE name = ? LIMIT 1', [$name])->fetch();
    if ($r) return (int)$r['id'];
    $base = slugify($name); $slug = $base; $i = 2;
    while (q('SELECT id FROM categories WHERE slug = ?', [$slug])->fetch()) $slug = $base . '-' . $i++;
    q('INSERT INTO categories (name,slug,is_main,is_active,sort_order) VALUES (?,?,0,1,999)', [$name,$slug]);
    return (int)db()->lastInsertId();
}
function lg_find_or_create_store(string $name): ?int {
    if ($name === '') return null;
    $r = q('SELECT id FROM affiliate_stores WHERE name = ? LIMIT 1', [$name])->fetch();
    if ($r) return (int)$r['id'];
    q('INSERT INTO affiliate_stores (name,is_active,notes) VALUES (?,1,?)', [$name,'Criada automaticamente durante importação de produtos. Completar dados do programa de afiliados no Admin → Lojas/Afiliados.']);
    return (int)db()->lastInsertId();
}
function lg_import_products(array $rows): array {
    $created = 0; $updated = 0; $errors = [];
    db()->beginTransaction();
    try {
        foreach ($rows as $i => $r) {
            try {
                $categoryId = lg_find_or_create_category($r['category']);
                $storeId = lg_find_or_create_store($r['store']);
                $existing = null;
                if ($r['external_id'] > 0) $existing = q('SELECT * FROM products WHERE id=?', [$r['external_id']])->fetch();
                if (!$existing) $existing = q('SELECT * FROM products WHERE slug=?', [$r['slug']])->fetch();
                $slug = $r['slug'];
                if ($existing && $slug !== $existing['slug']) {
                    $collision = q('SELECT id FROM products WHERE slug=? AND id<>?', [$slug,$existing['id']])->fetch();
                    if ($collision) $slug = $existing['slug'];
                } elseif (!$existing) {
                    $base = $slug; $n = 2;
                    while (q('SELECT id FROM products WHERE slug=?', [$slug])->fetch()) $slug = $base . '-' . $n++;
                }
                $imagePath = $r['image_path'] ?: ($existing['image_path'] ?? null);
                $imageUrl = $r['image_url'] ?: ($existing['image_url'] ?? null);
                $vals = [
                    $categoryId, $storeId, $r['name'], $slug,
                    $r['short_description'] ?: null, $r['description'] ?: null, $r['brand'] ?: null, $r['model'] ?: null,
                    $r['price'], $r['old_price'], $r['promo_text'] ?: null, $imagePath, $imageUrl,
                    $r['affiliate_url'] ?: ($existing['affiliate_url'] ?? null),
                    $r['is_featured'], $r['is_promo'], $r['is_recommended'], $r['status'], $r['sort_order'],
                    $r['seo_title'] ?: null, $r['meta_description'] ?: null
                ];
                if ($existing) {
                    q('UPDATE products SET category_id=?,store_id=?,name=?,slug=?,short_description=?,description=?,brand=?,model=?,price=?,old_price=?,promo_text=?,image_path=?,image_url=?,affiliate_url=?,is_featured=?,is_promo=?,is_recommended=?,status=?,sort_order=?,seo_title=?,meta_description=? WHERE id=?', array_merge($vals, [(int)$existing['id']]));
                    $updated++;
                } else {
                    q('INSERT INTO products (category_id,store_id,name,slug,short_description,description,brand,model,price,old_price,promo_text,image_path,image_url,affiliate_url,is_featured,is_promo,is_recommended,status,sort_order,seo_title,meta_description) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', $vals);
                    $created++;
                }
            } catch (Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ' (' . $r['name'] . '): ' . $e->getMessage();
            }
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        throw $e;
    }
    log_admin('importacao_produtos', "Criados: $created; atualizados: $updated; erros: " . count($errors));
    return [$created, $updated, $errors];
}

$title = 'Importar produtos por planilha';
$actions = '<a class="btn ghost" href="products.php">← Voltar aos produtos</a>';
$errors = []; $preview = $_SESSION['lg_product_import_preview'] ?? [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'cancel') {
        unset($_SESSION['lg_product_import_preview']);
        flash('ok', 'Pré-visualização cancelada.');
        redirect('import-products.php');
    }
    if ($action === 'import') {
        $rows = $_SESSION['lg_product_import_preview'] ?? [];
        if (!$rows) { $errors[] = 'A pré-visualização expirou. Envie a planilha novamente.'; }
        else {
            try {
                [$created,$updated,$importErrors] = lg_import_products($rows);
                unset($_SESSION['lg_product_import_preview']);
                flash('ok', "Importação concluída: $created produtos criados e $updated atualizados.");
                if ($importErrors) $_SESSION['lg_product_import_errors'] = $importErrors;
                redirect('import-products.php?done=1');
            } catch (Throwable $e) { error_log('import-products: '.$e->getMessage()); $errors[] = 'Falha ao importar. Nenhuma alteração foi confirmada.'; }
        }
    } elseif ($action === 'preview') {
        unset($_SESSION['lg_product_import_preview']);
        if (empty($_FILES['sheet']['tmp_name']) || ($_FILES['sheet']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'Selecione a planilha para enviar.';
        } elseif (($_FILES['sheet']['size'] ?? 0) > 10 * 1024 * 1024) {
            $errors[] = 'O arquivo deve ter no máximo 10 MB.';
        } else {
            $ext = strtolower(pathinfo($_FILES['sheet']['name'], PATHINFO_EXTENSION));
            try {
                if ($ext === 'xlsx') $raw = lg_xlsx_rows($_FILES['sheet']['tmp_name']);
                elseif ($ext === 'csv') $raw = lg_csv_rows($_FILES['sheet']['tmp_name']);
                else throw new RuntimeException('Formato não suportado. Envie XLSX ou CSV UTF-8.');
                $preview = lg_map_rows($raw);
                $_SESSION['lg_product_import_preview'] = $preview;
            } catch (Throwable $e) { $errors[] = $e->getMessage(); }
        }
    }
}
$preview = $_SESSION['lg_product_import_preview'] ?? [];
require __DIR__ . '/_head.php';
?>
<?php if ($errors): ?><div class="alert alert-err"><?= implode('<br>', array_map('e',$errors)) ?></div><?php endif; ?>
<?php if (!empty($_GET['done']) && !empty($_SESSION['lg_product_import_errors'])): ?>
<section class="box"><h2>Linhas que precisam de revisão</h2><ul><?php foreach ($_SESSION['lg_product_import_errors'] as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?></ul><?php unset($_SESSION['lg_product_import_errors']); ?></section>
<?php endif; ?>
<section class="box">
<h2>Importar catálogo do Loot Gamer</h2>
<p>Envie a planilha Excel gerada para o catálogo. O sistema lê a aba <b>Produtos</b>, mostra uma prévia e só grava os dados depois da sua confirmação.</p>
<ul>
<li>Formatos aceitos: <b>.xlsx</b> e <b>.csv UTF-8</b>; tamanho máximo 10 MB; até 1.000 produtos por importação.</li>
<li>Se o produto já existir, o sistema tenta atualizar pelo ID do banco informado na coluna <b>ID_Banco</b> e, em seguida, pelo <b>Slug</b>. Caso contrário, cria um novo produto.</li>
<li>Categorias e lojas que não existirem serão criadas automaticamente. Complete os dados da loja em <b>Lojas/Afiliados</b>.</li>
<li>Preço, descrição, imagem principal, link de afiliado, promoção, destaque e campos SEO são importados quando preenchidos.</li>
<li>Imagens externas precisam ter URL HTTPS pública; caminhos locais só funcionam se o arquivo já existir no servidor.</li>
</ul>
<form method="post" enctype="multipart/form-data" class="formgrid">
<?= csrf_field() ?><input type="hidden" name="action" value="preview">
<label>Planilha de produtos (.xlsx ou .csv)<input type="file" name="sheet" accept=".xlsx,.csv" required></label>
<button class="btn">📄 Ler planilha e visualizar</button>
</form>
</section>
<?php if ($preview): ?>
<section class="box">
<h2>Pré-visualização: <?= count($preview) ?> produtos</h2>
<p>Confira os primeiros registros. Nenhum produto foi gravado ainda.</p>
<div class="scroll"><table class="list"><thead><tr><th>Produto</th><th>Categoria</th><th>Loja</th><th>Preço</th><th>Imagem</th><th>Afiliado</th><th>Status</th></tr></thead><tbody>
<?php foreach (array_slice($preview,0,12) as $r): ?><tr>
<td><b><?= e($r['name']) ?></b><br><small><?= e($r['slug']) ?></small></td>
<td><?= e($r['category'] ?: '—') ?></td><td><?= e($r['store'] ?: '—') ?></td>
<td><?= $r['price'] !== null ? money($r['price']) : '—' ?></td>
<td><?= ($r['image_url'] || $r['image_path']) ? 'Informada' : 'Sem imagem' ?></td>
<td><?= $r['affiliate_url'] ? 'Informado' : 'Ausente' ?></td><td><?= e($r['status']) ?></td>
</tr><?php endforeach; ?>
</tbody></table></div>
<?php if (count($preview)>12): ?><p class="hint">Exibindo os primeiros 12 de <?= count($preview) ?> registros.</p><?php endif; ?>
<form method="post" class="actionsbar"><?= csrf_field() ?><button class="btn" name="action" value="import" data-confirm="Confirmar importação? Produtos existentes poderão ser atualizados.">✅ Confirmar e importar <?= count($preview) ?> produtos</button><button class="btn ghost" name="action" value="cancel">Cancelar</button></form>
</section>
<?php endif; ?>
<?php require __DIR__ . '/_foot.php';