<?php
session_start();
require_once 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$kelas_id = $_GET['kelas_id'] ?? null;

if (!$kelas_id) {
    echo json_encode(['success' => false, 'message' => 'Kelas ID diperlukan']);
    exit();
}

try {
    $user_id = $_SESSION['user']['id'];
    $user_role = $_SESSION['user']['role'];
    
    // Verify user has access to this class
    if ($user_role === 'guru') {
        $stmt = $pdo->prepare("SELECT id FROM kelas WHERE id = ? AND guru_id = ?");
        $stmt->execute([$kelas_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT k.id FROM kelas k JOIN kelas_siswa ks ON k.id = ks.kelas_id WHERE k.id = ? AND ks.siswa_id = ?");
        $stmt->execute([$kelas_id, $user_id]);
    }
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
        exit();
    }
    
    // Get assignments for this class
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            (SELECT COUNT(*) FROM kelas_siswa WHERE kelas_id = t.kelas_id) as total_students,
            (SELECT COUNT(*) FROM pengumpulan_tugas pt WHERE pt.assignment_id = t.id) as submitted_count
        FROM tugas t 
        WHERE t.kelas_id = ? 
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$kelas_id]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'assignments' => $assignments]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
