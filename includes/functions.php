<?php
/**
 * XLL Gaming Store - funções auxiliares (front-end e painel).
 */

declare(strict_types=1);

/* ====================================================================== */
/* Escapes e URLs                                                          */
/* ====================================================================== */

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function xll_is_external(string $url): bool
{
    return (bool) preg_match('~^(https?:)?//|^(mailto|tel):|^#|^data:~i', trim($url));
}

/**
 * URLs amigáveis estão ativas? (pode ser desligado em Aparência)
 */
function xll_pretty_urls(): bool
{
    return (string) xll_setting('pretty_urls', '1') !== '0';
}

/**
 * Converte um texto em slug (sem acentos, minúsculo e com hífens).
 */
function xll_slugify(string $text): string
{
    $text = trim(mb_strtolower($text));
    $text = strtr($text, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n', 'ý' => 'y',
    ]);
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
    $text = trim($text, '-');

    return $text !== '' ? $text : 'item';
}

/**
 * Gera um slug amigável para o produto, com sufixo do ID para não repetir.
 */
function xll_generate_product_slug(string $title, string $id): string
{
    $base = xll_slugify($title);
    if (mb_strlen($base) > 70) {
        $base = rtrim(mb_substr($base, 0, 70), '-');
    }
    $suffix = strtolower(substr(preg_replace('/[^A-Za-z0-9]/', '', $id) ?? '', -6));

    return $base . ($suffix !== '' ? '-' . $suffix : '');
}

/**
 * Monta uma URL interna (respeitando instalação em subpasta).
 *
 * Com URLs amigáveis ativas, traduz:
 * - ofertas.php          -> /ofertas (ou /ofertas/{categoria})
 * - produto.php?id=...   -> /produto/{slug}
 * - pagina.php?p=sobre   -> /sobre
 * - comparar.php         -> /comparar
 * - go.php?id=...        -> /ir/{id}
 * - sitemap.php          -> /sitemap.xml
 * - robots.php           -> /robots.txt
 */
function xll_url(string $path = '', array $query = []): string
{
    $path = trim($path);
    if ($path !== '' && xll_is_external($path)) {
        return $path;
    }

    // Aceita chamadas antigas com query embutida (ex.: pagina.php?p=contato).
    if (str_contains($path, '?')) {
        [$path, $embeddedQuery] = explode('?', $path, 2);
        $embedded = [];
        parse_str($embeddedQuery, $embedded);
        $query = array_merge($embedded, $query);
    }
    $path = ltrim($path, '/');
    $route = null;

    if (xll_pretty_urls()) {
        switch ($path) {
            case 'index.php':
                $route = '';
                break;

            case 'ofertas.php':
                $categoria = trim((string) ($query['categoria'] ?? ''));
                unset($query['categoria']);
                $route = 'ofertas' . ($categoria !== '' ? '/' . rawurlencode(xll_slugify($categoria)) : '');
                break;

            case 'produto.php':
                $slug = trim((string) ($query['slug'] ?? ''));
                $id = trim((string) ($query['id'] ?? ''));
                unset($query['slug'], $query['id']);
                if ($slug !== '' || $id !== '') {
                    $route = 'produto/' . rawurlencode($slug !== '' ? $slug : $id);
                }
                break;

            case 'pagina.php':
                $page = trim((string) ($query['p'] ?? ''));
                unset($query['p']);
                if ($page !== '') {
                    $route = rawurlencode($page);
                }
                break;

            case 'comparar.php':
                $route = 'comparar';
                break;

            case 'go.php':
                $id = trim((string) ($query['id'] ?? ''));
                unset($query['id']);
                if ($id !== '') {
                    $route = 'ir/' . rawurlencode($id);
                }
                break;

            case 'sitemap.php':
                $route = 'sitemap.xml';
                break;

            case 'robots.php':
                $route = 'robots.txt';
                break;
        }
    }

    if ($route !== null) {
        $url = XLL_BASE . $route;
    } else {
        $url = XLL_BASE . $path;
    }
    if ($query) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
    }

    return $url;
}

/**
 * URL canônica do site (com domínio), usada em canonical/OG/sitemap.
 */
function xll_site_url(): string
{
    $configured = trim((string) xll_setting('site_url', ''));
    if ($configured !== '') {
        return rtrim($configured, '/') . '/';
    }
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

    return ($isHttps ? 'https' : 'http') . '://' . $host . (XLL_BASE === '/' ? '/' : XLL_BASE);
}

function xll_absolute_url(string $path = '', array $query = []): string
{
    $path = trim($path);
    if ($path !== '' && xll_is_external($path)) {
        return $path;
    }
    $url = xll_url($path, $query);
    if (XLL_BASE !== '/' && str_starts_with($url, XLL_BASE)) {
        $url = substr($url, strlen(XLL_BASE));
    }
    $url = ltrim($url, '/');

    return rtrim(xll_site_url(), '/') . '/' . $url;
}

function xll_asset(string $path): string
{
    $url = xll_url($path);
    $arquivo = XLL_ROOT . '/' . ltrim($path, '/');
    if (is_file($arquivo)) {
        $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($arquivo);
    }
    return $url;
}

/**
 * URL de uma imagem salva no banco: aceita URL absoluta, data URI,
 * caminho já iniciando com / ou caminho relativo da aplicação.
 */
function xll_media_url(?string $path, string $fallback = 'assets/img/placeholder.svg'): string
{
    $path = trim((string) $path);
    if ($path === '') {
        $path = $fallback;
    }
    if (xll_is_external($path) || str_starts_with($path, '/')) {
        return $path;
    }

    return XLL_BASE . ltrim($path, '/');
}

function xll_redirect(string $path, array $query = []): never
{
    $url = xll_is_external($path) ? $path : xll_url($path, $query);
    header('Location: ' . $url);
    exit;
}

function xll_current_script(): string
{
    if (!empty($_SERVER['XLL_ROUTE_SCRIPT'])) {
        return basename((string) $_SERVER['XLL_ROUTE_SCRIPT']);
    }
    $script = (string) ($_SERVER['SCRIPT_FILENAME'] ?? '');
    if ($script !== '') {
        return basename($script);
    }

    return basename((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
}

/**
 * Verifica escrita de verdade em uma pasta (is_writable pode falhar em
 * alguns ambientes com sandbox).
 */
function xll_dir_writable(string $dir): bool
{
    if (!is_dir($dir)) {
        return false;
    }
    $probe = rtrim($dir, '/\\') . '/.write-test-' . bin2hex(random_bytes(4));
    $ok = @file_put_contents($probe, 'ok');
    if ($ok !== false) {
        @unlink($probe);

        return true;
    }

    return false;
}

function xll_is_post(): bool
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
}

/* ====================================================================== */
/* Sessão / flash / CSRF                                                   */
/* ====================================================================== */

function xll_flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * @return array<int,array{type:string,message:string}>
 */
function xll_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return is_array($flashes) ? $flashes : [];
}

