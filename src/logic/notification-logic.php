<?php
require_once 'koneksi.php';

class NotificationLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Membuat notifikasi baru
     */
    public function createNotification($user_id, $type, $title, $message, $related_id = null, $related_class_id = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_id, related_class_id) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssii", $user_id, $type, $title, $message, $related_id, $related_class_id);
            
            if ($stmt->execute()) {
                return $this->conn->insert_id;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mengambil notifikasi untuk user tertentu
     */
    public function getUserNotifications($user_id, $limit = null, $unread_only = false) {
        try {
            $sql = "
                SELECT n.*, k.namaKelas as nama_kelas 
                FROM notifications n
                LEFT JOIN kelas k ON n.related_class_id = k.id
                WHERE n.user_id = ?
            ";
            
            if ($unread_only) {
                $sql .= " AND n.is_read = 0";
            }
            
            $sql .= " ORDER BY n.created_at DESC";
            
            if ($limit) {
                $sql .= " LIMIT ?";
            }
            
            $stmt = $this->conn->prepare($sql);
            
            if ($limit) {
                $stmt->bind_param("ii", $user_id, $limit);
            } else {
                $stmt->bind_param("i", $user_id);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $notifications = [];
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
            
            return $notifications;
        } catch (Exception $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Menandai notifikasi sebagai sudah dibaca
     */
    public function markAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $notification_id, $user_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Menandai semua notifikasi sebagai sudah dibaca
     */
    public function markAllAsRead($user_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->bind_param("i", $user_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Menghapus notifikasi
     */
    public function deleteNotification($notification_id, $user_id) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $notification_id, $user_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Menghapus semua notifikasi yang sudah dibaca
     */
    public function deleteReadNotifications($user_id) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM notifications 
                WHERE user_id = ? AND is_read = 1
            ");
            $stmt->bind_param("i", $user_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error deleting read notifications: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mendapatkan jumlah notifikasi yang belum dibaca
     */
    public function getUnreadCount($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            return $row['count'];
        } catch (Exception $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Helper functions untuk membuat notifikasi spesifik
     */
    
    public function createTugasBaruNotification($user_id, $assignment_title, $class_name, $assignment_id, $class_id) {
        return $this->createNotification(
            $user_id,
            'tugas_baru',
            'Tugas Baru Tersedia',
            "Guru membuat tugas baru",
            $assignment_id,
            $class_id
        );
    }
    
    public function createPostinganBaruNotification($user_id, $post_author, $class_name, $post_id, $class_id) {
        return $this->createNotification(
            $user_id,
            'postingan_baru',
            'Postingan Baru',
            "{$post_author} membuat postingan baru",
            $post_id,
            $class_id
        );
    }
    
    public function createUjianBaruNotification($user_id, $exam_title, $class_name, $exam_id, $class_id) {
        return $this->createNotification(
            $user_id,
            'ujian_baru',
            'Ujian Baru Tersedia',
            "Guru membuat ujian baru",
            $exam_id,
            $class_id
        );
    }
    
    public function createPengingatUjianNotification($user_id, $exam_title, $class_name, $exam_date, $exam_id, $class_id) {
        return $this->createNotification(
            $user_id,
            'pengingat_ujian',
            'Pengingat Ujian',
            "Ujian '{$exam_title}' akan dimulai besok ({$exam_date}) di kelas {$class_name}",
            $exam_id,
            $class_id
        );
    }
    
    /**
     * Mendapatkan icon untuk type notifikasi
     */
    public function getNotificationIcon($type) {
        switch ($type) {
            case 'tugas_baru':
                return 'ti-clipboard-plus';
            case 'postingan_baru':
                return 'ti-message-circle';
            case 'ujian_baru':
                return 'ti-file-text';
            case 'pengingat_ujian':
                return 'ti-bell';
            default:
                return 'ti-info-circle';
        }
    }
    
    /**
     * Mendapatkan warna untuk type notifikasi
     */
    public function getNotificationColor($type) {
        switch ($type) {
            case 'tugas_baru':
                return 'text-blue-500';
            case 'postingan_baru':
                return 'text-green-500';
            case 'ujian_baru':
                return 'text-purple-500';
            case 'pengingat_ujian':
                return 'text-orange-500';
            default:
                return 'text-gray-500';
        }
    }
    
    /**
     * Format waktu relatif
     */
    public function getTimeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'Baru saja';
        if ($time < 3600) return floor($time/60) . ' menit lalu';
        if ($time < 86400) return floor($time/3600) . ' jam lalu';
        if ($time < 2592000) return floor($time/86400) . ' hari lalu';
        if ($time < 31536000) return floor($time/2592000) . ' bulan lalu';
        
        return floor($time/31536000) . ' tahun lalu';
    }
    
    /**
     * Generate URL redirect berdasarkan jenis notifikasi
     */
    public function getNotificationRedirectUrl($notification) {
        $type = $notification['type'];
        $related_id = $notification['related_id'];
        $related_class_id = $notification['related_class_id'];
        
        switch ($type) {
            case 'postingan_baru':
                // Redirect ke halaman kelas dengan highlight postingan
                if ($related_class_id) {
                    return "kelas-user.php?id=" . $related_class_id . ($related_id ? "#post-" . $related_id : "");
                }
                break;
                
            case 'tugas_baru':
                // Redirect ke halaman kelas dengan highlight tugas/assignment
                if ($related_class_id) {
                    return "kelas-user.php?id=" . $related_class_id . ($related_id ? "#assignment-" . $related_id : "") . "&tab=assignments";
                }
                break;
                
            case 'ujian_baru':
                // Redirect ke halaman ujian siswa
                if ($related_id) {
                    return "ujian-user.php#ujian-" . $related_id;
                } elseif ($related_class_id) {
                    return "kelas-user.php?id=" . $related_class_id . "&tab=exams";
                }
                break;
                
            case 'pengingat_ujian':
                // Redirect langsung ke halaman ujian
                if ($related_id) {
                    return "ujian-user.php#ujian-" . $related_id;
                } elseif ($related_class_id) {
                    return "kelas-user.php?id=" . $related_class_id . "&tab=exams";
                }
                break;
                
            default:
                // Default redirect ke kelas jika ada class_id
                if ($related_class_id) {
                    return "kelas-user.php?id=" . $related_class_id;
                }
                break;
        }
        
        // Fallback ke beranda jika tidak ada redirect yang cocok
        return "beranda-user.php";
    }
    
    /**
     * Check if notification has valid redirect target
     */
    public function hasValidRedirect($notification) {
        return !empty($notification['related_id']) || !empty($notification['related_class_id']);
    }
}
?>