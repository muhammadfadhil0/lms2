<?php
/**
 * API Keys Management API
 * Endpoint untuk mengelola API Keys dari admin panel
 */

// Start output buffering to catch any unexpected output
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Start session and check authentication
session_start();

// Include required files
require_once 'api-keys-logic.php';
require_once 'api-keys-helper.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - Please login as admin'
    ]);
    exit;
}

try {
    $apiKeysLogic = new ApiKeysLogic();
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_all':
            $apiKeys = $apiKeysLogic->getAllApiKeys();
            echo json_encode([
                'success' => true,
                'data' => $apiKeys
            ]);
            break;
            
        case 'get_by_id':
            $id = $_GET['id'] ?? $_POST['id'] ?? '';
            if (empty($id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID tidak boleh kosong'
                ]);
                break;
            }
            
            $apiKey = $apiKeysLogic->getApiKeyById($id);
            if ($apiKey) {
                echo json_encode([
                    'success' => true,
                    'data' => $apiKey
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'API key tidak ditemukan'
                ]);
            }
            break;
            
        case 'create':
            $data = [
                'service_name' => $_POST['service_name'] ?? '',
                'service_label' => $_POST['service_label'] ?? '',
                'api_key' => $_POST['api_key'] ?? '',
                'api_url' => $_POST['api_url'] ?? '',
                'model_name' => $_POST['model_name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
                'created_by' => $_SESSION['user']['id'] ?? null
            ];
            
            // Handle config_data
            if (isset($_POST['config_data']) && is_array($_POST['config_data'])) {
                $data['config_data'] = $_POST['config_data'];
            } elseif (isset($_POST['config_json']) && !empty($_POST['config_json'])) {
                $data['config_data'] = json_decode($_POST['config_json'], true);
            }
            
            $result = $apiKeysLogic->createApiKey($data);
            echo json_encode($result);
            break;
            
        case 'update':
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID tidak boleh kosong'
                ]);
                break;
            }
            
            $data = [
                'service_name' => $_POST['service_name'] ?? '',
                'service_label' => $_POST['service_label'] ?? '',
                'api_key' => $_POST['api_key'] ?? '',
                'api_url' => $_POST['api_url'] ?? '',
                'model_name' => $_POST['model_name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0
            ];
            
            // Handle config_data
            if (isset($_POST['config_data']) && is_array($_POST['config_data'])) {
                $data['config_data'] = $_POST['config_data'];
            } elseif (isset($_POST['config_json']) && !empty($_POST['config_json'])) {
                $data['config_data'] = json_decode($_POST['config_json'], true);
            }
            
            $result = $apiKeysLogic->updateApiKey($id, $data);
            
            // If updating Groq or Pingo Chat, also update pingo config
            if ($result['success'] && isset($data['api_key']) && !empty($data['api_key'])) {
                $apiKey = $apiKeysLogic->getApiKeyById($id);
                if ($apiKey && ($apiKey['service_name'] === 'groq' || $apiKey['service_name'] === 'pingo_chat')) {
                    $configResult = ApiKeysHelper::updatePingoConfig($apiKey['service_name'], $data['api_key']);
                    if (!$configResult['success']) {
                        error_log("Failed to update Pingo config: " . $configResult['message']);
                    }
                }
            }
            
            echo json_encode($result);
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? $_GET['id'] ?? '';
            if (empty($id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID tidak boleh kosong'
                ]);
                break;
            }
            
            $result = $apiKeysLogic->deleteApiKey($id);
            echo json_encode($result);
            break;
            
        case 'test':
            $id = $_POST['id'] ?? $_GET['id'] ?? '';
            if (empty($id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID tidak boleh kosong'
                ]);
                break;
            }
            
            $result = $apiKeysLogic->testApiKey($id);
            echo json_encode($result);
            break;
            
        case 'toggle_status':
            $id = $_POST['id'] ?? '';
            $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
            
            if (empty($id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID tidak boleh kosong'
                ]);
                break;
            }
            
            $result = $apiKeysLogic->updateApiKey($id, ['is_active' => $isActive]);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Action tidak valid'
            ]);
            break;
    }
    
} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    error_log("API Keys API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} catch (Error $e) {
    // Catch fatal errors
    ob_clean();
    
    error_log("API Keys API Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage()
    ]);
}

// Clean output buffer and send response
ob_end_flush();
?>