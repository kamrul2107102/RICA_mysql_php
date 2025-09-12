<?php
header('Content-Type: application/json');
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $stmt = $conn->prepare("SELECT * FROM Institutions WHERE institution_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Institution not found']);
                    exit;
                }
                
                echo json_encode($result->fetch_assoc());
            } else {
                // Add pagination and filtering
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
                $offset = ($page - 1) * $limit;
                
                $where = [];
                $params = [];
                $types = "";
                
                if (isset($_GET['country'])) {
                    $where[] = "country = ?";
                    $params[] = $_GET['country'];
                    $types .= "s";
                }
                
                if (isset($_GET['search'])) {
                    $where[] = "(name LIKE ? OR country LIKE ?)";
                    $params[] = "%" . $_GET['search'] . "%";
                    $params[] = "%" . $_GET['search'] . "%";
                    $types .= "ss";
                }
                
                $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
                
                $sql = "SELECT * FROM Institutions $whereClause ORDER BY name LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;
                $types .= "ii";
                
                $stmt = $conn->prepare($sql);
                if ($params) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                
                // Get total count for pagination
                $countSql = "SELECT COUNT(*) as total FROM Institutions $whereClause";
                $countStmt = $conn->prepare($countSql);
                if ($where) {
                    $bindParams = array_slice($params, 0, -2);
                    $countStmt->bind_param(substr($types, 0, -2), ...$bindParams);
                }
                $countStmt->execute();
                $total = $countStmt->get_result()->fetch_assoc()['total'];
                
                echo json_encode([
                    'data' => $result->fetch_all(MYSQLI_ASSOC),
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validation
            if (empty($data['name']) || empty($data['country'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and country are required']);
                exit;
            }
            
            // Check for duplicate name
            $checkStmt = $conn->prepare("SELECT institution_id FROM Institutions WHERE name = ?");
            $checkStmt->bind_param("s", $data['name']);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Institution with this name already exists']);
                exit;
            }
            
            $stmt = $conn->prepare("INSERT INTO Institutions (name, country, ranking) VALUES (?, ?, ?)");
            $ranking = !empty($data['ranking']) ? (int)$data['ranking'] : null;
            $stmt->bind_param("ssi", $data['name'], $data['country'], $ranking);
            
            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'institution_id' => $newId,
                    'message' => 'Institution created successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create institution']);
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['institution_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Institution ID is required']);
                exit;
            }
            
            // Check if institution exists
            $checkStmt = $conn->prepare("SELECT institution_id FROM Institutions WHERE institution_id = ?");
            $checkStmt->bind_param("i", $data['institution_id']);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Institution not found']);
                exit;
            }
            
            $stmt = $conn->prepare("UPDATE Institutions SET name=?, country=?, ranking=? WHERE institution_id=?");
            $ranking = !empty($data['ranking']) ? (int)$data['ranking'] : null;
            $stmt->bind_param("ssii", $data['name'], $data['country'], $ranking, $data['institution_id']);
            
            echo json_encode(['success' => $stmt->execute()]);
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Institution ID is required']);
                exit;
            }
            
            $id = (int)$data['id'];
            
            // Check if institution exists
            $checkStmt = $conn->prepare("SELECT institution_id FROM Institutions WHERE institution_id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Institution not found']);
                exit;
            }
            
            // Update authors to set institution_id to NULL before deletion
            $updateAuthors = $conn->prepare("UPDATE Authors SET institution_id = NULL WHERE institution_id = ?");
            $updateAuthors->bind_param("i", $id);
            $updateAuthors->execute();
            
            $stmt = $conn->prepare("DELETE FROM Institutions WHERE institution_id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Institution deleted successfully. Associated authors have been unlinked.'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete institution']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>