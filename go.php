<?php
// Redirecionamento controlado: o destino vem SOMENTE do banco (nunca de parâmetro do visitante).
require_once __DIR__ . '/includes/bootstrap.php';
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
$p = q("SELECT p.id, p.store_id, p.affiliate_url FROM products p WHERE p.id = ? AND p.status = 'publicado'", [$id])->fetch();
$dest = $p['affiliate_url'] ?? '';
$myHost = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$host = strtolower((string)parse_url($dest, PHP_URL_HOST));
if (!$p || !valid_http_url($dest) || $host === '' || $host === $myHost) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
$src = 'direto';
$ref = $_SERVER['HTTP_REFERER'] ?? '';
if ($ref && strtolower((string)parse_url($ref, PHP_URL_HOST)) === $myHost) $src = mb_substr((string)parse_url($ref, PHP_URL_PATH), 0, 110);
try { q('INSERT INTO clicks (product_id, store_id, source_page) VALUES (?,?,?)', [$p['id'], $p['store_id'], $src]); } catch (Throwable $e) { error_log('click: ' . $e->getMessage()); }
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('Location: ' . $dest, true, 302);
exit;
