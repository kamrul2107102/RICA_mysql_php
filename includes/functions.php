<?php
// Common helper functions

function sanitize($value) {
    if (is_array($value)) return array_map('sanitize', $value);
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url, int $code = 302): void {
    header('Location: ' . $url, true, $code);
    exit;
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function is_admin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function flash_set(string $type, string $message): void {
    $_SESSION['flash'][$type] = $message;
}

function flash_get(?string $type = null) {
    if ($type === null) {
        $all = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $all;
    }
    if (!empty($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

?>
