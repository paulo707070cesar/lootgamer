<?php
require_once __DIR__ . '/../includes/auth.php';
$me = require_admin();

// Exporta o catálogo completo, sem limitar à página ou aos filtros da listagem.
$rows = q("SELECT p.*, c.name AS category_name, s.name AS store_name
           FROM products p
           LEFT JOIN categories c ON c.id = p.category_id
           LEFT JOIN affiliate_stores s ON s.id = p.store_id
           ORDER BY p.id ASC")->fetchAll(PDO::FETCH_ASSOC);

$filename = 'loot-gamer-produtos-' . date('Y-m-d-His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM para abrir corretamente acentos no Excel.
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'wb');
$headers = [
    'ID_Banco', 'Nome_Produto', 'Slug', 'Categoria', 'Loja', 'Marca', 'Modelo',
    'Descricao_Curta', 'Descricao_Completa', 'Preco_Atual', 'Preco_Anterior',
    'Texto_Promocao', 'Imagem_Path_Banco', 'Image_URL_Banco', 'Link_Afiliado',
    'Destaque_Home', 'Em_Promocao', 'Recomendado', 'Status', 'Ordem',
    'Titulo_SEO', 'Meta_Descricao'
];
fputcsv($out, $headers, ';', '"', '');

foreach ($rows as $r) {
    $line = [
        $r['id'],
        $r['name'],
        $r['slug'],
        $r['category_name'] ?? '',
        $r['store_name'] ?? '',
        $r['brand'] ?? '',
        $r['model'] ?? '',
        $r['short_description'] ?? '',
        $r['description'] ?? '',
        $r['price'] ?? '',
        $r['old_price'] ?? '',
        $r['promo_text'] ?? '',
        $r['image_path'] ?? '',
        $r['image_url'] ?? '',
        $r['affiliate_url'] ?? '',
        (int)$r['is_featured'],
        (int)$r['is_promo'],
        (int)$r['is_recommended'],
        $r['status'] ?? 'rascunho',
        (int)$r['sort_order'],
        $r['seo_title'] ?? '',
        $r['meta_description'] ?? ''
    ];
    fputcsv($out, $line, ';', '"', '');
}
fclose($out);
exit;
