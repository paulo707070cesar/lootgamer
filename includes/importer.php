<?php
/**
 * Loot Gamer - importação e exportação da tabela de produtos (CSV).
 */

declare(strict_types=1);

/**
 * Colunas oficiais da tabela (mesmas do arquivo produtos.csv).
 *
 * @return array<string,string> chave => rótulo
 */
function xll_csv_columns(): array
{
    return [
        'id'            => 'ID',
        'titulo'        => 'Título',
        'descricao'     => 'Descrição',
        'categoria'     => 'Categoria',
        'marketplace'   => 'Marketplace',
        'selo'          => 'Selo',
        'preco'         => 'Preço',
        'preco_antigo'  => 'Preço antigo',
        'desconto'      => 'Desconto (%)',
        'avaliacao'     => 'Avaliação',
        'imagem'        => 'Imagem (URL)',
        'link_afiliado' => 'Link de afiliado',
        'secao_home'    => 'Seção da home',
        'ordem'         => 'Ordem',
        'slug'          => 'Slug',
    ];
}

function xll_csv_normalize_header(string $value): string
{
    $value = str_replace("\xEF\xBB\xBF", '', $value);
    $value = trim(mb_strtolower($value));
    $value = strtr($value, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', ' ' => '_', '-' => '_',
    ]);
    $value = preg_replace('/[^a-z0-9_]/', '', $value) ?? '';

    return $value;
}

/**
 * Aliases aceitos para cada coluna.
 *
 * @return array<string,string>
 */
function xll_csv_header_aliases(): array
{
    $aliases = [];
    foreach (array_keys(xll_csv_columns()) as $key) {
        $aliases[$key] = $key;
    }
    $aliases += [
        'codigo'      => 'id',
        'code'        => 'id',
        'title'       => 'titulo',
        'nome'        => 'titulo',
        'name'        => 'titulo',
        'description' => 'descricao',
        'category'    => 'categoria',
        'loja'        => 'marketplace',
        'store'       => 'marketplace',
        'badge'       => 'selo',
        'price'       => 'preco',
        'preco_antigo' => 'preco_antigo',
        'precoantigo' => 'preco_antigo',
        'old_price'   => 'preco_antigo',
        'oldprice'    => 'preco_antigo',
        'discount'    => 'desconto',
        'rating'      => 'avaliacao',
        'nota'        => 'avaliacao',
        'image'       => 'imagem',
        'imagem_url'  => 'imagem',
        'image_url'   => 'imagem',
        'url'         => 'link_afiliado',
        'link'        => 'link_afiliado',
        'affiliate'   => 'link_afiliado',
        'affiliate_url' => 'link_afiliado',
        'section'     => 'secao_home',
        'secao'       => 'secao_home',
        'position'    => 'ordem',
        'order'       => 'ordem',
        'ordem_home'  => 'ordem',
        'slug_url'    => 'slug',
    ];

    return $aliases;
}

function xll_csv_detect_delimiter(string $firstLine): string
{
    $best = ';';
    $bestCount = -1;
    foreach ([';', ',', "\t", '|'] as $delimiter) {
        $count = substr_count($firstLine, $delimiter);
        if ($count > $bestCount) {
            $bestCount = $count;
            $best = $delimiter;
        }
    }

    return $best;
}

/**
 * Lê o CSV e devolve as linhas normalizadas.
 *
 * @return array{rows:array<int,array<string,mixed>>,errors:array<int,string>,delimiter:string}
 */
