<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

require_once '../logic/koneksi.php';

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create_notification':
        createNotification();
        break;
    
    case 'get_notifications':
        getNotifications();
        break;
        
    case 'update_notification':
        updateNotification();
        break;
        
    case 'delete_notification':
        deleteNotification();
        break;
        
    case 'get_notification_stats':
        getNotificationStats();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function createNotification() {
    global $koneksi;
    
    try {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $icon = $_POST['icon'] ?? 'info';
        $priority = $_POST['priority'] ?? 'medium';
        $target_roles = $_POST['target_roles'] ?? null;
        $expires_at = $_POST['expires_at'] ?? null;
        
        // Validation
        if (empty($title) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Judul dan deskripsi harus diisi']);
            return;
        }
        
        // Process target roles
        $target_roles_value = null;
        if (!empty($target_roles) && $target_roles !== 'all' && $target_roles !== 'null') {
            $target_roles_value = $target_roles; // Store as string, not JSON
        }
        
        // Process expires_at
        $expires_at_value = null;
        if (!empty($expires_at)) {
            $expires_at_value = date('Y-m-d H:i:s', strtotime($expires_at));
        }
        
        // Debug logging
        error_log("Creating notification: title=$title, icon=$icon, priority=$priority, target_roles_received=" . var_export($target_roles, true) . ", target_roles_value=" . var_export($target_roles_value, true));
        
        $query = "INSERT INTO global_notifications (title, description, icon, priority, target_roles, expires_at, created_by, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ssssssi", $title, $description, $icon, $priority, $target_roles_value, $expires_at_value, $_SESSION['user']['id']);
        
        if ($stmt->execute()) {
            $notification_id = $koneksi->insert_id;
            echo json_encode([
                'success' => true, 
                'message' => 'Notifikasi berhasil dibuat',
                'notification_id' => $notification_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal membuat notifikasi']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getNotifications() {
    global $koneksi;
    
    try {
        $page = (int)($_POST['page'] ?? 1);
        $per_page = (int)($_POST['per_page'] ?? 10);
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT gn.*, u.namaLengkap as created_by_name,
                         COUNT(nr.id) as read_count,
                         (SELECT COUNT(*) FROM users WHERE role IN ('guru', 'siswa')) as total_users
                  FROM global_notifications gn
                  LEFT JOIN users u ON gn.created_by = u.id
                  LEFT JOIN notification_reads nr ON gn.id = nr.notification_id
                  WHERE gn.is_active = TRUE
                  GROUP BY gn.id
                  ORDER BY gn.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ii", $per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            // Parse target roles
            $target_roles = null;
            if ($row['target_roles']) {
                $target_roles = json_decode($row['target_roles'], true);
            }
            
            // Calculate read percentage
            $read_percentage = 0;
            if ($row['total_users'] > 0) {
                $read_percentage = round(($row['read_count'] / $row['total_users']) * 100, 1);
            }
            
            $notifications[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'icon' => $row['icon'],
                'priority' => $row['priority'],
                'target_roles' => $target_roles,
                'created_by_name' => $row['created_by_name'],
                'created_at' => $row['created_at'],
                'expires_at' => $row['expires_at'],
                'read_count' => $row['read_count'],
                'total_users' => $row['total_users'],
                'read_percentage' => $read_percentage
            ];
        }
        
        // Get total count
        $count_query = "SELECT COUNT(*) as total FROM global_notifications WHERE is_active = TRUE";
        $count_result = $koneksi->query($count_query);
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

function updateNotification() {
    global $koneksi;
    
    try {
        $id = $_POST['id'] ?? '';
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $icon = $_POST['icon'] ?? 'info';
        $priority = $_POST['priority'] ?? 'medium';
        $target_roles = $_POST['target_roles'] ?? null;
        $expires_at = $_POST['expires_at'] ?? null;
        
        // Validation
        if (empty($id) || empty($title) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'ID, judul dan deskripsi harus diisi']);
            return;
        }
        
        // Process target roles
        $target_roles_json = null;
        if (!empty($target_roles) && $target_roles !== 'all') {
            $roles = explode(',', $target_roles);
            $target_roles_json = json_encode(array_map('trim', $roles));
        }
        
        // Process expires_at
        $expires_at_value = null;
        if (!empty($expires_at)) {
            $expires_at_value = date('Y-m-d H:i:s', strtotime($expires_at));
        }
        
        $query = "UPDATE global_notifications 
                  SET title = ?, description = ?, icon = ?, priority = ?, target_roles = ?, expires_at = ?, updated_at = CURRENT_TIMESTAMP
                  WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ssssssi", $title, $description, $icon, $priority, $target_roles_json, $expires_at_value, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Notifikasi berhasil diupdate']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupdate notifikasi']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function deleteNotification() {
    global $koneksi;
    
    try {
        $id = $_POST['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID notifikasi harus diisi']);
            return;
        }
        
        // Soft delete - set is_active = FALSE
        $query = "UPDATE global_notifications SET is_active = FALSE WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Notifikasi berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus notifikasi']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getNotificationStats() {
    global $koneksi;
    
    try {
        // Total notifications
        $total_query = "SELECT COUNT(*) as total FROM global_notifications WHERE is_active = TRUE";
        $total_result = $koneksi->query($total_query);
        $total_notifications = $total_result->fetch_assoc()['total'];
        
        // Total users (guru + siswa)
        $users_query = "SELECT COUNT(*) as total FROM users WHERE role IN ('guru', 'siswa') AND status = 'aktif'";
        $users_result = $koneksi->query($users_query);
        $total_users = $users_result->fetch_assoc()['total'];
        
        // Total reads today
        $reads_today_query = "SELECT COUNT(*) as total FROM notification_reads WHERE DATE(read_at) = CURDATE()";
        $reads_today_result = $koneksi->query($reads_today_query);
        $reads_today = $reads_today_result->fetch_assoc()['total'];
        
        // Unread notifications per user average
        $unread_query = "SELECT 
                            (SELECT COUNT(*) FROM global_notifications WHERE is_active = TRUE) * ? - 
                            (SELECT COUNT(*) FROM notification_reads) as total_unread";
        $unread_stmt = $koneksi->prepare($unread_query);
        $unread_stmt->bind_param("i", $total_users);
        $unread_stmt->execute();
        $unread_result = $unread_stmt->get_result();
        $total_unread = $unread_result->fetch_assoc()['total_unread'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_notifications' => $total_notifications,
                'total_users' => $total_users,
                'reads_today' => $reads_today,
                'total_unread' => max(0, $total_unread)
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
