<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config.php';

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
            require_admin_auth();
            handlePostRequest();
            break;
        case 'PUT':
            require_admin_auth();
            handlePutRequest();
            break;
        case 'DELETE':
            require_admin_auth();
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
        $stmt = $conn->prepare("SELECT * FROM Journals WHERE journal_id = ?");
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
        $countStmt = $conn->prepare("SELECT COUNT(*) as paper_count FROM Papers WHERE journal_id = ?");
        $countStmt->bind_param("i", $id);
        $countStmt->execute();
        $journal['paper_count'] = $countStmt->get_result()->fetch_assoc()['paper_count'];
        
        echo json_encode($journal);
        return;
    }
    
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, (int)($_GET['limit'] ?? 50));
    $offset = ($page - 1) * $limit;
    
    $countQuery = "SELECT COUNT(*) as total FROM Journals";
    $total = $conn->query($countQuery)->fetch_assoc()['total'];
    
    $query = "
        SELECT j.*, 
               (SELECT COUNT(*) FROM Papers WHERE journal_id = j.journal_id) as paper_count
        FROM Journals j
        ORDER BY j.name
        LIMIT ? OFFSET ?
    ";
    
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
    
    if (!isset($data['name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Journal name is required']);
        return;
    }
    
    $name = sanitize_input($data['name']);
    $publisher = isset($data['publisher']) ? sanitize_input($data['publisher']) : null;
    $ISSN = isset($data['ISSN']) ? sanitize_input($data['ISSN']) : null;
    $impact_factor = isset($data['impact_factor']) ? (float)$data['impact_factor'] : 0.0;
    
    // Check if journal already exists
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Journals WHERE name = ?");
    $checkStmt->bind_param("s", $name);
    $checkStmt->execute();
    if ($checkStmt->get_result()->fetch_row()[0] > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Journal with this name already exists']);
        return;
    }
    
    $stmt = $conn->prepare("
        INSERT INTO Journals (name, publisher, ISSN, impact_factor) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("sssd", $name, $publisher, $ISSN, $impact_factor);
    
    if ($stmt->execute()) {
        $journal_id = $conn->insert_id;
        $journal = $conn->query("SELECT * FROM Journals WHERE journal_id = $journal_id")->fetch_assoc();
        
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
        $journal = $conn->query("SELECT * FROM Journals WHERE journal_id = $journal_id")->fetch_assoc();
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
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Papers WHERE journal_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->fetch_row()[0] > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Cannot delete journal with associated papers']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM Journals WHERE journal_id = ?");
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