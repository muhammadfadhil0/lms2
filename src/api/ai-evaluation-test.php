<?php
// Simple test endpoint to check if basic functionality works
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Start output buffering
ob_start();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit();
}

// Validate required fields
$requiredFields = ['ujian_id', 'ujian_siswa_id', 'guru_id'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
        exit();
    }
}

// Mock evaluation result for testing
$mockEvaluation = [
    'deskripsi' => 'Siswa menunjukkan pemahaman yang cukup baik pada materi ujian dengan tingkat keberhasilan 70%. Masih perlu peningkatan pada beberapa konsep dasar.',
    'materi_perlu_evaluasi' => [
        'jumlah_salah' => 3,
        'total_soal' => 10,
        'topik' => [
            'Pemahaman konsep dasar matematika',
            'Aplikasi rumus dalam pemecahan masalah',
            'Analisis soal cerita'
        ]
    ],
    'materi_sudah_dikuasai' => [
        'jumlah_benar' => 7,
        'total_soal' => 10,
        'topik' => [
            'Operasi hitung dasar',
            'Penguasaan rumus sederhana',
            'Kemampuan membaca soal'
        ]
    ],
    'saran_pembelajaran' => [
        'Perbanyak latihan soal dengan tingkat kesulitan bertahap',
        'Gunakan metode pembelajaran visual dengan diagram dan gambar',
        'Berikan contoh aplikasi nyata dari konsep matematika dalam kehidupan sehari-hari',
        'Lakukan review berkala untuk memperkuat pemahaman konsep dasar'
    ]
];

// Clean output buffer and send response
ob_clean();
echo json_encode([
    'success' => true,
    'evaluation' => $mockEvaluation,
    'debug' => [
        'received_data' => $input,
        'timestamp' => date('Y-m-d H:i:s')
    ]
]);
?>