function xll_csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_csrf'];
}

function xll_csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(xll_csrf_token()) . '">';
}

function xll_csrf_valid(): bool
{
    $token = (string) ($_POST['_csrf'] ?? '');

    return $token !== '' && hash_equals((string) ($_SESSION['_csrf'] ?? ''), $token);
}

function xll_require_csrf(): void
{
    if (!xll_csrf_valid()) {
        http_response_code(419);
        exit('Sessão expirada. Volte e tente novamente.');
    }
}

/* ====================================================================== */
/* Configurações salvas no MySQL                                           */
/* ====================================================================== */

function xll_settings_cache_refresh(): void
{
    $GLOBALS['XLL_SETTINGS_CACHE'] = null;
}

/**
 * @return array<string,string>
 */
function xll_settings_all(): array
{
    if (isset($GLOBALS['XLL_SETTINGS_CACHE'])) {
        return $GLOBALS['XLL_SETTINGS_CACHE'];
    }

    $settings = [];
    try {
        $stmt = xll_db()->query('SELECT chave, valor FROM configuracoes');
        foreach ($stmt->fetchAll() as $row) {
            $settings[(string) $row['chave']] = (string) ($row['valor'] ?? '');
        }
    } catch (Throwable) {
        $settings = [];
    }

    return $GLOBALS['XLL_SETTINGS_CACHE'] = $settings;
}

/**
 * Mescla recursivamente os padrões com o que foi salvo.
 * Listas (arrays numéricos) são substituídas por inteiro.
 */
function xll_merge_defaults(mixed $defaults, mixed $saved): mixed
{
    if (!is_array($defaults)) {
        return $saved;
    }
    if (!is_array($saved)) {
        return $defaults;
    }

    $defaultsIsList = array_is_list($defaults);
    $savedIsList    = array_is_list($saved);
    if ($defaultsIsList || $savedIsList) {
        return $saved;
    }

    $result = $defaults;
    foreach ($saved as $key => $value) {
        $result[$key] = array_key_exists($key, $defaults)
            ? xll_merge_defaults($defaults[$key], $value)
            : $value;
    }

    return $result;
}

function xll_defaults(): array
{
    static $defaults = null;
    if ($defaults === null) {
        $defaults = require XLL_ROOT . '/includes/defaults.php';
    }

    return $defaults;
}

/**
 * Lê uma configuração. Arrays são decodificados de JSON.
 */
function xll_setting(string $key, mixed $default = null): mixed
{
    $defaults = xll_defaults();
    $fallback = func_num_args() >= 2 ? $default : ($defaults[$key] ?? null);

    $all = xll_settings_all();
    if (!array_key_exists($key, $all)) {
        return $fallback;
    }

    $raw = $all[$key];
    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }

    return $raw;
}

/**
 * Lê um grupo de configuração mesclando com os padrões.
 *
 * @return array<string,mixed>
 */
function xll_setting_group(string $key): array
{
    $defaults = xll_defaults();
    $default  = $defaults[$key] ?? [];
    $saved    = xll_setting($key, null);

    if (!is_array($saved)) {
        return is_array($default) ? $default : [];
    }

    return is_array($default) ? xll_merge_defaults($default, $saved) : $saved;
}

function xll_setting_save(string $key, mixed $value): void
{
    $encoded = is_array($value) || is_object($value)
        ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : (string) $value;
    if ($encoded === false) {
        $encoded = '';
    }

    $stmt = xll_db()->prepare(
        'INSERT INTO configuracoes (chave, valor) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
    );
    $stmt->execute([$key, $encoded]);
    xll_settings_cache_refresh();
}

/**
 * @param array<string,mixed> $values
 */
function xll_settings_save_many(array $values): void
{
    foreach ($values as $key => $value) {
        xll_setting_save((string) $key, $value);
    }
}

/* ====================================================================== */
/* Cores e fontes                                                          */
/* ====================================================================== */

function xll_hex_to_hsl(string $hex, string $fallback = '191 90% 7%'): string
{
    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return $fallback;
    }

    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $l = ($max + $min) / 2;

    if ($max === $min) {
        $h = $s = 0.0;
    } else {
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        if ($max === $r) {
            $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
        } elseif ($max === $g) {
            $h = ($b - $r) / $d + 2;
        } else {
            $h = ($r - $g) / $d + 4;
        }
        $h /= 6;
    }

    return round($h * 360) . ' ' . round($s * 100) . '% ' . round($l * 100) . '%';
}

function xll_valid_hex(string $value, string $fallback): string
{
    $value = strtoupper(trim($value));
    $value = ltrim($value, '#');
    // aceita 3 (FFF) ou 6 (FFFFFF) dígitos, com ou sem #
    if (preg_match('/^[0-9A-F]{3}$/', $value)) {
        $value = $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
    }
    return preg_match('/^[0-9A-F]{6}$/', $value) ? '#' . $value : $fallback;
}

function xll_font_family(string $family): string
{
    $family = trim(preg_replace('/[^A-Za-z0-9 \-]/', '', $family) ?? '');
    if ($family === '') {
        $family = 'Inter';
    }

    return "'" . $family . "', Inter, ui-sans-serif, system-ui, sans-serif";
}

function xll_google_fonts_url(): string
{
    $fonts = xll_setting_group('fonts');
    $custom = trim((string) ($fonts['google_url'] ?? ''));
    if ($custom !== '') {
        return $custom;
    }

    $families = [];
    foreach ([($fonts['body'] ?? 'Inter'), ($fonts['heading'] ?? 'Inter')] as $family) {
        $family = trim(preg_replace('/[^A-Za-z0-9 \-]/', '', (string) $family) ?? '');
        if ($family !== '' && strcasecmp($family, 'Inter') !== 0) {
            $families[] = 'family=' . str_replace(' ', '+', $family) . ':wght@400;500;600;700;800';
        }
    }

    if (!$families) {
        return '';
    }

    return 'https://fonts.googleapis.com/css2?' . implode('&', array_unique($families)) . '&display=swap';
}

/**
 * CSS com as variáveis de tema (cores, fontes e tamanhos).
 */
