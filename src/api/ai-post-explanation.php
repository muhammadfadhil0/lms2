<?php
// AI Post Explanation using Groq API
// Disable display errors to prevent corrupted JSON output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Start output buffering to prevent any accidental output
ob_start();

// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../logic/koneksi.php';
require_once '../pingo/config.php';
require_once '../pingo/ai-logger.php';

// Ensure database connection is available
if (!isset($koneksi)) {
    error_log("⭐ Database connection not available, creating new one");
    // Connection should be available from koneksi.php
}

header('Content-Type: application/json');

// Debug logging
error_log("AI Explanation API called - Session check: " . (isset($_SESSION['user']) ? 'OK' : 'NO SESSION'));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

// Check authentication - but allow for testing
if (!isset($_SESSION['user'])) {
    error_log("AI Explanation: No session found");
    
    // Check if this is a test request
    $rawInput = file_get_contents('php://input');
    $testInput = json_decode($rawInput, true);
    
    if (isset($testInput['test_mode']) && $testInput['test_mode']) {
        error_log("AI Explanation: Test mode enabled, skipping auth");
    } else {
        echo json_encode(['success' => false, 'error' => 'Unauthorized - Please login first']);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

class AIPostExplainer {
    private $apiKey;
    private $apiUrl;
    private $model;
    
    public function __construct($customApiKey = null, $customApiUrl = null, $customModel = null) {
        // Use custom API configuration if provided, otherwise use default
        $this->apiKey = $customApiKey ?? GROQ_API_KEY;
        $this->apiUrl = $customApiUrl ?? GROQ_API_URL;
        $this->model = $customModel ?? GROQ_MODEL;
        
        if (empty($this->apiKey) || $this->apiKey === 'your_groq_api_key_here') {
            throw new Exception('AI API Key belum dikonfigurasi');
        }
        
        error_log("AI Explainer initialized with API key: " . substr($this->apiKey, 0, 10) . "...");
    }
    
    /**
     * Generate explanation menggunakan real AI
     */
    public function generateExplanation($postData) {
        try {
            $prompt = $this->buildPrompt($postData);
            $response = $this->callGroqAPI($prompt);
            
            if (!$response) {
                throw new Exception('Tidak ada response dari AI');
            }
            
            $explanation = $this->parseAIResponse($response);
            
            return [
                'success' => true,
                'explanation' => $explanation
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Build prompt for AI with assignment detection
     */
    private function buildPrompt($postData) {
        $prompt = "Kamu adalah AI asisten yang menganalisis postingan di kelas digital learning.\n\n";
        
        $prompt .= "GUIDELINES:\n";
        $prompt .= "- AI boleh: menganalisis konteks, menebak maksud, berekspresi bebas tentang apa yang diamati\n";
        $prompt .= "- AI tidak boleh: menggunakan template kaku, terlalu formal, atau terlalu panjang\n";
        $prompt .= "- Maksimal 50 kata untuk analisis utama\n";
        $prompt .= "- Gunakan bahasa santai dan natural seperti 'kayaknya', 'nih', 'banget'\n";
        $prompt .= "- Bebas menebak dan berasumsi berdasarkan data\n";
        $prompt .= "- PENTING: Identifikasi apakah ini postingan TUGAS atau postingan BIASA\n\n";
        
        $prompt .= "DATA POSTINGAN:\n";
        $prompt .= "Author: " . ($postData['authorName'] ?? 'Pengguna') . "\n";
        $prompt .= "Kelas: " . ($postData['namaKelas'] ?? 'kelas ini') . "\n";
        $prompt .= "Tipe Postingan: " . ($postData['tipePost'] ?? 'umum') . "\n";
        $prompt .= "Konten: " . ($postData['konten'] ?? 'Tidak ada teks') . "\n";
        
        // ⭐ ENHANCED: Add assignment-specific data
        if (isset($postData['isAssignment']) && $postData['isAssignment']) {
            $prompt .= "\n🎯 INI ADALAH POSTINGAN TUGAS:\n";
            $assignmentData = $postData['assignmentDetails'];
            
            if (!empty($assignmentData['title'])) {
                $prompt .= "Judul Tugas: " . $assignmentData['title'] . "\n";
            }
            if (!empty($assignmentData['description'])) {
                $prompt .= "Deskripsi Tugas: " . $assignmentData['description'] . "\n";
            }
            if (!empty($assignmentData['deadline'])) {
                $deadline = new DateTime($assignmentData['deadline']);
                $now = new DateTime();
                $interval = $now->diff($deadline);
                
                $prompt .= "Deadline: " . $deadline->format('d/m/Y H:i') . "\n";
                
                if ($deadline < $now) {
                    $prompt .= "Status Deadline: SUDAH LEWAT ❌\n";
                } else {
                    $timeLeft = "";
                    if ($interval->days > 0) {
                        $timeLeft .= $interval->days . " hari ";
                    }
                    if ($interval->h > 0) {
                        $timeLeft .= $interval->h . " jam ";
                    }
                    $prompt .= "Sisa Waktu: " . trim($timeLeft) . "⏰\n";
                }
            }
            if (!empty($assignmentData['maxScore'])) {
                $prompt .= "Nilai Maksimal: " . $assignmentData['maxScore'] . "\n";
            }
            if (!empty($assignmentData['attachmentPath'])) {
                $prompt .= "Ada File Tugas: YA\n";
            }
        } else if (isset($postData['likelyAssignment']) && $postData['likelyAssignment']) {
            $prompt .= "\n⚠️ KEMUNGKINAN POSTINGAN TUGAS (terdeteksi kata kunci):\n";
            $prompt .= "Kata kunci ditemukan: " . implode(', ', $postData['assignmentKeywords']) . "\n";
        }
        
        if (!empty($postData['gambar']) && count($postData['gambar']) > 0) {
            $prompt .= "Media: Ada " . count($postData['gambar']) . " gambar/media\n";
        }
        
        if (!empty($postData['files']) && count($postData['files']) > 0) {
            $prompt .= "Files: Ada " . count($postData['files']) . " file attachment\n";
        }
        
        if (!empty($postData['createdAt'])) {
            $prompt .= "Waktu: " . $postData['createdAt'] . "\n";
        }
        
        $prompt .= "\nTUGAS AI:\n";
        $prompt .= "Berikan analisis postingan ini dalam format JSON dengan struktur berikut:\n";
        $prompt .= "{\n";
        $prompt .= '  "analysis": "Analisis bebas maksimal 50 kata dengan bahasa formal",' . "\n";
        $prompt .= '  "keyPoints": ["Point 1", "Point 2", "Point 3"],' . "\n";
        $prompt .= '  "postType": "tugas" atau "biasa",' . "\n";
        $prompt .= '  "urgency": "high/medium/low" (khusus tugas)' . "\n";
        $prompt .= "}\n\n";
        
        $prompt .= "CONTOH OUTPUT TUGAS:\n";
        $prompt .= "{\n";
        $prompt .= '  "analysis": "Guru ngasih tugas baru nih! Deadline tinggal 3 hari lagi, harus dikerjain cepet-cepet.",' . "\n";
        $prompt .= '  "keyPoints": ["Ada tugas baru", "Deadline 3 hari lagi", "Perlu dikerjakan segera"],' . "\n";
        $prompt .= '  "postType": "tugas",' . "\n";
        $prompt .= '  "urgency": "high"' . "\n";
        $prompt .= "}\n\n";
        
        $prompt .= "CONTOH OUTPUT BIASA:\n";
        $prompt .= "{\n";
        $prompt .= '  "analysis": "User lagi sharing info biasa aja. Kayaknya ngobrol santai atau tanya sesuatu.",' . "\n";
        $prompt .= '  "keyPoints": ["Komunikasi santai", "Sharing informasi", "Diskusi kelas"],' . "\n";
        $prompt .= '  "postType": "biasa",' . "\n";
        $prompt .= '  "urgency": "low"' . "\n";
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
                    'content' => 'Anda adalah AI asisten yang ahli menganalisis postingan pembelajaran. Selalu berikan response dalam format JSON yang valid dengan bahasa yang santai dan natural.'
                ],
                [
                    'role' => 'user', 
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1000,
            'temperature' => 1.0
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
            throw new Exception('Connection error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'API Error';
            throw new Exception('API Error (' . $httpCode . '): ' . $errorMsg);
        }
        
        $responseData = json_decode($response, true);
        
        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new Exception('Invalid AI response format');
        }
        
        return $responseData['choices'][0]['message']['content'];
    }
    
    /**
     * Parse AI response with assignment detection
     */
    private function parseAIResponse($response) {
        // Clean response untuk memastikan format JSON yang benar
        $cleanResponse = trim($response);
        
        // Remove potential markdown code blocks
        $cleanResponse = preg_replace('/```json\s*/', '', $cleanResponse);
        $cleanResponse = preg_replace('/```\s*/', '', $cleanResponse);
        
        $data = json_decode($cleanResponse, true);
        
        if (!$data) {
            // Fallback jika parsing gagal
            return [
                'analysis' => 'AI lagi nganalisis postingan ini nih. Sepertinya ada info menarik yang dibagi!',
                'keyPoints' => ['Komunikasi di kelas', 'Sharing informasi'],
                'postType' => 'biasa',
                'urgency' => 'low'
            ];
        }
        
        return [
            'analysis' => $data['analysis'] ?? 'AI sedang menganalisis postingan ini.',
            'keyPoints' => array_slice($data['keyPoints'] ?? ['Komunikasi standar di kelas'], 0, 3), // Max 3 points
            'postType' => $data['postType'] ?? 'biasa',
            'urgency' => $data['urgency'] ?? 'low'
        ];
    }
}

try {
    // ⭐ SUPER DEBUG PHP MODE ACTIVATED! ⭐
    error_log("⭐ AI API ENDPOINT CALLED ⭐");
    
    // Get input with debugging
    $rawInput = file_get_contents('php://input');
    error_log("⭐ Raw input received: " . $rawInput);
    error_log("⭐ Raw input length: " . strlen($rawInput));
    
    if (empty($rawInput)) {
        error_log("⭐ ERROR: Empty input received!");
        echo json_encode(['success' => false, 'error' => 'Empty input received']);
        exit();
    }
    
    $input = json_decode($rawInput, true);
    $jsonError = json_last_error();
    
    error_log("⭐ JSON decode result: " . json_encode($input));
    error_log("⭐ JSON error code: " . $jsonError);
    error_log("⭐ JSON error message: " . json_last_error_msg());
    
    if (!$input) {
        error_log("⭐ FATAL: JSON decode failed completely!");
        echo json_encode([
            'success' => false, 
            'error' => 'Invalid JSON input: ' . json_last_error_msg(),
            'debug' => [
                'raw_input' => $rawInput,
                'json_error' => json_last_error_msg()
            ]
        ]);
        exit();
    }
    
    if (!isset($input['post_id'])) {
        error_log("⭐ ERROR: Missing post_id in input");
        error_log("⭐ Available keys: " . implode(', ', array_keys($input)));
        echo json_encode([
            'success' => false, 
            'error' => 'Missing post_id in request',
            'debug' => ['available_keys' => array_keys($input)]
        ]);
        exit();
    }
    
    $postId = $input['post_id'];
    error_log("⭐ SUCCESS: Processing post_id: " . $postId);
    
    // Check if test mode
    if (isset($input['test_mode']) && $input['test_mode'] && isset($input['test_data'])) {
        // Use test data
        $post = $input['test_data'];
    } else {
        // Get real post data from database
        // Use correct connection variable name from koneksi.php
        global $koneksi;  // Use $koneksi instead of $conn
        
        // Create a temporary function to get post data with assignment details
        // Use correct field names from database schema - namaLengkap instead of firstName/lastName
        $stmt = $koneksi->prepare("
            SELECT p.*, k.namaKelas,
                   u.namaLengkap as authorName,
                   t.id as tugas_id, t.judul as tugas_judul, 
                   t.deskripsi as tugas_deskripsi, t.deadline as tugas_deadline,
                   t.nilai_maksimal, t.file_path as tugas_file_path
            FROM postingan_kelas p 
            JOIN kelas k ON p.kelas_id = k.id 
            JOIN users u ON p.user_id = u.id 
            LEFT JOIN tugas t ON p.assignment_id = t.id
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Post not found');
        }
        
        $post = $result->fetch_assoc();
        
        // ⭐ ENHANCED: Add assignment detection logic
        $post['isAssignment'] = false;
        $post['assignmentDetails'] = null;
        
        // Check if this is an assignment post
        if ($post['tipePost'] === 'tugas' || !empty($post['assignment_id']) || !empty($post['tugas_id'])) {
            $post['isAssignment'] = true;
            $post['assignmentDetails'] = [
                'id' => $post['tugas_id'],
                'title' => $post['tugas_judul'],
                'description' => $post['tugas_deskripsi'],
                'deadline' => $post['tugas_deadline'],
                'maxScore' => $post['nilai_maksimal'],
                'attachmentPath' => $post['tugas_file_path']
            ];
        }
        
        // Also check if content mentions assignment keywords
        $assignmentKeywords = ['tugas', 'deadline', 'kerjakan', 'submit', 'kumpul', 'dikumpulkan', 'buku', 'halaman', 'latihan', 'soal'];
        $contentLower = strtolower($post['konten'] ?? '');
        
        $foundKeywords = [];
        foreach ($assignmentKeywords as $keyword) {
            if (strpos($contentLower, $keyword) !== false) {
                $foundKeywords[] = $keyword;
            }
        }
        
        if (!empty($foundKeywords)) {
            $post['likelyAssignment'] = true;
            $post['assignmentKeywords'] = $foundKeywords;
        }
        
        // Get images - use correct field names from database
        $stmt = $koneksi->prepare("SELECT path_gambar as file_path, media_type FROM postingan_gambar WHERE postingan_id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $gambarResult = $stmt->get_result();
        $post['gambar'] = $gambarResult->fetch_all(MYSQLI_ASSOC);
        
        // Get files - use correct field names from database
        $stmt = $koneksi->prepare("SELECT nama_file as file_name, file_path FROM postingan_files WHERE postingan_id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $filesResult = $stmt->get_result();
        $post['files'] = $filesResult->fetch_all(MYSQLI_ASSOC);
    }
    
    // Initialize AI explainer with configurable API key
    $customApiKey = null;
    $customApiUrl = null;
    $customModel = null;
    
    // Check if admin configured API key should be used
    if (isset($input['use_configured_api']) && $input['use_configured_api'] && isset($input['api_key_id'])) {
        error_log("Using admin-configured API key ID: " . $input['api_key_id']);
        
        try {
            // Get API key from admin configuration
            require_once '../logic/api-keys-logic.php';
            $apiKeysLogic = new ApiKeysLogic();
            $apiKeyData = $apiKeysLogic->getApiKeyById($input['api_key_id']);
            
            if ($apiKeyData && $apiKeyData['is_active']) {
                $customApiKey = $apiKeyData['api_key'];
                $customApiUrl = $apiKeyData['api_url'] ?? GROQ_API_URL;
                $customModel = $apiKeyData['model'] ?? GROQ_MODEL;
                error_log("Successfully loaded admin API configuration for provider: " . $apiKeyData['provider']);
            } else {
                error_log("Configured API key not found or inactive, using default");
            }
        } catch (Exception $e) {
            error_log("Error loading configured API key: " . $e->getMessage());
            // Continue with default configuration
        }
    }
    
    // Initialize AI explainer and generate explanation
    $aiExplainer = new AIPostExplainer($customApiKey, $customApiUrl, $customModel);
    $result = $aiExplainer->generateExplanation($post);
    
    // Clean any buffer content and output clean JSON
    ob_clean();
    echo json_encode($result);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>