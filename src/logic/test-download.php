<?php
require_once 'koneksi.php';

$file_id = $_GET['file_id'] ?? 3;

try {
    // Get file info
    $sql = "SELECT * FROM kelas_files WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$file_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        throw new Exception('File not found in database');
    }
    
    if (!file_exists($file['file_path'])) {
        throw new Exception('File not found on disk: ' . $file['file_path']);
    }
    
    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
    header('Content-Length: ' . filesize($file['file_path']));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Read and output the file
    readfile($file['file_path']);
    exit();
    
} catch (Exception $e) {
    header('HTTP/1.1 404 Not Found');
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit();
}
?>