function xll_theme_styles(): string
{
    $colors = xll_setting_group('colors');
    $fonts  = xll_setting_group('fonts');

    $map = [
        'background' => '#01191f',
        'card'       => '#0d3840',
        'border'     => '#0d4450',
        'text'       => '#f4f4f5',
        'muted'      => '#9bbcc2',
        'accent'     => '#00e676',
        'discount'   => '#ff5a1f',
    ];
    $css = [];
    foreach ($map as $k => $fallback) {
        $hex = xll_valid_hex((string) ($colors[$k] ?? $fallback), $fallback);
        $css[$k] = $hex;
    }
    $radius = max(0, min(24, (int) ($colors['radius'] ?? 6)));
    $bodyFamily = xll_font_family((string) ($fonts['body'] ?? 'Inter'));
    $headFamily = xll_font_family((string) ($fonts['heading'] ?? 'Inter'));
    $bodySize = max(12, min(22, (int) ($fonts['body_size'] ?? 15)));
    $h1 = max(20, min(64, (int) ($fonts['h1_size'] ?? 30)));
    $h2 = max(16, min(52, (int) ($fonts['h2_size'] ?? 24)));
    $h3 = max(14, min(44, (int) ($fonts['h3_size'] ?? 20)));

    $cssVars = [
        '--xll-bg'       => $css['background'],
        '--xll-card'     => $css['card'],
        '--xll-border'   => $css['border'],
        '--xll-text'     => $css['text'],
        '--xll-muted'    => $css['muted'],
        '--xll-accent'   => $css['accent'],
        '--xll-green'    => 'var(--xll-accent)',
        '--xll-discount' => $css['discount'],
        '--xll-radius'   => $radius . 'px',
        '--xll-font-body'    => $bodyFamily,
        '--xll-font-heading' => $headFamily,
        '--xll-font-size'    => $bodySize . 'px',
        '--xll-h1'       => $h1 . 'px',
        '--xll-h2'       => $h2 . 'px',
        '--xll-h3'       => $h3 . 'px',
        '--background'   => xll_hex_to_hsl($css['background']),
        '--foreground'   => xll_hex_to_hsl($css['text'], '0 0% 100%'),
        '--card'         => xll_hex_to_hsl($css['card']),
        '--border'       => xll_hex_to_hsl($css['border']),
        '--muted-foreground' => xll_hex_to_hsl($css['muted']),
        '--primary'      => xll_hex_to_hsl($css['accent']),
        '--accent'       => xll_hex_to_hsl($css['accent']),
        '--acid'         => xll_hex_to_hsl($css['accent']),
        '--cyan'         => xll_hex_to_hsl($css['accent']),
        '--signal'       => xll_hex_to_hsl($css['discount']),
    ];

    $decl = [];
    foreach ($cssVars as $name => $value) {
        $decl[] = $name . ':' . $value;
    }

    return "<style id=\"xll-theme-vars\">:root{" . implode(';', $decl) . '}</style>';
}

/* ====================================================================== */
/* Preços, descontos e selos                                               */
/* ====================================================================== */

function xll_format_price(int|float|null $cents): string
{
    $cents = (int) $cents;
    if ($cents <= 0) {
        return '—';
    }

    return 'R$ ' . number_format($cents / 100, 2, ',', '.');
}

/**
 * Converte "R$ 1.234,56", "1234,56" ou "1234.56" para centavos.
 */
function xll_parse_price(mixed $value): int
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return 0;
    }
    $raw = preg_replace('/[^0-9,.\-]/', '', $raw) ?? '';
    if ($raw === '') {
        return 0;
    }

    if (str_contains($raw, ',')) {
        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);
    } elseif (substr_count($raw, '.') > 1) {
        $raw = str_replace('.', '', $raw);
    } elseif (preg_match('/\.\d{1,2}$/', $raw) !== 1 && str_contains($raw, '.')) {
        $raw = str_replace('.', '', $raw);
    }

    return (int) round(((float) $raw) * 100);
}

function xll_price_to_csv(int $cents): string
{
    return number_format($cents / 100, 2, ',', '.');
}

function xll_discount_percent(array $product): int
{
    $discount = (int) ($product['desconto'] ?? 0);
    if ($discount > 0) {
        return min(99, $discount);
    }
    $price = (int) ($product['preco'] ?? 0);
    $old   = (int) ($product['preco_antigo'] ?? 0);
    if ($price > 0 && $old > $price) {
        return (int) round((1 - $price / $old) * 100);
    }

    return 0;
}

function xll_parse_rating(mixed $value): ?float
{
    $raw = trim(str_replace(',', '.', (string) $value));
    if ($raw === '' || !is_numeric($raw)) {
        return null;
    }
    $rating = (float) $raw;

    return $rating >= 0 && $rating <= 5 ? $rating : null;
}

/**
 * @return array{bg:string,color:string}
 */
function xll_badge_colors(string $selo): array
{
    $selo = mb_strtoupper(trim($selo));
    $map = [
        'MAIS VENDIDO'      => ['#7c3aed', '#ffffff'],
        'RECOMENDADO'       => ['#7c3aed', '#ffffff'],
        'SUPER DESCONTO'    => ['#ff5a1f', '#ffffff'],
        'OFERTA RELÂMPAGO'  => ['#ff5a1f', '#ffffff'],
        'OFERTA RELAMPAGO'  => ['#ff5a1f', '#ffffff'],
        'MENOR PREÇO'       => ['#0ea5e9', '#ffffff'],
        'MENOR PRECO'       => ['#0ea5e9', '#ffffff'],
        'NOVIDADE'          => ['#0ea5e9', '#ffffff'],
        'EXCLUSIVO'         => ['#f59e0b', '#1a1a1a'],
    ];

    [$bg, $color] = $map[$selo] ?? ['rgba(255,255,255,.12)', '#ffffff'];

    return ['bg' => $bg, 'color' => $color];
}

/* ====================================================================== */
/* Ícones SVG                                                              */
/* ====================================================================== */

function xll_icon_options(): array
{
    return [
        'keyboard' => 'Teclado',
        'headset'  => 'Headset',
        'cpu'      => 'Processador',
        'monitor'  => 'Monitor',
        'gamepad'  => 'Controle',
        'ruler'    => 'Mesa',
        'chair'    => 'Cadeira',
        'pc'       => 'PC',
        'mouse'    => 'Mouse',
        'speaker'  => 'Caixa de som',
        'storage'  => 'SSD',
        'box'      => 'Caixa',
        'star'     => 'Estrela',
        'percent'  => 'Desconto',
        'truck'    => 'Entrega',
        'shield'   => 'Segurança',
        'link'     => 'Link',
        'tag'      => 'Etiqueta',
    ];
}

