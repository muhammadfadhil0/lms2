<?php
/**
 * API Keys Management Logic
 * Mengelola operasi CRUD untuk API Keys
 */

require_once 'api-keys-helper.php';

class ApiKeysLogic {
    private $pdo;
    
    public function __construct() {
        $this->initializeConnection();
    }
    
    private function initializeConnection() {
        require_once __DIR__ . '/koneksi.php';
        global $pdo;
        
        if (!$pdo) {
            // Create PDO connection if not exists
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "lms";
            
            try {
                $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        
        $this->pdo = $pdo;
    }
    
    /**
     * Ambil semua API keys
     */
    public function getAllApiKeys() {
        try {
            $sql = "SELECT 
                        id, service_name, service_label, api_key, api_url, model_name, 
                        is_active, description, config_data, created_at, updated_at,
                        last_tested, test_status, test_message
                    FROM api_keys 
                    ORDER BY created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON config_data dan mask API keys untuk security
            foreach ($results as &$row) {
                if ($row['config_data']) {
                    $row['config_data'] = json_decode($row['config_data'], true);
                }
                // Decrypt and mask API key untuk tampilan
                $decryptedKey = ApiKeysHelper::decryptApiKey($row['api_key']);
                $row['api_key_masked'] = $this->maskApiKey($decryptedKey);
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Database error in getAllApiKeys: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ambil API key berdasarkan service name
     */
    public function getApiKeyByService($serviceName) {
        try {
            $sql = "SELECT * FROM api_keys WHERE service_name = ? AND is_active = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$serviceName]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                if ($result['config_data']) {
                    $result['config_data'] = json_decode($result['config_data'], true);
                }
                // Decrypt API key for use
                $result['api_key'] = ApiKeysHelper::decryptApiKey($result['api_key']);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Database error in getApiKeyByService: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Ambil API key berdasarkan ID
     */
    public function getApiKeyById($id) {
        try {
            $sql = "SELECT * FROM api_keys WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                if ($result['config_data']) {
                    $result['config_data'] = json_decode($result['config_data'], true);
                }
                // Decrypt API key for editing
                $result['api_key'] = ApiKeysHelper::decryptApiKey($result['api_key']);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Database error in getApiKeyById: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Tambah API key baru
     */
    public function createApiKey($data) {
        try {
            // Validate required fields
            $required_fields = ['service_name', 'service_label', 'api_key'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => "Field $field wajib diisi"
                    ];
                }
            }
            
            // Validate API key format
            if (!ApiKeysHelper::validateApiKeyFormat($data['service_name'], $data['api_key'])) {
                return [
                    'success' => false,
                    'message' => "Format API key tidak valid untuk service {$data['service_name']}"
                ];
            }
            
            // Check if exact same service_label already exists instead of service_name
            // This allows multiple API keys for the same service with different labels
            $sql_check = "SELECT id FROM api_keys WHERE service_label = ?";
            $stmt_check = $this->pdo->prepare($sql_check);
            $stmt_check->execute([$data['service_label']]);
            $existing = $stmt_check->fetch();
            
            if ($existing) {
                return [
                    'success' => false,
                    'message' => "Service label '{$data['service_label']}' sudah ada. Gunakan label yang berbeda."
                ];
            }
            
            // Prepare config data
            $configData = null;
            if (isset($data['config_data']) && is_array($data['config_data'])) {
                $configData = json_encode($data['config_data']);
            }
            
            $sql = "INSERT INTO api_keys 
                    (service_name, service_label, api_key, api_url, model_name, is_active, description, config_data, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Encrypt API key before storing
            $encryptedApiKey = ApiKeysHelper::encryptApiKey($data['api_key']);
            
            $result = $stmt->execute([
                $data['service_name'],
                $data['service_label'],
                $encryptedApiKey,
                $data['api_url'] ?? null,
                $data['model_name'] ?? null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $data['description'] ?? null,
                $configData,
                $data['created_by'] ?? null
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'API key berhasil ditambahkan',
                    'id' => $this->pdo->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal menambahkan API key'
                ];
            }
        } catch (PDOException $e) {
            error_log("Database error in createApiKey: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error database: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update API key
     */
    public function updateApiKey($id, $data) {
        try {
            // Check if API key exists
            $existing = $this->getApiKeyById($id);
            if (!$existing) {
                return [
                    'success' => false,
                    'message' => 'API key tidak ditemukan'
                ];
            }
            
            // Prepare config data
            $configData = null;
            if (isset($data['config_data']) && is_array($data['config_data'])) {
                $configData = json_encode($data['config_data']);
            }
            
            $sql = "UPDATE api_keys SET 
                        service_name = ?,
                        service_label = ?, 
                        api_key = ?, 
                        api_url = ?, 
                        model_name = ?, 
                        is_active = ?, 
                        description = ?, 
                        config_data = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Encrypt API key if provided
            $apiKeyToStore = $existing['api_key'];
            if (isset($data['api_key']) && !empty($data['api_key'])) {
                $apiKeyToStore = ApiKeysHelper::encryptApiKey($data['api_key']);
            }
            
            // Handle config_data properly - ensure it's JSON string for database
            $configDataToStore = $configData;
            if ($configDataToStore === null && isset($existing['config_data'])) {
                // If existing config_data is array, convert to JSON
                if (is_array($existing['config_data'])) {
                    $configDataToStore = json_encode($existing['config_data']);
                } else {
                    $configDataToStore = $existing['config_data'];
                }
            }
            
            $result = $stmt->execute([
                $data['service_name'] ?? $existing['service_name'],
                $data['service_label'] ?? $existing['service_label'],
                $apiKeyToStore,
                $data['api_url'] ?? $existing['api_url'],
                $data['model_name'] ?? $existing['model_name'],
                isset($data['is_active']) ? (int)$data['is_active'] : $existing['is_active'],
                $data['description'] ?? $existing['description'],
                $configDataToStore,
                $id
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'API key berhasil diupdate'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal mengupdate API key'
                ];
            }
        } catch (PDOException $e) {
            error_log("Database error in updateApiKey: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error database: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete API key
     */
    public function deleteApiKey($id) {
        try {
            $sql = "DELETE FROM api_keys WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'API key berhasil dihapus'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'API key tidak ditemukan atau gagal dihapus'
                ];
            }
        } catch (PDOException $e) {
            error_log("Database error in deleteApiKey: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error database: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test API key connection
     */
    public function testApiKey($id) {
        try {
            $apiKey = $this->getApiKeyById($id);
            if (!$apiKey) {
                return [
                    'success' => false,
                    'message' => 'API key tidak ditemukan'
                ];
            }
            
            $testResult = $this->performApiTest($apiKey);
            
            // Update test status
            $sql = "UPDATE api_keys SET 
                        last_tested = CURRENT_TIMESTAMP,
                        test_status = ?,
                        test_message = ?
                    WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $testResult['success'] ? 'success' : 'failed',
                $testResult['message'],
                $id
            ]);
            
            return $testResult;
        } catch (Exception $e) {
            error_log("Error in testApiKey: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error testing API: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Perform actual API test based on service type
     */
    private function performApiTest($apiKey) {
        try {
            if ($apiKey['service_name'] === 'groq' || $apiKey['service_name'] === 'pingo_chat') {
                return $this->testGroqApi($apiKey);
            }
            
            // Default test for unknown services
            return [
                'success' => true,
                'message' => 'API key tersimpan (test tidak tersedia untuk service ini)'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test Groq API connection
     */
    private function testGroqApi($apiKey) {
        try {
            $url = $apiKey['api_url'] ?: 'https://api.groq.com/openai/v1/chat/completions';
            $model = $apiKey['model_name'] ?: 'llama3-8b-8192';
            
            // Decrypt API key for testing
            $decryptedKey = ApiKeysHelper::decryptApiKey($apiKey['api_key']);
            
            $data = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Hello, this is a test. Please respond with "Test successful".'
                    ]
                ],
                'max_tokens' => 50,
                'temperature' => 0.1
            ];
            
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $decryptedKey
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return [
                    'success' => false,
                    'message' => 'cURL error: ' . $error
                ];
            }
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['choices'][0]['message']['content'])) {
                    return [
                        'success' => true,
                        'message' => 'API test berhasil. Response: ' . trim($responseData['choices'][0]['message']['content'])
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Response tidak valid dari API'
                    ];
                }
            } else {
                $responseData = json_decode($response, true);
                $errorMsg = isset($responseData['error']['message']) ? $responseData['error']['message'] : 'HTTP Error ' . $httpCode;
                
                return [
                    'success' => false,
                    'message' => 'API test gagal: ' . $errorMsg
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Test error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Mask API key untuk security
     */
    private function maskApiKey($apiKey) {
        if (strlen($apiKey) <= 16) {
            return str_repeat('*', strlen($apiKey));
        }
        
        return substr($apiKey, 0, 8) . str_repeat('*', 20) . substr($apiKey, -8);
    }
    
    /**
     * Update Pingo config file with new API key
     */
    public function updatePingoConfig($groqApiKey) {
        return ApiKeysHelper::updatePingoConfig('groq', $groqApiKey);
    }
}
?>