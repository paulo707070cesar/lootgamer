<?php
/**
 * Loot Gamer - sair do painel.
 */

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
xll_require_installed();

if (xll_is_post()) {
    xll_require_csrf();
    xll_admin_logout();
    xll_flash('success', 'Você saiu do painel.');
}

xll_redirect('admin/login.php');
