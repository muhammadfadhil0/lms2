<?php
// Debug mode - comment out for production
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    die('Error: Please login first');
}

require_once 'kelas-files-logic.php';

try {
    $file_id = $_GET['file_id'] ?? null;
    
    if (!$file_id) {
        throw new Exception('Missing file ID');
    }
    
    $kelasFilesLogic = new KelasFilesLogic();
    $file = $kelasFilesLogic->getFileForDownload($file_id, null);
    
    if (!$file) {
        throw new Exception('File not found in database');
    }
    
    // For students, check if they're enrolled in the class (simplified for now)
    if ($_SESSION['user']['role'] === 'siswa') {
        // Skip enrollment check for now to test basic download functionality
        // TODO: Add enrollment check back later
    }
    
    // Check if file exists - fix relative path
    $file_path = $file['file_path'];
    
    if (!file_exists($file_path)) {
        throw new Exception('File not found on server: ' . $file_path);
    }
    
    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Read and output the file
    readfile($file_path);
    exit();
    
} catch (Exception $e) {
    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Return error page instead of redirect
    http_response_code(404);
    echo '<h1>Download Error</h1>';
    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<a href="javascript:history.back()">Go Back</a>';
    exit();
}
?>
