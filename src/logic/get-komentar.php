<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if (!isset($_GET['postingan_id']) || !is_numeric($_GET['postingan_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

require_once 'postingan-logic.php';

$postinganLogic = new PostinganLogic();
$postingan_id = intval($_GET['postingan_id']);

// Get comments
$comments = $postinganLogic->getKomentarPostingan($postingan_id);

echo json_encode([
    'success' => true,
    'comments' => $comments
]);
?>
