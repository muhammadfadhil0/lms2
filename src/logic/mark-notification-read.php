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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['notification_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit();
}

$notificationLogic = new NotificationLogic();
$user_id = $_SESSION['user']['id'];
$notification_id = intval($input['notification_id']);

try {
    $result = $notificationLogic->markAsRead($notification_id, $user_id);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
    }
    
} catch (Exception $e) {
    error_log("Error in mark-notification-read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>