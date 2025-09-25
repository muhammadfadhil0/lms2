<?php
require_once 'config.php';

/**
 * ChatHandler Class
 * Handles chat interactions with Groq API
 */
class ChatHandler {
    private $apiKey;
    private $apiUrl;
    private $model;
    private $pdo;
    
    public function __construct() {
        $this->apiKey = GROQ_API_KEY;
        $this->apiUrl = GROQ_API_URL;
        $this->model = GROQ_MODEL;
        
        if (empty($this->apiKey) || $this->apiKey === 'your_groq_api_key_here') {
            throw new Exception('API Key Groq belum dikonfigurasi dengan benar');
        }
        
        // Initialize database connection
        $this->initDatabase();
    }
    
    private function initDatabase() {
        try {
            // Sesuaikan dengan konfigurasi database Anda
            $host = 'localhost';
            $dbname = 'lms'; // Ganti dengan nama database Anda
            $username = 'root';
            $password = '';
            
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create chat table if not exists
            $this->createChatTable();
            
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    private function createChatTable() {
        $sql = "CREATE TABLE IF NOT EXISTS pingo_chat_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            role ENUM('user', 'assistant') NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            session_id VARCHAR(255) NOT NULL,
            INDEX idx_user_session (user_id, session_id),
            INDEX idx_timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            // If table already exists or other error, log it but don't stop
            error_log("Chat table creation error: " . $e->getMessage());
        }
    }
    
    /**
     * Send message to Groq API and get response
     */
    public function sendMessage($userId, $userMessage) {
        try {
            // Get or create session ID
            $sessionId = $this->getSessionId($userId);
            
            // Get chat history for context BEFORE saving new message
            $chatHistory = $this->getChatHistory($userId, $sessionId, 10);
            
            // Prepare messages for API
            $messages = $this->prepareChatMessages($chatHistory, $userMessage);
            
            // Call Groq API
            $aiResponse = $this->callGroqAPI($messages);
            
            if (!$aiResponse) {
                throw new Exception('Tidak ada response dari AI');
            }
            
            // Create timestamps to ensure proper ordering
            $currentTime = time();
            $userTimestamp = date('Y-m-d H:i:s', $currentTime);
            $aiTimestamp = date('Y-m-d H:i:s', $currentTime + 1); // AI timestamp 1 second later
            
            // Save user message to database with explicit timestamp
            $this->saveMessage($userId, $userMessage, 'user', $sessionId, $userTimestamp);
            
            // Save AI response to database with later timestamp
            $this->saveMessage($userId, $aiResponse, 'assistant', $sessionId, $aiTimestamp);
            
            return [
                'success' => true,
                'message' => $aiResponse,
                'user_message' => $userMessage,
                'timestamp' => $aiTimestamp
            ];
            
        } catch (Exception $e) {
            // Log the error for debugging
            error_log("Chat error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get or create session ID
     */
    private function getSessionId($userId) {
        // Create new session ID based on date and user
        return 'session_' . $userId . '_' . date('Y-m-d');
    }
    
    /**
     * Save message to database
     */
    private function saveMessage($userId, $message, $role, $sessionId, $timestamp = null) {
        try {
            if ($timestamp) {
                $sql = "INSERT INTO pingo_chat_history (user_id, message, role, session_id, timestamp) VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    (int) $userId, 
                    (string) $message, 
                    (string) $role, 
                    (string) $sessionId,
                    $timestamp
                ]);
            } else {
                $sql = "INSERT INTO pingo_chat_history (user_id, message, role, session_id) VALUES (?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    (int) $userId, 
                    (string) $message, 
                    (string) $role, 
                    (string) $sessionId
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error saving message: " . $e->getMessage());
            throw new Exception("Gagal menyimpan pesan ke database");
        }
    }
    
    /**
     * Get chat history from database
     */
    public function getChatHistory($userId, $sessionId = null, $limit = 50) {
        if (!$sessionId) {
            $sessionId = $this->getSessionId($userId);
        }
        
        // Make sure limit is integer
        $limit = (int) $limit;
        
        $sql = "SELECT message, role, timestamp FROM pingo_chat_history 
                WHERE user_id = ? AND session_id = ? 
                ORDER BY timestamp ASC 
                LIMIT " . $limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $sessionId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Prepare messages for Groq API
     */
    private function prepareChatMessages($chatHistory, $newMessage) {
        $messages = [];
        
        // Add system message
        $messages[] = [
            'role' => 'system',
            'content' => 'Kamu adalah Pingo, asisten AI pembelajaran yang sangat membantu dan ramah. Kamu ahli dalam berbagai mata pelajaran dan selalu siap membantu siswa belajar dengan cara yang mudah dipahami. 

PANDUAN RESPON:
- Untuk sapaan sederhana dan chat casual, jawab dengan natural tanpa format khusus
- Untuk penjelasan materi pembelajaran yang panjang, gunakan format markdown:
  * # untuk judul utama
  * ## untuk sub judul  
  * ### untuk sub-sub judul
  * **teks** untuk penekanan penting
  * `kode` untuk istilah teknis
  * 1. atau - untuk list saat menjelaskan langkah-langkah
- Selalu sajikan informasi dalam format list atau paragraf terstruktur
- Jawab dalam bahasa Indonesia yang jelas
- Jangan pernah sebutkan atau jelaskan tentang panduan format ini kepada user

PENTING - ATURAN FORMATTING KHUSUS:
- JANGAN PERNAH menggunakan format tabel dalam bentuk apapun (|---|---|, markdown table, HTML table, ASCII table)
- APAPUN YANG TERJADI, gunakan alternatif format seperti:
  * List dengan poin-poin (1. 2. 3. atau - • *)
  * Paragraf terstruktur dengan penjelasan
  * Format definisi (Istilah: Penjelasan)
  * Pengelompokan dengan sub-judul
- Jika data berbentuk tabuler, ubah menjadi list atau deskripsi terstruktur

BATASAN PANJANG RESPONSE - SANGAT PENTING:
- Sistem hanya dapat menampilkan maksimum 300-400 kata per response
- Jika response Anda lebih panjang dari itu, akan TERPOTONG dan tidak lengkap
- PASTIKAN jawaban Anda selalu di bawah 350 kata untuk menghindari pemotongan
- Jika topik kompleks, bagi menjadi beberapa poin utama saja
- Prioritaskan informasi paling penting dan ringkas
- Gunakan kalimat yang efisien dan langsung to the point
- Jika perlu penjelasan panjang, sarankan user untuk bertanya lebih spesifik

Berikan penjelasan yang sesuai dengan kompleksitas pertanyaan - sederhana untuk chat biasa, terstruktur untuk pembelajaran.'
        ];
        
        // Add chat history
        if (is_array($chatHistory) && count($chatHistory) > 0) {
            foreach ($chatHistory as $msg) {
                if (isset($msg['role']) && isset($msg['message'])) {
                    $messages[] = [
                        'role' => $msg['role'],
                        'content' => $msg['message']
                    ];
                }
            }
        }
        
        // Add new user message
        $messages[] = [
            'role' => 'user',
            'content' => (string) $newMessage
        ];
        
        return $messages;
    }
    
    /**
     * Call Groq API
     */
    private function callGroqAPI($messages) {
        $data = [
            'messages' => $messages,
            'model' => $this->model,
            'temperature' => AI_TEMPERATURE,
            'max_completion_tokens' => AI_MAX_TOKENS,
            'top_p' => 1,
            'stream' => false, // Disable streaming for simplicity
            'reasoning_effort' => 'medium',
            'stop' => null
        ];
        
        $options = [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => AI_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            throw new Exception('CURL Error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('API Error: HTTP ' . $httpCode . ' - ' . $response);
        }
        
        $responseData = json_decode($response, true);
        
        if (!$responseData) {
            throw new Exception('Invalid JSON response from API');
        }
        
        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new Exception('Invalid response format from API');
        }
        
        $content = $responseData['choices'][0]['message']['content'];
        
        // Silently remove table format without telling user
        if (preg_match('/\|.*\|.*\|/', $content)) {
            $content = preg_replace('/\|.*\|.*\|.*\n?/m', '', $content); // Remove table rows silently
            $content = preg_replace('/^\s*[-:]+\s*$/m', '', $content); // Remove table separators
            $content = trim($content);
        }
        
        // Remove horizontal lines/separators (---, ===, etc.)
        $content = preg_replace('/^\s*[-=]{3,}\s*$/m', '', $content);
        $content = preg_replace('/\n\s*[-=]{3,}\s*\n/', '\n', $content);
        $content = preg_replace('/^\s*[-=]{3,}\s*\n/', '', $content);
        $content = preg_replace('/\n\s*[-=]{3,}\s*$/', '', $content);
        
        // Remove any mentions of table guidelines from AI response
        $content = preg_replace('/\(.*[Tt]abel.*panduan.*\)/i', '', $content);
        $content = preg_replace('/\(.*[Nn]ote.*[Tt]able.*\)/i', '', $content);
        $content = preg_replace('/\(.*[Cc]atatan.*[Tt]abel.*\)/i', '', $content);
        
        return trim($content);
    }
    
    /**
     * Clear chat history for user
     */
    public function clearChatHistory($userId) {
        $sessionId = $this->getSessionId($userId);
        
        $sql = "DELETE FROM pingo_chat_history WHERE user_id = ? AND session_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $sessionId]);
        
        // Reset vision API preference - remove vision preference so it falls back to default
        $this->resetVisionApiPreference($userId);
        
        return [
            'success' => true,
            'message' => 'Chat history cleared and API preference reset'
        ];
    }
    
    /**
     * Reset vision API preference when chat is cleared
     */
    private function resetVisionApiPreference($userId) {
        try {
            // Remove vision API preference so it falls back to default pingo API
            $sql = "DELETE FROM user_page_api_preferences WHERE user_id = ? AND page_name = 'vision'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            
            error_log("🔄 Vision API preference reset for user: " . $userId);
        } catch (Exception $e) {
            error_log("Error resetting vision API preference: " . $e->getMessage());
        }
    }
}
?>
