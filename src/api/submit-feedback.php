<?php
session_start();

// Set JSON header and error handling
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

try {
    // Use absolute path for connection
    $connectionPath = dirname(dirname(__FILE__)) . '/logic/koneksi.php';
    require_once $connectionPath;
    
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit();
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['article_id']) || !isset($input['action'])) {
            echo json_encode(['success' => false, 'error' => 'Missing data']);
            exit();
        }
        
        $articleId = (int)$input['article_id'];
        $action = $input['action'];
        $userId = $_SESSION['user']['id'];
        
        if (!in_array($action, ['like', 'dislike'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit();
        }
        
        $pdo->beginTransaction();
        
        // Check existing feedback
        $checkStmt = $pdo->prepare("SELECT action FROM article_feedback WHERE article_id = ? AND user_id = ?");
        $checkStmt->execute([$articleId, $userId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            if ($existing['action'] === $action) {
                // Remove feedback
                $deleteStmt = $pdo->prepare("DELETE FROM article_feedback WHERE article_id = ? AND user_id = ?");
                $deleteStmt->execute([$articleId, $userId]);
                
                $column = $action === 'like' ? 'likes' : 'dislikes';
                $updateStmt = $pdo->prepare("UPDATE ai_information SET $column = GREATEST(0, $column - 1) WHERE id = ?");
                $updateStmt->execute([$articleId]);
            } else {
                // Switch feedback
                $updateFeedbackStmt = $pdo->prepare("UPDATE article_feedback SET action = ? WHERE article_id = ? AND user_id = ?");
                $updateFeedbackStmt->execute([$action, $articleId, $userId]);
                
                $oldColumn = $existing['action'] === 'like' ? 'likes' : 'dislikes';
                $newColumn = $action === 'like' ? 'likes' : 'dislikes';
                
                $updateOldStmt = $pdo->prepare("UPDATE ai_information SET $oldColumn = GREATEST(0, $oldColumn - 1) WHERE id = ?");
                $updateOldStmt->execute([$articleId]);
                
                $updateNewStmt = $pdo->prepare("UPDATE ai_information SET $newColumn = $newColumn + 1 WHERE id = ?");
                $updateNewStmt->execute([$articleId]);
            }
        } else {
            // New feedback
            $insertStmt = $pdo->prepare("INSERT INTO article_feedback (article_id, user_id, action) VALUES (?, ?, ?)");
            $insertStmt->execute([$articleId, $userId, $action]);
            
            $column = $action === 'like' ? 'likes' : 'dislikes';
            $updateStmt = $pdo->prepare("UPDATE ai_information SET $column = $column + 1 WHERE id = ?");
            $updateStmt->execute([$articleId]);
        }
        
        // Get updated data
        $countStmt = $pdo->prepare("SELECT COALESCE(likes, 0) as likes, COALESCE(dislikes, 0) as dislikes FROM ai_information WHERE id = ?");
        $countStmt->execute([$articleId]);
        $counts = $countStmt->fetch(PDO::FETCH_ASSOC);
        
        $userFeedbackStmt = $pdo->prepare("SELECT action FROM article_feedback WHERE article_id = ? AND user_id = ?");
        $userFeedbackStmt->execute([$articleId, $userId]);
        $userFeedback = $userFeedbackStmt->fetch(PDO::FETCH_ASSOC);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'likes' => (int)$counts['likes'],
            'dislikes' => (int)$counts['dislikes'],
            'user_feedback' => $userFeedback ? $userFeedback['action'] : null
        ]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
}
?>