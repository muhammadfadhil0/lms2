<?php
session_start();
require_once 'postingan-logic.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $postinganLogic = new PostinganLogic();
    $user_id = $_SESSION['user']['id'];
    
    // Get form data
    $postingan_id = intval($_POST['postingan_id'] ?? 0);
    
    if ($postingan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID postingan tidak valid']);
        exit();
    }
    
    // Toggle like
    $result = $postinganLogic->toggleLike($postingan_id, $user_id);
    
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
