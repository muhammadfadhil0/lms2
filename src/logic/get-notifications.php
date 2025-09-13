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

// Get parameters
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
$unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] == '1';

try {
    // Get notifications for the user
    $notifications = $notificationLogic->getUserNotifications($user_id, $limit, $unread_only);
    
    // Add time_ago field to each notification
    foreach ($notifications as &$notification) {
        $notification['time_ago'] = $notificationLogic->getTimeAgo($notification['created_at']);
        $notification['icon'] = $notificationLogic->getNotificationIcon($notification['type']);
        $notification['color'] = $notificationLogic->getNotificationColor($notification['type']);
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'count' => count($notifications)
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-notifications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>