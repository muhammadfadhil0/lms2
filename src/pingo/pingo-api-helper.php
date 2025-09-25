<?php
/**
 * API Helper untuk Pingo Chat
 * Helper untuk mengintegrasikan API switcher dengan Pingo Chat
 */

class PingoApiHelper {
    private $pdo;
    
    public function __construct() {
        $this->initDatabase();
    }
    
    private function initDatabase() {
        try {
            $host = 'localhost';
            $dbname = 'lms';
            $username = 'root';
            $password = '';
            
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get API key for specific page and user
     */
    public function getApiKeyForPage($userId, $page) {
        try {
            error_log("⭐ 🔍 VISION DEBUG - Getting API key for user $userId, page: $page");
            
            // Get user's preferred API key for this page
            $sql = "SELECT ak.* FROM api_keys ak
                    JOIN user_page_api_preferences up ON ak.id = up.api_key_id
                    WHERE up.user_id = ? AND up.page_name = ? AND ak.is_active = 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $page]);
            $apiKey = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($apiKey) {
                error_log("⭐ 🎯 VISION DEBUG - Found user preference for $page: " . $apiKey['service_label']);
                // Decrypt the API key for use
                require_once __DIR__ . '/../logic/api-keys-helper.php';
                $apiKey['api_key'] = ApiKeysHelper::decryptApiKey($apiKey['api_key']);
                return $apiKey;
            }
            
            error_log("⭐ ⚠️ VISION DEBUG - No user preference found for $page");
            
            // Special handling for vision page - look for vision-capable API
            if ($page === 'vision') {
                $visionSql = "SELECT * FROM api_keys WHERE service_name = 'groq_vision' AND is_active = 1 ORDER BY id LIMIT 1";
                $visionStmt = $this->pdo->prepare($visionSql);
                $visionStmt->execute();
                $visionKey = $visionStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($visionKey) {
                    error_log("⭐ ✅ VISION DEBUG - Found Vision API: " . $visionKey['service_label']);
                    require_once __DIR__ . '/../logic/api-keys-helper.php';
                    $visionKey['api_key'] = ApiKeysHelper::decryptApiKey($visionKey['api_key']);
                    return $visionKey;
                } else {
                    error_log("⭐ ❌ VISION DEBUG - No groq_vision API found in database!");
                }
            }
            
            // Fallback to first active API key if no preference set
            $fallbackSql = "SELECT * FROM api_keys WHERE is_active = 1 ORDER BY id LIMIT 1";
            $fallbackStmt = $this->pdo->prepare($fallbackSql);
            $fallbackStmt->execute();
            $fallbackKey = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($fallbackKey) {
                error_log("⭐ 🔄 VISION DEBUG - Using fallback API: " . $fallbackKey['service_label']);
                require_once __DIR__ . '/../logic/api-keys-helper.php';
                $fallbackKey['api_key'] = ApiKeysHelper::decryptApiKey($fallbackKey['api_key']);
                return $fallbackKey;
            }
            
            error_log("⭐ ❌ VISION DEBUG - No API keys found at all!");
            return null;
            
        } catch (Exception $e) {
            error_log("Error getting API key for page: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Send message to AI using selected API key with complete chat flow
     * Now supports attachments and separate display message
     */
    public function sendMessage($userId, $userMessage, $page = 'pingo', $attachment = null, $userDisplayMessage = null) {
        try {
            // Check if message has image attachment - switch to vision API
            $hasImage = $this->hasImageAttachment($attachment);
            $targetPage = $hasImage ? 'vision' : $page;
            
            // 🔍 DEBUG: Log image detection and API selection process
            error_log("⭐ 🔍 VISION DEBUG - Message processing:");
            error_log("⭐    - Has image attachment: " . ($hasImage ? 'YES' : 'NO'));
            error_log("⭐    - Target page: " . $targetPage);
            error_log("⭐    - Original page: " . $page);
            
            $apiConfig = $this->getApiKeyForPage($userId, $targetPage);
            
            if (!$apiConfig) {
                // Fallback to default page if vision API not configured
                if ($hasImage && $targetPage === 'vision') {
                    error_log("⭐ ⚠️ Vision API not configured, falling back to default API");
                    $apiConfig = $this->getApiKeyForPage($userId, $page);
                }
                
                if (!$apiConfig) {
                    throw new Exception('No API key configured for this page');
                }
            }
            
            // 🔍 DEBUG: Log selected API configuration
            if ($apiConfig) {
                error_log("⭐ 🔍 VISION DEBUG - Selected API:");
                error_log("⭐    - Service: " . $apiConfig['service_name']);
                error_log("⭐    - Model: " . $apiConfig['model_name']);
                error_log("⭐    - Label: " . $apiConfig['service_label']);
                error_log("⭐    - Supports Vision: " . ($this->supportsVision($apiConfig) ? 'YES' : 'NO'));
            }
            
            // Log vision API usage
            if ($hasImage && $targetPage === 'vision') {
                error_log("⭐ 🖼️ Image detected - Using Vision API: " . $apiConfig['service_label']);
                error_log("⭐ 📊 Image attachment data: " . json_encode([
                    'type' => gettype($attachment),
                    'has_images' => isset($attachment['images']),
                    'image_count' => isset($attachment['images']) ? count($attachment['images']) : 0
                ]));
            }
            
            // Ensure chat table exists
            $this->createChatTable();
            
            // Get or create session ID
            $sessionId = $this->getSessionId($userId);
            
            // Get chat history for context
            $chatHistory = $this->getChatHistory($userId, $sessionId, 10);
            
            // Get AI response with context and image support
            if ($hasImage && $this->supportsVision($apiConfig)) {
                $aiResponse = $this->getAIResponseWithImage($apiConfig, $userMessage, $chatHistory, $attachment);
            } else {
                $aiResponse = $this->getAIResponse($apiConfig, $userMessage, $chatHistory);
            }
            
            if (!$aiResponse) {
                throw new Exception('Tidak ada response dari AI');
            }
            
            // Create timestamps to ensure proper ordering
            $currentTime = time();
            $userTimestamp = date('Y-m-d H:i:s', $currentTime);
            $aiTimestamp = date('Y-m-d H:i:s', $currentTime + 1); // AI timestamp 1 second later
            
            // Save user message with attachment and AI response to database with explicit timestamps
            // Use userDisplayMessage for database if provided (for UI), userMessage for AI processing
            $messageToSave = $userDisplayMessage ?: $userMessage;
            $this->saveMessage($userId, $messageToSave, 'user', $sessionId, $attachment, $userTimestamp);
            $this->saveMessage($userId, $aiResponse, 'assistant', $sessionId, null, $aiTimestamp);
            
            return [
                'success' => true,
                'message' => $aiResponse,
                'user_message' => $userMessage,
                'timestamp' => $aiTimestamp,
                'used_vision_api' => $hasImage && $targetPage === 'vision',
                'model_info' => [
                    'service' => $apiConfig['service_name'],
                    'model' => $apiConfig['model_name'],
                    'label' => $apiConfig['service_label'],
                    'is_vision' => $hasImage && $this->supportsVision($apiConfig)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Chat error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get AI response with chat history context or prepared messages
     */
    private function getAIResponse($apiConfig, $userMessage = null, $chatHistory = [], $preparedMessages = null) {
        // Use prepared messages if provided, otherwise prepare from chat history
        if ($preparedMessages !== null) {
            $messages = $preparedMessages;
            // For prepared messages, use default medium token allocation
            $dynamicTokens = 500;
        } else {
            // Analyze user message for dynamic token allocation
            $dynamicTokens = $this->analyzeQuestionType($userMessage);
            $messages = $this->prepareChatMessages($chatHistory, $userMessage, $dynamicTokens);
            
            // Debug logging for token allocation
            error_log("🔄 Token Dinamis - Pertanyaan: " . substr($userMessage, 0, 100) . "...");
            error_log("🎯 Token dialokasikan: " . $dynamicTokens);
        }
        
        // Use the appropriate API based on service name
        switch ($apiConfig['service_name']) {
            case 'groq':
            case 'pingo_chat':
                return $this->sendGroqMessage($apiConfig, $messages, $dynamicTokens);
                
            case 'openai':
                return $this->sendOpenAIMessage($apiConfig, $messages, $dynamicTokens);
                
            default:
                throw new Exception('Unsupported API service: ' . $apiConfig['service_name']);
        }
    }
    
    /**
     * Send message to Groq API with dynamic token allocation
     */
    private function sendGroqMessage($apiConfig, $messages, $maxTokens = 600) {
        $apiKey = $apiConfig['api_key'];
        $apiUrl = $apiConfig['api_url'] ?: 'https://api.groq.com/openai/v1/chat/completions';
        $model = $apiConfig['model_name'] ?: 'llama3-8b-8192';
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => $maxTokens
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL Error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('API Error: HTTP ' . $httpCode . ' - ' . $response);
        }
        
        $responseData = json_decode($response, true);
        
        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }
        
        return $responseData['choices'][0]['message']['content'];
    }
    
    /**
     * Send message to OpenAI API with dynamic token allocation
     */
    private function sendOpenAIMessage($apiConfig, $messages, $maxTokens = 600) {
        $apiKey = $apiConfig['api_key'];
        $apiUrl = $apiConfig['api_url'] ?: 'https://api.openai.com/v1/chat/completions';
        $model = $apiConfig['model_name'] ?: 'gpt-3.5-turbo';
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => $maxTokens
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL Error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('API Error: HTTP ' . $httpCode . ' - ' . $response);
        }
        
        $responseData = json_decode($response, true);
        
        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }
        
        return $responseData['choices'][0]['message']['content'];
    }
    
    /**
     * Create chat table if it doesn't exist
     */
    private function createChatTable() {
        $sql = "CREATE TABLE IF NOT EXISTS pingo_chat_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            role ENUM('user', 'assistant') NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_session (user_id, session_id),
            INDEX idx_timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Get or create session ID for user
     */
    private function getSessionId($userId) {
        // Create a consistent session ID per day per user
        return date('Y-m-d') . '_' . $userId;
    }
    
    /**
     * Save message to database
     */
    private function saveMessage($userId, $message, $role, $sessionId, $attachmentData = null, $timestamp = null) {
        try {
            if ($timestamp) {
                $sql = "INSERT INTO pingo_chat_history (user_id, message, role, session_id, attachment_data, timestamp) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                
                // Convert attachment to JSON if provided, but exclude large base64 data
                $attachmentJson = null;
                if ($attachmentData) {
                    $attachmentForDb = $this->prepareAttachmentForDatabase($attachmentData);
                    $attachmentJson = json_encode($attachmentForDb);
                }
                
                $stmt->execute([$userId, $message, $role, $sessionId, $attachmentJson, $timestamp]);
            } else {
                $sql = "INSERT INTO pingo_chat_history (user_id, message, role, session_id, attachment_data) VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                
                // Convert attachment to JSON if provided, but exclude large base64 data
                $attachmentJson = null;
                if ($attachmentData) {
                    $attachmentForDb = $this->prepareAttachmentForDatabase($attachmentData);
                    $attachmentJson = json_encode($attachmentForDb);
                }
                
                $stmt->execute([$userId, $message, $role, $sessionId, $attachmentJson]);
            }
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception('Failed to save message: ' . $e->getMessage());
        }
    }

    /**
     * Prepare attachment data for database storage and save images to filesystem
     */
    private function prepareAttachmentForDatabase($attachmentData) {
        if (!is_array($attachmentData)) {
            return $attachmentData;
        }
        
        $dbAttachment = $attachmentData;
        
        // Process images: save to filesystem and keep metadata in database
        if (isset($dbAttachment['images']) && is_array($dbAttachment['images'])) {
            foreach ($dbAttachment['images'] as &$image) {
                if (isset($image['base64_data'])) {
                    error_log("⭐ 💾 Saving image to filesystem: " . $image['name']);
                    
                    // Generate unique filename
                    $timestamp = time();
                    $randomString = bin2hex(random_bytes(8));
                    $extension = $this->getFileExtension($image['name']);
                    $filename = "pingo_{$timestamp}_{$randomString}.{$extension}";
                    $filepath = __DIR__ . '/../../cache/pingo/img/' . $filename;
                    
                    try {
                        // Extract base64 data and save to file
                        $base64Data = $image['base64_data'];
                        if (strpos($base64Data, 'data:') === 0) {
                            // Remove data URL prefix
                            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                        }
                        
                        $imageData = base64_decode($base64Data);
                        if ($imageData === false) {
                            throw new Exception('Failed to decode base64 image data');
                        }
                        
                        // Save to filesystem
                        if (file_put_contents($filepath, $imageData) === false) {
                            throw new Exception('Failed to save image file');
                        }
                        
                        error_log("⭐ ✅ Image saved: $filename");
                        
                        // Update image data for database
                        $image['saved_filename'] = $filename;
                        $image['file_path'] = 'cache/pingo/img/' . $filename;
                        $image['saved_at'] = date('Y-m-d H:i:s');
                        $image['original_base64_size'] = strlen($image['base64_data']);
                        
                        // Remove base64 data from database storage
                        unset($image['base64_data']);
                        
                    } catch (Exception $e) {
                        error_log("⭐ ❌ Error saving image: " . $e->getMessage());
                        // Keep base64 data in database as fallback if file save fails
                        $image['save_error'] = $e->getMessage();
                        $image['fallback_storage'] = true;
                    }
                }
            }
        }
        
        return $dbAttachment;
    }
    
    /**
     * Get file extension from filename
     */
    private function getFileExtension($filename) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return $ext ?: 'jpg'; // Default to jpg if no extension
    }
    
    /**
     * Get chat history for context with attachment support
     */
    public function getChatHistory($userId, $sessionId = null, $limit = 50) {
        try {
            // Ensure limit is an integer to prevent SQL injection
            $limit = (int)$limit;
            
            if ($sessionId) {
                // For specific session, get messages in chronological order (oldest first)
                $sql = "SELECT * FROM pingo_chat_history WHERE user_id = ? AND session_id = ? ORDER BY timestamp ASC LIMIT $limit";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$userId, $sessionId]);
            } else {
                // For all user messages, get latest messages but in chronological order
                // First get the latest messages (DESC), then reverse to chronological order (ASC)
                $sql = "SELECT * FROM (
                    SELECT * FROM pingo_chat_history 
                    WHERE user_id = ? 
                    ORDER BY timestamp DESC 
                    LIMIT $limit
                ) AS latest_messages 
                ORDER BY timestamp ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$userId]);
            }
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse JSON attachment data and ensure consistent structure
            foreach ($results as &$row) {
                if (!empty($row['attachment_data'])) {
                    $row['attachment'] = json_decode($row['attachment_data'], true);
                }
                // Remove raw JSON from response to keep it clean
                unset($row['attachment_data']);
                
                // Ensure consistent field naming with localStorage
                // Add 'content' field for compatibility with frontend
                $row['content'] = $row['message'];
            }
            
