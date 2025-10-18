<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';
require_once __DIR__ . '/../includes/sql.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        global $conn;
        
        $analyticsType = $_GET['type'] ?? 'overview';
        
        switch ($analyticsType) {
            case 'top_cited':
                getTopCitedPapers();
                break;
            case 'top_authors':
                getTopAuthors();
                break;
            case 'publication_stats':
                getPublicationStats();
                break;
            case 'institution_stats':
                getInstitutionStats();
                break;
            case 'overview':
            default:
                getOverviewAnalytics();
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

function getOverviewAnalytics() {
    global $conn;
    
    $stats = [
        'total_papers' => $conn->query(sql_named('analyticsQuery.sql', 'COUNT_TOTAL_PAPERS'))->fetch_row()[0],
        'total_authors' => $conn->query(sql_named('analyticsQuery.sql', 'COUNT_TOTAL_AUTHORS'))->fetch_row()[0],
        'total_citations' => $conn->query(sql_named('analyticsQuery.sql', 'COUNT_TOTAL_CITATIONS'))->fetch_row()[0],
        'total_journals' => $conn->query(sql_named('analyticsQuery.sql', 'COUNT_TOTAL_JOURNALS'))->fetch_row()[0],
        'total_institutions' => $conn->query(sql_named('analyticsQuery.sql', 'COUNT_TOTAL_INSTITUTIONS'))->fetch_row()[0],
        'total_conferences' => $conn->query(sql_named('analyticsQuery.sql', 'COUNT_TOTAL_CONFERENCES'))->fetch_row()[0],
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

function getTopCitedPapers() {
    global $conn;
    
    $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
    
    $query = sql_named('analyticsQuery.sql', 'GET_TOP_CITED_PAPERS');
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $papers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $papers
    ]);
}

function getTopAuthors() {
    global $conn;
    
    $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
    
    $query = sql_named('analyticsQuery.sql', 'GET_TOP_AUTHORS');
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $authors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $authors
    ]);
}

function getPublicationStats() {
    global $conn;
    
    $query = sql_named('analyticsQuery.sql', 'GET_PUBLICATION_STATS_BY_YEAR');
    
    $result = $conn->query($query);
    $stats = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

function getInstitutionStats() {
    global $conn;
    
    $query = sql_named('analyticsQuery.sql', 'GET_INSTITUTION_STATS');
    
    $result = $conn->query($query);
    $stats = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

$conn->close();
?>