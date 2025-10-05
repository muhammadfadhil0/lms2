<?php
// Prevent any output before JSON headers
ob_start();

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Clear any buffered output to ensure clean JSON
ob_clean();

require_once 'pingo-api-helper.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $apiHelper = new PingoApiHelper();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['message']) || empty(trim($input['message']))) {
                throw new Exception('Pesan tidak boleh kosong');
            }
            
            $aiMessage = trim($input['message']); // Full message for AI (with document content)
            $userDisplayMessage = isset($input['user_display_message']) ? trim($input['user_display_message']) : $aiMessage;
            $userId = $_SESSION['user']['id'];
            
            // Extract attachment data if provided
            $attachment = isset($input['attachment']) ? $input['attachment'] : null;
            
            // 🔍 DEBUG: Log attachment data for debugging Vision API issues
            if ($attachment) {
                error_log("⭐ 🔍 VISION DEBUG - Attachment received:");
                error_log("⭐ 📊 Attachment type: " . (is_array($attachment) ? 'array' : gettype($attachment)));
                error_log("⭐ 📋 Attachment keys: " . (is_array($attachment) ? implode(', ', array_keys($attachment)) : 'N/A'));
                
                if (is_array($attachment)) {
                    if (isset($attachment['images'])) {
                        error_log("⭐ 🖼️ Images count: " . (is_array($attachment['images']) ? count($attachment['images']) : '0'));
                        if (is_array($attachment['images']) && count($attachment['images']) > 0) {
                            $firstImage = $attachment['images'][0];
                            error_log("⭐ 📸 First image info:");
                            error_log("⭐    - Name: " . ($firstImage['name'] ?? 'N/A'));
                            error_log("⭐    - MIME: " . ($firstImage['mime_type'] ?? 'N/A'));
                            error_log("⭐    - Size: " . ($firstImage['file_size'] ?? 'N/A'));
                            error_log("⭐    - Has base64: " . (isset($firstImage['base64_data']) ? 'YES (' . strlen($firstImage['base64_data']) . ' chars)' : 'NO'));
                        }
                    }
                    if (isset($attachment['documents'])) {
                        error_log("⭐ 📄 Documents count: " . (is_array($attachment['documents']) ? count($attachment['documents']) : '0'));
                    }
                }
                error_log("⭐ 🔍 VISION DEBUG - End attachment info");
            }
            
            // Use API helper to send message with attachment and selected API key
            // Pass both AI message and display message
            $response = $apiHelper->sendMessage($userId, $aiMessage, 'pingo', $attachment, $userDisplayMessage);
            
            echo json_encode($response);
            break;
            
        case 'GET':
            // Get chat history
            $userId = $_SESSION['user']['id'];
            $action = $_GET['action'] ?? 'messages';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            
            if ($action === 'history') {
                $history = $apiHelper->getChatHistory($userId, null, $limit);
                echo json_encode([
                    'success' => true,
                    'history' => $history
                ]);
            } else {
                $history = $apiHelper->getChatHistory($userId, null, $limit);
                echo json_encode([
                    'success' => true,
                    'messages' => $history
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