function xll_svg_icon(string $icon, string $class = 'xll-icon'): string
{
    $paths = [
        'keyboard' => '<rect x="2" y="6" width="20" height="12" rx="2"></rect><path d="M6 10h.01"></path><path d="M10 10h.01"></path><path d="M14 10h.01"></path><path d="M18 10h.01"></path><path d="M6 14h.01"></path><path d="M18 14h.01"></path><path d="M10 14h4"></path>',
        'headset'  => '<path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"></path>',
        'cpu'      => '<rect x="4" y="4" width="16" height="16" rx="2"></rect><rect x="9" y="9" width="6" height="6" rx="1"></rect><path d="M15 2v2"></path><path d="M9 2v2"></path><path d="M15 20v2"></path><path d="M9 20v2"></path><path d="M2 15h2"></path><path d="M2 9h2"></path><path d="M20 15h2"></path><path d="M20 9h2"></path>',
        'monitor'  => '<path d="M20 16V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9"></path><path d="M2 16h20"></path><path d="M4 20h16"></path>',
        'gamepad'  => '<path d="M17.32 5H6.68a4 4 0 0 0-3.98 3.59L2 16a3 3 0 0 0 3 3c1 0 1.5-.5 2-1l1.41-1.41a2 2 0 0 1 1.42-.59h4.34a2 2 0 0 1 1.42.59L17 18c.5.5 1 1 2 1a3 3 0 0 0 3-3l-.68-7.41A4 4 0 0 0 17.32 5z"></path><path d="M6 11h4"></path><path d="M8 9v4"></path><path d="M15 12h.01"></path><path d="M18 10h.01"></path>',
        'ruler'    => '<path d="M21.3 8.7 8.7 21.3a1 1 0 0 1-1.4 0L2.7 16.7a1 1 0 0 1 0-1.4L15.3 2.7a1 1 0 0 1 1.4 0l4.6 4.6a1 1 0 0 1 0 1.4Z"></path><path d="m7.5 10.5 2 2"></path><path d="m10.5 7.5 2 2"></path><path d="m13.5 4.5 2 2"></path><path d="m4.5 13.5 2 2"></path>',
        'chair'    => '<path d="M19 9V6a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v3"></path><path d="M3 16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5a2 2 0 0 0-4 0v2H7v-2a2 2 0 0 0-4 0Z"></path><path d="M5 18v2"></path><path d="M19 18v2"></path>',
        'pc'       => '<rect x="2" y="3" width="20" height="14" rx="2"></rect><path d="M8 21h8"></path><path d="M12 17v4"></path>',
        'mouse'    => '<rect x="6" y="2" width="12" height="20" rx="6"></rect><path d="M12 6v4"></path>',
        'speaker'  => '<rect x="4" y="2" width="16" height="20" rx="2"></rect><circle cx="12" cy="14" r="4"></circle><path d="M12 6h.01"></path>',
        'storage'  => '<rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="M7 8h.01"></path><path d="M7 12h.01"></path><path d="M7 16h10"></path>',
        'box'      => '<path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path>',
        'star'     => '<path d="M11.5 2.3 14.3 8l6.3.9-4.6 4.4 1.1 6.2-5.6-2.9L5.9 19.5l1.1-6.2L2.4 8.9 8.7 8Z"></path>',
        'percent'  => '<line x1="19" y1="5" x2="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle>',
        'truck'    => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"></path><path d="M14 9h4l4 4v4a1 1 0 0 1-1 1h-2"></path><circle cx="7" cy="18" r="2"></circle><circle cx="17" cy="18" r="2"></circle>',
        'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path><path d="m9 12 2 2 4-4"></path>',
        'link'     => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>',
        'tag'      => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path><circle cx="7.5" cy="7.5" r="1"></circle>',
    ];

    // Ícone personalizado: o admin aceita o código SVG completo.
    if ($icon !== '' && stripos(ltrim($icon), '<svg') === 0) {
        $svg = xll_svg_sanitize_icon($icon);
        if ($svg === '') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"></svg>';
        }
        if (!preg_match('#<svg\b[^>]*\bclass\s*=#i', $svg)) {
            $svg = preg_replace('#<svg\b#i', '<svg class="' . e($class) . '"', $svg, 1);
        }
        return $svg;
    }

    $path = $paths[$icon] ?? $paths['gamepad'];

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' . e($class) . '" aria-hidden="true">' . $path . '</svg>';
}

/**
 * Limpa o código SVG enviado pelo painel: remove scripts, estilos,
 * iframes, atributos de evento (on*) e links javascript:.
 */
