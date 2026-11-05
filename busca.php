<?php
require_once __DIR__ . '/includes/bootstrap.php';
$term = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 80));
$listTitle = $term !== '' ? 'Resultados para “' . $term . '”' : 'Busca';
$pageTitle = 'Busca: ' . $term; $metaDesc = 'Resultados da busca no ' . setting('site_name', 'Loot Gamer');
$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term) . '%';
$baseWhere = $term === '' ? '0=1' : '(p.name LIKE ? OR p.brand LIKE ? OR p.model LIKE ? OR p.short_description LIKE ? OR c.name LIKE ?)';
$baseParams = $term === '' ? [] : [$like, $like, $like, $like, $like];
$defaultOrder = 'p.sort_order ASC, p.id DESC'; $breadcrumb = [['Busca', null]];
$robotsNoindex = true;
include __DIR__ . '/includes/listing.php';
