<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/auth.php';

echo "<h1>PRODUTOS HOME</h1>";
$products = q("SELECT id, slug, name, affiliate_url, image_path, image_url, old_price, price, status, is_featured FROM products WHERE is_featured=1 ORDER BY id DESC LIMIT 20")->fetchAll();

foreach($products as $p) {
    echo "<hr><b>ID {$p['id']}</b> - {$p['name']}<br>";
    echo "slug: {$p['slug']}<br>";
    echo "affiliate_url: {$p['affiliate_url']}<br>";
    echo "image_path: {$p['image_path']}<br>";
    echo "image_url: {$p['image_url']}<br>";
    echo "status: {$p['status']}<br>";
}

echo "<hr><h1>ARQUIVOS EM 2026/11/</h1>";
$folder = __DIR__ . '/../uploads/blog/2026/11/';
$files = glob($folder . '*');
foreach($files as $f) {
    echo basename($f) . " - " . (file_exists($f) ? "EXISTE" : "NÃO EXISTE") . " - " . filesize($f) . " bytes<br>";
}

echo "<hr><h1>TESTE CARD.PHP ATUAL</h1>";
echo file_get_contents(__DIR__ . '/../includes/card.php');