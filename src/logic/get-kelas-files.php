<?php
// Start session hanya jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode([
        'error' => 'User not logged in',
        'files' => []
    ]);
    exit();
}

require_once 'kelas-files-logic.php';

try {
    $kelas_id = $_GET['kelas_id'] ?? null;
    $file_type = $_GET['file_type'] ?? null;
    
    if (!$kelas_id || !$file_type) {
        throw new Exception('Missing required parameters: kelas_id=' . $kelas_id . ', file_type=' . $file_type);
    }
    
    $kelasFilesLogic = new KelasFilesLogic();
    $files = $kelasFilesLogic->getFilesByType($kelas_id, $file_type);
    
    echo json_encode($files);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'files' => []
    ]);
}
?>
