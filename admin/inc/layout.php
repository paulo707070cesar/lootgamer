<?php
/**
 * Loot Gamer - estrutura do painel administrativo.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes-site/bootstrap.php';
xll_require_installed();
xll_require_admin();

/**
 * Redireciona dentro do painel com mensagem flash.
 */
function adm_redirect(string $path, string $message = '', string $type = 'success'): never
{
    if ($message !== '') {
        xll_flash($type, $message);
    }
    xll_redirect('admin/' . ltrim($path, '/'));
}

function adm_text(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function adm_int(string $key, int $default = 0): int
{
    return (int) ($_POST[$key] ?? $default);
}

function adm_checked(string $key): bool
{
    return !empty($_POST[$key]);
}

/**
 * @return array<int|string,mixed>
 */
function adm_array(string $key): array
{
    $value = $_POST[$key] ?? [];

    return is_array($value) ? $value : [];
}

/**
 * Executa um upload opcional e devolve [caminho, erro].
 *
 * @return array{0:string,1:string}
 */
function adm_upload(string $field, string $prefix, string $current = ''): array
{
    try {
        return [xll_handle_upload($field, $prefix, $current), ''];
    } catch (Throwable $error) {
        return [$current, $error->getMessage()];
    }
}

function adm_icon(string $name): string
{
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect>',
        'home'      => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><path d="M9 22V12h6v10"></path>',
        'box'       => '<path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path>',
        'plus'      => '<path d="M12 5v14"></path><path d="M5 12h14"></path>',
        'import'    => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="m7 10 5 5 5-5"></path><path d="M12 15V3"></path>',
        'palette'   => '<circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"></path>',
        'footer'    => '<rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 15h18"></path><path d="M7 19h4"></path>',
        'user'      => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
        'external'  => '<path d="M15 3h6v6"></path><path d="M10 14 21 3"></path><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="m16 17 5-5-5-5"></path><path d="M21 12H9"></path>',
        'image'     => '<rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="9" cy="9" r="2"></circle><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>',
        'menu'      => '<path d="M4 6h16"></path><path d="M4 12h16"></path><path d="M4 18h16"></path>',
        'save'      => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><path d="M17 21v-8H7v8"></path><path d="M7 3v5h8"></path>',
        'eye'       => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle>',
        'chart'     => '<path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path>',
        'mail'      => '<rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>',
        'seo'       => '<circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path><path d="M11 8v6"></path><path d="M8 11h6"></path>',
    ];
    $path = $paths[$name] ?? $paths['dashboard'];

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

function adm_header(string $title, string $active = 'dashboard', string $subtitle = ''): void
{
    $siteName = (string) xll_setting('site_name', 'Loot Gamer');
    $logo = xll_media_url((string) xll_setting('logo', 'assets/img/logo.webp'));
    $userName = xll_admin_name();
    $initials = mb_strtoupper(mb_substr($userName !== '' ? $userName : 'A', 0, 1));
    $nav = [
        'Geral' => [
            ['dashboard', 'Painel', 'index.php', 'dashboard'],
            ['home', 'Página inicial', 'home.php', 'home'],
        ],
        'Catálogo' => [
            ['produtos', 'Produtos', 'produtos.php', 'box'],
            ['novo', 'Adicionar produto', 'produto-form.php', 'plus'],
            ['importar', 'Importar / Exportar', 'importar.php', 'import'],
            ['analytics', 'Analytics de cliques', 'analytics.php', 'chart'],
        ],
        'Site' => [
            ['aparencia', 'Aparência (cores, fontes e logo)', 'aparencia.php', 'palette'],
            ['empresa', 'Rodapé, menu e páginas', 'empresa.php', 'footer'],
            ['mensagens', 'Mensagens de contato', 'mensagens.php', 'mail'],
        ],
        'Conta' => [
            ['conta', 'Minha conta', 'conta.php', 'user'],
        ],
    ];
    $flashes = xll_flashes();
    ?><!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($title) ?> — Painel <?= e($siteName) ?></title>
    <link rel="icon" type="image/png" href="<?= e(xll_media_url((string) xll_setting('favicon', 'assets/img/favicon-192.png'))) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if (($fontsUrl = xll_google_fonts_url()) !== ''): ?>
        <link rel="stylesheet" href="<?= e($fontsUrl) ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(xll_asset('assets/css/admin.css')) ?>">
</head>
<body class="adm-body">
<div class="adm-shell">
    <aside class="adm-sidebar" id="adm-sidebar">
        <a class="adm-brand" href="<?= e(xll_url('admin/index.php')) ?>">
            <img src="<?= e($logo) ?>" alt="<?= e($siteName) ?>">
            <span class="adm-brand-badge">Admin</span>
        </a>
        <nav class="adm-nav" aria-label="Menu do painel">
            <?php foreach ($nav as $group => $items): ?>
                <div class="adm-nav-group">
                    <div class="adm-nav-label"><?= e($group) ?></div>
                    <?php foreach ($items as $item): ?>
                        <?php [$key, $label, $file, $icon] = $item; ?>
                        <a class="adm-nav-link<?= $active === $key ? ' is-active' : '' ?>" href="<?= e(xll_url('admin/' . $file)) ?>">
                            <?= adm_icon($icon) ?><span><?= e($label) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <div class="adm-nav-group">
                <div class="adm-nav-label">Atalhos</div>
                <a class="adm-nav-link" href="<?= e(xll_url('index.php')) ?>" target="_blank" rel="noopener">
                    <?= adm_icon('external') ?><span>Ver site publicado</span>
                </a>
            </div>
        </nav>
        <div class="adm-sidebar-foot">
            <form method="post" action="<?= e(xll_url('admin/logout.php')) ?>">
                <?= xll_csrf_field() ?>
                <button type="submit" class="adm-btn adm-btn-secondary"><?= adm_icon('logout') ?> Sair do painel</button>
            </form>
            <div style="font-size:10.5px;color:#5d7d83;text-align:center">Loot Gamer · v<?= e(XLL_VERSION) ?></div>
        </div>
    </aside>

    <div class="adm-main">
        <header class="adm-topbar">
            <button type="button" class="adm-menu-btn" aria-label="Abrir menu"><?= adm_icon('menu') ?></button>
            <div>
                <div class="adm-topbar-title"><?= e($title) ?></div>
                <div class="adm-topbar-crumbs">Painel<?= $title !== 'Painel' ? ' / ' . e($title) : '' ?></div>
            </div>
            <div class="adm-topbar-right">
                <a class="adm-btn adm-btn-secondary adm-btn-sm" href="<?= e(xll_url('index.php')) ?>" target="_blank" rel="noopener"><?= adm_icon('eye') ?> Ver site</a>
                <span class="adm-user">
                    <span class="adm-user-avatar"><?= e($initials) ?></span>
                    <?= e($userName) ?>
                </span>
            </div>
        </header>

        <div class="adm-content">
            <?php foreach ($flashes as $flash): ?>
                <?php $type = in_array($flash['type'], ['success', 'error', 'warning'], true) ? $flash['type'] : 'success'; ?>
                <div class="adm-flash adm-flash-<?= e($type) ?>" role="status">
                    <div style="flex:1"><?= e($flash['message']) ?></div>
                    <button type="button" data-flash-close style="border:0;background:transparent;color:inherit;cursor:pointer;font-size:16px;line-height:1" aria-label="Fechar">×</button>
                </div>
            <?php endforeach; ?>
            <?php if ($subtitle !== ''): ?>
                <div style="margin-top:-6px">
                    <h1 style="font-size:22px"><?= e($subtitle) ?></h1>
                </div>
            <?php endif; ?>
<?php
}

function adm_footer(): void
{

    /* // sync no rodape: espelha alteracoes salvas no site publico */
    if (is_file(dirname(__DIR__, 2) . '/includes/lg-sync-legacy.php')) {
        require_once dirname(__DIR__, 2) . '/includes/lg-sync-legacy.php';
        if (function_exists('lg_sync_legacy')) {
            try { lg_sync_legacy(); } catch (Throwable) {}
        }
    }
    ?>
        </div><!-- .adm-content -->
    </div><!-- .adm-main -->
</div><!-- .adm-shell -->
<script src="<?= e(xll_asset('assets/js/admin.js')) ?>" defer></script>
</body>
</html>
<?php
}

/**
 * Campo de imagem usado nos formulários (URL + upload + preview).
 */
function adm_image_field(string $urlName, string $urlValue, string $filePrefix, string $label, string $help = ''): void
{
    ?>
    <div class="adm-field adm-field-full">
        <label class="adm-label"><?= e($label) ?></label>
        <div class="adm-image-field">
            <div class="adm-image-preview">
                <?php if ($urlValue !== ''): ?>
                    <img src="<?= e(xll_media_url($urlValue)) ?>" alt="">
                <?php else: ?>
                    Nenhuma imagem
                <?php endif; ?>
            </div>
            <div class="adm-image-fields">
                <input class="adm-input" type="text" name="<?= e($urlName) ?>" value="<?= e($urlValue) ?>" placeholder="assets/img/... ou https://...">
                <input class="adm-file" type="file" name="<?= e($filePrefix) ?>" accept="image/*" data-preview="1">
                <?php if ($help !== ''): ?><span class="adm-help"><?= e($help) ?></span><?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
