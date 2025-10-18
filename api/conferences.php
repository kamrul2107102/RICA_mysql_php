<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config.php';
require_once __DIR__ . '/../includes/sql.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is logged in for write operations
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
    
    // Get specific conference by ID
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare(sql_named('conferenceQuery.sql', 'GET_ONE'));
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Conference not found']);
            return;
        }
        
        echo json_encode($result->fetch_assoc());
        return;
    }
    
    // Get all conferences with pagination and filtering
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, (int)($_GET['limit'] ?? 50));
    $offset = ($page - 1) * $limit;
    
    $whereClause = "";
    $params = [];
    $types = "";
    
    // Filter by year
    if (isset($_GET['year'])) {
        $whereClause = "WHERE year = ?";
        $params[] = (int)$_GET['year'];
        $types .= "i";
    }
    
    // Filter by location
    if (isset($_GET['location'])) {
        $whereClause = $whereClause ? "AND location LIKE ?" : "WHERE location LIKE ?";
        $params[] = "%" . $_GET['location'] . "%";
        $types .= "s";
    }
    
    // Get total count
    $countSql = sql_named_with('conferenceQuery.sql', 'COUNT_WITH_FILTERS', ['WHERE' => $whereClause]);
    $countStmt = $conn->prepare($countSql);
    if ($params) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Get conferences data
    $query = sql_named_with('conferenceQuery.sql', 'LIST_WITH_FILTERS', ['WHERE' => $whereClause]);
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $conferences = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $conferences,
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
    
    // Validate required fields
    if (!isset($data['name']) || !isset($data['year'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Name and year are required']);
        return;
    }
    
    // Validate year range
    $year = (int)$data['year'];
    if ($year < 1900 || $year > 2100) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Year must be between 1900 and 2100']);
        return;
    }
    
    $name = sanitize_input($data['name']);
    $location = isset($data['location']) ? sanitize_input($data['location']) : null;
    $organizer = isset($data['organizer']) ? sanitize_input($data['organizer']) : 'Unknown';
    
    // Check if conference already exists (same name and year)
    $checkStmt = $conn->prepare(sql_named('conferenceQuery.sql', 'CHECK_EXISTS_BY_NAME_YEAR'));
    $checkStmt->bind_param("si", $name, $year);
    $checkStmt->execute();
    if ($checkStmt->get_result()->fetch_row()[0] > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Conference with this name and year already exists']);
        return;
    }
    
    $stmt = $conn->prepare(sql_named('conferenceQuery.sql', 'INSERT'));
    $stmt->bind_param("ssis", $name, $location, $year, $organizer);
    
    if ($stmt->execute()) {
        $conference_id = $conn->insert_id;
        
        // Get the created conference with paper count
        $stmt = $conn->prepare(sql_named('conferenceQuery.sql', 'GET_ONE'));
        $stmt->bind_param("i", $conference_id);
        $stmt->execute();
        $conference = $stmt->get_result()->fetch_assoc();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Conference created successfully',
            'data' => $conference
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create conference: ' . $conn->error]);
    }
}

function handlePutRequest() {
    global $conn;
    
    $data = get_json_input();
    
    if (!isset($data['conference_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'conference_id is required']);
        return;
    }
    
    $conference_id = (int)$data['conference_id'];
    
    // Check if conference exists
    $checkStmt = $conn->prepare(sql_named('conferenceQuery.sql', 'CHECK_EXISTS_BY_ID'));
    $checkStmt->bind_param("i", $conference_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->fetch_row()[0] === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Conference not found']);
        return;
    }
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['name'])) {
        $updates[] = "name = ?";
        $params[] = sanitize_input($data['name']);
        $types .= 's';
    }
    
    if (isset($data['location'])) {
        $updates[] = "location = ?";
        $params[] = sanitize_input($data['location']);
        $types .= 's';
    }
    
    if (isset($data['year'])) {
        $year = (int)$data['year'];
        if ($year < 1900 || $year > 2100) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Year must be between 1900 and 2100']);
            return;
        }
        $updates[] = "year = ?";
        $params[] = $year;
        $types .= 'i';
    }
    
    if (isset($data['organizer'])) {
        $updates[] = "organizer = ?";
        $params[] = sanitize_input($data['organizer']);
        $types .= 's';
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        return;
    }
    
    $params[] = $conference_id;
    $types .= 'i';
    
    $query = "UPDATE Conferences SET " . implode(', ', $updates) . " WHERE conference_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Get the updated conference
        $stmt = $conn->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM Papers WHERE conference_id = c.conference_id) as paper_count
            FROM Conferences c 
            WHERE c.conference_id = ?
        ");
        $stmt->bind_param("i", $conference_id);
        $stmt->execute();
        $conference = $stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Conference updated successfully',
            'data' => $conference
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update conference: ' . $conn->error]);
    }
}

function handleDeleteRequest() {
    global $conn;
    
    $data = get_json_input();
    $id = isset($data['id']) ? (int)$data['id'] : null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Conference ID is required']);
        return;
    }
    
    // Check if conference exists
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Conferences WHERE conference_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->fetch_row()[0] === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Conference not found']);
        return;
    }
    
    // Check if conference has associated papers
    $paperCheck = $conn->prepare(sql_named('conferenceQuery.sql', 'COUNT_PAPERS_BY_ID'));
    $paperCheck->bind_param("i", $id);
    $paperCheck->execute();
    if ($paperCheck->get_result()->fetch_row()[0] > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Cannot delete conference with associated papers']);
        return;
    }
    
    $stmt = $conn->prepare(sql_named('conferenceQuery.sql', 'DELETE_BY_ID'));
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Conference deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete conference: ' . $conn->error]);
    }
}

// Close connection
$conn->close();
?>