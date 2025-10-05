<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../logic/koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$userRole = $_SESSION['user']['role'];

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get help articles based on user role and optional search
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $role = isset($_GET['role']) ? $_GET['role'] : $userRole;
            
            // Base query
            $query = "
                SELECT id, title, content, target_role, created_at, updated_at 
                FROM ai_information 
                WHERE (target_role = ? OR target_role = 'all')
            ";
            $params = [$role];
            
            // Add search functionality if search term provided
            if (!empty($search)) {
                $query .= " AND (title LIKE ? OR content LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $query .= " ORDER BY created_at DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the articles for better frontend consumption
            $formattedArticles = array_map(function($article) {
                return [
                    'id' => (int)$article['id'],
                    'title' => $article['title'],
                    'content' => $article['content'],
                    'target_role' => $article['target_role'],
                    'created_at' => $article['created_at'],
                    'updated_at' => $article['updated_at'],
                    'preview' => mb_substr(strip_tags($article['content']), 0, 300) . (mb_strlen($article['content']) > 300 ? '...' : ''),
                    'word_count' => str_word_count(strip_tags($article['content'])),
                    'read_time' => max(1, ceil(str_word_count(strip_tags($article['content'])) / 200)) // Assuming 200 WPM reading speed
                ];
            }, $articles);
            
            echo json_encode([
                'success' => true,
                'data' => $formattedArticles,
                'total' => count($formattedArticles),
                'user_role' => $userRole,
                'search' => $search
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Database error in help articles API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General error in help articles API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching help articles'
    ]);
}
?>