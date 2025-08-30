<?php
session_start();
require_once 'postingan-logic.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user']['id'];

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_post_detail':
        getPostDetail();
        break;
        
    case 'update_post':
        updatePost();
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getPostDetail() {
    global $user_id;
    
    $postingan_id = intval($_GET['postingan_id'] ?? 0);
    
    if (!$postingan_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID postingan tidak valid']);
        return;
    }
    
    $postinganLogic = new PostinganLogic();
    $postingan = $postinganLogic->getDetailPostinganForEdit($postingan_id, $user_id);
    
    if (!$postingan) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Postingan tidak ditemukan atau Anda tidak memiliki izin']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $postingan
    ]);
}

function updatePost() {
    global $user_id;
    
    $postingan_id = intval($_POST['postingan_id'] ?? 0);
    $konten = trim($_POST['konten'] ?? '');
    $images_to_delete = $_POST['images_to_delete'] ?? [];
    
    if (!$postingan_id || empty($konten)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        return;
    }
    
    // Handle new images upload
    $new_images = [];
    if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
        $new_images = handleImageUpload($_FILES['new_images']);
        if ($new_images === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Gagal mengupload gambar']);
            return;
        }
    }
    
    // Convert images_to_delete from string to array if needed
    if (is_string($images_to_delete)) {
        $images_to_delete = json_decode($images_to_delete, true) ?: [];
    }
    
    $postinganLogic = new PostinganLogic();
    $result = $postinganLogic->editPostingan($postingan_id, $user_id, $konten, $images_to_delete, $new_images);
    
    echo json_encode($result);
}

function handleImageUpload($files) {
    $uploadDir = '../../uploads/postingan/';
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $uploadedImages = [];
    
    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $originalName = $files['name'][$i];
            $tmpName = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileType = $files['type'][$i];
            
            // Validate file type
            if (!in_array($fileType, $allowedTypes)) {
                continue;
            }
            
            // Validate file size
            if ($fileSize > $maxFileSize) {
                continue;
            }
            
            // Generate unique filename
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            
            // Move uploaded file
            if (move_uploaded_file($tmpName, $filePath)) {
                $uploadedImages[] = [
                    'nama_file' => $originalName,
                    'path_gambar' => 'postingan/' . $fileName,
                    'ukuran_file' => $fileSize,
                    'tipe_file' => $fileType,
                    'urutan' => $i + 1
                ];
            }
        }
    }
    
    return $uploadedImages;
}
?>
