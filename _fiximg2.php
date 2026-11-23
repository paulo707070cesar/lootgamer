<?php
declare(strict_types=1);
require __DIR__."/includes/bootstrap.php";
header("Content-Type: application/json");
$pdo = xll_db();
$pdo->exec("UPDATE produtos SET imagem = REPLACE(imagem, '/uploads/assets/', 'assets/') WHERE imagem LIKE '/uploads/assets/%'");
echo json_encode(["ok"=>true,"exemplos"=>$pdo->query("SELECT imagem FROM produtos WHERE imagem <> "" LIMIT 4")->fetchAll(PDO::FETCH_COLUMN)]);
@unlink(__FILE__);