function xll_svg_sanitize_icon(string $svg): string
{
    $svg = trim($svg);
    if ($svg === '') {
        return '';
    }
    if (strlen($svg) > 60000) {
        $svg = substr($svg, 0, 60000);
    }
    $svg = preg_replace('#<\s*(script|style|iframe|object|embed|foreignObject|template|noscript)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $svg);
    $svg = preg_replace('#<\s*(script|style|iframe|object|embed|foreignObject|template|noscript)\b[^>]*/?>#is', '', $svg);
    $svg = preg_replace('#<!--.*?-->#s', '', $svg);
    $svg = preg_replace('#\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $svg);
    $svg = preg_replace('#\s+(?:xlink:)?href\s*=\s*("|\')\s*(?:javascript:|data:text/html)[^"\']*\1#i', '', $svg);
    if (!preg_match('#^<svg\b#i', $svg)) {
        $pos = stripos($svg, '<svg');
        if ($pos === false) {
            return '';
        }
        $svg = substr($svg, $pos);
    }
    return trim($svg);
}

/* ====================================================================== */
/* Produtos                                                                */
/* ====================================================================== */

function xll_normalize_product(array $p): array
{
    return [
        'id'            => (string) ($p['id'] ?? ''),
        'titulo'        => (string) ($p['titulo'] ?? ''),
        'descricao'     => (string) ($p['descricao'] ?? ''),
        'categoria'     => (string) ($p['categoria'] ?? ''),
        'marketplace'   => (string) ($p['marketplace'] ?? ''),
        'selo'          => (string) ($p['selo'] ?? ''),
        'preco'         => (int) ($p['preco'] ?? 0),
        'preco_antigo'  => (int) ($p['preco_antigo'] ?? 0),
        'desconto'      => (int) ($p['desconto'] ?? 0),
        'avaliacao'     => isset($p['avaliacao']) && $p['avaliacao'] !== null && $p['avaliacao'] !== '' ? (float) $p['avaliacao'] : null,
        'imagem'        => (string) ($p['imagem'] ?? ''),
        'link_afiliado' => (string) ($p['link_afiliado'] ?? ''),
        'secao_home'    => (string) ($p['secao_home'] ?? ''),
        'ordem'         => (int) ($p['ordem'] ?? 0),
        'slug'          => (string) ($p['slug'] ?? ''),
    ];
}

function xll_product_url(array $p): string
{
    $id = (string) ($p['id'] ?? '');
    $slug = trim((string) ($p['slug'] ?? ''));
    if (xll_pretty_urls() && $slug !== '') {
        return xll_url('produto.php', ['slug' => $slug]);
    }

    return xll_url('produto.php', ['id' => $id]);
}

function xll_product_by_id(string $id): ?array
{
    if ($id === '') {
        return null;
    }
    $stmt = xll_db()->prepare('SELECT * FROM produtos WHERE id = ? OR slug = ? LIMIT 1');
    $stmt->execute([$id, $id]);
    $row = $stmt->fetch();

    return is_array($row) ? xll_normalize_product($row) : null;
}

/**
 * @return array<int,array<string,mixed>>
 */
function xll_products_home(array $section): array
{
    $limit = max(1, min(24, (int) ($section['limite'] ?? 8)));
    $mode  = (string) ($section['modo'] ?? 'secao');
    $value = trim((string) ($section['valor'] ?? ''));

    $sql = 'SELECT * FROM produtos';
    $params = [];

    if ($mode === 'secao' && $value !== '') {
        $sql .= ' WHERE secao_home = ?';
        $params[] = $value;
        $sql .= ' ORDER BY ordem ASC, id ASC';
    } elseif ($mode === 'desconto') {
        $sql .= ' ORDER BY desconto DESC, preco ASC';
    } elseif ($mode === 'recentes') {
        $sql .= ' ORDER BY ordem DESC, id DESC';
    } else {
        $sql .= ' ORDER BY ordem ASC, id ASC';
    }

    $sql .= ' LIMIT ' . $limit;
    $stmt = xll_db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return array_map('xll_normalize_product', $rows ?: []);
}

/**
 * Catálogo com busca, filtros, ordenação e paginação.
 *
 * @param array<string,mixed> $filters
 * @return array{produtos:array<int,array<string,mixed>>,total:int,paginas:int,pagina:int}
 */
function xll_catalog(array $filters, int $perPage = 24, int $page = 1): array
{
    $where = [];
    $params = [];

    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(titulo LIKE ? OR descricao LIKE ? OR categoria LIKE ? OR marketplace LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like);
    }

    $categoria = trim((string) ($filters['categoria'] ?? ''));
    if ($categoria !== '') {
        $where[] = 'categoria = ?';
        $params[] = $categoria;
    }

    $marketplace = trim((string) ($filters['marketplace'] ?? ''));
    if ($marketplace !== '') {
        $where[] = 'marketplace = ?';
        $params[] = $marketplace;
    }

    $secao = trim((string) ($filters['secao'] ?? ''));
    if ($secao !== '') {
        $where[] = 'secao_home = ?';
        $params[] = $secao;
    }

    if (!empty($filters['desconto'])) {
        $where[] = 'desconto > 0';
    }

    $orderMap = [
        'padrao'         => 'ordem ASC, id ASC',
        'menor-preco'    => 'preco ASC',
        'maior-preco'    => 'preco DESC',
        'maior-desconto' => 'desconto DESC',
        'avaliacao'      => 'avaliacao DESC, desconto DESC',
        'nome'           => 'titulo ASC',
        'recentes'       => 'ordem DESC, id DESC',
    ];
    $order = $orderMap[(string) ($filters['ordenar'] ?? 'padrao')] ?? $orderMap['padrao'];

    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $countStmt = xll_db()->prepare('SELECT COUNT(*) FROM produtos' . $whereSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($pages, $page));
    $offset = ($page - 1) * $perPage;

    $stmt = xll_db()->prepare(
        'SELECT * FROM produtos' . $whereSql . ' ORDER BY ' . $order . ' LIMIT ' . $perPage . ' OFFSET ' . $offset
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return [
        'produtos' => array_map('xll_normalize_product', $rows ?: []),
        'total'    => $total,
        'paginas'  => $pages,
        'pagina'   => $page,
    ];
}

/**
 * @return array<int,array<string,mixed>>
 */
function xll_related_products(array $product, int $limit = 4): array
{
    $stmt = xll_db()->prepare(
        'SELECT * FROM produtos WHERE categoria = ? AND id <> ? ORDER BY desconto DESC, ordem ASC LIMIT ' . max(1, $limit)
    );
    $stmt->execute([$product['categoria'], $product['id']]);
    $rows = $stmt->fetchAll();

    return array_map('xll_normalize_product', $rows ?: []);
}

/**
 * @return array<int,string>
 */
function xll_marketplaces(): array
{
    try {
        $rows = xll_db()->query("SELECT DISTINCT marketplace FROM produtos WHERE marketplace <> '' ORDER BY marketplace")->fetchAll();
        $values = array_map(static fn ($r) => (string) $r['marketplace'], $rows ?: []);
    } catch (Throwable) {
        $values = [];
    }

    return $values ?: ['Mercado Livre', 'Amazon', 'Shopee'];
}

/**
 * Categorias da home com contagem dinâmica de produtos.
 *
 * @return array<int,array<string,mixed>>
 */
function xll_categories_with_count(): array
{
    $group = xll_setting_group('categories');
    $items = is_array($group['items'] ?? null) ? $group['items'] : [];

    $counts = [];
    try {
        $rows = xll_db()->query('SELECT categoria, COUNT(*) AS total FROM produtos GROUP BY categoria')->fetchAll();
        foreach ($rows ?: [] as $row) {
            $counts[(string) $row['categoria']] = (int) $row['total'];
        }
    } catch (Throwable) {
        $counts = [];
    }

    $result = [];
    foreach ($items as $item) {
        $name = trim((string) ($item['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $link = trim((string) ($item['link'] ?? ''));
        if ($link === '') {
            $link = xll_url('ofertas.php', ['categoria' => $name]);
        }
        $result[] = [
            'name'        => $name,
            'description' => (string) ($item['description'] ?? ''),
            'icon'        => (string) ($item['icon'] ?? 'gamepad'),
            'image'       => (string) ($item['image'] ?? ''),
            'link'        => $link,
            'active'      => !isset($item['active']) || (bool) $item['active'],
            'count'       => $counts[$name] ?? 0,
        ];
    }

    return $result;
}

/**
 * Estatísticas rápidas usadas no painel.
 *
 * @return array<string,int|float>
 */
function xll_stats(): array
{
    $pdo = xll_db();

    return [
        'produtos'      => (int) $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn(),
        'categorias'    => (int) $pdo->query("SELECT COUNT(DISTINCT categoria) FROM produtos WHERE categoria <> ''")->fetchColumn(),
        'marketplaces'  => (int) $pdo->query("SELECT COUNT(DISTINCT marketplace) FROM produtos WHERE marketplace <> ''")->fetchColumn(),
        'com_desconto'  => (int) $pdo->query('SELECT COUNT(*) FROM produtos WHERE desconto > 0')->fetchColumn(),
        'desconto_medio' => (float) ($pdo->query('SELECT COALESCE(AVG(desconto),0) FROM produtos WHERE desconto > 0')->fetchColumn() ?: 0),
    ];
}

/* ====================================================================== */
/* Cards de produto                                                        */
/* ====================================================================== */

function xll_render_product_card(array $product): void
{
    $p = xll_normalize_product($product);
    $url = xll_product_url($p);
    $affiliate = xll_affiliate_click_url($p);
    $discount = xll_discount_percent($p);
    $badge = trim($p['selo']);
    $badgeColors = xll_badge_colors($badge);
    $image = xll_image_url($p['imagem'], 600);
    $oldPrice = $p['preco_antigo'] > $p['preco'] ? $p['preco_antigo'] : 0;
    ?>
    <article class="xll-card" data-product-id="<?= e($p['id']) ?>">
        <a class="xll-card-media" href="<?= e($url) ?>" aria-label="<?= e($p['titulo']) ?>">
            <img src="<?= e($image) ?>" alt="<?= e($p['titulo']) ?>" loading="lazy" decoding="async"
                 width="400" height="225"
                 onerror="this.onerror=null;this.src='<?= e(xll_url('assets/img/placeholder.svg')) ?>';">
            <?php if ($badge !== ''): ?>
                <span class="xll-card-badge" style="background:<?= e($badgeColors['bg']) ?>;color:<?= e($badgeColors['color']) ?>"><?= e($badge) ?></span>
            <?php endif; ?>
        </a>
        <div class="xll-card-body">
            <a class="xll-card-title" href="<?= e($url) ?>"><?= e($p['titulo']) ?></a>
            <div class="xll-card-prices">
                <?php if ($discount > 0): ?>
                    <span class="xll-card-discount">-<?= e($discount) ?>%</span>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
                <div class="xll-card-price-col">
                    <?php if ($oldPrice > 0): ?>
                        <span class="xll-card-old"><?= e(xll_format_price($oldPrice)) ?></span>
                    <?php endif; ?>
                    <span class="xll-card-price"><?= e(xll_format_price($p['preco'])) ?></span>
                </div>
            </div>
            <button type="button" class="xll-compare-btn"
                    data-compare="1"
                    data-id="<?= e($p['id']) ?>"
                    data-title="<?= e($p['titulo']) ?>"
                    data-price="<?= e($p['preco']) ?>"
                    data-old-price="<?= e($oldPrice) ?>"
                    data-discount="<?= e($discount) ?>"
                    data-image="<?= e($image) ?>"
                    data-url="<?= e($url) ?>"
                    data-affiliate="<?= e($affiliate) ?>"
                    data-marketplace="<?= e($p['marketplace']) ?>"
                    data-badge="<?= e($badge) ?>"
                    data-category="<?= e($p['categoria']) ?>"
                    data-rating="<?= e($p['avaliacao'] ?? '') ?>"
                    data-section="<?= e($p['secao_home']) ?>">
                <?= xll_svg_icon('ruler', 'xll-compare-icon') ?>
                <span>Comparar</span>
            </button>
            <a class="btn-hype xll-card-buy" href="<?= e($affiliate) ?>" target="_blank" rel="sponsored noopener noreferrer">COMPRAR</a>
        </div>
    </article>
    <?php
}

/**
 * @param array<int,array<string,mixed>> $products
 */
function xll_render_product_grid(array $products, string $emptyMessage = 'Nenhum produto encontrado.'): void
{
    if (!$products) {
        echo '<p class="xll-empty">' . e($emptyMessage) . '</p>';

        return;
    }
    echo '<div class="xll-products-grid">';
    foreach ($products as $product) {
        xll_render_product_card($product);
    }
    echo '</div>';
}

/* ====================================================================== */
/* Paginação, links e textos                                               */
/* ====================================================================== */

function xll_pagination(int $page, int $pages, array $params = []): string
{
    if ($pages <= 1) {
        return '';
    }

    $html = '<nav class="xll-pagination" aria-label="Paginação">';
    $window = 2;
    $start = max(1, $page - $window);
    $end = min($pages, $page + $window);

    if ($page > 1) {
        $html .= '<a class="xll-page" href="' . e(xll_url('ofertas.php', array_merge($params, ['pagina' => $page - 1]))) . '#catalogo" rel="prev">‹</a>';
    }
    if ($start > 1) {
        $html .= '<a class="xll-page" href="' . e(xll_url('ofertas.php', array_merge($params, ['pagina' => 1]))) . '#catalogo">1</a>';
        if ($start > 2) {
            $html .= '<span class="xll-page-dots">…</span>';
        }
    }
    for ($i = $start; $i <= $end; $i++) {
        $html .= $i === $page
            ? '<span class="xll-page is-active" aria-current="page">' . $i . '</span>'
            : '<a class="xll-page" href="' . e(xll_url('ofertas.php', array_merge($params, ['pagina' => $i]))) . '#catalogo">' . $i . '</a>';
    }
    if ($end < $pages) {
        if ($end < $pages - 1) {
            $html .= '<span class="xll-page-dots">…</span>';
        }
        $html .= '<a class="xll-page" href="' . e(xll_url('ofertas.php', array_merge($params, ['pagina' => $pages]))) . '#catalogo">' . $pages . '</a>';
    }
    if ($page < $pages) {
        $html .= '<a class="xll-page" href="' . e(xll_url('ofertas.php', array_merge($params, ['pagina' => $page + 1]))) . '#catalogo" rel="next">›</a>';
    }
    $html .= '</nav>';

    return $html;
}

function xll_excerpt(string $text, int $limit = 160): string
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
}

function xll_nav_active(string $url): bool
{
    if (xll_is_external($url)) {
        return false;
    }

    $targetUrl = xll_url($url);
    $target = parse_url($targetUrl);
    $current = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''));
    if (!is_array($target) || !is_array($current)) {
        return false;
    }

    $targetPath = rawurldecode((string) ($target['path'] ?? '/'));
    $currentPath = rawurldecode((string) ($current['path'] ?? '/'));
    $normalize = static function (string $path): string {
        $base = rtrim(XLL_BASE, '/');
        $path = rtrim($path, '/');
        if ($path === '' || $path === $base || $path === $base . '/index.php') {
            return $base . '/';
        }
        if (str_ends_with($path, '/index.php')) {
            return substr($path, 0, -strlen('index.php'));
        }

        return $path;
    };
    if ($normalize($targetPath) !== $normalize($currentPath)) {
        /* Compatibilidade: link legado com ?categoria= em /ofertas/{slug}. */
        parse_str((string) ($target['query'] ?? ''), $targetQuery);
        if (empty($targetQuery['categoria']) || (string) ($_GET['categoria'] ?? '') !== (string) $targetQuery['categoria']) {
            return false;
        }
    }

    parse_str((string) ($target['query'] ?? ''), $targetQuery);
    foreach ($targetQuery as $key => $value) {
        if ((string) ($_GET[$key] ?? '') !== (string) $value) {
            return false;
        }
    }

    return true;
}

function xll_page_url(): string
{
    return (string) ($_SERVER['REQUEST_URI'] ?? xll_url('index.php'));
}

function xll_stars(?float $rating): string
{
    if ($rating === null || $rating <= 0) {
        return '';
    }
    $rounded = (int) round($rating);
    $html = '<span class="xll-stars" title="' . e(number_format($rating, 1, ',', '.')) . ' de 5">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<span class="' . ($i <= $rounded ? 'is-on' : '') . '">★</span>';
    }
    $html .= '<small>' . e(number_format($rating, 1, ',', '.')) . '</small></span>';

    return $html;
}

/* ====================================================================== */
/* SEO / dados estruturados                                                */
/* ====================================================================== */

function xll_json_ld(array $data): string
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return '';
    }
    $json = str_replace('</', '<\/', $json);

    return '<script type="application/ld+json">' . $json . '</script>';
}

