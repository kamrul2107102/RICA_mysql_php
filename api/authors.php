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
            // require_admin_auth(); // Temporarily disabled for testing
            handlePostRequest();
            break;
            
        case 'PUT':
            // require_admin_auth(); // Temporarily disabled for testing
            handlePutRequest();
            break;
            
        case 'DELETE':
            // require_admin_auth(); // Temporarily disabled for testing
            handleDeleteRequest();
            break;        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetRequest() {
    global $conn;
    
    // Get specific author by ID
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare(sql_named('authorQuery.sql', 'GET_ONE_WITH_INSTITUTION'));
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Author not found']);
            return;
        }
        
        echo json_encode($result->fetch_assoc());
        return;
    }
    
    // Get authors with pagination and filtering
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;
    $offset = ($page - 1) * $limit;
    
    $whereClause = "";
    $params = [];
    $types = "";
    
    // Filter by institution
    if (isset($_GET['institution_id'])) {
        $whereClause = "WHERE a.institution_id = ?";
        $params[] = (int)$_GET['institution_id'];
        $types .= "i";
    }
    
    // Filter by field of study
    if (isset($_GET['field'])) {
        $whereClause = $whereClause ? "AND a.field_of_study LIKE ?" : "WHERE a.field_of_study LIKE ?";
        $params[] = "%" . $_GET['field'] . "%";
        $types .= "s";
    }
    
    // Get total count
    $countQuery = sql_named_with('authorQuery.sql', 'COUNT_WITH_FILTERS', ['WHERE' => $whereClause]);
    $countStmt = $conn->prepare($countQuery);
    if ($params) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalResult = $countStmt->get_result();
    $total = $totalResult->fetch_assoc()['total'];
    
    // Get authors data
    $query = sql_named_with('authorQuery.sql', 'LIST_WITH_FILTERS', ['WHERE' => $whereClause]);
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $authors = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $authors,
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
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['name']) || !isset($data['email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Name and email are required']);
        return;
    }
    
    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        return;
    }
    
    // Check if email already exists
    if (emailExists($data['email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email already exists']);
        return;
    }
    
    // Validate institution exists if provided
    if (isset($data['institution_id']) && $data['institution_id'] && !institutionExists($data['institution_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Institution does not exist']);
        return;
    }
    
    $name = sanitize_input($data['name']);
    $email = sanitize_input($data['email']);
    $field_of_study = isset($data['field_of_study']) ? sanitize_input($data['field_of_study']) : null;
    $institution_id = isset($data['institution_id']) ? (int)$data['institution_id'] : null;
    
    $stmt = $conn->prepare(sql_named('authorQuery.sql', 'INSERT'));
    $stmt->bind_param("sssi", $name, $email, $field_of_study, $institution_id);
    
    if ($stmt->execute()) {
        $author_id = $conn->insert_id;
        
        // Get the created author with full details
        $stmt = $conn->prepare(sql_named('authorQuery.sql', 'GET_ONE_WITH_INSTITUTION'));
        $stmt->bind_param("i", $author_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $author = $result->fetch_assoc();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Author created successfully',
            'data' => $author
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create author: ' . $conn->error]);
    }
}

function handlePutRequest() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['author_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'author_id is required']);
        return;
    }
    
    $author_id = (int)$data['author_id'];
    
    // Check if author exists
    if (!authorExists($author_id)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Author not found']);
        return;
    }
    
    // Validate email if provided
    if (isset($data['email'])) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid email format']);
            return;
        }
        
        // Check if email already exists (excluding current author)
        if (emailExists($data['email'], $author_id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email already exists']);
            return;
        }
    }
    
    // Validate institution exists if provided
    if (isset($data['institution_id']) && $data['institution_id'] && !institutionExists($data['institution_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Institution does not exist']);
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
    
    if (isset($data['email'])) {
        $updates[] = "email = ?";
        $params[] = sanitize_input($data['email']);
        $types .= 's';
    }
    
    if (isset($data['field_of_study'])) {
        $updates[] = "field_of_study = ?";
        $params[] = sanitize_input($data['field_of_study']);
        $types .= 's';
    }
    
    if (isset($data['institution_id'])) {
        $updates[] = "institution_id = ?";
        $params[] = $data['institution_id'] ? (int)$data['institution_id'] : null;
        $types .= 'i';
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        return;
    }
    
    $params[] = $author_id;
    $types .= 'i';
    
    $query = "UPDATE Authors SET " . implode(', ', $updates) . " WHERE author_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Get the updated author
        $stmt = $conn->prepare(sql_named('authorQuery.sql', 'GET_ONE_WITH_INSTITUTION'));
        $stmt->bind_param("i", $author_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $author = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Author updated successfully',
            'data' => $author
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update author: ' . $conn->error]);
    }
}

function handleDeleteRequest() {
    global $conn;
    
    parse_str(file_get_contents("php://input"), $data);
    $id = isset($data['id']) ? (int)$data['id'] : null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Author ID is required']);
        return;
    }
    
    // Check if author exists
    if (!authorExists($id)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Author not found']);
        return;
    }
    
    // Check if author has papers
    if (authorHasPapers($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Cannot delete author with associated papers']);
        return;
    }
    
    $stmt = $conn->prepare(sql_named('authorQuery.sql', 'DELETE_BY_ID'));
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Author deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete author: ' . $conn->error]);
    }
}

// Helper functions
function authorExists($author_id) {
    global $conn;
    $stmt = $conn->prepare(sql_named('authorQuery.sql', 'CHECK_EXISTS_BY_ID'));
    $stmt->bind_param("i", $author_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0] > 0;
}

function emailExists($email, $exclude_author_id = null) {
    global $conn;
    if ($exclude_author_id) {
        $stmt = $conn->prepare(sql_named('authorQuery.sql', 'CHECK_EMAIL_EXISTS_EXCLUDE'));
        $stmt->bind_param("si", $email, $exclude_author_id);
    } else {
        $stmt = $conn->prepare(sql_named('authorQuery.sql', 'CHECK_EMAIL_EXISTS'));
        $stmt->bind_param("s", $email);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0] > 0;
}

function institutionExists($institution_id) {
    global $conn;
    $stmt = $conn->prepare(sql_named('authorQuery.sql', 'CHECK_INSTITUTION_EXISTS'));
    $stmt->bind_param("i", $institution_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0] > 0;
}

function authorHasPapers($author_id) {
    global $conn;
    $stmt = $conn->prepare(sql_named('authorQuery.sql', 'CHECK_HAS_PAPERS'));
    $stmt->bind_param("i", $author_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0] > 0;
}

// Close connection
$conn->close();
?>