function xll_csv_read(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException('Arquivo CSV não encontrado.');
    }
    $content = (string) file_get_contents($path);
    if (trim($content) === '') {
        throw new RuntimeException('O arquivo CSV está vazio.');
    }
    if (!mb_check_encoding($content, 'UTF-8')) {
        $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    }
    $content = str_replace("\xEF\xBB\xBF", '', $content);

    $handle = fopen('php://temp', 'r+');
    fwrite($handle, $content);
    rewind($handle);

    $firstLine = (string) fgets($handle);
    $delimiter = xll_csv_detect_delimiter($firstLine);
    rewind($handle);

    $aliases = xll_csv_header_aliases();
    $columns = array_keys(xll_csv_columns());
    $map = [];
    $header = fgetcsv($handle, 0, $delimiter);
    if (!is_array($header)) {
        fclose($handle);
        return ['rows' => [], 'errors' => ['Não foi possível ler o cabeçalho.'], 'delimiter' => $delimiter];
    }

    $normalized = array_map(static fn ($h) => xll_csv_normalize_header((string) $h), $header);
    $hasHeader = count(array_intersect($normalized, ['titulo', 'title', 'nome'])) > 0;
    if ($hasHeader) {
        foreach ($normalized as $index => $name) {
            $canonical = $aliases[$name] ?? null;
            if ($canonical !== null) {
                $map[$index] = $canonical;
            }
        }
    } else {
        // Sem cabeçalho: assume a ordem oficial das colunas.
        foreach ($columns as $index => $canonical) {
            $map[$index] = $canonical;
        }
        rewind($handle);
    }

    $rows = [];
    $errors = [];
    $lineNumber = $hasHeader ? 1 : 0;
    while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
        $lineNumber++;
        if (!is_array($data)) {
            continue;
        }
        $allEmpty = true;
        foreach ($data as $cell) {
            if (trim((string) $cell) !== '') {
                $allEmpty = false;
                break;
            }
        }
        if ($allEmpty) {
            continue;
        }

        $item = array_fill_keys($columns, '');
        foreach ($map as $index => $canonical) {
            $item[$canonical] = trim((string) ($data[$index] ?? ''));
        }

        if (($item['titulo'] ?? '') === '') {
            $errors[] = 'Linha ' . $lineNumber . ' ignorada: título vazio.';
            continue;
        }

        $id = trim((string) $item['id']);
        if ($id === '' || !preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $id)) {
            $id = bin2hex(random_bytes(12));
        }

        $price = xll_parse_price($item['preco']);
        $oldPrice = xll_parse_price($item['preco_antigo']);
        $discount = (int) preg_replace('/\D+/', '', $item['desconto'] ?? '') ?: 0;
        if ($discount <= 0 && $oldPrice > $price && $price > 0) {
            $discount = (int) round((1 - $price / $oldPrice) * 100);
        }
        $discount = max(0, min(99, $discount));
        $rating = xll_parse_rating($item['avaliacao']);

        $rows[] = [
            'id'            => $id,
            'titulo'        => (string) $item['titulo'],
            'descricao'     => (string) $item['descricao'],
            'categoria'     => (string) $item['categoria'],
            'marketplace'   => (string) $item['marketplace'],
            'selo'          => (string) $item['selo'],
            'preco'         => $price,
            'preco_antigo'  => $oldPrice,
            'desconto'      => $discount,
            'avaliacao'     => $rating,
            'imagem'        => (string) $item['imagem'],
            'link_afiliado' => (string) $item['link_afiliado'],
            'secao_home'    => (string) $item['secao_home'],
            'ordem'         => is_numeric($item['ordem']) ? (int) $item['ordem'] : count($rows),
            'slug'          => (string) (
                $item['slug'] !== '' && $item['slug'] !== $id
                    ? $item['slug']
                    : xll_generate_product_slug((string) $item['titulo'], $id)
            ),
        ];
    }

    fclose($handle);

    return ['rows' => $rows, 'errors' => $errors, 'delimiter' => $delimiter];
}

/**
 * Resumo antes de importar (não grava nada).
 *
 * @return array{total:int,novos:int,atualizados:int,erros:array<int,string>,amostra:array<int,array<string,mixed>>}
 */
function xll_import_preview(string $path): array
{
    $data = xll_csv_read($path);
    $ids = [];
    try {
        $ids = array_flip(array_map('strval', xll_db()->query('SELECT id FROM produtos')->fetchAll(PDO::FETCH_COLUMN)));
    } catch (Throwable) {
        $ids = [];
    }

    $novos = 0;
    $atualizados = 0;
    $seen = [];
    foreach ($data['rows'] as $row) {
        if (isset($ids[$row['id']])) {
            $atualizados++;
            continue;
        }
        if (!isset($seen[$row['id']])) {
            $novos++;
            $seen[$row['id']] = true;
        }
    }

    return [
        'total'       => count($data['rows']),
        'novos'       => $novos,
        'atualizados' => $atualizados,
        'erros'       => $data['errors'],
        'amostra'     => array_slice($data['rows'], 0, 8),
        'delimiter'   => $data['delimiter'],
    ];
}

/**
 * Importa o CSV para o MySQL.
 *
 * @param string $mode 'merge' (atualiza/adiciona) ou 'replace' (substitui tudo).
 * @return array<string,mixed>
 */
function xll_import_csv(string $path, string $mode = 'merge', string $filename = ''): array
{
    $mode = $mode === 'replace' ? 'replace' : 'merge';
    $data = xll_csv_read($path);
    if (!$data['rows']) {
        throw new RuntimeException('Nenhum produto válido encontrado no CSV.');
    }

    $pdo = xll_db();
    $existing = array_flip(array_map('strval', $pdo->query('SELECT id FROM produtos')->fetchAll(PDO::FETCH_COLUMN)));

    if ($mode === 'replace') {
        $pdo->exec('DELETE FROM produtos');
        $removidos = count($existing);
        $existing = [];
    } else {
        $removidos = 0;
    }

    $sql = 'INSERT INTO produtos
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
                slug = VALUES(slug)';

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare($sql);
        $inseridos = 0;
        $atualizados = 0;
        foreach ($data['rows'] as $row) {
            $isUpdate = isset($existing[$row['id']]);
            $stmt->execute([
                $row['id'],
                $row['titulo'],
                $row['descricao'],
                $row['categoria'],
                $row['marketplace'],
                $row['selo'],
                $row['preco'],
                $row['preco_antigo'],
                $row['desconto'],
                $row['avaliacao'],
                $row['imagem'],
                $row['link_afiliado'],
                $row['secao_home'],
                $row['ordem'],
                $row['slug'],
            ]);
            if ($isUpdate) {
                $atualizados++;
            } else {
                $inseridos++;
                $existing[$row['id']] = true;
            }
        }

        $log = $pdo->prepare(
            'INSERT INTO importacoes (arquivo, modo, total, inseridos, atualizados, removidos, erros)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $log->execute([
            $filename !== '' ? $filename : basename($path),
            $mode,
            count($data['rows']),
            $inseridos,
            $atualizados,
            $removidos,
            count($data['errors']),
        ]);

        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }

    return [
        'total'       => count($data['rows']),
        'inseridos'   => $inseridos,
        'atualizados' => $atualizados,
        'removidos'   => $removidos,
        'erros'       => $data['errors'],
        'modo'        => $mode,
    ];
}

