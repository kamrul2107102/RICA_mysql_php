<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="export.csv"');

require_once '../config.php';

if (!is_logged_in()) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Authentication required');
}

$table = $_GET['table'] ?? '';
$type = $_GET['type'] ?? 'table';

$allowedTables = ['Authors', 'Papers', 'Journals', 'Institutions', 'Conferences', 'Citations'];
$allowedTypes = ['table', 'full'];

if (!in_array($table, $allowedTables) || !in_array($type, $allowedTypes)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid table or type specified');
}

if ($type === 'full') {
    exportFullDatabase();
} else {
    exportTable($table);
}

function exportTable($table) {
    global $conn;
    
    $result = $conn->query("SELECT * FROM $table");
    $output = fopen('php://output', 'w');
    
    // Write headers
    $fields = $result->fetch_fields();
    $headers = array();
    foreach ($fields as $field) {
        $headers[] = $field->name;
    }
    fputcsv($output, $headers);
    
    // Write data
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    
    fclose($output);
}

function exportFullDatabase() {
    global $conn;
    
    $output = fopen('php://output', 'w');
    
    // Get all tables
    $tables = $conn->query("SHOW TABLES");
    $tableData = [];
    
    while ($table = $tables->fetch_row()) {
        $tableName = $table[0];
        $result = $conn->query("SELECT * FROM $tableName");
        
        // Write table header
        fputcsv($output, ["=== TABLE: $tableName ==="]);
        
        // Write column headers
        $fields = $result->fetch_fields();
        $headers = array();
        foreach ($fields as $field) {
            $headers[] = $field->name;
        }
        fputcsv($output, $headers);
        
        // Write data
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        
        // Add empty line between tables
        fputcsv($output, []);
        fputcsv($output, []);
    }
    
    fclose($output);
}

$conn->close();
?>