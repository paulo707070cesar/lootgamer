<?php
require_once __DIR__ . '/bootstrap.php';
function admin_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    static $u = false;
    if ($u === false) $u = q('SELECT id,name,email,role FROM users WHERE id = ? AND is_active = 1', [$_SESSION['uid']])->fetch() ?: null;
    return $u;
}
function require_admin(): array {
    $u = admin_user();
    if (!$u) { redirect(url('admin/index.php')); }
    return $u;
}
function login_blocked(): bool {
    $h = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');
    q('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)');
    return (int)q('SELECT COUNT(*) FROM login_attempts WHERE ip_hash = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)', [$h])->fetchColumn() >= 5;
}
function login_fail(): void { q('INSERT INTO login_attempts (ip_hash) VALUES (?)', [hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '')]); }
function attempt_login(string $email, string $pass): bool {
    $u = q('SELECT * FROM users WHERE email = ? AND is_active = 1', [$email])->fetch();
    if ($u && password_verify($pass, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        q('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$u['id']]);
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
        log_admin('login', $email);
        return true;
    }
    login_fail();
    return false;
}
