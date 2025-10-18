<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

if (!session_check_timeout()) {
    redirect('/login.php?timeout=1');
}

if (!is_logged_in()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? '/';
    redirect('/login.php');
}

?>
