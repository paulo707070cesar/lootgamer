<?php
require_once __DIR__ . '/includes/bootstrap.php';
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug'] ?? ''));
$cat = q('SELECT * FROM categories WHERE slug = ? AND is_active = 1', [$slug])->fetch();
if (!$cat) { include __DIR__ . '/404.php'; exit; }
$ids = cat_ids($slug);
$listTitle = $cat['name'];
$pageTitle = $cat['seo_title'] ?: $cat['name'];
$metaDesc = $cat['meta_description'] ?: ($cat['description'] ?: 'Ofertas de ' . $cat['name']);
$intro = $cat['description'] ?? '';
$baseWhere = 'p.category_id IN (' . in_clause($ids) . ')'; $baseParams = $ids;
$defaultOrder = 'p.sort_order ASC, p.id DESC';
$breadcrumb = [[$cat['name'], null]];
include __DIR__ . '/includes/listing.php';
