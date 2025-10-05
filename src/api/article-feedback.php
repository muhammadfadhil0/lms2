<?php
session_start();

// Set error handling to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set JSON header first
header('Content-Type: application/json');

// Try to include database connection
try {
    $connectionPath = dirname(dirname(__FILE__)) . '/logic/koneksi.php';
    
    if (!file_exists($connectionPath)) {
        throw new Exception('Connection file not found at: ' . $connectionPath);
    }
    
    require_once $connectionPath;
    
    if (!isset($pdo)) {
        throw new Exception('PDO connection not initialized');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please login first']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Handle like/dislike action
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['article_id']) || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }
    
    $articleId = (int)$input['article_id'];
    $action = $input['action']; // 'like' or 'dislike'
    $userId = $_SESSION['user']['id'];
    
    if (!in_array($action, ['like', 'dislike'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit();
    }
    
    try {
        // Check if PDO connection exists
        if (!isset($pdo)) {
            throw new Exception('Database connection not available');
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Check if user already liked/disliked this article
        $checkStmt = $pdo->prepare("
            SELECT action FROM article_feedback 
            WHERE article_id = ? AND user_id = ?
        ");
        $checkStmt->execute([$articleId, $userId]);
        $existingFeedback = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingFeedback) {
            if ($existingFeedback['action'] === $action) {
                // User clicked same action - remove feedback
                $deleteStmt = $pdo->prepare("DELETE FROM article_feedback WHERE article_id = ? AND user_id = ?");
                $deleteStmt->execute([$articleId, $userId]);
                
                // Decrease count
                $column = $action === 'like' ? 'likes' : 'dislikes';
                $updateStmt = $pdo->prepare("UPDATE ai_information SET $column = GREATEST(0, $column - 1) WHERE id = ?");
                $updateStmt->execute([$articleId]);
                
                $message = 'Feedback removed';
            } else {
                // User switched from like to dislike or vice versa
                $oldAction = $existingFeedback['action'];
                $newAction = $action;
                
                // Update feedback
                $updateFeedbackStmt = $pdo->prepare("UPDATE article_feedback SET action = ? WHERE article_id = ? AND user_id = ?");
                $updateFeedbackStmt->execute([$newAction, $articleId, $userId]);
                
                // Decrease old count and increase new count
                $oldColumn = $oldAction === 'like' ? 'likes' : 'dislikes';
                $newColumn = $newAction === 'like' ? 'likes' : 'dislikes';
                
                $updateOldStmt = $pdo->prepare("UPDATE ai_information SET $oldColumn = GREATEST(0, $oldColumn - 1) WHERE id = ?");
                $updateOldStmt->execute([$articleId]);
                
                $updateNewStmt = $pdo->prepare("UPDATE ai_information SET $newColumn = $newColumn + 1 WHERE id = ?");
                $updateNewStmt->execute([$articleId]);
                
                $message = 'Feedback updated';
            }
        } else {
            // New feedback
            $insertStmt = $pdo->prepare("INSERT INTO article_feedback (article_id, user_id, action) VALUES (?, ?, ?)");
            $insertStmt->execute([$articleId, $userId, $action]);
            
            // Increase count
            $column = $action === 'like' ? 'likes' : 'dislikes';
            $updateStmt = $pdo->prepare("UPDATE ai_information SET $column = $column + 1 WHERE id = ?");
            $updateStmt->execute([$articleId]);
            
            $message = 'Feedback added';
        }
        
        // Get updated counts
        $countStmt = $pdo->prepare("SELECT likes, dislikes FROM ai_information WHERE id = ?");
        $countStmt->execute([$articleId]);
        $counts = $countStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get user's current feedback
        $userFeedbackStmt = $pdo->prepare("SELECT action FROM article_feedback WHERE article_id = ? AND user_id = ?");
        $userFeedbackStmt->execute([$articleId, $userId]);
        $userFeedback = $userFeedbackStmt->fetch(PDO::FETCH_ASSOC);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'likes' => (int)$counts['likes'],
            'dislikes' => (int)$counts['dislikes'],
            'user_feedback' => $userFeedback ? $userFeedback['action'] : null
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    
} elseif ($method === 'GET') {
    // Get feedback counts for an article
    if (!isset($_GET['article_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing article_id']);
        exit();
    }
    
    $articleId = (int)$_GET['article_id'];
    $userId = $_SESSION['user']['id'];
    
    try {
        // Check if PDO connection exists
        if (!isset($pdo)) {
            throw new Exception('Database connection not available');
        }
        
        // Get counts
        $countStmt = $pdo->prepare("SELECT likes, dislikes FROM ai_information WHERE id = ?");
        $countStmt->execute([$articleId]);
        $counts = $countStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$counts) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Article not found']);
            exit();
        }
        
        // Get user's feedback
        $userFeedbackStmt = $pdo->prepare("SELECT action FROM article_feedback WHERE article_id = ? AND user_id = ?");
        $userFeedbackStmt->execute([$articleId, $userId]);
        $userFeedback = $userFeedbackStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'likes' => (int)$counts['likes'],
            'dislikes' => (int)$counts['dislikes'],
            'user_feedback' => $userFeedback ? $userFeedback['action'] : null
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>