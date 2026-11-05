<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
$t = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 60));
if (mb_strlen($t) < 2) { echo '[]'; exit; }
$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $t) . '%';
$rows = q("SELECT name, slug, price FROM products WHERE status='publicado' AND name LIKE ? ORDER BY sort_order, id DESC LIMIT 6", [$like])->fetchAll();
echo json_encode(array_map(fn($r) => ['name' => $r['name'], 'url' => url('produto/' . $r['slug'])], $rows), JSON_UNESCAPED_UNICODE);
