<?php
require_once 'config.php';

// Destroy session and redirect to login
session_destroy();

// Clear any existing cookies
setcookie(session_name(), '', time() - 3600, '/');

header('Location: login.php');
exit();
?>