<?php
session_start();
require_once 'notification-logic.php';
require_once 'koneksi.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['siswa', 'guru', 'admin'])) {
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
$notification_id = $input['notification_id'];

try {
    // Check if it's a global notification (has 'global_' prefix)
    if (strpos($notification_id, 'global_') === 0) {
        // It's a global notification
        $global_id = intval(str_replace('global_', '', $notification_id));
        
        // Insert into notification_reads table
        $stmt = $koneksi->prepare("INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $global_id, $user_id);
        $result = $stmt->execute();
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Global notification marked as read']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark global notification as read']);
        }
    } else {
        // It's a personal notification, use existing logic
        $notification_id = intval($notification_id);
        $result = $notificationLogic->markAsRead($notification_id, $user_id);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
        }
    }
    
} catch (Exception $e) {
    error_log("Error in mark-notification-read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>