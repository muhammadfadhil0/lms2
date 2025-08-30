<?php
session_start();
require_once 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$assignment_id = $_GET['assignment_id'] ?? null;

if (!$assignment_id) {
    echo json_encode(['success' => false, 'message' => 'Assignment ID diperlukan']);
    exit();
}

try {
    $guru_id = $_SESSION['user']['id'];
    
    // Get assignment details and verify ownership
    $stmt = $pdo->prepare("
        SELECT t.*, k.namaKelas 
        FROM tugas t 
        JOIN kelas k ON t.kelas_id = k.id 
        WHERE t.id = ? AND k.guru_id = ?
    ");
    $stmt->execute([$assignment_id, $guru_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) {
        echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan atau akses ditolak']);
        exit();
    }
    
    // Get all students in the class with their submission status
    $stmt = $pdo->prepare("
        SELECT 
            u.id as siswa_id,
            u.namaLengkap as nama_siswa,
            pt.id as submission_id,
            pt.file_path,
            pt.catatan_pengumpulan,
            pt.tanggal_pengumpulan,
            pt.status,
            pt.nilai,
            pt.feedback
        FROM kelas_siswa ks
        JOIN users u ON ks.siswa_id = u.id
        LEFT JOIN pengumpulan_tugas pt ON pt.assignment_id = ? AND pt.siswa_id = u.id
        WHERE ks.kelas_id = ?
        ORDER BY u.namaLengkap ASC
    ");
    $stmt->execute([$assignment_id, $assignment['kelas_id']]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set status for students who haven't submitted
    foreach ($submissions as &$submission) {
        if (!$submission['submission_id']) {
            $submission['status'] = 'belum_mengumpulkan';
        }
    }
    
    echo json_encode([
        'success' => true, 
        'assignment' => $assignment,
        'submissions' => $submissions
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
