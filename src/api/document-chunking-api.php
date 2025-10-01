<?php
/**
 * Document Chunking API Endpoint
 * File: src/api/document-chunking-api.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../classes/DocumentChunker.php';

function sendResponse($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

function sendError($message, $code = 400) {
    http_response_code($code);
    sendResponse(false, $message);
}

// Check if user is logged in (temporary bypass for testing)
$testMode = isset($_COOKIE['user_id']) && $_COOKIE['user_id'] === '17';

if (!isset($_SESSION['user']) && !$testMode) {
    sendError('User not authenticated', 401);
}

$userId = $_SESSION['user']['id'] ?? $_COOKIE['user_id'] ?? 17;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $chunker = new DocumentChunker();
    
    switch ($action) {
        case 'upload_document':
            handleDocumentUpload($chunker, $userId);
            break;
            
        case 'get_processing_status':
            handleGetProcessingStatus($chunker);
            break;
            
        case 'search_chunks':
            handleSearchChunks($chunker);
            break;
            
        case 'get_document_info':
            handleGetDocumentInfo($chunker);
            break;
            
        case 'get_user_documents':
            handleGetUserDocuments($chunker, $userId);
            break;
            
        default:
            sendError('Invalid action specified');
    }
    
} catch (Exception $e) {
    error_log('Document Chunking API Error: ' . $e->getMessage());
    sendError('An error occurred: ' . $e->getMessage(), 500);
}

/**
 * Handle document upload and processing
 */
function handleDocumentUpload($chunker, $userId) {
    if (!isset($_FILES['document'])) {
        sendError('No document file uploaded');
    }
    
    $file = $_FILES['document'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        sendError('File upload error: ' . $file['error']);
    }
    
    $maxSize = 10 * 1024 * 1024; // 10MB limit
    if ($file['size'] > $maxSize) {
        sendError('File too large. Maximum size is 10MB');
    }
    
    $allowedTypes = ['txt', 'pdf', 'doc', 'docx'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedTypes)) {
        sendError('Unsupported file type. Allowed: ' . implode(', ', $allowedTypes));
    }
    
    // Create upload directory if not exists
    $uploadDir = __DIR__ . '/../../uploads/documents/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $uniqueFilename = uniqid() . '_' . $file['name'];
    $filePath = $uploadDir . $uniqueFilename;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        sendError('Failed to save uploaded file');
    }
    
    try {
        // Process document in background or immediately
        $result = $chunker->processDocument($filePath, $file['name'], $userId);
        
        // Keep the file for user reference (don't delete)
        // File will be kept in uploads/documents/ for future access
        
        // Add file path to result for reference
        $result['file_path'] = $filePath;
        $result['file_url'] = '/lms/uploads/documents/' . $uniqueFilename;
        
        sendResponse(true, 'Document processed successfully', $result);
        
    } catch (Exception $e) {
        // Clean up on error
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        throw $e;
    }
}

/**
 * Get processing status of a document
 */
function handleGetProcessingStatus($chunker) {
    $documentId = $_GET['document_id'] ?? null;
    
    if (!$documentId) {
        sendError('Document ID required');
    }
    
    $info = $chunker->getDocumentInfo($documentId);
    
    if (!$info) {
        sendError('Document not found', 404);
    }
    
    sendResponse(true, 'Document status retrieved', [
        'document_id' => $documentId,
        'status' => $info['processing_status'],
        'total_chunks' => $info['total_chunks'],
        'total_words' => $info['total_words'],
        'error' => $info['processing_error']
    ]);
}

/**
 * Search for relevant chunks
 */
function handleSearchChunks($chunker) {
    $documentId = $_POST['document_id'] ?? null;
    $query = $_POST['query'] ?? null;
    $limit = $_POST['limit'] ?? 3;
    
    if (!$documentId || !$query) {
        sendError('Document ID and query required');
    }
    
    $chunks = $chunker->findRelevantChunks($documentId, $query, intval($limit));
    
    sendResponse(true, 'Relevant chunks found', [
        'document_id' => $documentId,
        'query' => $query,
        'chunks' => $chunks,
        'total_found' => count($chunks)
    ]);
}

/**
 * Get document information
 */
function handleGetDocumentInfo($chunker) {
    $documentId = $_GET['document_id'] ?? null;
    
    if (!$documentId) {
        sendError('Document ID required');
    }
    
    $info = $chunker->getDocumentInfo($documentId);
    
    if (!$info) {
        sendError('Document not found', 404);
    }
    
    sendResponse(true, 'Document info retrieved', $info);
}

/**
 * Get user's documents
 */
function handleGetUserDocuments($chunker, $userId) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT id, original_filename, file_type, file_size, upload_date, 
               total_chunks, processing_status, total_words
        FROM documents 
        WHERE user_id = ? 
        ORDER BY upload_date DESC
        LIMIT 50
    ");
    
    $stmt->execute([$userId]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(true, 'User documents retrieved', [
        'documents' => $documents,
        'total_count' => count($documents)
    ]);
}

/**
 * Simple database connection function
 */
function getDB() {
    $host = 'localhost';
    $dbname = 'lms'; // Adjust database name
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
}
?>