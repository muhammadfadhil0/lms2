<?php
session_start();
require_once 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$submission_id = $_GET['submission_id'] ?? null;

if (!$submission_id) {
    echo json_encode(['success' => false, 'message' => 'Submission ID diperlukan']);
    exit();
}

try {
    $guru_id = $_SESSION['user']['id'];
    
    // Debug logging
    error_log("DEBUG get-submission-details.php: submission_id=$submission_id, guru_id=$guru_id");
    
    // Get submission details with verification
    $stmt = $pdo->prepare("
        SELECT 
            pt.*,
            u.namaLengkap as nama_siswa,
            t.nilai_maksimal,
            t.judul as assignment_title
        FROM pengumpulan_tugas pt
        JOIN users u ON pt.siswa_id = u.id
        JOIN tugas t ON pt.assignment_id = t.id
        JOIN kelas k ON t.kelas_id = k.id
        WHERE pt.id = ? AND k.guru_id = ?
    ");
    $stmt->execute([$submission_id, $guru_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("DEBUG get-submission-details.php: submission found=" . ($submission ? 'YES' : 'NO'));
    if ($submission) {
        error_log("DEBUG get-submission-details.php: file_path=" . $submission['file_path']);
    }
    
    if (!$submission) {
        echo json_encode(['success' => false, 'message' => 'Pengumpulan tidak ditemukan atau akses ditolak']);
        exit();
    }
    
    echo json_encode(['success' => true, 'submission' => $submission]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
