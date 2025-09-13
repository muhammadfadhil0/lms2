<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';
require_once 'chat-handler.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $chatHandler = new ChatHandler();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['message']) || empty(trim($input['message']))) {
                throw new Exception('Pesan tidak boleh kosong');
            }
            
            $userMessage = trim($input['message']);
            $userId = $_SESSION['user']['id'];
            
            // Get chat response
            $response = $chatHandler->sendMessage($userId, $userMessage);
            
            echo json_encode($response);
            break;
            
        case 'GET':
            // Get chat history
            $userId = $_SESSION['user']['id'];
            $history = $chatHandler->getChatHistory($userId);
            
            echo json_encode([
                'success' => true,
                'messages' => $history
            ]);
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
