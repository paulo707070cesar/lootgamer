<?php
/**
 * Loot Gamer - conexão MySQL, instalação e estrutura do banco.
 */

declare(strict_types=1);

if (!defined('XLL_SCHEMA_VERSION')) {
    define('XLL_SCHEMA_VERSION', '3');
}

/**
 * @return array{host:string,port:int,database:string,user:string,password:string,charset:string}
 */
function xll_db_config(): array
{
    $db = xll_config('db', []);

    return [
        'host'     => (string) ($db['host'] ?? '127.0.0.1'),
        'port'     => (int) ($db['port'] ?? 3306),
        'database' => (string) ($db['database'] ?? 'xll_gaming'),
        'user'     => (string) ($db['user'] ?? 'root'),
        'password' => (string) ($db['password'] ?? ''),
        'charset'  => (string) ($db['charset'] ?? 'utf8mb4'),
    ];
}

function xll_db_server(): PDO
{
    $cfg = xll_db_config();
    $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $cfg['host'], $cfg['port'], $cfg['charset']);

    return new PDO($dsn, $cfg['user'], $cfg['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

/**
 * Conexão principal com o banco da aplicação.
 */
function xll_db(): PDO
{
    if (isset($GLOBALS['XLL_PDO']) && $GLOBALS['XLL_PDO'] instanceof PDO) {
        return $GLOBALS['XLL_PDO'];
    }

    $cfg = xll_db_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['database'],
        $cfg['charset']
    );

    $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $GLOBALS['XLL_PDO'] = $pdo;
}

function xll_db_available(): bool
{
    try {
        xll_db();

        return true;
    } catch (Throwable) {
        return false;
    }
}

function xll_table_exists(string $table): bool
{
    try {
        $pdo = xll_db();
        $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));

        return (bool) $stmt->fetchColumn();
    } catch (Throwable) {
        return false;
    }
}

function xll_installed(): bool
{
    try {
        return xll_table_exists('configuracoes')
            && xll_table_exists('produtos')
            && xll_table_exists('admin_usuarios');
    } catch (Throwable) {
        return false;
    }
}

/**
 * Garante que o banco existe e está instalado; caso contrário mostra
 * uma página de erro amigável ou redireciona para o instalador.
 */
function xll_require_installed(): void
{
    try {
        xll_db();
    } catch (Throwable $error) {
        xll_output_db_error($error);
    }

    if (!xll_installed()) {
        if (basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'install.php') {
            return;
        }
        header('Location: ' . xll_url('install.php'));
        exit;
    }

    xll_migrate();
}

/**
 * Atualiza estrutura/configurações de instalações antigas sem perder dados.
 * Executa no máximo uma vez por versão de schema.
 */
