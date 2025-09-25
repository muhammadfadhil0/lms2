<?php
session_start();
require_once 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'siswa') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$assignment_id = $_GET['assignment_id'] ?? null;

if (!$assignment_id) {
    echo json_encode(['success' => false, 'message' => 'Assignment ID diperlukan']);
    exit();
}

try {
    $siswa_id = $_SESSION['user']['id'];
    
    // Get student's submission for this assignment
    $stmt = $pdo->prepare("
        SELECT 
            pt.*,
            t.nilai_maksimal
        FROM pengumpulan_tugas pt
        JOIN tugas t ON pt.assignment_id = t.id
        JOIN kelas k ON t.kelas_id = k.id
        JOIN kelas_siswa ks ON k.id = ks.kelas_id
        WHERE pt.assignment_id = ? AND pt.siswa_id = ? AND ks.siswa_id = ?
    ");
    $stmt->execute([$assignment_id, $siswa_id, $siswa_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($submission) {
        echo json_encode(['success' => true, 'submission' => $submission]);
    } else {
        echo json_encode(['success' => true, 'submission' => null]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
