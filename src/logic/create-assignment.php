<?php
session_start();
require_once 'koneksi.php';

header('Content-Type: application/json');

// Disable error display in production (but still log errors)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $kelas_id = $_POST['kelas_id'] ?? null;
    $guru_id = $_SESSION['user']['id'];
    $judul = $_POST['assignmentTitle'] ?? null;
    $deskripsi = $_POST['assignmentDescription'] ?? null;
    $deadline = $_POST['assignmentDeadline'] ?? null;
    $nilai_maksimal = $_POST['maxScore'] ?? null;
    
    // Validate input
    if (empty($kelas_id) || empty($judul) || empty($deskripsi) || empty($deadline) || empty($nilai_maksimal)) {
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi', 'debug' => $_POST]);
        exit();
    }
    
    // Verify that the teacher owns this class
    $stmt = $pdo->prepare("SELECT id FROM kelas WHERE id = ? AND guru_id = ?");
    $stmt->execute([$kelas_id, $guru_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses ke kelas ini']);
        exit();
    }
    
    // Handle file upload if present
    $file_path = null;
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/assignments/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                echo json_encode(['success' => false, 'message' => 'Gagal membuat direktori upload']);
                exit();
            }
        }
        
        $file_extension = pathinfo($_FILES['assignment_file']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'];
        
        if (!in_array(strtolower($file_extension), $allowed_extensions)) {
            echo json_encode(['success' => false, 'message' => 'Format file tidak didukung']);
            exit();
        }
        
        if ($_FILES['assignment_file']['size'] > 10 * 1024 * 1024) { // 10MB limit
            echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar (maksimal 10MB)']);
            exit();
        }
        
        $filename = uniqid() . '_' . $_FILES['assignment_file']['name'];
        $full_file_path = $upload_dir . $filename;
        
        if (!move_uploaded_file($_FILES['assignment_file']['tmp_name'], $full_file_path)) {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupload file']);
            exit();
        }
        
        // Store relative path
        $file_path = 'uploads/assignments/' . $filename;
    }
    
    // Insert assignment
    $stmt = $pdo->prepare("
        INSERT INTO tugas (kelas_id, judul, deskripsi, file_path, deadline, nilai_maksimal, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$kelas_id, $judul, $deskripsi, $file_path, $deadline, $nilai_maksimal]);
    
    $assignment_id = $pdo->lastInsertId();
    
    // Create a special post for this assignment - only description in content
    $konten_post = "{$deskripsi}";
    
    $stmt = $pdo->prepare("


        INSERT INTO postingan_kelas (kelas_id, user_id, konten, tipe_postingan, assignment_id, dibuat) 
        VALUES (?, ?, ?, 'assignment', ?, NOW())
    ");
    $stmt->execute([$kelas_id, $guru_id, $konten_post, $assignment_id]);


    
    echo json_encode(['success' => true, 'message' => 'Tugas berhasil dibuat', 'assignment_id' => $assignment_id]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
