<?php
session_start();
require_once 'koneksi.php';
require_once 'postingan-logic.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_POST['assignment_id'] == null) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

try {
    global $pdo;
    $assignment_id = (int)$_POST['assignment_id'];
    $user_id = $_SESSION['user']['id'];
    
    // Verify user owns the assignment
    $sql = "SELECT t.*, p.id as postingan_id FROM tugas t 
            JOIN postingan_kelas p ON p.assignment_id = t.id 
            WHERE t.id = ? AND p.user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$assignment_id, $user_id]);
    $assignment = $stmt->fetch();
    
    if (!$assignment) {
        echo json_encode(['success' => false, 'message' => 'Assignment tidak ditemukan atau Anda tidak memiliki izin']);
        exit;
    }
    
    // Use PostinganLogic to delete the post (which will also delete assignment)
    $postinganLogic = new PostinganLogic();
    $result = $postinganLogic->hapusPostingan($assignment['postingan_id'], $user_id);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
