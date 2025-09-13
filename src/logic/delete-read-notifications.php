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
    $result = $notificationLogic->deleteReadNotifications($user_id);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Read notifications deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete read notifications']);
    }
    
} catch (Exception $e) {
    error_log("Error in delete-read-notifications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>