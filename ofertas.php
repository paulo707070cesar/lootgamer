<?php
require_once __DIR__ . '/includes/bootstrap.php';
$listTitle = 'Ofertas'; $pageTitle = 'Ofertas em games, consoles e tecnologia';
$metaDesc = 'Produtos em promoção selecionados em lojas parceiras.';
$baseWhere = '(p.is_promo = 1 OR (p.old_price IS NOT NULL AND p.price IS NOT NULL AND p.old_price > p.price))'; $baseParams = [];
$defaultOrder = 'p.sort_order ASC, p.id DESC'; $breadcrumb = [['Ofertas', null]];
include __DIR__ . '/includes/listing.php';
