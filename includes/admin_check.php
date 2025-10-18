<?php
require_once __DIR__ . '/auth_check.php';

if (!is_admin()) {
    http_response_code(403);
    exit('Access denied. Admin only.');
}

?>
