<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');
require_once '../logic/koneksi.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_user_notifications':
        getUserNotifications();
        break;
    
    case 'mark_as_read':
        markAsRead();
        break;
        
    case 'mark_all_read':
        markAllAsRead();
        break;
        
    case 'get_unread_count':
        getUnreadCount();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getUserNotifications() {
    global $koneksi;
    
    try {
        $user_id = $_SESSION['user']['id'];
        $user_role = $_SESSION['user']['role'];
        $page = (int)($_GET['page'] ?? 1);
        $per_page = (int)($_GET['per_page'] ?? 10);
        $offset = ($page - 1) * $per_page;
        
        // Get notifications for this user's role
        $query = "SELECT gn.*, 
                         nr.read_at,
                         CASE WHEN nr.id IS NOT NULL THEN TRUE ELSE FALSE END as is_read,
                         u.namaLengkap as created_by_name
                  FROM global_notifications gn
                  LEFT JOIN notification_reads nr ON gn.id = nr.notification_id AND nr.user_id = ?
                  LEFT JOIN users u ON gn.created_by = u.id
                  WHERE gn.is_active = TRUE 
                    AND (gn.expires_at IS NULL OR gn.expires_at > NOW())
                    AND (gn.target_roles IS NULL OR JSON_CONTAINS(gn.target_roles, ?, '$'))
                  ORDER BY gn.priority = 'urgent' DESC, gn.priority = 'high' DESC, gn.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $stmt = $koneksi->prepare($query);
        $role_json = '"' . $user_role . '"';
        $stmt->bind_param("isii", $user_id, $role_json, $per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'icon' => $row['icon'],
                'priority' => $row['priority'],
                'created_by_name' => $row['created_by_name'],
                'created_at' => $row['created_at'],
                'expires_at' => $row['expires_at'],
                'is_read' => (bool)$row['is_read'],
                'read_at' => $row['read_at']
            ];
        }
        
        // Get total count
        $count_query = "SELECT COUNT(*) as total 
                        FROM global_notifications gn
                        WHERE gn.is_active = TRUE 
                          AND (gn.expires_at IS NULL OR gn.expires_at > NOW())
                          AND (gn.target_roles IS NULL OR JSON_CONTAINS(gn.target_roles, ?, '$'))";
        $count_stmt = $koneksi->prepare($count_query);
        $count_stmt->bind_param("s", $role_json);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_count = $count_result->fetch_assoc()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => $notifications,
            'total' => $total_count,
            'page' => $page,
            'per_page' => $per_page
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function markAsRead() {
    global $koneksi;
    
    try {
        $notification_id = $_POST['notification_id'] ?? '';
        $user_id = $_SESSION['user']['id'];
        
        if (empty($notification_id)) {
            echo json_encode(['success' => false, 'message' => 'Notification ID required']);
            return;
        }
        
        // Insert or ignore if already read
        $query = "INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (?, ?)";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ii", $notification_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Notifikasi ditandai sudah dibaca']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menandai notifikasi']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function markAllAsRead() {
    global $koneksi;
    
    try {
        $user_id = $_SESSION['user']['id'];
        $user_role = $_SESSION['user']['role'];
        
        // Get all unread notifications for this user
        $query = "INSERT IGNORE INTO notification_reads (notification_id, user_id)
                  SELECT gn.id, ? as user_id
                  FROM global_notifications gn
                  LEFT JOIN notification_reads nr ON gn.id = nr.notification_id AND nr.user_id = ?
                  WHERE gn.is_active = TRUE 
                    AND (gn.expires_at IS NULL OR gn.expires_at > NOW())
                    AND (gn.target_roles IS NULL OR JSON_CONTAINS(gn.target_roles, ?, '$'))
                    AND nr.id IS NULL";
        
        $stmt = $koneksi->prepare($query);
        $role_json = '"' . $user_role . '"';
        $stmt->bind_param("iis", $user_id, $user_id, $role_json);
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            echo json_encode(['success' => true, 'message' => "Semua notifikasi ({$affected_rows}) ditandai sudah dibaca"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menandai notifikasi']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getUnreadCount() {
    global $koneksi;
    
    try {
        $user_id = $_SESSION['user']['id'];
        $user_role = $_SESSION['user']['role'];
        
        $query = "SELECT COUNT(*) as unread_count
                  FROM global_notifications gn
                  LEFT JOIN notification_reads nr ON gn.id = nr.notification_id AND nr.user_id = ?
                  WHERE gn.is_active = TRUE 
                    AND (gn.expires_at IS NULL OR gn.expires_at > NOW())
                    AND (gn.target_roles IS NULL OR JSON_CONTAINS(gn.target_roles, ?, '$'))
                    AND nr.id IS NULL";
        
        $stmt = $koneksi->prepare($query);
        $role_json = '"' . $user_role . '"';
        $stmt->bind_param("is", $user_id, $role_json);
        $stmt->execute();
        $result = $stmt->get_result();
        $unread_count = $result->fetch_assoc()['unread_count'];
        
        echo json_encode([
            'success' => true,
            'unread_count' => (int)$unread_count
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>