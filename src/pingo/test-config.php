<?php
/**
 * Test PingoAI Configuration
 * File untuk testing koneksi dan konfigurasi PingoAI
 */

require_once 'config.php';
require_once 'pingo-ai.php';

// Set content type
header('Content-Type: application/json');

try {
    // Test basic configuration
    if (empty(GROQ_API_KEY) || GROQ_API_KEY === 'your_groq_api_key_here') {
        throw new Exception('API Key Groq belum dikonfigurasi');
    }
    
    // Test PingoAI initialization
    $pingoAI = new PingoAI();
    
    // Test parameters
    $testParams = [
        'exam_name' => 'Test Ujian Matematika',
        'subject' => 'Matematika',
        'description' => 'Ujian untuk menguji pemahaman dasar matematika',
        'question_count' => 2,
        'question_type' => 'multiple_choice',
        'answer_options' => 4,
        'difficulty' => 'mudah',
        'existing_questions' => []
    ];
    
    // Validate parameters
    $validationErrors = $pingoAI->validateParams($testParams);
    if (!empty($validationErrors)) {
        throw new Exception('Validation errors: ' . implode(', ', $validationErrors));
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Konfigurasi PingoAI berhasil!',
        'config' => [
            'api_url' => GROQ_API_URL,
            'model' => GROQ_MODEL,
            'timeout' => AI_TIMEOUT,
            'max_tokens' => AI_MAX_TOKENS,
            'temperature' => AI_TEMPERATURE,
            'max_questions' => MAX_QUESTIONS_PER_REQUEST
        ],
        'test_params' => $testParams,
        'note' => 'Untuk testing penuh, jalankan generate questions dengan parameter ini'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'suggestions' => [
            'Pastikan API Key Groq sudah diisi di config.php',
            'Periksa koneksi internet',
            'Pastikan semua dependencies sudah terpasang'
        ]
    ]);
}
?>
