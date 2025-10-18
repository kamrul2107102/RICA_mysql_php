<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config.php';
require_once __DIR__ . '/../includes/sql.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please login first']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        handlePostRequest();
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function handlePostRequest() {
    global $conn;
    
    $data = get_json_input();
    $action = $data['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'query':
            handleQuery($data);
            break;
            
        case 'reset_counters':
            handleResetCounters();
            break;
            
        case 'database_stats':
            handleDatabaseStats();
            break;
            
        case 'system_info':
            handleSystemInfo();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}

function handleQuery($data) {
    global $conn;
    
    $query = $data['query'] ?? '';
    
    if (empty($query)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Query is required']);
        return;
    }
    
    // Security: Only allow SELECT queries
    $trimmedQuery = trim($query);
    if (stripos($trimmedQuery, 'SELECT') !== 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Only SELECT queries are allowed for security']);
        return;
    }
    
    // Prevent potentially dangerous queries
    $forbiddenPatterns = [
        '/\b(INSERT|UPDATE|DELETE|DROP|ALTER|CREATE|TRUNCATE|EXEC|UNION|LOAD_FILE|OUTFILE|DUMPFILE)\b/i',
        '/\/\*.*\*\//',
        '/--/',
        '/;/.*$/'
    ];
    
    foreach ($forbiddenPatterns as $pattern) {
        if (preg_match($pattern, $query)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Query contains forbidden patterns']);
            return;
        }
    }
    
    // Limit query execution time
    set_time_limit(30); // 30 seconds max
    
    try {
        $result = $conn->query($query);
        
        if ($result === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Query error: ' . $conn->error]);
            return;
        }
        
        // Handle different result types
        if ($result === true) {
            // For queries that don't return results (shouldn't happen with SELECT only)
            echo json_encode([
                'success' => true,
                'message' => 'Query executed successfully',
                'affected_rows' => $conn->affected_rows
            ]);
        } else {
            // For SELECT queries
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $num_rows = $result->num_rows;
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'metadata' => [
                    'row_count' => $num_rows,
                    'field_count' => $result->field_count,
                    'execution_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4)
                ]
            ]);
            
            $result->free();
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Query execution failed: ' . $e->getMessage()]);
    }
}

function handleResetCounters() {
    global $conn;
    
    $tables = [
        'Authors' => 'author_id',
        'Papers' => 'paper_id',
        'Journals' => 'journal_id',
        'Institutions' => 'institution_id',
        'Conferences' => 'conference_id',
        'Citations' => 'citation_id'
    ];
    
    $results = [];
    
    foreach ($tables as $table => $id_column) {
        $query = sql_named_with('adminQuery.sql', 'GET_TABLE_MAX_ID', [
            'TABLE' => $table,
            'ID_COLUMN' => $id_column
        ]);
        $result = $conn->query($query);
        $max_id = $result->fetch_assoc()['max_id'] + 1;
        
        $resetQuery = sql_named_with('adminQuery.sql', 'RESET_AUTO_INCREMENT', ['TABLE' => $table]);
        if ($conn->query($resetQuery)) {
            $results[$table] = ['success' => true, 'new_auto_increment' => $max_id];
        } else {
            $results[$table] = ['success' => false, 'error' => $conn->error];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Auto-increment counters reset',
        'results' => $results
    ]);
}

function handleDatabaseStats() {
    global $conn;
    
    $tables = ['Authors', 'Papers', 'Journals', 'Institutions', 'Conferences', 'Citations'];
    $stats = [];
    
    foreach ($tables as $table) {
        $countQuery = sql_named_with('adminQuery.sql', 'COUNT_TABLE_ROWS', ['TABLE' => $table]);
        $result = $conn->query($countQuery);
        $stats[$table] = $result->fetch_assoc()['count'];
    }
    
    // Get database size
    $sizeQuery = sql_named('adminQuery.sql', 'GET_DATABASE_SIZE');
    $stmt = $conn->prepare($sizeQuery);
    $dbName = DB_NAME;
    $stmt->bind_param('s', $dbName);
    $stmt->execute();
    $sizeResult = $stmt->get_result();
    $dbSize = $sizeResult->fetch_assoc()['size_mb'];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'database_size_mb' => $dbSize,
        'server_time' => date('Y-m-d H:i:s')
    ]);
}

function handleSystemInfo() {
    global $conn;
    
    $info = [
        'php_version' => phpversion(),
        'mysql_version' => $conn->server_info,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
        'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'request_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
        'max_execution_time' => ini_get('max_execution_time') . ' seconds',
        'memory_limit' => ini_get('memory_limit')
    ];
    
    // Get MySQL status
    $statusQuery = sql_named('adminQuery.sql', 'SHOW_MYSQL_UPTIME');
    $statusResult = $conn->query($statusQuery);
    $uptime = $statusResult->fetch_assoc()['Value'];
    $info['mysql_uptime_seconds'] = $uptime;
    $info['mysql_uptime_formatted'] = gmdate("H:i:s", $uptime);
    
    echo json_encode([
        'success' => true,
        'system_info' => $info
    ]);
}

// Close connection
$conn->close();
?>