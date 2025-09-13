<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a guru
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once 'kelas-files-logic.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $kelas_id = $_POST['kelas_id'] ?? null;
    $file_type = $_POST['file_type'] ?? null;
    
    if (!$kelas_id || !$file_type) {
        throw new Exception('Missing required fields: kelas_id=' . $kelas_id . ', file_type=' . $file_type);
    }
    
    // Check if file was uploaded
    $file_field = $file_type === 'schedule' ? 'schedule_file' : 'material_file';
    if (!isset($_FILES[$file_field])) {
        throw new Exception('No file field found: ' . $file_field);
    }
    
    if ($_FILES[$file_field]['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (form limit)',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang dipilih',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension'
        ];
        $error_message = $upload_errors[$_FILES[$file_field]['error']] ?? 'Unknown upload error';
        throw new Exception('Upload error: ' . $error_message);
    }
    
    // Use filename as title, no description
    $title = pathinfo($_FILES[$file_field]['name'], PATHINFO_FILENAME);
    $description = '';
    
    $kelasFilesLogic = new KelasFilesLogic();
    $result = $kelasFilesLogic->uploadFile(
        $kelas_id,
        $_SESSION['user']['id'],
        $file_type,
        $title,
        $description,
        $_FILES[$file_field]
    );
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'POST' => $_POST,
            'FILES' => array_map(function($file) {
                return [
                    'name' => $file['name'],
                    'size' => $file['size'],
                    'error' => $file['error'],
                    'type' => $file['type']
                ];
            }, $_FILES)
        ]
    ]);
}
?>
