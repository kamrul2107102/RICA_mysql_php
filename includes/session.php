<?php
// Session management utilities

if (session_status() === PHP_SESSION_NONE) {
    // Customize session settings if needed
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    // Set to 1 when serving over HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// 30 minutes timeout by default
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800);
}

function session_check_timeout(): bool {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - (int)$_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['LAST_ACTIVITY'] = time();
    return true;
}

function session_regenerate(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function session_set(string $key, $value): void {
    $_SESSION[$key] = $value;
}

function session_get(string $key, $default = null) {
    return $_SESSION[$key] ?? $default;
}

function session_forget(string $key): void {
    if (isset($_SESSION[$key])) unset($_SESSION[$key]);
}

function session_destroy_all(): void {
    session_unset();
    session_destroy();
}

?>
