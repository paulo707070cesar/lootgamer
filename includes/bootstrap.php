<?php
/**
 * Loot Gamer - bootstrap da aplicação.
 *
 * Responsável por carregar configuração, iniciar sessão, definir constantes
 * de caminho/URL e incluir as bibliotecas internas.
 */

declare(strict_types=1);

if (defined('XLL_BOOTSTRAPPED')) {
    return;
}
define('XLL_BOOTSTRAPPED', true);
define('XLL_VERSION', '1.0.0');
define('XLL_ROOT', dirname(__DIR__));

$config = require XLL_ROOT . '/config/config.php';
if (is_file(XLL_ROOT . '/config/config.local.php')) {
    $local = require XLL_ROOT . '/config/config.local.php';
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}
$GLOBALS['XLL_CONFIG'] = $config;

$debug = (bool) ($config['debug'] ?? false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

date_default_timezone_set((string) ($config['timezone'] ?? 'America/Sao_Paulo'));
mb_internal_encoding('UTF-8');

/* ---------------------------------------------------------------------- */
/* URL base da aplicação (funciona em subpasta e na raiz)                 */
/* ---------------------------------------------------------------------- */
$base = trim((string) ($config['base_url'] ?? ''));
if ($base === '') {
    $docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
    $appRoot = realpath(XLL_ROOT);
    if ($docRoot && $appRoot) {
        $docRoot = str_replace('\\', '/', rtrim($docRoot, '/\\'));
        $appRoot = str_replace('\\', '/', $appRoot);
        if (str_starts_with($appRoot, $docRoot)) {
            $relative = substr($appRoot, strlen($docRoot));
            $base = $relative === '' ? '/' : $relative . '/';
        }
    }
}
$base = rtrim($base, '/');
if ($base === '') {
    $base = '/';
} else {
    $base .= '/';
}
define('XLL_BASE', $base);
define('XLL_UPLOAD_DIR', XLL_ROOT . '/data/uploads');
define('XLL_UPLOAD_URL', 'data/uploads');
define('XLL_BACKUP_DIR', XLL_ROOT . '/data/backups');
define('XLL_SEED_CSV', XLL_ROOT . '/data/seed/produtos.csv');

/* ---------------------------------------------------------------------- */
/* Sessão                                                                  */
/* ---------------------------------------------------------------------- */
if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    // Sessões ficam dentro da aplicação (funciona em hospedagem compartilhada
    // e em ambientes onde o session.save_path padrão não é gravável).
    $sessionDir = XLL_ROOT . '/data/sessions';
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0775, true);
    }
    if (is_dir($sessionDir)) {
        session_save_path($sessionDir);
    }
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    session_name('XLLSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    @session_start();
}

require_once XLL_ROOT . '/includes/defaults.php';
require_once XLL_ROOT . '/includes/functions.php';
require_once XLL_ROOT . '/includes/db.php';
require_once XLL_ROOT . '/includes/auth.php';
require_once XLL_ROOT . '/includes/uploads.php';
require_once XLL_ROOT . '/includes/importer.php';

/**
 * Configuração carregada.
 *
 * @param string|null $key
 * @param mixed       $default
 * @return mixed
 */
function xll_config(?string $key = null, mixed $default = null): mixed
{
    $config = $GLOBALS['XLL_CONFIG'] ?? [];

    return $key === null ? $config : ($config[$key] ?? $default);
}
