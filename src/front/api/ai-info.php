<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../logic/koneksi.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get all AI information entries
            $stmt = $pdo->query("SELECT * FROM ai_information ORDER BY created_at DESC");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $results
            ]);
            break;
            
        case 'POST':
            // Create new help article entry
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['title']) || !isset($input['content']) || !isset($input['target_role'])) {
                throw new Exception('Missing required fields');
            }
            
            $stmt = $pdo->prepare("INSERT INTO ai_information (title, description, content, target_role, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                $input['title'],
                $input['description'] ?? '',
                $input['content'],
                $input['target_role']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'AI information created successfully',
                'id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'PUT':
            // Update existing help article entry
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id']) || !isset($input['title']) || !isset($input['content']) || !isset($input['target_role'])) {
                throw new Exception('Missing required fields');
            }
            
            $stmt = $pdo->prepare("UPDATE ai_information SET title = ?, description = ?, content = ?, target_role = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([
                $input['title'],
                $input['description'] ?? '',
                $input['content'],
                $input['target_role'],
                $input['id']
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Help article updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Help article not found or no changes made'
                ]);
            }
            break;
            
        case 'DELETE':
            // Delete AI information entry
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                throw new Exception('Missing ID parameter');
            }
            
            $stmt = $pdo->prepare("DELETE FROM ai_information WHERE id = ?");
            $result = $stmt->execute([$input['id']]);
            
            if ($result && $stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'AI information deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'AI information not found'
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>