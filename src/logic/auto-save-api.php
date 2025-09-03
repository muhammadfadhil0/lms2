<?php
// Auto Save API Endpoint
session_start();

// Set proper headers untuk AJAX
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Cek apakah user sudah login dan role siswa
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'siswa') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once '../logic/auto-save-logic.php';

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
            echo json_encode($result);
            break;
            
        case 'get_status':
            // Get status semua jawaban
            $ujian_siswa_id = (int)($_POST['ujian_siswa_id'] ?? 0);
            
            if ($ujian_siswa_id <= 0) {
                throw new Exception('Parameter tidak valid');
            }
            
            $result = $autoSave->getStatusJawaban($ujian_siswa_id, $siswa_id);
            echo json_encode($result);
            break;
            
        case 'delete_answer':
            // Hapus jawaban
            $ujian_siswa_id = (int)($_POST['ujian_siswa_id'] ?? 0);
            $soal_id = (int)($_POST['soal_id'] ?? 0);
            
            if ($ujian_siswa_id <= 0 || $soal_id <= 0) {
                throw new Exception('Parameter tidak valid');
            }
            
            $result = $autoSave->hapusJawaban($ujian_siswa_id, $soal_id, $siswa_id);
            echo json_encode($result);
            break;
            
        case 'clear_all':
            // Clear semua jawaban (untuk reset)
            $ujian_siswa_id = (int)($_POST['ujian_siswa_id'] ?? 0);
            
            if ($ujian_siswa_id <= 0) {
                throw new Exception('Parameter tidak valid');
            }
            
            $result = $autoSave->clearAllAnswers($ujian_siswa_id, $siswa_id);
            echo json_encode($result);
            break;
            
        default:
            throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) {
    error_log("Auto Save API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