            return $results;
        } catch (PDOException $e) {
            throw new Exception('Failed to get chat history: ' . $e->getMessage());
        }
    }
    
    /**
     * Prepare chat messages for API
     */
    private function prepareChatMessages($chatHistory, $newMessage, $allocatedTokens = 500) {
        // Determine response style based on allocated tokens
        $responseStyle = '';
        if ($allocatedTokens <= 300) {
            $responseStyle = "\n\n[STYLE: Jawab singkat tapi tetap ramah. Jika perlu info lebih, tanya balik ke user dengan ekspresif.]";
        } elseif ($allocatedTokens <= 600) {
            $responseStyle = "\n\n[STYLE: Berikan penjelasan yang cukup dengan contoh. Tetap ekspresif dan tanya jika user butuh detail tertentu.]";
        } else {
            $responseStyle = "\n\n[STYLE: Boleh explain detail. Tetap interaktif dan tanya jika user ingin fokus pada aspek tertentu.]";
        }
        
        $messages = [
            [
                'role' => 'system',
                'content' => 'Kamu adalah Pingo, asisten AI yang ramah dan ekspresif untuk membantu pembelajaran! 😊

KEPRIBADIAN & GAYA BICARA:
- Bicara natural dan ekspresif seperti teman yang antusias membantu
- Gunakan emoji secukupnya untuk membuat percakapan hidup
- Jangan kaku atau terlalu formal - be friendly!
- Kalau jawaban singkat, tetap ramah dan tanyakan balik jika user butuh info lebih
- Kalau user minta detail/ringkasan, berikan dengan semangat dan terstruktur

ATURAN RESPONSE NATURAL:
- Pertanyaan SINGKAT → Jawab SINGKAT tapi ekspresif + tanya balik jika perlu
  Contoh: "HTML itu bahasa markup untuk bikin web! 🌐 Mau tau lebih detail tentang tag-tagnya?"
  
- Pertanyaan BUTUH PENJELASAN → Berikan penjelasan yang cukup + tetap interaktif
  Contoh: "Cara bikin website ada beberapa langkah nih... [explain] Mau saya jelasin salah satu langkah lebih detail?"
  
- Pertanyaan MINTA DETAIL/RINGKASAN → Berikan detail lengkap tapi tetap engaging
  Contoh: "Oke, saya jelasin OOP secara detail ya! 🚀 [detailed explanation] Ada konsep tertentu yang mau diperdalam?"

FORMATTING (PENTING):
- JANGAN gunakan tabel format apapun (|---|, markdown table, dll)
- Gunakan bullet points (•), numbering (1. 2. 3.), atau paragraf
- Buat struktur yang mudah dibaca tapi tetap natural
- Kalau data kompleks, jadikan list atau penjelasan bertahap

INTERAKTIVITAS:
- Selalu siap untuk follow-up questions
- Jika jawaban mungkin belum lengkap, tanya: "Ada yang mau ditanyakan lebih lanjut?"
- Jika topik luas, tanya: "Mau fokus ke aspek mana dulu nih?"
- Be curious tentang apa yang user butuhkan!' . $responseStyle
            ]
        ];
        
        // Add previous chat history for context (last 10 messages)
        $recentHistory = array_slice($chatHistory, -10);
        
        foreach ($recentHistory as $chat) {
            $messages[] = [
                'role' => $chat['role'],
                'content' => $chat['message']
            ];
        }
        
        // Add the new user message
        $messages[] = [
            'role' => 'user',
            'content' => $newMessage
        ];
        
        return $messages;
    }
    
    /**
     * Generate questions using AI with selected API key
     */
    public function generateQuestions($userId, $params, $page = 'buat-soal') {
        try {
            $apiConfig = $this->getApiKeyForPage($userId, $page);
            
            if (!$apiConfig) {
                throw new Exception('No API key configured for this page');
            }
            
            // Build prompt for question generation
            $prompt = $this->buildQuestionPrompt($params);
            
            // Prepare messages for API
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'Anda adalah asisten AI yang ahli dalam membuat soal pendidikan. Buatlah soal-soal yang berkualitas sesuai dengan parameter yang diberikan.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ];
            
            // Get AI response
            $aiResponse = $this->getAIResponse($apiConfig, null, [], $messages);
            
            if (!$aiResponse) {
                throw new Exception('Tidak ada response dari AI');
            }
            
            // Parse the AI response into questions
            $questions = $this->parseQuestionResponse($aiResponse, $params);
            
            return [
                'success' => true,
                'questions' => $questions,
                'total' => count($questions),
                'raw_response' => $aiResponse
            ];
            
        } catch (Exception $e) {
            error_log("Question generation error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Build prompt for question generation
     */
    private function buildQuestionPrompt($params) {
        $mapel = $params['mapel'] ?? 'Umum';
        $kelas = $params['kelas'] ?? 'Umum';
        $judul = $params['judul'] ?? '';
        $deskripsi = $params['deskripsi'] ?? '';
        $questionCount = $params['question_count'] ?? 5;
        $questionType = $params['question_type'] ?? 'multiple_choice';
        $answerOptions = $params['answer_options'] ?? 4;
        $difficulty = $params['difficulty'] ?? 'sedang';
        
        $prompt = "Buatlah {$questionCount} soal {$questionType} untuk mata pelajaran {$mapel} tingkat {$kelas}.\n\n";
        
        if (!empty($judul)) {
            $prompt .= "Topik: {$judul}\n";
        }
        
        if (!empty($deskripsi)) {
            $prompt .= "Deskripsi: {$deskripsi}\n";
        }
        
        $prompt .= "Tingkat kesulitan: {$difficulty}\n";
        
        if ($questionType === 'multiple_choice') {
            $prompt .= "Jumlah pilihan jawaban: {$answerOptions}\n\n";
        }
        
        $prompt .= "Format output harus dalam JSON dengan struktur berikut:\n";
        $prompt .= "{\n";
        $prompt .= "  \"questions\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"question\": \"teks pertanyaan\",\n";
        
        if ($questionType === 'multiple_choice') {
            $prompt .= "      \"options\": [\"opsi A\", \"opsi B\", \"opsi C\", \"opsi D\"],\n";
            $prompt .= "      \"correct_answer\": \"opsi yang benar\",\n";
        } else {
            $prompt .= "      \"correct_answer\": \"jawaban yang benar\",\n";
        }
        
        $prompt .= "      \"explanation\": \"penjelasan jawaban\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}\n\n";
        $prompt .= "Pastikan semua soal relevan, jelas, dan sesuai dengan tingkat pendidikan yang diminta. Jawab hanya dengan JSON yang valid, tanpa teks tambahan.";
        
        return $prompt;
    }
    
    /**
     * Parse AI response into structured questions
     */
    private function parseQuestionResponse($response, $params) {
        // Clean the response to extract JSON
        $response = trim($response);
        
        // Remove code blocks if present
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        
        // Try to decode JSON
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from AI: ' . json_last_error_msg());
        }
        
        if (!isset($data['questions']) || !is_array($data['questions'])) {
            throw new Exception('No questions found in AI response');
        }
        
        $questions = [];
        foreach ($data['questions'] as $index => $questionData) {
            if (!isset($questionData['question']) || !isset($questionData['correct_answer'])) {
                continue; // Skip invalid questions
            }
            
            $questions[] = [
                'question' => trim($questionData['question']),
                'options' => isset($questionData['options']) ? $questionData['options'] : [],
                'correct_answer' => trim($questionData['correct_answer']),
                'explanation' => isset($questionData['explanation']) ? trim($questionData['explanation']) : '',
                'type' => $params['question_type'] ?? 'multiple_choice'
            ];
        }
        
        return $questions;
    }
    
    /**
     * Clear all chat history for a user
     */
    public function clearChatHistory($userId) {
        try {
            $sql = "DELETE FROM pingo_chat_history WHERE user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            
            $deletedRows = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => 'Chat history cleared successfully',
                'deleted_messages' => $deletedRows
            ];
        } catch (PDOException $e) {
            throw new Exception('Failed to clear chat history: ' . $e->getMessage());
        }
    }
    
    /**
     * Analyze question type to determine appropriate token allocation
     * Simplified: Short for simple questions, Long for detailed requests
     */
    private function analyzeQuestionType($userMessage) {
        // Normalize message for analysis
        $message = strtolower(trim($userMessage));
        
        // Check for explicit SHORT requests first
        if (preg_match('/\b(singkat|pendek|cepat|sekilas|simple|short)\b/', $message)) {
            return 400; // Force short but adequate
        }
        
        // Check for explicit LONG/DETAIL requests
        if (preg_match('/\b(detail|lengkap|mendalam|panjang|rinci|komprehensif|ringkas|rangkum|jelaskan|uraikan|analisis|tutorial|panduan|sebutkan|daftar)\b/', $message)) {
            return 1500; // Long response for detailed requests - diperbesar
        }
        
        // Check for very simple question patterns
        $simplePatterns = array('apa itu', 'siapa', 'kapan', 'dimana', 'berapa');
        foreach ($simplePatterns as $pattern) {
            if (strpos($message, $pattern) !== false) {
                return 600; // Short but complete - diperbesar
            }
        }
        
        // Check message length as fallback
        $messageLength = strlen($message);
        if ($messageLength < 30) {
            return 500; // Very short question - diperbesar
        } elseif ($messageLength > 100) {
            return 1000; // Longer question - diperbesar
        }
        
        // Default medium response - diperbesar
        return 800;
    }
    
    /**
     * Check if attachment contains image
     */
    public function hasImageAttachment($attachment) {
        if (!$attachment) {
            return false;
        }
        
        // New format from frontend: check for images array
        if (is_array($attachment) && isset($attachment['images']) && !empty($attachment['images'])) {
            return true;
        }
        
        // Legacy: If attachment is array, check file_type
        if (is_array($attachment) && isset($attachment['file_type'])) {
            return strpos($attachment['file_type'], 'image/') === 0;
        }
        
        // If attachment is string (file path), check extension
        if (is_string($attachment)) {
            $extension = strtolower(pathinfo($attachment, PATHINFO_EXTENSION));
            return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
        }
        
        return false;
    }
    
    /**
     * Check if API config supports vision/image analysis
     */
    public function supportsVision($apiConfig) {
        // Currently only groq_vision service supports image analysis
        return in_array($apiConfig['service_name'], ['groq_vision']);
    }
    
    /**
     * Get AI response with image support
     */
    private function getAIResponseWithImage($apiConfig, $userMessage, $chatHistory, $attachment) {
        // Prepare messages with image
        $messages = $this->prepareChatMessagesWithImage($chatHistory, $userMessage, $attachment);
        
        // Use vision-specific API call
        switch ($apiConfig['service_name']) {
            case 'groq_vision':
                return $this->sendGroqVisionMessage($apiConfig, $messages);
                
            default:
                throw new Exception('API service does not support vision: ' . $apiConfig['service_name']);
        }
    }
    
    /**
     * Prepare chat messages with image support
     */
    private function prepareChatMessagesWithImage($chatHistory, $userMessage, $attachment) {
        $messages = [];
        
        // System message
        $messages[] = [
            'role' => 'system',
            'content' => 'Anda adalah asisten AI yang membantu menganalisis gambar dan menjawab pertanyaan. Berikan jawaban yang informatif dan akurat berdasarkan gambar yang diberikan.'
        ];
        
        // Add recent chat history for context (without images)
        foreach ($chatHistory as $chat) {
            if ($chat['role'] === 'user') {
                $messages[] = [
                    'role' => 'user',
                    'content' => $chat['message']
                ];
            } else {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $chat['message']
                ];
            }
        }
        
        // Add current user message with image
        $content = [];
        
        // Add text content
        $content[] = [
            'type' => 'text',
            'text' => $userMessage ?: 'Apa yang ada di gambar ini?'
        ];
        
        // Add image content
        if ($attachment && $this->hasImageAttachment($attachment)) {
            $imageUrl = $this->prepareImageUrl($attachment);
            if ($imageUrl) {
                $content[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $imageUrl
                    ]
                ];
            }
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => $content
        ];
        
        return $messages;
    }
    
    /**
     * Prepare image URL for API consumption
     */
    private function prepareImageUrl($attachment) {
        // New format from frontend: images array with base64_data
        if (is_array($attachment) && isset($attachment['images']) && !empty($attachment['images'])) {
            $firstImage = $attachment['images'][0];
            if (isset($firstImage['base64_data'])) {
                // Frontend already provides data URL format
                return $firstImage['base64_data'];
            }
        }
        
        // Legacy: If attachment has URL already
        if (is_array($attachment) && isset($attachment['url'])) {
            return $attachment['url'];
        }
        
        // Legacy: If attachment has file path, convert to data URL
        if (is_array($attachment) && isset($attachment['file_path'])) {
            $filePath = $attachment['file_path'];
        } elseif (is_string($attachment)) {
            $filePath = $attachment;
        } else {
            return null;
        }
        
        // Check if file exists
        if (!file_exists($filePath)) {
            error_log("Image file not found: " . $filePath);
            return null;
        }
        
        // Read file and convert to base64
        $imageData = file_get_contents($filePath);
        if ($imageData === false) {
            return null;
        }
        
        $mimeType = mime_content_type($filePath);
        $base64 = base64_encode($imageData);
        
        return "data:$mimeType;base64,$base64";
    }
    
    /**
     * Send message to Groq with vision support
     */
    private function sendGroqVisionMessage($apiConfig, $messages) {
        // 🔍 DEBUG: Log Vision API request details
        error_log("⭐ 🔍 VISION DEBUG - Groq Vision API Request:");
        error_log("⭐    - Model: " . ($apiConfig['model_name'] ?: 'meta-llama/llama-4-maverick-17b-128e-instruct'));
        error_log("⭐    - URL: " . $apiConfig['api_url']);
        error_log("⭐    - Messages count: " . count($messages));
        
        // Log the structure of the last message (user message with image)
        if (!empty($messages)) {
            $lastMessage = end($messages);
            if (isset($lastMessage['content']) && is_array($lastMessage['content'])) {
                error_log("⭐    - Last message content parts: " . count($lastMessage['content']));
                foreach ($lastMessage['content'] as $i => $part) {
                    error_log("⭐      Part " . ($i + 1) . ": " . $part['type']);
                    if ($part['type'] === 'image_url' && isset($part['image_url']['url'])) {
                        $urlLength = strlen($part['image_url']['url']);
                        $isBase64 = strpos($part['image_url']['url'], 'data:') === 0;
                        error_log("⭐        Image URL length: " . $urlLength . " chars, Base64: " . ($isBase64 ? 'YES' : 'NO'));
                    }
                }
            }
        }
        
        $headers = [
            'Authorization: Bearer ' . $apiConfig['api_key'],
            'Content-Type: application/json'
        ];
        
        $data = [
            'model' => $apiConfig['model_name'] ?: 'meta-llama/llama-4-maverick-17b-128e-instruct',
            'messages' => $messages,
            'temperature' => 0.7,
            'max_completion_tokens' => 1024,
            'top_p' => 1,
            'stream' => false,
            'stop' => null
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiConfig['api_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        
        // 🔍 DEBUG: Log Vision API response details
        error_log("⭐ 🔍 VISION DEBUG - Groq Vision API Response:");
        error_log("⭐    - HTTP Code: " . $httpCode);
        error_log("⭐    - cURL Error: " . ($curlError ?: 'None'));
        error_log("⭐    - Response length: " . strlen($response) . " chars");

        if ($curlError) {
            error_log("⭐ ❌ VISION DEBUG - cURL Error: " . $curlError);
            throw new Exception('cURL Error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            error_log("⭐ ❌ VISION DEBUG - HTTP Error " . $httpCode . ": " . substr($response, 0, 500));
            error_log("Groq Vision API Error (HTTP $httpCode): " . $response);
            throw new Exception("Groq Vision API returned error: HTTP $httpCode");
        }

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("⭐ ❌ VISION DEBUG - JSON decode error: " . json_last_error_msg());
            throw new Exception('Invalid JSON response from Groq Vision API');
        }

        if (!isset($decodedResponse['choices'][0]['message']['content'])) {
            error_log("⭐ ❌ VISION DEBUG - Unexpected response structure: " . json_encode($decodedResponse));
            throw new Exception('Unexpected response format from Groq Vision API');
        }
        
        $aiContent = $decodedResponse['choices'][0]['message']['content'];
        error_log("⭐ ✅ VISION DEBUG - AI Response received: " . strlen($aiContent) . " chars");
        error_log("⭐ 📝 VISION DEBUG - AI Response preview: " . substr($aiContent, 0, 200) . "...");

        return $aiContent;
    }
}
?>