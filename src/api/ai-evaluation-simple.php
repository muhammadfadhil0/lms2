<?php
// AI Evaluation API - Simple Version with Database Fix
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Start output buffering to catch any errors
ob_start();

// Start session
session_start();

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

try {
    // Initialize database connection using same config as system
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "lms";
    
    try {
        $dsn = "mysql:host=localhost;port=3306;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
    
    // Get ujian data directly from database
    $stmt = $pdo->prepare("
        SELECT u.*, us.*, s.namaLengkap, s.email, s.fotoProfil,
               COUNT(CASE WHEN js.benar = 1 THEN 1 END) as jumlahBenar,
               COUNT(CASE WHEN js.benar = 0 THEN 1 END) as jumlahSalah,
               AVG(CASE WHEN js.benar IS NOT NULL THEN js.poin END) as avgNilai,
               us.status, us.totalNilai
        FROM ujian u 
        JOIN ujian_siswa us ON u.id = us.ujian_id 
        JOIN users s ON us.siswa_id = s.id 
        LEFT JOIN jawaban_siswa js ON us.ujian_id = js.ujian_id AND us.siswa_id = js.siswa_id
        WHERE u.id = ? AND us.id = ? AND u.guru_id = ?
        GROUP BY u.id, us.id
    ");
    $stmt->execute([$input['ujian_id'], $input['ujian_siswa_id'], $input['guru_id']]);
    $ujianData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ujianData) {
        throw new Exception('Data ujian tidak ditemukan atau tidak memiliki akses');
    }
    
    // Get soal data
    $stmt = $pdo->prepare("
        SELECT s.*, js.jawaban, js.pilihan_jawaban as pilihanJawaban, js.benar, js.poin as poin_jawaban
        FROM soal s 
        LEFT JOIN jawaban_siswa js ON s.id = js.soal_id AND js.ujian_id = ? AND js.siswa_id = (
            SELECT siswa_id FROM ujian_siswa WHERE id = ?
        )
        WHERE s.ujian_id = ?
        ORDER BY s.nomorSoal
    ");
    $stmt->execute([$input['ujian_id'], $input['ujian_siswa_id'], $input['ujian_id']]);
    $soalList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process pilihan for pilihan ganda
    foreach ($soalList as &$soal) {
        if ($soal['tipeSoal'] === 'pilihan_ganda' && !empty($soal['pilihan'])) {
            $soal['pilihan_array'] = json_decode($soal['pilihan'], true);
        }
    }
    
    // Prepare data for AI evaluation
    $evaluationData = [
        'ujian' => [
            'namaUjian' => $ujianData['namaUjian'],
            'durasi' => $ujianData['durasi'] ?? null,
            'tanggal' => $ujianData['tanggalUjian'] ?? $ujianData['waktuMulai'] ?? null
        ],
        'siswa' => [
            'namaLengkap' => $ujianData['namaLengkap'],
            'kelas' => $ujianData['kelas'],
            'email' => $ujianData['email'],
            'id' => $ujianData['siswa_id']
        ],
        'soal_list' => $soalList,
        'hasil_ujian' => [
            'jumlahBenar' => (int)$ujianData['jumlahBenar'],
            'jumlahSalah' => (int)$ujianData['jumlahSalah'],
            'totalNilai' => $ujianData['totalNilai'],
            'status' => $ujianData['status']
        ]
    ];
    
    // Create AI evaluator
    $aiEvaluator = new SimpleAIEvaluator();
    
    // Generate evaluation
    $evaluationResult = $aiEvaluator->evaluateStudentPerformance($evaluationData);
    
    if (!$evaluationResult['success']) {
        throw new Exception($evaluationResult['error'] ?? 'Gagal melakukan evaluasi AI');
    }
    
    // Clean output buffer and send response
    ob_clean();
    echo json_encode([
        'success' => true,
        'evaluation' => $evaluationResult['evaluation']
    ]);
    
} catch (Exception $e) {
    // Clean output buffer and send error response
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
 * Simple AI Evaluator Class
 */
class SimpleAIEvaluator {
    private $apiKey;
    private $apiUrl;
    private $model;
    
    public function __construct() {
        // Get API configuration from admin settings
        $apiConfig = $this->getApiConfigForPage('ai-evaluation-detail-jawaban');
        
        if ($apiConfig && $apiConfig['success']) {
            $this->apiKey = $apiConfig['data']['api_key'];
            $this->apiUrl = $apiConfig['data']['api_url'];
            $this->model = $apiConfig['data']['model_name'];
        } else {
            // Fallback to old config method
            $configFile = '../pingo/config.php';
            
            if (file_exists($configFile)) {
                require_once $configFile;
                
                if (defined('GROQ_API_KEY') && GROQ_API_KEY !== 'your_groq_api_key_here') {
                    $this->apiKey = GROQ_API_KEY;
                    $this->apiUrl = defined('GROQ_API_URL') ? GROQ_API_URL : 'https://api.groq.com/openai/v1/chat/completions';
                    $this->model = defined('GROQ_MODEL') ? GROQ_MODEL : 'llama3-8b-8192';
                } else {
                    throw new Exception('API Key belum dikonfigurasi. Silakan atur di Admin Settings > API Keys');
                }
            } else {
                throw new Exception('API Key belum dikonfigurasi. Silakan atur di Admin Settings > API Keys');
            }
        }
    }
    
    /**
     * Get API configuration for specific page from admin settings
     */
    private function getApiConfigForPage($pageName) {
        try {
            // Mock the API switcher call since we're in a different context
            // We'll make a direct database call instead
            
            if (!isset($_SESSION['user'])) {
                return ['success' => false, 'message' => 'No user session'];
            }
            
            $userId = $_SESSION['user']['id'];
            
            // Get database connection
            require_once '../logic/koneksi.php';
            require_once '../logic/api-keys-logic.php';
            
            // Get user's preferred API key for this page
            $sql = "SELECT ak.* FROM api_keys ak
                    JOIN user_page_api_preferences up ON ak.id = up.api_key_id
                    WHERE up.user_id = ? AND up.page_name = ? AND ak.is_active = 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $pageName]);
            $apiKey = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($apiKey) {
                // Decrypt the API key for use
                require_once '../logic/api-keys-helper.php';
                $apiKey['api_key'] = ApiKeysHelper::decryptApiKey($apiKey['api_key']);
                
                return [
                    'success' => true,
                    'data' => $apiKey
                ];
            } else {
                // Fallback to first active API key if no preference set
                $apiKeysLogic = new ApiKeysLogic();
                $allKeys = $apiKeysLogic->getAllApiKeys();
                
                foreach ($allKeys as $key) {
                    if ($key['is_active']) {
                        $firstActiveKey = $apiKeysLogic->getApiKeyById($key['id']);
                        if ($firstActiveKey) {
                            return [
                                'success' => true,
                                'data' => $firstActiveKey,
                                'is_fallback' => true
                            ];
                        }
                    }
                }
                
                return ['success' => false, 'message' => 'No active API keys available'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Evaluate student performance
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
     * Build evaluation prompt
     */
    private function buildEvaluationPrompt($data) {
        $ujian = $data['ujian'];
        $siswa = $data['siswa'];
        $soalList = $data['soal_list'];
        $hasilUjian = $data['hasil_ujian'];
        
        $totalSoal = count($soalList);
        $jawabanBenar = $hasilUjian['jumlahBenar'];
        $jawabanSalah = $hasilUjian['jumlahSalah'];
        $persentaseBenar = $totalSoal > 0 ? round(($jawabanBenar / $totalSoal) * 100, 1) : 0;
        
        $prompt = "Anda adalah guru yang berpengalaman. Analisis hasil ujian berikut dengan mengevaluasi setiap pertanyaan dan jawaban siswa:\n\n";
        $prompt .= "INFORMASI UJIAN:\n";
        $prompt .= "- Nama Ujian: {$ujian['namaUjian']}\n";
        $prompt .= "- Mata Pelajaran: {$ujian['mataPelajaran']}\n";
        $prompt .= "- Nama Siswa: {$siswa['namaLengkap']}\n\n";
        
        $prompt .= "DETAIL SOAL DAN JAWABAN:\n";
        foreach ($soalList as $index => $soal) {
            $no = $index + 1;
            $prompt .= "Soal {$no}: {$soal['pertanyaan']}\n";
            $prompt .= "Tipe: {$soal['tipeSoal']}\n";
            $prompt .= "Kunci Jawaban: {$soal['kunciJawaban']}\n";
            
            if (!empty($soal['jawaban']) || !empty($soal['pilihanJawaban'])) {
                $jawabanSiswa = !empty($soal['pilihanJawaban']) ? $soal['pilihanJawaban'] : $soal['jawaban'];
                $prompt .= "Jawaban Siswa: {$jawabanSiswa}\n";
                $status = $soal['benar'] ? 'BENAR' : 'SALAH';
                $prompt .= "Status: {$status}\n";
            } else {
                $prompt .= "Jawaban Siswa: [Tidak dijawab]\n";
                $prompt .= "Status: SALAH\n";
            }
            $prompt .= "\n";
        }
        
        $prompt .= "RINGKASAN HASIL:\n";
        $prompt .= "- Total Soal: {$totalSoal}\n";
        $prompt .= "- Jawaban Benar: {$jawabanBenar}\n";
        $prompt .= "- Jawaban Salah: {$jawabanSalah}\n";
        $prompt .= "- Persentase: {$persentaseBenar}%\n\n";
        
        $prompt .= "Berdasarkan analisis di atas, berikan evaluasi dalam format JSON berikut (hanya JSON, tanpa teks lain):\n";
        $prompt .= "{\n";
        $prompt .= '  "deskripsi": "Analisis performa siswa berdasarkan pertanyaan dan jawaban yang diberikan (3-4 kalimat)",' . "\n";
        $prompt .= '  "materi_perlu_evaluasi": {' . "\n";
        $prompt .= '    "jumlah_salah": ' . $jawabanSalah . ',' . "\n";
        $prompt .= '    "total_soal": ' . $totalSoal . ',' . "\n";
        $prompt .= '    "topik": ["Berdasarkan soal yang salah, sebutkan topik spesifik yang perlu diperbaiki"]' . "\n";
        $prompt .= '  },' . "\n";
        $prompt .= '  "materi_sudah_dikuasai": {' . "\n";
        $prompt .= '    "jumlah_benar": ' . $jawabanBenar . ',' . "\n";
        $prompt .= '    "total_soal": ' . $totalSoal . ',' . "\n";
        $prompt .= '    "topik": ["Berdasarkan soal yang benar, sebutkan topik spesifik yang sudah dikuasai"]' . "\n";
        $prompt .= '  },' . "\n";
        $prompt .= '  "saran_pembelajaran": ["Saran spesifik berdasarkan kesalahan", "Metode belajar yang cocok", "Fokus perbaikan untuk topik tertentu"]' . "\n";
        $prompt .= "}";
        
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
                    'content' => 'Anda adalah guru yang berpengalaman. Berikan hanya JSON yang valid, tanpa teks lain.'
                ],
                [
                    'role' => 'user', 
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.3
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
     * Parse evaluation response
     */
    private function parseEvaluationResponse($response) {
        // Clean response
        $response = trim($response);
        
        // Find JSON content
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception('No valid JSON found in AI response');
        }
        
        $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $evaluation = json_decode($jsonStr, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse JSON: ' . json_last_error_msg() . '. Response: ' . substr($response, 0, 200));
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