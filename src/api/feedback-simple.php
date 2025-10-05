<?php
session_start();
header('Content-Type: application/json');

// Basic error handling
error_reporting(0);
ini_set('display_errors', 0);

// Simple check
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Please login first']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lms";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Connection failed']);
    exit();
}

$userId = $_SESSION['user']['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $articleId = (int)($_GET['article_id'] ?? 0);
    
    if (!$articleId) {
        echo json_encode(['success' => false, 'error' => 'No article ID']);
        exit();
    }
    
    // Get article data
    $stmt = $pdo->prepare("SELECT id, COALESCE(likes, 0) as likes, COALESCE(dislikes, 0) as dislikes FROM ai_information WHERE id = ?");
    $stmt->execute([$articleId]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$article) {
        echo json_encode(['success' => false, 'error' => 'Article not found']);
        exit();
    }
    
    // Get user feedback
    $stmt2 = $pdo->prepare("SELECT action FROM article_feedback WHERE article_id = ? AND user_id = ?");
    $stmt2->execute([$articleId, $userId]);
    $feedback = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'likes' => (int)$article['likes'],
        'dislikes' => (int)$article['dislikes'],
        'user_feedback' => $feedback ? $feedback['action'] : null
    ]);

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $articleId = (int)($input['article_id'] ?? 0);
    $action = $input['action'] ?? '';
    
    if (!$articleId || !in_array($action, ['like', 'dislike'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        exit();
    }
    
    $pdo->beginTransaction();
    
    try {
        // Check existing
        $stmt = $pdo->prepare("SELECT action FROM article_feedback WHERE article_id = ? AND user_id = ?");
        $stmt->execute([$articleId, $userId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            if ($existing['action'] === $action) {
                // Remove
                $pdo->prepare("DELETE FROM article_feedback WHERE article_id = ? AND user_id = ?")->execute([$articleId, $userId]);
                $column = $action === 'like' ? 'likes' : 'dislikes';
                $pdo->prepare("UPDATE ai_information SET $column = GREATEST(0, $column - 1) WHERE id = ?")->execute([$articleId]);
            } else {
                // Switch
                $pdo->prepare("UPDATE article_feedback SET action = ? WHERE article_id = ? AND user_id = ?")->execute([$action, $articleId, $userId]);
                $oldCol = $existing['action'] === 'like' ? 'likes' : 'dislikes';
                $newCol = $action === 'like' ? 'likes' : 'dislikes';
                $pdo->prepare("UPDATE ai_information SET $oldCol = GREATEST(0, $oldCol - 1) WHERE id = ?")->execute([$articleId]);
                $pdo->prepare("UPDATE ai_information SET $newCol = $newCol + 1 WHERE id = ?")->execute([$articleId]);
            }
        } else {
            // New
            $pdo->prepare("INSERT INTO article_feedback (article_id, user_id, action) VALUES (?, ?, ?)")->execute([$articleId, $userId, $action]);
            $column = $action === 'like' ? 'likes' : 'dislikes';
            $pdo->prepare("UPDATE ai_information SET $column = $column + 1 WHERE id = ?")->execute([$articleId]);
        }
        
        // Get updated data
        $stmt = $pdo->prepare("SELECT COALESCE(likes, 0) as likes, COALESCE(dislikes, 0) as dislikes FROM ai_information WHERE id = ?");
        $stmt->execute([$articleId]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt2 = $pdo->prepare("SELECT action FROM article_feedback WHERE article_id = ? AND user_id = ?");
        $stmt2->execute([$articleId, $userId]);
        $userFeedback = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'likes' => (int)$counts['likes'],
            'dislikes' => (int)$counts['dislikes'],
            'user_feedback' => $userFeedback ? $userFeedback['action'] : null
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    
} else {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>