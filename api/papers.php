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
        $stmt = $conn->prepare("
            SELECT p.*, j.name as journal_name, c.name as conference_name,
                   (SELECT COUNT(*) FROM Citations WHERE cited_paper_id = p.paper_id) as citation_count
            FROM Papers p 
            LEFT JOIN Journals j ON p.journal_id = j.journal_id 
            LEFT JOIN Conferences c ON p.conference_id = c.conference_id 
            WHERE p.paper_id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Paper not found']);
            return;
        }
        
        $paper = $result->fetch_assoc();
        
        // Get authors
        $authorStmt = $conn->prepare("
            SELECT a.*, ap.author_role 
            FROM Authors a 
            JOIN Authorship ap ON a.author_id = ap.author_id 
            WHERE ap.paper_id = ?
        ");
        $authorStmt->bind_param("i", $id);
        $authorStmt->execute();
        $paper['authors'] = $authorStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode($paper);
        return;
    }
    
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, (int)($_GET['limit'] ?? 50));
    $offset = ($page - 1) * $limit;
    
    $whereClause = "";
    $params = [];
    $types = "";
    
    if (isset($_GET['journal_id'])) {
        $whereClause = "WHERE p.journal_id = ?";
        $params[] = (int)$_GET['journal_id'];
        $types .= "i";
    }
    
    if (isset($_GET['conference_id'])) {
        $whereClause = $whereClause ? "AND p.conference_id = ?" : "WHERE p.conference_id = ?";
        $params[] = (int)$_GET['conference_id'];
        $types .= "i";
    }
    
    if (isset($_GET['year'])) {
        $whereClause = $whereClause ? "AND p.publication_year = ?" : "WHERE p.publication_year = ?";
        $params[] = (int)$_GET['year'];
        $types .= "i";
    }
    
    $countQuery = "SELECT COUNT(*) as total FROM Papers p $whereClause";
    $countStmt = $conn->prepare($countQuery);
    if ($params) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    $query = "
        SELECT p.*, j.name as journal_name, c.name as conference_name,
               (SELECT COUNT(*) FROM Citations WHERE cited_paper_id = p.paper_id) as citation_count
        FROM Papers p 
        LEFT JOIN Journals j ON p.journal_id = j.journal_id 
        LEFT JOIN Conferences c ON p.conference_id = c.conference_id 
        $whereClause
        ORDER BY p.publication_year DESC, p.paper_id DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $papers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $papers,
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
    
    if (!isset($data['title']) || !isset($data['publication_year'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Title and publication year are required']);
        return;
    }
    
    $title = sanitize_input($data['title']);
    $publication_year = (int)$data['publication_year'];
    $abstract = isset($data['abstract']) ? sanitize_input($data['abstract']) : null;
    $conference_id = isset($data['conference_id']) ? (int)$data['conference_id'] : null;
    $journal_id = isset($data['journal_id']) ? (int)$data['journal_id'] : null;
    
    $stmt = $conn->prepare("
        INSERT INTO Papers (title, publication_year, abstract, conference_id, journal_id) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sisii", $title, $publication_year, $abstract, $conference_id, $journal_id);
    
    if ($stmt->execute()) {
        $paper_id = $conn->insert_id;
        
        $stmt = $conn->prepare("
            SELECT p.*, j.name as journal_name, c.name as conference_name
            FROM Papers p 
            LEFT JOIN Journals j ON p.journal_id = j.journal_id 
            LEFT JOIN Conferences c ON p.conference_id = c.conference_id 
            WHERE p.paper_id = ?
        ");
        $stmt->bind_param("i", $paper_id);
        $stmt->execute();
        $paper = $stmt->get_result()->fetch_assoc();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Paper created successfully',
            'data' => $paper
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create paper: ' . $conn->error]);
    }
}

function handlePutRequest() {
    global $conn;
    
    $data = get_json_input();
    
    if (!isset($data['paper_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'paper_id is required']);
        return;
    }
    
    $paper_id = (int)$data['paper_id'];
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['title'])) {
        $updates[] = "title = ?";
        $params[] = sanitize_input($data['title']);
        $types .= 's';
    }
    
    if (isset($data['publication_year'])) {
        $updates[] = "publication_year = ?";
        $params[] = (int)$data['publication_year'];
        $types .= 'i';
    }
    
    if (isset($data['abstract'])) {
        $updates[] = "abstract = ?";
        $params[] = sanitize_input($data['abstract']);
        $types .= 's';
    }
    
    if (isset($data['conference_id'])) {
        $updates[] = "conference_id = ?";
        $params[] = $data['conference_id'] ? (int)$data['conference_id'] : null;
        $types .= 'i';
    }
    
    if (isset($data['journal_id'])) {
        $updates[] = "journal_id = ?";
        $params[] = $data['journal_id'] ? (int)$data['journal_id'] : null;
        $types .= 'i';
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        return;
    }
    
    $params[] = $paper_id;
    $types .= 'i';
    
    $query = "UPDATE Papers SET " . implode(', ', $updates) . " WHERE paper_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $stmt = $conn->prepare("
            SELECT p.*, j.name as journal_name, c.name as conference_name
            FROM Papers p 
            LEFT JOIN Journals j ON p.journal_id = j.journal_id 
            LEFT JOIN Conferences c ON p.conference_id = c.conference_id 
            WHERE p.paper_id = ?
        ");
        $stmt->bind_param("i", $paper_id);
        $stmt->execute();
        $paper = $stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Paper updated successfully',
            'data' => $paper
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update paper: ' . $conn->error]);
    }
}

function handleDeleteRequest() {
    global $conn;
    
    $data = get_json_input();
    $id = isset($data['id']) ? (int)$data['id'] : null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Paper ID is required']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM Papers WHERE paper_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Paper deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete paper: ' . $conn->error]);
    }
}

$conn->close();
?>