function xll_schema_organization(): array
{
    $logo = xll_absolute_url(xll_media_url((string) xll_setting('logo', 'assets/img/logo.webp')));

    return [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => (string) xll_setting('site_name', 'XLL Gaming Store'),
        'url'      => rtrim(xll_site_url(), '/') . '/',
        'logo'     => $logo,
    ];
}

function xll_schema_website(): array
{
    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        'name'            => (string) xll_setting('site_name', 'XLL Gaming Store'),
        'url'             => rtrim(xll_site_url(), '/') . '/',
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'      => 'EntryPoint',
                'urlTemplate' => xll_absolute_url('ofertas.php') . '?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

/**
 * @return array<int,array<string,mixed>>
 */
function xll_schema_product(array $p): array
{
    $p = xll_normalize_product($p);
    $url = xll_absolute_url(xll_product_url($p));
    $image = xll_absolute_url(xll_image_url($p['imagem'], 900));
    $description = xll_excerpt($p['descricao'] !== '' ? $p['descricao'] : $p['titulo'], 300);

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => $p['titulo'],
        'description' => $description,
        'sku'         => $p['id'],
        'url'         => $url,
        'image'       => [$image],
        'offers'      => [
            '@type'         => 'Offer',
            'url'           => $url,
            'priceCurrency' => 'BRL',
            'price'         => number_format($p['preco'] / 100, 2, '.', ''),
            'availability'  => 'https://schema.org/InStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller'        => [
                '@type' => 'Organization',
                'name'  => $p['marketplace'] !== '' ? $p['marketplace'] : (string) xll_setting('site_name', 'XLL Gaming Store'),
            ],
        ],
    ];

    if ($p['categoria'] !== '') {
        $schema['category'] = $p['categoria'];
    }
    if ($p['marketplace'] !== '') {
        $schema['brand'] = ['@type' => 'Brand', 'name' => $p['marketplace']];
    }
    if ($p['avaliacao'] !== null && $p['avaliacao'] > 0) {
        $schema['aggregateRating'] = [
            '@type'       => 'AggregateRating',
            'ratingValue' => number_format((float) $p['avaliacao'], 1, '.', ''),
            'bestRating'  => '5',
            'worstRating' => '1',
            'ratingCount' => '1',
        ];
    }

    return $schema;
}

