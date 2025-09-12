<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

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
        'total_papers' => $conn->query("SELECT COUNT(*) FROM Papers")->fetch_row()[0],
        'total_authors' => $conn->query("SELECT COUNT(*) FROM Authors")->fetch_row()[0],
        'total_citations' => $conn->query("SELECT COUNT(*) FROM Citations")->fetch_row()[0],
        'total_journals' => $conn->query("SELECT COUNT(*) FROM Journals")->fetch_row()[0],
        'total_institutions' => $conn->query("SELECT COUNT(*) FROM Institutions")->fetch_row()[0],
        'total_conferences' => $conn->query("SELECT COUNT(*) FROM Conferences")->fetch_row()[0],
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

function getTopCitedPapers() {
    global $conn;
    
    $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
    
    $query = "
        SELECT p.paper_id, p.title, p.publication_year,
               COUNT(c.citation_id) as citation_count,
               j.name as journal_name,
               conf.name as conference_name
        FROM Papers p
        LEFT JOIN Citations c ON p.paper_id = c.cited_paper_id
        LEFT JOIN Journals j ON p.journal_id = j.journal_id
        LEFT JOIN Conferences conf ON p.conference_id = conf.conference_id
        GROUP BY p.paper_id, p.title, p.publication_year
        ORDER BY citation_count DESC
        LIMIT ?
    ";
    
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
    
    $query = "
        SELECT a.author_id, a.name, a.email, i.name as institution_name,
               COUNT(DISTINCT ap.paper_id) as paper_count,
               COUNT(DISTINCT c.citation_id) as citation_count,
               ROUND(COUNT(DISTINCT c.citation_id) / GREATEST(COUNT(DISTINCT ap.paper_id), 1), 2) as avg_citations
        FROM Authors a
        LEFT JOIN Authorship ap ON a.author_id = ap.author_id
        LEFT JOIN Papers p ON ap.paper_id = p.paper_id
        LEFT JOIN Citations c ON p.paper_id = c.cited_paper_id
        LEFT JOIN Institutions i ON a.institution_id = i.institution_id
        GROUP BY a.author_id, a.name, a.email
        HAVING paper_count > 0
        ORDER BY avg_citations DESC, citation_count DESC
        LIMIT ?
    ";
    
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
    
    $query = "
        SELECT 
            publication_year,
            COUNT(*) as paper_count,
            SUM(CASE WHEN journal_id IS NOT NULL THEN 1 ELSE 0 END) as journal_papers,
            SUM(CASE WHEN conference_id IS NOT NULL THEN 1 ELSE 0 END) as conference_papers
        FROM Papers
        WHERE publication_year IS NOT NULL
        GROUP BY publication_year
        ORDER BY publication_year DESC
    ";
    
    $result = $conn->query($query);
    $stats = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

function getInstitutionStats() {
    global $conn;
    
    $query = "
        SELECT i.institution_id, i.name, i.country, i.ranking,
               COUNT(DISTINCT a.author_id) as author_count,
               COUNT(DISTINCT ap.paper_id) as paper_count,
               COUNT(DISTINCT c.citation_id) as citation_count
        FROM Institutions i
        LEFT JOIN Authors a ON i.institution_id = a.institution_id
        LEFT JOIN Authorship ap ON a.author_id = ap.author_id
        LEFT JOIN Papers p ON ap.paper_id = p.paper_id
        LEFT JOIN Citations c ON p.paper_id = c.cited_paper_id
        GROUP BY i.institution_id, i.name, i.country, i.ranking
        ORDER BY citation_count DESC, paper_count DESC
    ";
    
    $result = $conn->query($query);
    $stats = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

$conn->close();
?>