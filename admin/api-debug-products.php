<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

try {
    // tenta todos os includes possíveis
    $paths = [
        __DIR__.'/../includes/config.php',
        __DIR__.'/../includes/db.php',
        __DIR__.'/../config/database.php'
    ];
    foreach($paths as $p){
        if(file_exists($p)) require_once $p;
    }
    if(!isset($pdo) && isset($db)) $pdo=$db;
    if(!isset($pdo)) throw new Exception('PDO não achou - config não carregou');

    $total = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $q = $pdo->query("SELECT id, name, slug, price, old_price, affiliate_url, is_featured, status FROM products ORDER BY id DESC LIMIT 10");
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok'=>true,'total'=>$total,'last10'=>$rows], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

} catch(Throwable $e){
    echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()]);
}