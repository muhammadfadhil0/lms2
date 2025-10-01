<?php
// AI Evaluation API - Fixed Version
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Start session if needed
session_start();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit();
}

// Validate required fields
$requiredFields = ['ujian_id', 'ujian_siswa_id', 'guru_id'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
        exit();
    }
}

try {
    // Check and include required files
    $configPath = '../pingo/config.php';
    $ujianLogicPath = '../logic/ujian-logic.php';
    
    if (!file_exists($configPath)) {
        throw new Exception("Config file not found: $configPath");
    }
    
    if (!file_exists($ujianLogicPath)) {
        throw new Exception("UjianLogic file not found: $ujianLogicPath");
    }
    
    require_once $configPath;
    require_once $ujianLogicPath;
    
    // Initialize ujian logic
    $ujianLogic = new UjianLogic();
    
    // Get detail jawaban data
    $detailData = $ujianLogic->getDetailJawabanGuru(
        $input['ujian_id'], 
        $input['ujian_siswa_id'], 
        $input['guru_id']
    );
    
    if (!$detailData || isset($detailData['error'])) {
        throw new Exception('Data ujian tidak ditemukan atau tidak memiliki akses');
    }
    
    // Extract data
    $ujian = $detailData['ujian'];
    $siswa = $detailData['siswa'];
    $soalList = $detailData['soal_list'];
    $hasilUjian = $detailData['hasil_ujian'];
    
    // Create AI evaluator instance
    $aiEvaluator = new AIEvaluatorFixed();
    
    // Generate evaluation using AI
    $evaluationResult = $aiEvaluator->evaluateStudentPerformance([
        'ujian' => $ujian,
        'siswa' => $siswa,
        'soal_list' => $soalList,
        'hasil_ujian' => $hasilUjian
    ]);
    
    if (!$evaluationResult['success']) {
        throw new Exception($evaluationResult['error'] ?? 'Gagal melakukan evaluasi AI');
    }
    
    echo json_encode([
        'success' => true,
        'evaluation' => $evaluationResult['evaluation']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}

/**
 * Fixed AI Evaluator Class
 */
class AIEvaluatorFixed {
    private $apiKey;
    private $apiUrl;
    private $model;
    
    public function __construct() {
        // Check if constants are defined
        if (!defined('GROQ_API_KEY')) {
            throw new Exception('GROQ_API_KEY not defined. Please check config.php');
        }
        
        $this->apiKey = GROQ_API_KEY;
        $this->apiUrl = defined('GROQ_API_URL') ? GROQ_API_URL : 'https://api.groq.com/openai/v1/chat/completions';
        $this->model = defined('GROQ_MODEL') ? GROQ_MODEL : 'llama3-8b-8192';
        
        if (empty($this->apiKey) || $this->apiKey === 'your_groq_api_key_here') {
            throw new Exception('API Key Groq belum dikonfigurasi dengan benar');
        }
    }
    
    /**
     * Evaluate student performance using AI
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
     * Build evaluation prompt for AI
     */
    private function buildEvaluationPrompt($data) {
        $ujian = $data['ujian'];
        $siswa = $data['siswa'];
        $soalList = $data['soal_list'];
        $hasilUjian = $data['hasil_ujian'];
        
        // Calculate statistics
        $totalSoal = count($soalList);
        $jawabanBenar = (int)($hasilUjian['jumlahBenar'] ?? 0);
        $jawabanSalah = (int)($hasilUjian['jumlahSalah'] ?? 0);
        $tidakDijawab = $totalSoal - $jawabanBenar - $jawabanSalah;
        $persentaseBenar = $totalSoal > 0 ? round(($jawabanBenar / $totalSoal) * 100, 1) : 0;
        
        // Collect question and answer details
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
        
        $prompt = "Anda adalah seorang guru yang berpengalaman dalam mengevaluasi hasil belajar siswa. ";
        $prompt .= "Analisis hasil ujian berikut dan berikan evaluasi yang mendalam dan konstruktif.\n\n";
        
        $prompt .= "=== INFORMASI UJIAN ===\n";
        $prompt .= "Nama Ujian: {$ujian['namaUjian']}\n";
        $prompt .= "Mata Pelajaran: {$ujian['mataPelajaran']}\n";
        $prompt .= "Nama Siswa: {$siswa['namaLengkap']}\n";
        $prompt .= "Kelas: " . ($siswa['kelas'] ?? 'Tidak diketahui') . "\n\n";
        
        $prompt .= "=== STATISTIK HASIL ===\n";
        $prompt .= "Total Soal: {$totalSoal}\n";
        $prompt .= "Jawaban Benar: {$jawabanBenar}\n";
        $prompt .= "Jawaban Salah: {$jawabanSalah}\n";
        $prompt .= "Tidak Dijawab: {$tidakDijawab}\n";
        $prompt .= "Persentase Kebenaran: {$persentaseBenar}%\n\n";
        
        $prompt .= "=== DETAIL SOAL DAN JAWABAN ===\n";
        foreach ($detailSoalJawaban as $detail) {
            $status = $detail['dijawab'] ? ($detail['benar'] ? 'BENAR' : 'SALAH') : 'TIDAK DIJAWAB';
            $prompt .= "Soal {$detail['nomor']} ({$detail['tipe_soal']}): {$status}\n";
            $prompt .= "Pertanyaan: {$detail['pertanyaan']}\n\n";
        }
        
        $prompt .= "Berikan evaluasi dalam format JSON berikut:\n";
        $prompt .= "{\n";
        $prompt .= '  "deskripsi": "Deskripsi singkat performa siswa",' . "\n";
        $prompt .= '  "materi_perlu_evaluasi": {' . "\n";
        $prompt .= '    "jumlah_salah": ' . $jawabanSalah . ',' . "\n";
        $prompt .= '    "total_soal": ' . $totalSoal . ',' . "\n";
        $prompt .= '    "topik": ["Topik 1", "Topik 2"]' . "\n";
        $prompt .= '  },' . "\n";
        $prompt .= '  "materi_sudah_dikuasai": {' . "\n";
        $prompt .= '    "jumlah_benar": ' . $jawabanBenar . ',' . "\n";
        $prompt .= '    "total_soal": ' . $totalSoal . ',' . "\n";
        $prompt .= '    "topik": ["Topik 1", "Topik 2"]' . "\n";
        $prompt .= '  },' . "\n";
        $prompt .= '  "saran_pembelajaran": ["Saran 1", "Saran 2", "Saran 3"]' . "\n";
        $prompt .= "}\n";
        
        return $prompt;
    }
    
    /**
     * Call Groq API
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
                    'content' => 'Anda adalah guru yang berpengalaman. Berikan response dalam format JSON yang valid.'
                ],
                [
                    'role' => 'user', 
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1500,
            'temperature' => 0.7
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
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
     * Parse evaluation response from AI
     */
    private function parseEvaluationResponse($response) {
        // Extract JSON from response
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception('No valid JSON found in AI response');
        }
        
        $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $evaluation = json_decode($jsonStr, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse JSON: ' . json_last_error_msg());
        }
        
        // Validate required fields
        $requiredFields = ['deskripsi', 'materi_perlu_evaluasi', 'materi_sudah_dikuasai', 'saran_pembelajaran'];
        foreach ($requiredFields as $field) {
            if (!isset($evaluation[$field])) {
                throw new Exception("Missing field '$field' in AI evaluation");
            }
        }
        
        return $evaluation;
    }
}
?>