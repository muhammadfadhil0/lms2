<?php
// Auto Save API Endpoint
session_start();

// Start output buffering to capture accidental output (warnings, stray text)
if (!ob_get_level()) ob_start();

// Helper to send a clean JSON response (clears any prior output)
function send_json_response($data, $httpCode = 200) {
    // Clear any buffered output to avoid mixing non-JSON text
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    http_response_code($httpCode);
    echo json_encode($data);
    exit();
}

// Cek apakah user sudah login dan role siswa
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'siswa') {
    send_json_response(['success' => false, 'message' => 'Unauthorized'], 403);
}

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

require_once __DIR__ . '/auto-save-logic.php';

try {
    $autoSave = new AutoSaveLogic();
    $siswa_id = $_SESSION['user']['id'];
    
    // Ambil action dari request
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'auto_save':
            // Auto save jawaban
            $ujian_siswa_id = (int)($_POST['ujian_siswa_id'] ?? 0);
            $soal_id = (int)($_POST['soal_id'] ?? 0);
            $jawaban = $_POST['jawaban'] ?? '';
            
            if ($ujian_siswa_id <= 0 || $soal_id <= 0) {
                throw new Exception('Parameter tidak valid');
            }
            
            $result = $autoSave->autoSaveJawaban($ujian_siswa_id, $soal_id, $jawaban, $siswa_id);
            send_json_response($result);
            break;
            
        case 'get_status':
            // Get status semua jawaban
            $ujian_siswa_id = (int)($_POST['ujian_siswa_id'] ?? 0);
            
            if ($ujian_siswa_id <= 0) {
                throw new Exception('Parameter tidak valid');
            }
            
            $result = $autoSave->getStatusJawaban($ujian_siswa_id, $siswa_id);
            send_json_response($result);
            break;
            
        case 'delete_answer':
            // Hapus jawaban
            $ujian_siswa_id = (int)($_POST['ujian_siswa_id'] ?? 0);
            $soal_id = (int)($_POST['soal_id'] ?? 0);
            
            if ($ujian_siswa_id <= 0 || $soal_id <= 0) {
                throw new Exception('Parameter tidak valid');
            }
            
            $result = $autoSave->hapusJawaban($ujian_siswa_id, $soal_id, $siswa_id);
            send_json_response($result);
            break;
            
        case 'clear_all':
            // Clear semua jawaban (untuk reset)
            $ujian_siswa_id = (int)($_POST['ujian_siswa_id'] ?? 0);
            
            if ($ujian_siswa_id <= 0) {
                throw new Exception('Parameter tidak valid');
            }
            
            $result = $autoSave->clearAllAnswers($ujian_siswa_id, $siswa_id);
            send_json_response($result);
            break;
            
        default:
            throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) {
    error_log("Auto Save API Error: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => $e->getMessage()], 400);
}
?>
