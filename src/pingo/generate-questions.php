<?php
// DEBUG: Show all errors for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../logic/koneksi.php';
require_once '../logic/ujian-logic.php';
require_once '../logic/soal-logic.php';
require_once 'pingo-ai.php';
require_once 'pingo-api-helper.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

try {
    // Get and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Debug: Log received parameters
    error_log('PingoAI Received parameters: ' . json_encode($input));
    
    $ujian_id = $input['ujian_id'] ?? 0;
    $questionCount = $input['question_count'] ?? 5;
    $questionType = $input['question_type'] ?? 'multiple_choice';
    $answerOptions = $input['answer_options'] ?? 4;
    $difficulty = $input['difficulty'] ?? 'sedang';
    
    // Debug: Validate ujian_id
    if (empty($ujian_id) || !is_numeric($ujian_id)) {
        throw new Exception('ID Ujian tidak valid: ' . $ujian_id);
    }
    
    // Initialize logic classes
    $ujianLogic = new UjianLogic();
    $soalLogic = new SoalLogic();
    $guru_id = $_SESSION['user']['id'];
    
    // Debug: Log guru_id and ujian_id
    error_log("PingoAI: Checking ujian_id=$ujian_id for guru_id=$guru_id");
    
    // Verify ujian ownership
    $ujian = $ujianLogic->getUjianByIdAndGuru($ujian_id, $guru_id);
    if (!$ujian) {
        error_log("PingoAI: Ujian not found for ujian_id=$ujian_id, guru_id=$guru_id");
        throw new Exception('Ujian tidak ditemukan atau Anda tidak memiliki akses');
    }
    
    // Check if autoScore mode is enabled and validate question type
    $isAutoScore = isset($ujian['autoScore']) && (bool)$ujian['autoScore'];
    
    if ($isAutoScore && $questionType === 'essay') {
        throw new Exception('Soal essay tidak dapat dibuat dalam mode penilaian otomatis. Hanya soal pilihan ganda yang diperbolehkan.');
    }
    
    // Get existing questions to avoid duplication
    $existingQuestions = [];
    $existingSoal = $soalLogic->getSoalByUjian($ujian_id);
    foreach ($existingSoal as $soal) {
        $existingQuestions[] = $soal['pertanyaan'];
    }
    
    // Prepare parameters for AI
    $params = [
        'exam_name' => $ujian['namaUjian'],
        'subject' => $ujian['mataPelajaran'] ?? 'Umum',
        'description' => $ujian['deskripsi'] ?? '',
        'topik' => $ujian['topik'] ?? '', // ← Tambahkan topik spesifik
        'question_count' => $questionCount,
        'question_type' => $questionType,
        'answer_options' => $answerOptions,
        'difficulty' => $difficulty,
        'existing_questions' => $existingQuestions
    ];
    
    // Initialize API helper and use selected API key
    $apiHelper = new PingoApiHelper();
    $userId = $_SESSION['user']['id'];
    
    // Generate questions using selected API
    $result = $apiHelper->generateQuestions($userId, $params, 'buat-soal');
    
    if (!$result['success']) {
        throw new Exception($result['error']);
    }
    
    if (empty($result['questions'])) {
        throw new Exception('AI tidak menghasilkan soal apapun');
    }
    
    // Process and save generated questions
    $savedQuestions = [];
    $totalQuestions = 0;
    
    foreach ($result['questions'] as $index => $question) {
        try {
            $savedQuestion = ['success' => false, 'message' => 'Unknown question type']; // Default
            
            // Validate required fields
            if (empty($question['question'])) {
                continue;
            }
            
            if ($question['type'] === 'multiple_choice') {
                // Get next question number
                $conn = getConnection();
                $res = $conn->prepare('SELECT COALESCE(MAX(nomorSoal),0)+1 as nextNo FROM soal WHERE ujian_id=?');
                $res->bind_param('i', $ujian_id);
                $res->execute();
                $next = $res->get_result()->fetch_assoc();
                $nomor = (int)$next['nextNo'];
                
                // Save multiple choice question
                $savedQuestion = $soalLogic->buatSoalPilihanGanda(
                    $ujian_id,
                    $nomor,
                    $question['question'],
                    $question['options'],
                    $question['correct_answer'],
                    $question['points']
                );
            } else if ($question['type'] === 'essay') {
                // Get next question number
                $conn = getConnection();
                $res = $conn->prepare('SELECT COALESCE(MAX(nomorSoal),0)+1 as nextNo FROM soal WHERE ujian_id=?');
                $res->bind_param('i', $ujian_id);
                $res->execute();
                $next = $res->get_result()->fetch_assoc();
                $nomor = (int)$next['nextNo'];
                
                // Save essay question
                $savedQuestion = $soalLogic->buatSoalJawaban(
                    $ujian_id,
                    $nomor,
                    $question['question'],
                    'essay',
                    $question['sample_answer'] ?? '',
                    $question['points']
                );
            }
            
            if ($savedQuestion['success']) {
                $savedQuestions[] = [
                    'id' => $savedQuestion['soal_id'],
                    'question' => $question['question'],
                    'type' => $question['type'],
                    'options' => $question['options'] ?? [],
                    'correct_answer' => $question['correct_answer'] ?? '',
                    'explanation' => $question['explanation'] ?? '',
                    'points' => $question['points']
                ];
                $totalQuestions++;
            }
        } catch (Exception $e) {
            // Log error but continue with other questions
            error_log("Error saving question: " . $e->getMessage());
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Berhasil generate {$totalQuestions} soal menggunakan PingoAI",
        'questions' => $savedQuestions,
        'total' => $totalQuestions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
