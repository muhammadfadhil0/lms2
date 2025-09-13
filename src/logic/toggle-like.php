<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['postingan_id']) || !is_numeric($input['postingan_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

require_once 'postingan-logic.php';

$postinganLogic = new PostinganLogic();
$user_id = $_SESSION['user']['id'];
$postingan_id = intval($input['postingan_id']);

// Toggle like
$result = $postinganLogic->toggleLike($postingan_id, $user_id);

echo json_encode($result);
?>
