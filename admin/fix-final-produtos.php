<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/auth.php';

$map = [
    '/blog/controle-ps5-cosmic-red-oferta-2026' => 'uploads/blog/2026/11/controle-【entity-sony¦canonical_name=sony】-dualsense-ps5-co-6aecb5f35726e.webp',
    '/blog/controle-ps4-sem-fio-bluetooth-redfin-barato' => '[STRIPPED 65 bytes].webp',
    '/blog/controle-gamesir-x5-lite-usb-c-barato' => '[STRIPPED 65 bytes].webp',
    '/blog/headset-gamer-havit-h2002d-oferta' => '[STRIPPED 65 bytes].webp',
    '/blog/headset-jbl-quantum-100m2-barato-2026' => 'uploads/blog/2026/11/headset-jbl-quantum-100m2-por-6aecb5f532e8f.webp',
];

foreach($map as $aff => $img) {
    // Atualiza TODOS os produtos com esse affiliate_url (tem duplicados)
    q("UPDATE products SET image_path=?, image_url=? WHERE affiliate_url=?", [$img, $img, $aff]);
    echo "✅ {$aff} -> {$img}<br>";
    
    // Verifica se arquivo existe
    $full = __DIR__ . '/../' . $img;
    echo (file_exists($full) ? "   Arquivo EXISTE: " . filesize($full) . " bytes<br>" : "   ❌ Arquivo NÃO EXISTE<br>");
}
echo "<hr>Feito!";