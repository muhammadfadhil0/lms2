<?php
session_start();
require_once 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $submission_id = $_POST['submission_id'];
    $score = $_POST['score'];
    $feedback = $_POST['feedback'] ?? '';
    $guru_id = $_SESSION['user']['id'];
    
    // Validate input
    if (empty($submission_id) || !is_numeric($score)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        exit();
    }
    
    // Verify submission exists and belongs to teacher's class
    $stmt = $pdo->prepare("
        SELECT pt.*, t.nilai_maksimal
        FROM pengumpulan_tugas pt
        JOIN tugas t ON pt.assignment_id = t.id
        JOIN kelas k ON t.kelas_id = k.id
        WHERE pt.id = ? AND k.guru_id = ?
    ");
    $stmt->execute([$submission_id, $guru_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        echo json_encode(['success' => false, 'message' => 'Pengumpulan tidak ditemukan atau akses ditolak']);
        exit();
    }
    
    // Validate score
    if ($score < 0 || $score > $submission['nilai_maksimal']) {
        echo json_encode(['success' => false, 'message' => 'Nilai harus antara 0 dan ' . $submission['nilai_maksimal']]);
        exit();
    }
    
    // Update submission with grade
    $stmt = $pdo->prepare("
        UPDATE pengumpulan_tugas 
        SET nilai = ?, feedback = ?, status = 'dinilai', tanggal_penilaian = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$score, $feedback, $submission_id]);
    
    echo json_encode(['success' => true, 'message' => 'Nilai berhasil disimpan']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
