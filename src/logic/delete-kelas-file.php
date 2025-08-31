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
    $input = json_decode(file_get_contents('php://input'), true);
    $file_id = $input['file_id'] ?? null;
    
    if (!$file_id) {
        throw new Exception('Missing file ID');
    }
    
    $kelasFilesLogic = new KelasFilesLogic();
    $result = $kelasFilesLogic->deleteFile($file_id, $_SESSION['user']['id']);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
