<?php
/**
 * Loot Gamer - autenticação do painel administrativo.
 */

declare(strict_types=1);

function xll_admin_url(string $path = ''): string
{
    return xll_url('admin/' . ltrim($path, '/'));
}

/**
 * @return array{id:int,usuario:string,nome:string}|null
 */
function xll_admin_user(): ?array
{
    $user = $_SESSION['xll_admin'] ?? null;

    return is_array($user) ? $user : null;
}

function xll_admin_logged_in(): bool
{
    return xll_admin_user() !== null;
}

function xll_admin_name(): string
{
    $user = xll_admin_user();

    return $user ? (string) ($user['nome'] !== '' ? $user['nome'] : $user['usuario']) : '';
}

function xll_admin_login_blocked_for(): int
{
    $until = (int) ($_SESSION['_xll_login_block_until'] ?? 0);
    $remaining = $until - time();

    return max(0, $remaining);
}

/**
 * Tenta autenticar. Retorna [sucesso, mensagem].
 *
 * @return array{0:bool,1:string}
 */
function xll_admin_login(string $usuario, string $senha): array
{
    $usuario = trim($usuario);
    if ($usuario === '' || $senha === '') {
        return [false, 'Informe usuário e senha.'];
    }

    $blocked = xll_admin_login_blocked_for();
    if ($blocked > 0) {
        return [false, 'Muitas tentativas. Aguarde ' . $blocked . ' segundos para tentar novamente.'];
    }

    $stmt = xll_db()->prepare('SELECT * FROM admin_usuarios WHERE usuario = ? LIMIT 1');
    $stmt->execute([$usuario]);
    $row = $stmt->fetch();

    $valid = is_array($row) && password_verify($senha, (string) $row['senha_hash']);
    if (!$valid) {
        $_SESSION['_xll_login_attempts'] = (int) ($_SESSION['_xll_login_attempts'] ?? 0) + 1;
        if ($_SESSION['_xll_login_attempts'] >= 5) {
            $_SESSION['_xll_login_block_until'] = time() + 300;
            $_SESSION['_xll_login_attempts'] = 0;
        }
        usleep(350000);

        return [false, 'Usuário ou senha inválidos.'];
    }

    unset($_SESSION['_xll_login_attempts'], $_SESSION['_xll_login_block_until']);
    session_regenerate_id(true);
    $_SESSION['xll_admin'] = [
        'id'      => (int) $row['id'],
        'usuario' => (string) $row['usuario'],
        'nome'    => (string) ($row['nome'] ?? ''),
    ];

    $update = xll_db()->prepare('UPDATE admin_usuarios SET ultimo_login = NOW() WHERE id = ?');
    $update->execute([(int) $row['id']]);

    return [true, 'Bem-vindo de volta, ' . (string) ($row['nome'] !== '' ? $row['nome'] : $row['usuario']) . '!'];
}

function xll_admin_logout(): void
{
    unset($_SESSION['xll_admin']);
    session_regenerate_id(true);
}

/**
 * Redireciona para o login quando não há sessão ativa.
 */
function xll_require_admin(): void
{
    if (xll_admin_logged_in()) {
        return;
    }
    $_SESSION['_xll_admin_next'] = $_SERVER['REQUEST_URI'] ?? xll_admin_url();
    xll_redirect('admin/login.php');
}

/**
 * Altera a senha do usuário logado.
 *
 * @return array{0:bool,1:string}
 */
function xll_admin_change_password(string $atual, string $nova, string $confirmacao): array
{
    $user = xll_admin_user();
    if (!$user) {
        return [false, 'Sessão expirada. Faça login novamente.'];
    }
    if (strlen($nova) < 6) {
        return [false, 'A nova senha precisa ter pelo menos 6 caracteres.'];
    }
    if ($nova !== $confirmacao) {
        return [false, 'A confirmação da nova senha não confere.'];
    }

    $stmt = xll_db()->prepare('SELECT senha_hash FROM admin_usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $user['id']]);
    $hash = $stmt->fetchColumn();
    if (!is_string($hash) || !password_verify($atual, $hash)) {
        return [false, 'A senha atual está incorreta.'];
    }

    $update = xll_db()->prepare('UPDATE admin_usuarios SET senha_hash = ? WHERE id = ?');
    $update->execute([password_hash($nova, PASSWORD_DEFAULT), (int) $user['id']]);

    return [true, 'Senha alterada com sucesso.'];
}