/**
 * @param array<int,array{name:string,url:string}> $items
 */
function xll_schema_breadcrumb(array $items): array
{
    $list = [];
    $position = 1;
    foreach ($items as $item) {
        if (empty($item['name']) || empty($item['url'])) {
            continue;
        }
        $list[] = [
            '@type'    => 'ListItem',
            'position' => $position,
            'name'     => (string) $item['name'],
            'item'     => (string) $item['url'],
        ];
        $position++;
    }

    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $list,
    ];
}

/**
 * URLs para o sitemap.xml (com URLs amigáveis).
 *
 * @return array<int,array{loc:string,lastmod:string,changefreq:string,priority:string}>
 */
function xll_sitemap_urls(): array
{
    $today = date('Y-m-d');
    $urls = [
        ['loc' => rtrim(xll_site_url(), '/') . '/', 'lastmod' => $today, 'changefreq' => 'daily', 'priority' => '1.0'],
        ['loc' => xll_absolute_url('ofertas.php'), 'lastmod' => $today, 'changefreq' => 'daily', 'priority' => '0.9'],
        ['loc' => xll_absolute_url('comparar.php'), 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.4'],
    ];

    foreach (array_keys(xll_setting_group('pages')) as $slug) {
        $urls[] = [
            'loc'        => xll_absolute_url('pagina.php', ['p' => (string) $slug]),
            'lastmod'    => $today,
            'changefreq' => 'monthly',
            'priority'   => '0.3',
        ];
    }

    try {
        $rows = xll_db()->query('SELECT * FROM produtos ORDER BY ordem ASC, id ASC')->fetchAll();
        foreach ($rows ?: [] as $row) {
            $p = xll_normalize_product($row);
            $lastmod = !empty($row['atualizado_em']) ? date('Y-m-d', strtotime((string) $row['atualizado_em'])) : $today;
            $urls[] = [
                'loc'        => xll_absolute_url(xll_product_url($p)),
                'lastmod'    => $lastmod,
                'changefreq' => 'weekly',
                'priority'   => '0.7',
            ];
        }
    } catch (Throwable) {
        // sem banco: sitemap apenas com páginas fixas
    }

    return $urls;
}

/* ====================================================================== */
/* Imagens (cache/proxy local)                                             */
/* ====================================================================== */

function xll_image_url(?string $path, int $width = 600): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return xll_url('assets/img/placeholder.svg');
    }
    if (preg_match('~^https?://~i', $path)) {
        $host = strtolower((string) parse_url($path, PHP_URL_HOST));
        $self = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $self = preg_replace('/:\d+$/', '', $self) ?? '';
        if ($host !== '' && $host !== $self) {
            return xll_url('img.php', ['u' => $path, 'w' => max(80, min(1400, $width))]);
        }
    }

    return xll_media_url($path);
}

