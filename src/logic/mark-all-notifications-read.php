<?php
session_start();
require_once 'notification-logic.php';
require_once 'koneksi.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['siswa', 'guru'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$notificationLogic = new NotificationLogic();
$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

try {
    // Mark all personal notifications as read
    $personalResult = $notificationLogic->markAllAsRead($user_id);
    
    // Mark all global notifications as read
    $globalQuery = "INSERT IGNORE INTO notification_reads (notification_id, user_id)
                    SELECT gn.id, ? as user_id
                    FROM global_notifications gn
                    LEFT JOIN notification_reads nr ON gn.id = nr.notification_id AND nr.user_id = ?
                    WHERE gn.is_active = TRUE 
                      AND (gn.expires_at IS NULL OR gn.expires_at > NOW())
                      AND (gn.target_roles IS NULL OR JSON_CONTAINS(gn.target_roles, ?, '$'))
                      AND nr.id IS NULL";
    
    $stmt = $koneksi->prepare($globalQuery);
    $role_json = '"' . $user_role . '"';
    $stmt->bind_param("iis", $user_id, $user_id, $role_json);
    $globalResult = $stmt->execute();
    
    if ($personalResult || $globalResult) {
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