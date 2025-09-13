<?php
// Debug version of get-assignments.php
session_start();
require_once 'koneksi.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $kelas_id = $_GET['kelas_id'] ?? null;
    $search = trim($_GET['search'] ?? '');
    $sort = $_GET['sort'] ?? 'created_desc';

    if (!$kelas_id) {
        echo json_encode(['success' => false, 'message' => 'Kelas ID diperlukan']);
        exit();
    }

    $user_id = $_SESSION['user']['id'];
    $user_role = $_SESSION['user']['role'];
    
    // Debug info
    $debug = [
        'kelas_id' => $kelas_id,
        'user_id' => $user_id,
        'user_role' => $user_role,
        'search' => $search,
        'sort' => $sort
    ];
    
    // Get PDO connection
    global $pdo;
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed', 'debug' => $debug]);
        exit();
    }
    
    // Verify user has access to this class
    if ($user_role === 'guru') {
        $stmt = $pdo->prepare("SELECT id FROM kelas WHERE id = ? AND guru_id = ?");
        $stmt->execute([$kelas_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT k.id FROM kelas k JOIN kelas_siswa ks ON k.id = ks.kelas_id WHERE k.id = ? AND ks.siswa_id = ?");
        $stmt->execute([$kelas_id, $user_id]);
    }
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Akses ditolak', 'debug' => $debug]);
        exit();
    }
    
    // Build where clause for search
    $whereClause = "WHERE t.kelas_id = ?";
    $params = [$kelas_id];
    
    if (!empty($search)) {
        $whereClause .= " AND (t.judul LIKE ? OR t.deskripsi LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // Determine sort order
    $orderClause = "";
    switch ($sort) {
        case 'created_asc':
            $orderClause = "ORDER BY t.created_at ASC";
            break;
        case 'name_asc':
            $orderClause = "ORDER BY t.judul ASC";
            break;
        case 'name_desc':
            $orderClause = "ORDER BY t.judul DESC";
            break;
        case 'deadline_asc':
            $orderClause = "ORDER BY t.deadline ASC";
            break;
        case 'deadline_desc':
            $orderClause = "ORDER BY t.deadline DESC";
            break;
        case 'created_desc':
        default:
            $orderClause = "ORDER BY t.created_at DESC";
            break;
    }
    
    // Get assignments with submission status for students
    if ($user_role === 'siswa') {
        $sql = "SELECT t.*, 
                       pt.status as student_status,
                       pt.nilai as student_score,
                       pt.tanggal_pengumpulan as submission_date,
                       CASE 
                           WHEN t.deadline < NOW() THEN 'expired'
                           WHEN pt.status = 'dinilai' THEN 'graded'
                           WHEN pt.status = 'dikumpulkan' THEN 'submitted'
                           ELSE 'pending'
                       END as submission_status
                FROM tugas t
                LEFT JOIN pengumpulan_tugas pt ON t.id = pt.assignment_id AND pt.siswa_id = ?
                $whereClause
                $orderClause";
        array_unshift($params, $user_id);
    } else {
        // For teachers, get assignment statistics
        $sql = "SELECT t.*, 
                       COUNT(pt.id) as total_submissions,
                       COUNT(CASE WHEN pt.status = 'dinilai' THEN 1 END) as graded_count,
                       (SELECT COUNT(*) FROM kelas_siswa WHERE kelas_id = t.kelas_id) as total_students
                FROM tugas t
                LEFT JOIN pengumpulan_tugas pt ON t.id = pt.assignment_id
                $whereClause
                GROUP BY t.id
                $orderClause";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data for frontend
    foreach ($assignments as &$assignment) {
        // Format deadline
        if ($assignment['deadline']) {
            $assignment['deadline_formatted'] = date('j M Y, H:i', strtotime($assignment['deadline']));
            $assignment['is_deadline_soon'] = strtotime($assignment['deadline']) - time() < (24 * 60 * 60 * 3); // 3 days
            $assignment['is_deadline_passed'] = strtotime($assignment['deadline']) < time();
        }
        
        // Format creation date
        $assignment['created_formatted'] = date('j M Y, H:i', strtotime($assignment['created_at']));
        
        // Add relative time
        $timeDiff = time() - strtotime($assignment['created_at']);
        if ($timeDiff < 3600) {
            $assignment['time_ago'] = floor($timeDiff / 60) . ' menit yang lalu';
        } elseif ($timeDiff < 86400) {
            $assignment['time_ago'] = floor($timeDiff / 3600) . ' jam yang lalu';
        } else {
            $assignment['time_ago'] = floor($timeDiff / 86400) . ' hari yang lalu';
        }
    }
    
    echo json_encode([
        'success' => true, 
        'assignments' => $assignments,
        'total' => count($assignments),
        'search' => $search,
        'sort' => $sort,
        'debug' => $debug,
        'sql' => $sql,
        'params' => $params
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