function xll_migrate(): void
{
    try {
        if (!xll_installed()) {
            return;
        }
        $current = (string) xll_setting('schema_version', '');
        if ($current === XLL_SCHEMA_VERSION) {
            return;
        }

        $pdo = xll_db();
        foreach (xll_schema_statements() as $sql) {
            $pdo->exec($sql);
        }

        /* Garante que configurações novas entrem com valores padrão. */
        $insert = $pdo->prepare('INSERT IGNORE INTO configuracoes (chave, valor) VALUES (?, ?)');
        foreach (xll_defaults() as $key => $value) {
            $encoded = is_array($value)
                ? (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (string) $value;
            $insert->execute([$key, $encoded]);
        }

        if (version_compare($current !== '' ? $current : '1', '3', '<')) {
            xll_improve_product_slugs();
        }

        $save = $pdo->prepare(
            'INSERT INTO configuracoes (chave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        $save->execute(['schema_version', XLL_SCHEMA_VERSION]);
        xll_settings_cache_refresh();
    } catch (Throwable) {
        /* migração silenciosa: o site continua funcionando com o que já existe */
    }
}

/**
 * Gera slugs amigáveis para produtos que ainda usam o ID como slug.
 */
function xll_improve_product_slugs(): void
{
    try {
        $pdo = xll_db();
        $rows = $pdo->query('SELECT id, titulo, slug FROM produtos')->fetchAll();
        if (!$rows) {
            return;
        }

        $used = [];
        foreach ($rows as $row) {
            $current = trim((string) ($row['slug'] ?? ''));
            if ($current !== '' && $current !== (string) $row['id']) {
                $used[$current] = true;
            }
        }

        $update = $pdo->prepare('UPDATE produtos SET slug = ? WHERE id = ?');
        foreach ($rows as $row) {
            $current = trim((string) ($row['slug'] ?? ''));
            if ($current !== '' && $current !== (string) $row['id']) {
                continue;
            }
            $base = xll_generate_product_slug((string) $row['titulo'], (string) $row['id']);
            $slug = $base;
            $suffix = 2;
            while (isset($used[$slug])) {
                $slug = $base . '-' . $suffix;
                $suffix++;
            }
            $used[$slug] = true;
            $update->execute([$slug, (string) $row['id']]);
        }
    } catch (Throwable) {
    }
}

function xll_output_db_error(Throwable $error): never
{
    http_response_code(500);
    $message = e($error->getMessage());
    $configFile = e(XLL_ROOT . '/config/config.php');
    $install = e(xll_url('install.php'));
    echo <<<HTML
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Banco de dados indisponível — Loot Gamer</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:#01191f;color:#f4f4f5;font-family:Inter,system-ui,sans-serif;padding:24px}
.box{max-width:720px;background:#0d3840;border:1px solid #0d4450;border-radius:12px;padding:32px}
h1{font-size:22px;margin:0 0 8px;color:#fff}
p{color:#9bbcc2;line-height:1.6;font-size:14px}
code{background:#02222a;border:1px solid #0d4450;border-radius:6px;padding:2px 6px;color:#00e676;font-size:13px;word-break:break-all}
.err{background:#2a1113;border:1px solid #7f1d1d;color:#fecaca;border-radius:8px;padding:12px;font-size:13px;margin:16px 0;word-break:break-word}
a.btn{display:inline-block;margin-top:12px;background:#00e676;color:#062b24;text-decoration:none;font-weight:700;padding:10px 18px;border-radius:6px}
ol{color:#9bbcc2;font-size:14px;line-height:1.7;padding-left:20px}
</style></head><body>
<div class="box">
  <h1>Não foi possível conectar ao banco de dados MySQL</h1>
  <p>O site depende do MySQL para funcionar. Confira os passos abaixo.</p>
  <div class="err">{$message}</div>
  <ol>
    <li>Verifique se o MySQL está ligado (no XAMPP, inicie o serviço <strong>MySQL</strong>).</li>
    <li>Abra <code>{$configFile}</code> e confira <code>host</code>, <code>database</code>, <code>user</code> e <code>password</code>.</li>
    <li>Crie o banco no phpMyAdmin ou acesse <a href="{$install}" style="color:#00e676">{$install}</a> para instalar.</li>
  </ol>
  <a class="btn" href="{$install}">Abrir instalador</a>
</div>
</body></html>
HTML;
    exit;
}

/**
 * Lista de comandos SQL para criar a estrutura.
 *
 * @return array<int,string>
 */
function xll_schema_statements(): array
{
    return [
        "CREATE TABLE IF NOT EXISTS configuracoes (
            chave VARCHAR(80) NOT NULL,
            valor LONGTEXT NULL,
            atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (chave)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS produtos (
            id VARCHAR(64) NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            descricao LONGTEXT NULL,
            categoria VARCHAR(120) NOT NULL DEFAULT '',
            marketplace VARCHAR(120) NOT NULL DEFAULT '',
            selo VARCHAR(80) NOT NULL DEFAULT '',
            preco INT NOT NULL DEFAULT 0,
            preco_antigo INT NOT NULL DEFAULT 0,
            desconto INT NOT NULL DEFAULT 0,
            avaliacao DECIMAL(3,1) NULL,
            imagem LONGTEXT NULL,
            link_afiliado TEXT NULL,
            secao_home VARCHAR(80) NOT NULL DEFAULT '',
            ordem INT NOT NULL DEFAULT 0,
            slug VARCHAR(191) NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ordem (ordem),
            KEY idx_categoria (categoria),
            KEY idx_secao (secao_home),
            KEY idx_desconto (desconto)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS admin_usuarios (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario VARCHAR(100) NOT NULL,
            nome VARCHAR(150) NOT NULL DEFAULT '',
            senha_hash VARCHAR(255) NOT NULL,
            ultimo_login DATETIME NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_usuario (usuario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS importacoes (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            arquivo VARCHAR(255) NOT NULL DEFAULT '',
            modo VARCHAR(20) NOT NULL DEFAULT 'merge',
            total INT NOT NULL DEFAULT 0,
            inseridos INT NOT NULL DEFAULT 0,
            atualizados INT NOT NULL DEFAULT 0,
            removidos INT NOT NULL DEFAULT 0,
            erros INT NOT NULL DEFAULT 0,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS mensagens (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nome VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL,
            assunto VARCHAR(190) NOT NULL DEFAULT '',
            mensagem TEXT NULL,
            ip_hash CHAR(64) NOT NULL DEFAULT '',
            lido TINYINT(1) NOT NULL DEFAULT 0,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_lido (lido),
            KEY idx_criado (criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS cliques (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            produto_id VARCHAR(64) NOT NULL DEFAULT '',
            marketplace VARCHAR(120) NOT NULL DEFAULT '',
            ip_hash CHAR(64) NOT NULL DEFAULT '',
            user_agent VARCHAR(255) NOT NULL DEFAULT '',
            referer VARCHAR(255) NOT NULL DEFAULT '',
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_produto (produto_id),
            KEY idx_criado (criado_em),
            KEY idx_marketplace (marketplace)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}

/**
 * Cria o banco (se necessário), as tabelas, as configurações padrão,
 * o usuário do painel e importa o catálogo inicial.
 *
 * @return array<int,string> Passos executados.
 */
function xll_install_database(string $adminUser = '', string $adminPass = ''): array
{
    $steps = [];
    $cfg = xll_db_config();
    $database = $cfg['database'];
    if (preg_match('/^[A-Za-z0-9_]+$/', $database) !== 1) {
        throw new RuntimeException('Nome do banco inválido: use apenas letras, números e underline.');
    }

    $server = xll_db_server();
    $server->exec(
        'CREATE DATABASE IF NOT EXISTS `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    $steps[] = 'Banco de dados "' . $database . '" verificado/criado.';

    $pdo = xll_db();
    foreach (xll_schema_statements() as $sql) {
        $pdo->exec($sql);
    }
    $steps[] = 'Tabelas criadas/verificadas (configuracoes, produtos, admin_usuarios, importacoes, mensagens, cliques).';

    /* Configurações padrão: só insere as que ainda não existem. */
    $insert = $pdo->prepare('INSERT IGNORE INTO configuracoes (chave, valor) VALUES (?, ?)');
    $defaults = xll_defaults();
    foreach ($defaults as $key => $value) {
        $encoded = is_array($value)
            ? (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : (string) $value;
        $insert->execute([$key, $encoded]);
    }
    $insert->execute(['instalado_em', date('Y-m-d H:i:s')]);
    $steps[] = 'Configurações padrão do site aplicadas.';
    xll_settings_cache_refresh();

    /* Usuário administrador. */
    $installCfg = xll_config('install', []);
    $adminUser = trim($adminUser !== '' ? $adminUser : (string) ($installCfg['admin_user'] ?? 'admin'));
    $adminPass = $adminPass !== '' ? $adminPass : (string) ($installCfg['admin_pass'] ?? 'xll@2026');
    if ($adminUser === '' || strlen($adminPass) < 6) {
        throw new RuntimeException('Informe usuário e senha do admin (senha com pelo menos 6 caracteres).');
    }
    $exists = $pdo->prepare('SELECT id FROM admin_usuarios WHERE usuario = ? LIMIT 1');
    $exists->execute([$adminUser]);
    if (!$exists->fetchColumn()) {
        $stmt = $pdo->prepare('INSERT INTO admin_usuarios (usuario, nome, senha_hash) VALUES (?, ?, ?)');
        $stmt->execute([$adminUser, 'Administrador', password_hash($adminPass, PASSWORD_DEFAULT)]);
        $steps[] = 'Usuário do painel criado: ' . $adminUser;
    } else {
        $steps[] = 'Usuário do painel já existia: ' . $adminUser;
    }

    /* Catálogo inicial a partir do CSV de exemplo. */
    $total = (int) $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
    if ($total === 0 && is_file(XLL_SEED_CSV)) {
        $result = xll_import_csv(XLL_SEED_CSV, 'merge');
        $steps[] = 'Catálogo inicial importado: ' . $result['inseridos'] . ' produtos.';
    } else {
        $steps[] = 'Catálogo atual preservado (' . $total . ' produtos).';
    }

    /* URLs amigáveis: gera slugs descritivos para o catálogo. */
    xll_improve_product_slugs();
    $steps[] = 'Slugs amigáveis gerados para as URLs dos produtos.';

    $save = $pdo->prepare(
        'INSERT INTO configuracoes (chave, valor) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
    );
    $save->execute(['schema_version', XLL_SCHEMA_VERSION]);
    xll_settings_cache_refresh();

    return $steps;
}
