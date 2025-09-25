<?php
/**
 * API Switcher Endpoint
 * Endpoint untuk mengelola pemilihan API key per halaman
 */

header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - Please login'
    ]);
    exit;
}

require_once 'koneksi.php';
require_once 'api-keys-logic.php';

try {
    $action = $_POST['action'] ?? '';
    $apiKeysLogic = new ApiKeysLogic();
    $userId = $_SESSION['user']['id'];
    
    switch ($action) {
        case 'get_available_keys':
            // Get all active API keys
            $apiKeys = $apiKeysLogic->getAllApiKeys();
            $activeKeys = array_filter($apiKeys, function($key) {
                return $key['is_active'] == 1;
            });
            
            echo json_encode([
                'success' => true,
                'data' => array_values($activeKeys)
            ]);
            break;
            
        case 'get_current_selection':
            $page = $_POST['page'] ?? '';
            if (empty($page)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Page parameter required'
                ]);
                break;
            }
            
            // Get current API key selection for this page and user
            $sql = "SELECT api_key_id FROM user_page_api_preferences 
                    WHERE user_id = ? AND page_name = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $page]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'api_key_id' => $result ? $result['api_key_id'] : null,
                    'page' => $page
                ]
            ]);
            break;
            
        case 'set_page_api_key':
            $page = $_POST['page'] ?? '';
            $apiKeyId = $_POST['api_key_id'] ?? '';
            
            if (empty($page) || empty($apiKeyId)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Page and API key ID required'
                ]);
                break;
            }
            
            // Verify that the API key exists and is active
            $apiKey = $apiKeysLogic->getApiKeyById($apiKeyId);
            if (!$apiKey || !$apiKey['is_active']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'API key not found or inactive'
                ]);
                break;
            }
            
            // Insert or update preference
            $sql = "INSERT INTO user_page_api_preferences (user_id, page_name, api_key_id, updated_at)
                    VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE 
                    api_key_id = VALUES(api_key_id),
                    updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$userId, $page, $apiKeyId]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'API service berhasil diatur untuk halaman ini'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal menyimpan preferensi API'
                ]);
            }
            break;
            
        case 'get_page_api_key':
            // Get API key for specific page (used by other scripts)
            $page = $_POST['page'] ?? '';
            if (empty($page)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Page parameter required'
                ]);
                break;
            }
            
            // Get user's preferred API key for this page
            $sql = "SELECT ak.* FROM api_keys ak
                    JOIN user_page_api_preferences up ON ak.id = up.api_key_id
                    WHERE up.user_id = ? AND up.page_name = ? AND ak.is_active = 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $page]);
            $apiKey = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($apiKey) {
                // Decrypt the API key for use
                $apiKey['api_key'] = ApiKeysHelper::decryptApiKey($apiKey['api_key']);
                
                echo json_encode([
                    'success' => true,
                    'data' => $apiKey
                ]);
            } else {
                // Fallback to first active API key if no preference set
                $allKeys = $apiKeysLogic->getAllApiKeys();
                $firstActiveKey = null;
                
                foreach ($allKeys as $key) {
                    if ($key['is_active']) {
                        $firstActiveKey = $apiKeysLogic->getApiKeyById($key['id']);
                        break;
                    }
                }
                
                if ($firstActiveKey) {
                    echo json_encode([
                        'success' => true,
                        'data' => $firstActiveKey,
                        'is_fallback' => true
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No active API keys available'
                    ]);
                }
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("API Switcher Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>