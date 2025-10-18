<?php
// Helper to load SQL templates from queries/query directory

function sql_path(string $relative): string {
    $base = __DIR__ . '/../queries/query/';
    return $base . ltrim($relative, '/');
}

function sql_read(string $relative): string {
    $path = sql_path($relative);
    if (!file_exists($path)) {
        error_log('[SQL] Missing SQL file: ' . $path);
        return '';
    }
    $sql = trim(file_get_contents($path));
    return $sql;
}

// Named query loader using sections marked as:
// -- name: QUERY_NAME
// SQL ...
function sql_named(string $file, string $name): string {
    $content = sql_read($file);
    if ($content === '') return '';
    $pattern = '/^\s*--\s*name:\s*' . preg_quote($name, '/') . '\s*$\R((?:.|\R)*?)(?=^\s*--\s*name:|\z)/m';
    if (preg_match($pattern, $content, $m)) {
        return trim($m[1]);
    }
    error_log('[SQL] Named query not found: ' . $name . ' in ' . $file);
    return '';
}

// Load named query and replace tokens like /*WHERE*/ or {{TOKEN}}
function sql_named_with(string $file, string $name, array $replacements): string {
    $sql = sql_named($file, $name);
    foreach ($replacements as $key => $value) {
        // support both /*KEY*/ and {{KEY}}
        $sql = str_replace('/*' . $key . '*/', $value, $sql);
        $sql = str_replace('{{' . $key . '}}', $value, $sql);
    }
    return $sql;
}

?>
