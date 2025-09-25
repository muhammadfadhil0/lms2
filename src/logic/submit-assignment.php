<?php
session_start();
require_once 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'siswa') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $assignment_id = $_POST['assignment_id'];
    $siswa_id = $_SESSION['user']['id'];
    $catatan_pengumpulan = $_POST['notes'] ?? ''; // Updated parameter name
    
    // Validate assignment exists and is accessible
    $stmt = $pdo->prepare("
        SELECT t.*, k.guru_id 
        FROM tugas t 
        JOIN kelas k ON t.kelas_id = k.id 
        JOIN kelas_siswa ks ON k.id = ks.kelas_id 
        WHERE t.id = ? AND ks.siswa_id = ?
    ");
    $stmt->execute([$assignment_id, $siswa_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) {
        echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan atau Anda tidak memiliki akses']);
        exit();
    }
    
    // Check if deadline has passed
    if (strtotime($assignment['deadline']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Deadline tugas sudah terlewat']);
        exit();
    }
    
    // Handle file upload
    if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File tugas wajib diupload']);
        exit();
    }
    
    $upload_dir = '../../uploads/submissions/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION);
    $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png'];
    
    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung']);
        exit();
    }
    
    if ($_FILES['submission_file']['size'] > 10 * 1024 * 1024) { // 10MB limit
        echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar (maksimal 10MB)']);
        exit();
    }
    
    $filename = uniqid() . '_' . $_FILES['submission_file']['name'];
    $file_path = $upload_dir . $filename;
    
    if (!move_uploaded_file($_FILES['submission_file']['tmp_name'], $file_path)) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupload file']);
        exit();
    }
    
    // Store relative path
    $file_path = 'uploads/submissions/' . $filename;
    
    // Check if student has already submitted
    $stmt = $pdo->prepare("SELECT id FROM pengumpulan_tugas WHERE assignment_id = ? AND siswa_id = ?");
    $stmt->execute([$assignment_id, $siswa_id]);
    $existing_submission = $stmt->fetch();
    
    if ($existing_submission) {
        // Update existing submission
        $stmt = $pdo->prepare("
            UPDATE pengumpulan_tugas 
            SET file_path = ?, catatan_pengumpulan = ?, tanggal_pengumpulan = NOW(), status = 'dikumpulkan'
            WHERE id = ?
        ");
        $stmt->execute([$file_path, $catatan_pengumpulan, $existing_submission['id']]);
        $submission_id = $existing_submission['id'];
    } else {
        // Create new submission
        $stmt = $pdo->prepare("
            INSERT INTO pengumpulan_tugas (assignment_id, siswa_id, file_path, catatan_pengumpulan, tanggal_pengumpulan, status) 
            VALUES (?, ?, ?, ?, NOW(), 'dikumpulkan')
        ");
        $stmt->execute([$assignment_id, $siswa_id, $file_path, $catatan_pengumpulan]);
        $submission_id = $pdo->lastInsertId();
    }
    
    echo json_encode(['success' => true, 'message' => 'Tugas berhasil dikumpulkan', 'submission_id' => $submission_id]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
