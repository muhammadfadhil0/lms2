<?php
session_start();
require_once 'notification-logic.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'siswa') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$notificationLogic = new NotificationLogic();
$user_id = $_SESSION['user']['id'];

try {
    $result = $notificationLogic->markAllAsRead($user_id);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark all notifications as read']);
    }
    
} catch (Exception $e) {
    error_log("Error in mark-all-notifications-read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>