<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/auth.php';

$folder = __DIR__ . '/../uploads/blog/2026/11/';
$files = glob($folder . '*.webp');

echo "<h2>Corrigindo imagens do blog</h2>";
echo "Arquivos na pasta 2026/11/: " . count($files) . "<br><ul>";
foreach($files as $f) echo "<li>" . basename($f) . "</li>";
echo "</ul><hr>";

$posts = q("SELECT id, slug, title, featured_image FROM blog_posts ORDER BY id DESC")->fetchAll();

foreach($posts as $post) {
    $current = $post['featured_image'] ?? '';
    $fullPath = __DIR__ . '/../' . ltrim($current, '/');
    
    if (!file_exists($fullPath) || empty($current)) {
        // Procura arquivo que contenha parte do slug
        $found = null;
        $slugPart = substr($post['slug'], 0, 20);
        foreach($files as $f) {
            if (stripos(basename($f), $slugPart) !== false) {
                $found = 'uploads/blog/2026/11/' . basename($f);
                break;
            }
        }
        // Se não achou por slug, tenta por palavras do título
        if (!$found) {
            $words = explode(' ', strtolower($post['title']));
            foreach($files as $f) {
                foreach($words as $w) {
                    if (strlen($w) > 4 && stripos(basename($f), $w) !== false) {
                        $found = 'uploads/blog/2026/11/' . basename($f);
                        break 2;
                    }
                }
            }
        }
        
        if ($found) {
            q("UPDATE blog_posts SET featured_image=? WHERE id=?", [$found, $post['id']]);
            echo "✅ ID {$post['id']} [{$post['slug']}] -> CORRIGIDO para {$found}<br>";
        } else {
            echo "❌ ID {$post['id']} [{$post['slug']}] -> SEM ARQUIVO (atual: {$current})<br>";
        }
    } else {
        echo "✔️ ID {$post['id']} [{$post['slug']}] -> OK ({$current})<br>";
    }
}
echo "<hr>Feito! Apague esse arquivo depois.";