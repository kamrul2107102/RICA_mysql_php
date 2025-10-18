<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config.php';
require_once __DIR__ . '/../includes/sql.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function require_admin_auth() {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit();
    }
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetRequest();
            break;
        case 'POST':
            // Temporarily disabled for testing
            // require_admin_auth();
            handlePostRequest();
            break;
        case 'PUT':
            // Temporarily disabled for testing
            // require_admin_auth();
            handlePutRequest();
            break;
        case 'DELETE':
            // Temporarily disabled for testing
            // require_admin_auth();
            handleDeleteRequest();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetRequest() {
    global $conn;
    
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare(sql_named('journalQuery.sql', 'GET_ONE'));
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Journal not found']);
            return;
        }
        
        $journal = $result->fetch_assoc();
        
        // Get paper count
        $countStmt = $conn->prepare(sql_named('journalQuery.sql', 'COUNT_PAPERS_BY_ID'));
        $countStmt->bind_param("i", $id);
        $countStmt->execute();
        $journal['paper_count'] = $countStmt->get_result()->fetch_assoc()['paper_count'];
        
        echo json_encode($journal);
        return;
    }
    
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, (int)($_GET['limit'] ?? 50));
    $offset = ($page - 1) * $limit;
    
    $countQuery = sql_named('journalQuery.sql', 'COUNT_TOTAL');
    $total = $conn->query($countQuery)->fetch_assoc()['total'];
    
    $query = sql_named('journalQuery.sql', 'LIST_WITH_PAPER_COUNT');
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $journals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $journals,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function handlePostRequest() {
    global $conn;
    
    $data = get_json_input();
    
    // Debug logging
    error_log('=== POST REQUEST START ===');
    error_log('POST Data received: ' . print_r($data, true));
    error_log('Connection status: ' . ($conn->ping() ? 'Connected' : 'Disconnected'));
    
    if (!isset($data['name']) || empty($data['name'])) {
        error_log('ERROR: Journal name missing or empty');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Journal name is required', 'received_data' => $data]);
        return;
    }
    
    $name = sanitize_input($data['name']);
    $publisher = isset($data['publisher']) ? sanitize_input($data['publisher']) : null;
    $ISSN = isset($data['ISSN']) ? sanitize_input($data['ISSN']) : null;
    $impact_factor = isset($data['impact_factor']) ? (float)$data['impact_factor'] : 0.0;
    
    error_log("Sanitized values - Name: $name, Publisher: $publisher, ISSN: $ISSN, Impact: $impact_factor");
    
    // Check if journal already exists
    $checkStmt = $conn->prepare(sql_named('journalQuery.sql', 'CHECK_EXISTS_BY_NAME'));
    $checkStmt->bind_param("s", $name);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->fetch_row()[0];
    error_log("Duplicate check result: $exists");
    
    if ($exists > 0) {
        error_log('ERROR: Journal already exists with name: ' . $name);
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Journal with this name already exists']);
        return;
    }
    
    $stmt = $conn->prepare(sql_named('journalQuery.sql', 'INSERT'));
    $stmt->bind_param("sssd", $name, $publisher, $ISSN, $impact_factor);
    
    error_log('Executing INSERT query...');
    
    if ($stmt->execute()) {
        $journal_id = $conn->insert_id;
        error_log("SUCCESS: Journal inserted with ID: $journal_id");
        
        // Use prepared statement for GET_BY_ID
        $getStmt = $conn->prepare(sql_named('journalQuery.sql', 'GET_BY_ID'));
        $getStmt->bind_param("i", $journal_id);
        $getStmt->execute();
        $journal = $getStmt->get_result()->fetch_assoc();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Journal created successfully',
            'data' => $journal
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create journal: ' . $conn->error]);
    }
}

function handlePutRequest() {
    global $conn;
    
    $data = get_json_input();
    
    if (!isset($data['journal_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'journal_id is required']);
        return;
    }
    
    $journal_id = (int)$data['journal_id'];
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['name'])) {
        $updates[] = "name = ?";
        $params[] = sanitize_input($data['name']);
        $types .= 's';
    }
    
    if (isset($data['publisher'])) {
        $updates[] = "publisher = ?";
        $params[] = sanitize_input($data['publisher']);
        $types .= 's';
    }
    
    if (isset($data['ISSN'])) {
        $updates[] = "ISSN = ?";
        $params[] = sanitize_input($data['ISSN']);
        $types .= 's';
    }
    
    if (isset($data['impact_factor'])) {
        $updates[] = "impact_factor = ?";
        $params[] = (float)$data['impact_factor'];
        $types .= 'd';
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        return;
    }
    
    $params[] = $journal_id;
    $types .= 'i';
    
    $query = "UPDATE Journals SET " . implode(', ', $updates) . " WHERE journal_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Use prepared statement for GET_BY_ID
        $getStmt = $conn->prepare(sql_named('journalQuery.sql', 'GET_BY_ID'));
        $getStmt->bind_param("i", $journal_id);
        $getStmt->execute();
        $journal = $getStmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Journal updated successfully',
            'data' => $journal
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update journal: ' . $conn->error]);
    }
}

function handleDeleteRequest() {
    global $conn;
    
    $data = get_json_input();
    $id = isset($data['id']) ? (int)$data['id'] : null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Journal ID is required']);
        return;
    }
    
    // Check if journal has papers
    $checkStmt = $conn->prepare(sql_named('journalQuery.sql', 'CHECK_HAS_PAPERS'));
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->fetch_row()[0] > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Cannot delete journal with associated papers']);
        return;
    }
    
    $stmt = $conn->prepare(sql_named('journalQuery.sql', 'DELETE_BY_ID'));
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Journal deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete journal: ' . $conn->error]);
    }
}

$conn->close();
?>