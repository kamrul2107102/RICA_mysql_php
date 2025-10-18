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
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetRequest() {
    global $conn;
    
    // Get specific citation by ID
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare(sql_named('citationQuery.sql', 'GET_ONE_WITH_DETAILS'));
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Citation not found']);
            return;
        }
        
        echo json_encode($result->fetch_assoc());
        return;
    }
    
    // Get citations for a specific paper
    if (isset($_GET['paper_id'])) {
        $paper_id = (int)$_GET['paper_id'];
        $type = $_GET['type'] ?? 'both'; // citing, cited, or both
        
        if ($type === 'citing') {
            $query = sql_named('citationQuery.sql', 'GET_FOR_PAPER_CITING');
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $paper_id);
        } elseif ($type === 'cited') {
            $query = sql_named('citationQuery.sql', 'GET_FOR_PAPER_CITED');
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $paper_id);
        } else {
            $query = sql_named('citationQuery.sql', 'GET_FOR_PAPER_BOTH');
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $paper_id, $paper_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $citations = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode($citations);
        return;
    }
    
    // Get all citations with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $total_result = $conn->query(sql_named('citationQuery.sql', 'COUNT_TOTAL'));
    $total = $total_result->fetch_assoc()['total'];
    
    // Get citations with paper details
    $stmt = $conn->prepare(sql_named('citationQuery.sql', 'LIST_WITH_DETAILS'));
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $citations = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $citations,
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
    if (!isset($data['citing_paper_id']) || !isset($data['cited_paper_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields: citing_paper_id and cited_paper_id']);
        return;
    }
    
    $citing_paper_id = (int)$data['citing_paper_id'];
    $cited_paper_id = (int)$data['cited_paper_id'];
    $citation_date = isset($data['citation_date']) ? $data['citation_date'] : date('Y-m-d');
    
    // Check if papers exist
    if (!paperExists($citing_paper_id) || !paperExists($cited_paper_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'One or both papers do not exist']);
        return;
    }
    
    // Check for self-citation
    if ($citing_paper_id === $cited_paper_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Self-citation is not allowed']);
        return;
    }
    
    // Check if citation already exists
    if (citationExists($citing_paper_id, $cited_paper_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Citation already exists']);
        return;
    }
    
    $stmt = $conn->prepare(sql_named('citationQuery.sql', 'INSERT'));
    $stmt->bind_param("iis", $citing_paper_id, $cited_paper_id, $citation_date);
    
    if ($stmt->execute()) {
        $citation_id = $conn->insert_id;
        
        // Get the created citation with full details
        $stmt = $conn->prepare(sql_named('citationQuery.sql', 'GET_ONE_WITH_DETAILS'));
        $stmt->bind_param("i", $citation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $citation = $result->fetch_assoc();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Citation created successfully',
            'data' => $citation
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create citation: ' . $conn->error]);
    }
}

function handlePutRequest() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['citation_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'citation_id is required']);
        return;
    }
    
    $citation_id = (int)$data['citation_id'];
    
    // Check if citation exists
    if (!citationExistsById($citation_id)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Citation not found']);
        return;
    }
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['citation_date'])) {
        $updates[] = "citation_date = ?";
        $params[] = $data['citation_date'];
        $types .= 's';
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        return;
    }
    
    $params[] = $citation_id;
    $types .= 'i';
    
    $query = "UPDATE Citations SET " . implode(', ', $updates) . " WHERE citation_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Get the updated citation
        $stmt = $conn->prepare(sql_named('citationQuery.sql', 'GET_ONE_WITH_DETAILS'));
        $stmt->bind_param("i", $citation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $citation = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Citation updated successfully',
            'data' => $citation
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update citation: ' . $conn->error]);
    }
}

function handleDeleteRequest() {
    global $conn;
    
    parse_str(file_get_contents("php://input"), $data);
    $id = isset($data['id']) ? (int)$data['id'] : null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Citation ID is required']);
        return;
    }
    
    // Check if citation exists
    if (!citationExistsById($id)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Citation not found']);
        return;
    }
    
    $stmt = $conn->prepare(sql_named('citationQuery.sql', 'DELETE_BY_ID'));
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Citation deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete citation: ' . $conn->error]);
    }
}

// Helper functions
function paperExists($paper_id) {
    global $conn;
    $stmt = $conn->prepare(sql_named('citationQuery.sql', 'CHECK_PAPER_EXISTS'));
    $stmt->bind_param("i", $paper_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0] > 0;
}

function citationExists($citing_paper_id, $cited_paper_id) {
    global $conn;
    $stmt = $conn->prepare(sql_named('citationQuery.sql', 'CHECK_CITATION_EXISTS'));
    $stmt->bind_param("ii", $citing_paper_id, $cited_paper_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0] > 0;
}

function citationExistsById($citation_id) {
    global $conn;
    $stmt = $conn->prepare(sql_named('citationQuery.sql', 'CHECK_EXISTS_BY_ID'));
    $stmt->bind_param("i", $citation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0] > 0;
}

// Close connection
$conn->close();
?>