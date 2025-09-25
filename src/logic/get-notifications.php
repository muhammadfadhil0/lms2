<?php
// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once 'notification-logic.php';
require_once 'koneksi.php';

header('Content-Type: application/json');

// Log for debugging
error_log("get-notifications.php called by user: " . ($_SESSION['user']['id'] ?? 'not logged in'));

try {
    // Check if user is logged in
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['siswa', 'guru', 'admin'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $notificationLogic = new NotificationLogic();
    $user_id = $_SESSION['user']['id'];
    $user_role = $_SESSION['user']['role'];

    // Get parameters
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
    $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] == '1';

    error_log("Processing notifications for user $user_id, role: $user_role, limit: $limit, unread_only: " . ($unread_only ? 'true' : 'false'));
    // Get personal notifications
    $personalNotifications = $notificationLogic->getUserNotifications($user_id, null, $unread_only);
    
    // Get global notifications
    $globalNotifications = [];
    
    try {
        // Admin can see all notifications, regular users only see their targeted notifications
        if ($user_role === 'admin') {
            $global_query = "SELECT gn.*, 
                                   CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END as is_read,
                                   u.namaLengkap as created_by_name
                            FROM global_notifications gn
                            LEFT JOIN notification_reads nr ON gn.id = nr.notification_id AND nr.user_id = ?
                            LEFT JOIN users u ON gn.created_by = u.id
                            WHERE gn.is_active = TRUE 
                              AND (gn.expires_at IS NULL OR gn.expires_at > NOW())";
        } else {
            $global_query = "SELECT gn.*, 
                                   CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END as is_read,
                                   u.namaLengkap as created_by_name
                            FROM global_notifications gn
                            LEFT JOIN notification_reads nr ON gn.id = nr.notification_id AND nr.user_id = ?
                            LEFT JOIN users u ON gn.created_by = u.id
                            WHERE gn.is_active = TRUE 
                              AND (gn.expires_at IS NULL OR gn.expires_at > NOW())
                              AND (gn.target_roles IS NULL 
                                   OR gn.target_roles = ? 
                                   OR JSON_CONTAINS(gn.target_roles, JSON_QUOTE(?), '$'))";
        }
        
        if ($unread_only) {
            $global_query .= " AND nr.id IS NULL";
        }
        
        $global_query .= " ORDER BY gn.created_at DESC";
        
        error_log("Global query: " . $global_query);
        error_log("User ID: $user_id, Role: $user_role");
        error_log("Is Admin: " . ($user_role === 'admin' ? 'YES' : 'NO'));
        
        $stmt = $koneksi->prepare($global_query);
        if (!$stmt) {
            throw new Exception("Failed to prepare global notifications query: " . $koneksi->error);
        }
        
        // Bind parameters based on user role
        if ($user_role === 'admin') {
            $stmt->bind_param("i", $user_id);
            error_log("DEBUG: Using ADMIN query with user_id: $user_id");
        } else {
            $stmt->bind_param("iss", $user_id, $user_role, $user_role);
            error_log("DEBUG: Using REGULAR query with user_id: $user_id, role: $user_role");
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute global notifications query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        error_log("Query result rows: " . $result->num_rows);
        
        if ($result->num_rows === 0) {
            error_log("DEBUG: No global notifications found. Testing simple query...");
            $simple_test = "SELECT COUNT(*) as total FROM global_notifications WHERE is_active = TRUE";
            $test_result = $koneksi->query($simple_test);
            $total = $test_result->fetch_assoc()['total'];
            error_log("DEBUG: Total active global notifications in DB: " . $total);
        }
        
        while ($row = $result->fetch_assoc()) {
            $globalNotifications[] = [
                'id' => 'global_' . $row['id'], // Prefix untuk membedakan dengan notifikasi personal
                'type' => 'global_notification',
                'title' => $row['title'],
                'message' => $row['description'],
                'created_at' => $row['created_at'],
                'is_read' => $row['is_read'],
                'icon' => $row['icon'],
                'priority' => $row['priority'],
                'created_by_name' => $row['created_by_name'],
                'source' => 'global' // Penanda bahwa ini notifikasi global
            ];
        }
        
        error_log("Found " . count($globalNotifications) . " global notifications");
        
    } catch (Exception $e) {
        error_log("Error fetching global notifications: " . $e->getMessage());
        // Continue without global notifications rather than failing completely
    }
    
    // Gabungkan kedua jenis notifikasi
    $allNotifications = array_merge($personalNotifications, $globalNotifications);
    
    // Sort berdasarkan waktu terbaru
    usort($allNotifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Apply limit jika ada
    if ($limit && $limit > 0) {
        $allNotifications = array_slice($allNotifications, 0, $limit);
    }
    
    // Add time_ago field to each notification
    foreach ($allNotifications as &$notification) {
        $notification['time_ago'] = $notificationLogic->getTimeAgo($notification['created_at']);
        
        // Set icon dan color untuk notifikasi global
        if (isset($notification['source']) && $notification['source'] === 'global') {
            // Icon dan color sudah ada dari database, tapi perlu di-format untuk frontend
            $notification['icon'] = 'ti-' . $notification['icon']; // Add 'ti-' prefix for Tabler icons
            $notification['color'] = 'text-orange-600'; // Default color untuk global notifications
        } else {
            // Untuk notifikasi personal, gunakan logic yang sudah ada
            $notification['icon'] = $notificationLogic->getNotificationIcon($notification['type']);
            $notification['color'] = $notificationLogic->getNotificationColor($notification['type']);
        }
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $allNotifications,
        'count' => count($allNotifications)
    ]);
    
} catch (Exception $e) {
    error_log("MAIN CATCH - Error in get-notifications.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error: ' . $e->getMessage(),
        'debug' => [
            'user_id' => $user_id ?? null,
            'user_role' => $user_role ?? null,
            'limit' => $limit ?? null,
            'unread_only' => $unread_only ?? null
        ]
    ]);
} catch (Error $e) {
    error_log("FATAL ERROR in get-notifications.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fatal error occurred',
        'debug' => 'Check server logs for details'
    ]);
}
?>