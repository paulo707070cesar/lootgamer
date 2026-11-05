<?php
require_once __DIR__ . '/includes/bootstrap.php';
$listTitle = 'Novidades e Lançamentos'; $pageTitle = 'Novidades e lançamentos';
$metaDesc = 'Os produtos mais recentes publicados no site.';
$baseWhere = ''; $baseParams = [];
$defaultOrder = 'p.created_at DESC, p.id DESC'; $breadcrumb = [['Novidades', null]];
include __DIR__ . '/includes/listing.php';
