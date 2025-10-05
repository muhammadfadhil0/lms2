<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../logic/koneksi.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

try {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No image uploaded or upload error occurred');
    }

    $image = $_FILES['image'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($image['type'], $allowedTypes)) {
        throw new Exception('Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.');
    }
    
    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($image['size'] > $maxSize) {
        throw new Exception('Image size too large. Maximum size is 5MB.');
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../../../uploads/help-articles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
    $filename = 'help_' . uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($image['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // Generate URL for the uploaded image
    $imageUrl = '../../uploads/help-articles/' . $filename;
    
    // Optional: Store image info in database for tracking
    try {
        $stmt = $pdo->prepare("
            INSERT INTO help_article_images (filename, original_name, file_size, mime_type, uploaded_by, uploaded_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $filename,
            $image['name'],
            $image['size'],
            $image['type'],
            $_SESSION['user']['id']
        ]);
    } catch (PDOException $e) {
        // Don't fail if image tracking table doesn't exist
        error_log("Failed to track image upload: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'url' => $imageUrl,
        'filename' => $filename,
        'size' => $image['size']
    ]);

} catch (Exception $e) {
    error_log("Image upload error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>