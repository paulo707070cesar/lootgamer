<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/auth.php';

echo "<h2>Corrigindo lote 2 - imagens certas</h2>";

// Mapeamento correto slug -> arquivo existente na pasta 2026/11/
$map = [
    'controle-ps5-cosmic-red-oferta-2026' => '[STRIPPED 65 bytes].webp',
    'controle-ps4-sem-fio-bluetooth-redfin-barato' => '[STRIPPED 65 bytes].webp',
    'controle-gamesir-x5-lite-usb-c-barato' => '[STRIPPED 65 bytes].webp',
    'headset-gamer-havit-h2002d-oferta' => '[STRIPPED 65 bytes].webp',
    'headset-jbl-quantum-100m2-barato-2026' => 'uploads/blog/2026/11/headset-jbl-quantum-100m2-por-6aecb5f532e8f.webp',
];

foreach($map as $slugBase => $imgPath) {
    // Atualiza todos os posts que começam com esse slug base
    $posts = q("SELECT id, slug, featured_image FROM blog_posts WHERE slug LIKE ?", [$slugBase . '%'])->fetchAll();
    foreach($posts as $p) {
        q("UPDATE blog_posts SET featured_image=? WHERE id=?", [$imgPath, $p['id']]);
        echo "✅ ID {$p['id']} [{$p['slug']}] -> {$imgPath}<br>";
    }
}

echo "<hr>Feito! Agora Ctrl+F5 no blog e apaga esse arquivo.";