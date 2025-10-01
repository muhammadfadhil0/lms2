<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1); // Log errors to file

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Start session if needed
session_start();

// Start output buffering to catch any unexpected output
ob_start();

try {
    // Check if required files exist
    $requiredFiles = [
        '../pingo/config.php',
        '../logic/ujian-logic.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Required file not found: $file");
        }
    }
    
    require_once '../pingo/config.php';
    require_once '../logic/ujian-logic.php';
    
    // Try to include ai-logger if exists, but don't fail if it doesn't
    if (file_exists('../pingo/ai-logger.php')) {
        require_once '../pingo/ai-logger.php';
    }
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load required files: ' . $e->getMessage()]);
    exit();
}

/**
 * AI Evaluation API
 * Endpoint untuk menganalisis hasil ujian siswa menggunakan AI
 */

// Clean any previous output
ob_clean();

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Ambil data dari request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit();
}

// Validasi data yang diperlukan
$requiredFields = ['ujian_id', 'ujian_siswa_id', 'guru_id'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
        exit();
    }
}

try {
    // Inisialisasi ujian logic
    $ujianLogic = new UjianLogic();
    
    // Ambil data detail jawaban
    $detailData = $ujianLogic->getDetailJawabanGuru(
        $input['ujian_id'], 
        $input['ujian_siswa_id'], 
        $input['guru_id']
    );
    
    if (!$detailData || isset($detailData['error'])) {
        throw new Exception('Data ujian tidak ditemukan');
    }
    
    // Ekstrak data
    $ujian = $detailData['ujian'];
    $siswa = $detailData['siswa'];
    $soalList = $detailData['soal_list'];
    $hasilUjian = $detailData['hasil_ujian'];
    
    // Buat instance AI evaluator
    $aiEvaluator = new AIEvaluator();
    
    // Generate evaluasi menggunakan AI
    $evaluationResult = $aiEvaluator->evaluateStudentPerformance([
        'ujian' => $ujian,
        'siswa' => $siswa,
        'soal_list' => $soalList,
        'hasil_ujian' => $hasilUjian
    ]);
    
    if (!$evaluationResult['success']) {
        throw new Exception($evaluationResult['error'] ?? 'Gagal melakukan evaluasi AI');
    }
    
    // Clean output buffer before sending response
    ob_clean();
    echo json_encode([
        'success' => true,
        'evaluation' => $evaluationResult['evaluation']
    ]);
    
} catch (Exception $e) {
    // Clean output buffer before sending error response
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

/**
 * Class AIEvaluator
 * Handle evaluasi hasil ujian menggunakan AI
 */
class AIEvaluator {
    private $apiKey;
    private $apiUrl;
    private $model;
    
    public function __construct() {
        $this->apiKey = GROQ_API_KEY;
        $this->apiUrl = GROQ_API_URL;
        $this->model = GROQ_MODEL;
        
        if (empty($this->apiKey) || $this->apiKey === 'your_groq_api_key_here') {
            throw new Exception('API Key Groq belum dikonfigurasi');
        }
    }
    
    /**
     * Evaluasi performa siswa menggunakan AI
     */
    public function evaluateStudentPerformance($data) {
        try {
            $prompt = $this->buildEvaluationPrompt($data);
            $response = $this->callGroqAPI($prompt);
            
            if (!$response) {
                throw new Exception('Tidak ada response dari AI');
            }
            
            $evaluation = $this->parseEvaluationResponse($response);
            
            return [
                'success' => true,
                'evaluation' => $evaluation
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Build prompt untuk evaluasi AI
     */
    private function buildEvaluationPrompt($data) {
        $ujian = $data['ujian'];
        $siswa = $data['siswa'];
        $soalList = $data['soal_list'];
        $hasilUjian = $data['hasil_ujian'];
        
        // Hitung statistik jawaban
        $totalSoal = count($soalList);
        $jawabanBenar = (int)($hasilUjian['jumlahBenar'] ?? 0);
        $jawabanSalah = (int)($hasilUjian['jumlahSalah'] ?? 0);
        $tidakDijawab = $totalSoal - $jawabanBenar - $jawabanSalah;
        $persentaseBenar = $totalSoal > 0 ? round(($jawabanBenar / $totalSoal) * 100, 1) : 0;
        
        // Kumpulkan detail soal dan jawaban
        $detailSoalJawaban = [];
        foreach ($soalList as $soal) {
            $isAnswered = !empty($soal['jawaban']) || !empty($soal['pilihanJawaban']);
            $isCorrect = $soal['benar'] == 1;
            
            $detailSoalJawaban[] = [
                'nomor' => $soal['nomorSoal'],
                'pertanyaan' => substr($soal['pertanyaan'], 0, 100) . '...',
                'tipe_soal' => $soal['tipeSoal'],
                'dijawab' => $isAnswered,
                'benar' => $isCorrect,
                'jawaban_siswa' => $soal['jawaban'] ?? $soal['pilihanJawaban'] ?? 'Tidak dijawab'
            ];
        }
        
        $prompt = "Anda adalah seorang guru yang sangat berpengalaman dalam mengevaluasi hasil belajar siswa. ";
        $prompt .= "Analisis hasil ujian berikut dan berikan evaluasi yang mendalam dan konstruktif.\n\n";
        
        $prompt .= "=== INFORMASI UJIAN ===\n";
        $prompt .= "Nama Ujian: {$ujian['namaUjian']}\n";
        $prompt .= "Nama Siswa: {$siswa['namaLengkap']}\n";
        $prompt .= "Kelas: " . ($siswa['kelas'] ?? 'Tidak diketahui') . "\n";
        $prompt .= "Durasi Ujian: " . ($ujian['durasi'] ?? 'Tidak dibatasi') . "\n\n";
        
        $prompt .= "=== STATISTIK HASIL ===\n";
        $prompt .= "Total Soal: {$totalSoal}\n";
        $prompt .= "Jawaban Benar: {$jawabanBenar}\n";
        $prompt .= "Jawaban Salah: {$jawabanSalah}\n";
        $prompt .= "Tidak Dijawab: {$tidakDijawab}\n";
        $prompt .= "Persentase Kebenaran: {$persentaseBenar}%\n";
        $prompt .= "Nilai Total: " . ($hasilUjian['totalNilai'] ?? 'Belum dihitung') . "\n\n";
        
        $prompt .= "=== DETAIL SOAL DAN JAWABAN ===\n";
        foreach ($detailSoalJawaban as $detail) {
            $status = $detail['dijawab'] ? ($detail['benar'] ? 'BENAR' : 'SALAH') : 'TIDAK DIJAWAB';
            $prompt .= "Soal {$detail['nomor']} ({$detail['tipe_soal']}): {$status}\n";
            $prompt .= "Pertanyaan: {$detail['pertanyaan']}\n";
            $prompt .= "Jawaban Siswa: {$detail['jawaban_siswa']}\n\n";
        }
        
        $prompt .= "=== INSTRUKSI EVALUASI ===\n";
        $prompt .= "Berdasarkan data di atas, buatlah evaluasi komprehensif dalam format JSON dengan struktur berikut:\n\n";
        
        $prompt .= "{\n";
        $prompt .= '  "deskripsi": "Deskripsi singkat (2-3 kalimat) mengenai penilaian keseluruhan performa siswa ini",' . "\n";
        $prompt .= '  "materi_perlu_evaluasi": {' . "\n";
        $prompt .= '    "jumlah_salah": [jumlah soal yang salah],' . "\n";
        $prompt .= '    "total_soal": [total soal],' . "\n";
        $prompt .= '    "topik": [' . "\n";
        $prompt .= '      "Topik 1 yang perlu dipelajari ulang",' . "\n";
        $prompt .= '      "Topik 2 yang perlu dipelajari ulang"' . "\n";
        $prompt .= '    ]' . "\n";
        $prompt .= '  },' . "\n";
        $prompt .= '  "materi_sudah_dikuasai": {' . "\n";
        $prompt .= '    "jumlah_benar": [jumlah soal yang benar],' . "\n";
        $prompt .= '    "total_soal": [total soal],' . "\n";
        $prompt .= '    "topik": [' . "\n";
        $prompt .= '      "Topik 1 yang sudah dikuasai",' . "\n";
        $prompt .= '      "Topik 2 yang sudah dikuasai"' . "\n";
        $prompt .= '    ]' . "\n";
        $prompt .= '  },' . "\n";
        $prompt .= '  "saran_pembelajaran": [' . "\n";
        $prompt .= '    "Metode pembelajaran 1 yang direkomendasikan",' . "\n";
        $prompt .= '    "Metode pembelajaran 2 yang direkomendasikan",' . "\n";
        $prompt .= '    "Metode pembelajaran 3 yang direkomendasikan"' . "\n";
        $prompt .= '  ]' . "\n";
        $prompt .= "}\n\n";
        
        $prompt .= "PANDUAN EVALUASI:\n";
        $prompt .= "1. Deskripsi harus objektif dan konstruktif\n";
        $prompt .= "2. Identifikasi topik spesifik berdasarkan soal yang salah/benar\n";
        $prompt .= "3. Saran pembelajaran harus praktis dan sesuai dengan mata pelajaran\n";
        $prompt .= "4. Gunakan bahasa yang mendorong dan memotivasi\n";
        $prompt .= "5. Berikan response dalam format JSON yang valid\n";
        
        return $prompt;
    }
    
    /**
     * Panggil Groq API
     */
    private function callGroqAPI($prompt) {
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Anda adalah guru yang sangat berpengalaman dalam mengevaluasi hasil belajar siswa. Selalu berikan response dalam format JSON yang valid dan evaluasi yang konstruktif.'
                ],
                [
                    'role' => 'user', 
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 2000,
            'temperature' => 0.7
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_POST => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Curl error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'API Error';
            throw new Exception('API Error (' . $httpCode . '): ' . $errorMsg);
        }
        
        $responseData = json_decode($response, true);
        
        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }
        
        return $responseData['choices'][0]['message']['content'];
    }
    
    /**
     * Parse response evaluasi dari AI
     */
    private function parseEvaluationResponse($response) {
        // Coba extract JSON dari response
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception('Invalid JSON format in AI response');
        }
        
        $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $evaluation = json_decode($jsonStr, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse JSON: ' . json_last_error_msg());
        }
        
        // Validasi struktur response
        $requiredFields = ['deskripsi', 'materi_perlu_evaluasi', 'materi_sudah_dikuasai', 'saran_pembelajaran'];
        foreach ($requiredFields as $field) {
            if (!isset($evaluation[$field])) {
                throw new Exception("Missing field '$field' in AI evaluation");
            }
        }
        
        return $evaluation;
    }
}