function xll_affiliate_click_url(array $product): string
{
    $id = trim((string) ($product['id'] ?? ''));
    if ($id === '') {
        return xll_url('ofertas.php');
    }

    return xll_url('go.php', ['id' => $id]);
}

/* ====================================================================== */
/* E-mail (formulário de contato e notificações)                           */
/* ====================================================================== */

function xll_mail_encode(string $value): string
{
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function xll_send_mail(string $to, string $subject, string $htmlBody, string $replyTo = ''): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $host = (string) (parse_url(xll_site_url(), PHP_URL_HOST) ?: 'localhost');
    $fromEmail = trim((string) xll_setting('mail_from', 'no-reply@' . $host));
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $fromEmail = 'no-reply@' . $host;
    }
    $fromName = (string) xll_setting('site_name', 'XLL Gaming Store');

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . xll_mail_encode($fromName) . ' <' . $fromEmail . '>',
    ];
    if (filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    return @mail($to, xll_mail_encode($subject), $htmlBody, implode("\r\n", $headers));
}

function xll_contact_recipient(): string
{
    $email = trim((string) xll_setting('contact_email', ''));
    if ($email === '') {
        $footer = xll_setting_group('footer');
        $email = trim((string) ($footer['company']['email'] ?? ''));
    }

    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

/* ====================================================================== */
/* Analytics (cliques nos links de afiliado)                              */
/* ====================================================================== */

function xll_client_ip_hash(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $salt = (string) xll_setting('site_name', 'XLL Gaming Store');

    return hash('sha256', $ip . '|xll|' . $salt . '|' . date('Y-m-d'));
}

function xll_track_click(array $product): void
{
    if ((string) xll_setting('track_clicks', '1') === '0') {
        return;
    }
    try {
        $stmt = xll_db()->prepare(
            'INSERT INTO cliques (produto_id, marketplace, ip_hash, user_agent, referer)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (string) ($product['id'] ?? ''),
            (string) ($product['marketplace'] ?? ''),
            xll_client_ip_hash(),
            mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250),
            mb_substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 250),
        ]);
    } catch (Throwable) {
        // não interrompe o redirecionamento se a tabela ainda não existir
    }
}

/**
 * @return array{total:int,hoje:int,ultimos7:int,produtos:int}
 */
function xll_click_stats(): array
{
    $stats = ['total' => 0, 'hoje' => 0, 'ultimos7' => 0, 'produtos' => 0];
    try {
        $pdo = xll_db();
        $stats['total'] = (int) $pdo->query('SELECT COUNT(*) FROM cliques')->fetchColumn();
        $stats['hoje'] = (int) $pdo->query('SELECT COUNT(*) FROM cliques WHERE DATE(criado_em) = CURDATE()')->fetchColumn();
        $stats['ultimos7'] = (int) $pdo->query('SELECT COUNT(*) FROM cliques WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn();
        $stats['produtos'] = (int) $pdo->query('SELECT COUNT(DISTINCT produto_id) FROM cliques')->fetchColumn();
    } catch (Throwable) {
    }

    return $stats;
}

/**
 * @return array<int,array{data:string,total:int}>
 */
function xll_clicks_daily(int $days = 14): array
{
    $days = max(1, min(90, $days));
    $byDate = [];
    try {
        $stmt = xll_db()->query(
            'SELECT DATE(criado_em) AS dia, COUNT(*) AS total
             FROM cliques
             WHERE criado_em >= DATE_SUB(CURDATE(), INTERVAL ' . ($days - 1) . ' DAY)
             GROUP BY DATE(criado_em)'
        );
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $byDate[(string) $row['dia']] = (int) $row['total'];
        }
    } catch (Throwable) {
    }

    $result = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime('-' . $i . ' days'));
        $result[] = ['data' => $date, 'total' => $byDate[$date] ?? 0];
    }

    return $result;
}

/**
 * @return array<int,array<string,mixed>>
 */
function xll_clicks_top_products(int $limit = 10): array
{
    try {
        $stmt = xll_db()->query(
            'SELECT c.produto_id, COUNT(*) AS total, p.titulo, p.preco, p.imagem, p.marketplace
             FROM cliques c
             LEFT JOIN produtos p ON p.id = c.produto_id
             GROUP BY c.produto_id, p.titulo, p.preco, p.imagem, p.marketplace
             ORDER BY total DESC
             LIMIT ' . max(1, min(50, $limit))
        );

        return $stmt->fetchAll() ?: [];
    } catch (Throwable) {
        return [];
    }
}

/**
 * @return array<int,array<string,mixed>>
 */
function xll_clicks_top_marketplaces(int $limit = 5): array
{
    try {
        $stmt = xll_db()->query(
            'SELECT marketplace, COUNT(*) AS total
             FROM cliques
             WHERE marketplace <> \'\'
             GROUP BY marketplace
             ORDER BY total DESC
             LIMIT ' . max(1, min(20, $limit))
        );

        return $stmt->fetchAll() ?: [];
    } catch (Throwable) {
        return [];
    }
}

/**
 * @return array<int,array<string,mixed>>
 */
function xll_clicks_recent(int $limit = 20): array
{
    try {
        $stmt = xll_db()->query(
            'SELECT c.*, p.titulo
             FROM cliques c
             LEFT JOIN produtos p ON p.id = c.produto_id
             ORDER BY c.id DESC
             LIMIT ' . max(1, min(100, $limit))
        );

        return $stmt->fetchAll() ?: [];
    } catch (Throwable) {
        return [];
    }
}

/* ====================================================================== */
/* Mensagens de contato                                                    */
/* ====================================================================== */

function xll_unread_messages(): int
{
    try {
        return (int) xll_db()->query('SELECT COUNT(*) FROM mensagens WHERE lido = 0')->fetchColumn();
    } catch (Throwable) {
        return 0;
    }
}

/**
 * @return array<int,array<string,mixed>>
 */
function xll_messages_list(string $filter = 'todas', int $limit = 100): array
{
    try {
        $where = $filter === 'nao-lidas' ? ' WHERE lido = 0' : '';
        $stmt = xll_db()->query('SELECT * FROM mensagens' . $where . ' ORDER BY id DESC LIMIT ' . max(1, min(300, $limit)));

        return $stmt->fetchAll() ?: [];
    } catch (Throwable) {
        return [];
    }
}

function xll_message_get(int $id): ?array
{
    try {
        $stmt = xll_db()->prepare('SELECT * FROM mensagens WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    } catch (Throwable) {
        return null;
    }
}