function xll_export_csv(): string
{
    $rows = xll_db()
        ->query('SELECT * FROM produtos ORDER BY ordem ASC, id ASC')
        ->fetchAll();

    $handle = fopen('php://temp', 'r+');
    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, array_keys(xll_csv_columns()), ';', '"', '\\');
    foreach ($rows as $row) {
        $p = xll_normalize_product($row);
        fputcsv($handle, [
            $p['id'],
            $p['titulo'],
            $p['descricao'],
            $p['categoria'],
            $p['marketplace'],
            $p['selo'],
            $p['preco'] > 0 ? xll_price_to_csv($p['preco']) : '',
            $p['preco_antigo'] > 0 ? xll_price_to_csv($p['preco_antigo']) : '',
            $p['desconto'] > 0 ? (string) $p['desconto'] : '',
            $p['avaliacao'] !== null ? number_format($p['avaliacao'], 1, ',', '.') : '',
            $p['imagem'],
            $p['link_afiliado'],
            $p['secao_home'],
            (string) $p['ordem'],
            $p['slug'],
        ], ';', '"', '\\');
    }
    rewind($handle);
    $csv = (string) stream_get_contents($handle);
    fclose($handle);

    return $csv;
}

function xll_export_json(): string
{
    $rows = xll_db()
        ->query('SELECT * FROM produtos ORDER BY ordem ASC, id ASC')
        ->fetchAll();

    return (string) json_encode([
        'site'          => xll_setting('site_name', 'Loot Gamer'),
        'exportado_em'  => date('c'),
        'total'         => count($rows),
        'produtos'      => array_map('xll_normalize_product', $rows),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function xll_csv_model(): string
{
    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, array_keys(xll_csv_columns()), ';', '"', '\\');
    fputcsv($handle, [
        'exemplo000000000000000001',
        'Teclado Gamer Exemplo RGB',
        'Descrição completa do produto.',
        'Periféricos',
        'Mercado Livre',
        'MAIS VENDIDO',
        '199,90',
        '249,90',
        '20',
        '4,9',
        'https://exemplo.com/imagem.webp',
        'https://exemplo.com/produto',
        'Destaque',
        '0',
        'teclado-gamer-exemplo',
    ], ';', '"', '\\');
    rewind($handle);
    $csv = (string) stream_get_contents($handle);
    fclose($handle);

    return $csv;
}

/**
 * Salva o CSV enviado pelo formulário em data/imports.
 */
function xll_save_uploaded_csv(array $file): string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(xll_upload_error($error));
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    $name = (string) ($file['name'] ?? 'produtos.csv');
    $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['csv', 'txt'], true)) {
        throw new RuntimeException('Envie um arquivo .csv (ou .txt separado por ponto e vírgula).');
    }
    if (!is_uploaded_file($tmp)) {
        throw new RuntimeException('Upload inválido.');
    }
    if ((int) ($file['size'] ?? 0) > 12 * 1024 * 1024) {
        throw new RuntimeException('O CSV deve ter no máximo 12 MB.');
    }

    $dir = XLL_ROOT . '/data/imports';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $destination = $dir . '/import-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.csv';
    if (!move_uploaded_file($tmp, $destination)) {
        throw new RuntimeException('Não foi possível salvar o CSV enviado.');
    }
    @chmod($destination, 0644);

    return $destination;
}

/**
 * @return array<int,array<string,mixed>>
 */
function xll_import_history(int $limit = 8): array
{
    try {
        $stmt = xll_db()->query('SELECT * FROM importacoes ORDER BY id DESC LIMIT ' . max(1, $limit));

        return $stmt->fetchAll() ?: [];
    } catch (Throwable) {
        return [];
    }
}

function xll_send_download(string $filename, string $content, string $contentType = 'text/csv; charset=UTF-8'): never
{
    $filename = preg_replace('/[^A-Za-z0-9._\-]/', '-', $filename) ?? 'arquivo.csv';
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) strlen($content));
    header('Cache-Control: no-store');
    echo $content;
    exit;
}
