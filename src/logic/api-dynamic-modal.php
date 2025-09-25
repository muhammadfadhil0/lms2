<?php
header('Content-Type: application/json');
session_start();

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'koneksi.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'create':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            createModal();
            break;
            
        case 'update':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            updateModal();
            break;
            
        case 'get':
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            getModal();
            break;
            
        default:
            if ($method === 'DELETE') {
                deleteModal();
            } else {
                throw new Exception('Invalid action');
            }
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function createModal() {
    global $koneksi;
    
    // Validate required fields
    if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['target_files'])) {
        throw new Exception('Title, description, and target files are required');
    }
    
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $target_files = $_POST['target_files'];
    $display_frequency = $_POST['display_frequency'] ?? 'always';
    $priority = (int)($_POST['priority'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $created_by = $_SESSION['user']['id'];
    
    // Validate target files JSON
    $target_files_array = json_decode($target_files, true);
    if (!$target_files_array || !is_array($target_files_array)) {
        throw new Exception('Invalid target files format');
    }
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_path = handleImageUpload($_FILES['image']);
    }
    
    // Insert into database
    $stmt = $koneksi->prepare("INSERT INTO dynamic_modals (title, description, image_path, target_files, display_frequency, priority, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssiii", $title, $description, $image_path, $target_files, $display_frequency, $priority, $is_active, $created_by);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Modal berhasil dibuat']);
    } else {
        throw new Exception('Failed to create modal: ' . $koneksi->error);
    }
}

function updateModal() {
    global $koneksi;
    
    if (empty($_POST['id'])) {
        throw new Exception('Modal ID is required');
    }
    
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $target_files = $_POST['target_files'];
    $display_frequency = $_POST['display_frequency'] ?? 'always';
    $priority = (int)($_POST['priority'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate target files JSON
    $target_files_array = json_decode($target_files, true);
    if (!$target_files_array || !is_array($target_files_array)) {
        throw new Exception('Invalid target files format');
    }
    
    // Get current modal data
    $current_stmt = $koneksi->prepare("SELECT image_path FROM dynamic_modals WHERE id = ?");
    $current_stmt->bind_param("i", $id);
    $current_stmt->execute();
    $current_result = $current_stmt->get_result();
    $current_modal = $current_result->fetch_assoc();
    
    if (!$current_modal) {
        throw new Exception('Modal not found');
    }
    
    $image_path = $current_modal['image_path'];
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Delete old image if exists
        if ($image_path && file_exists('../../uploads/modals/' . basename($image_path))) {
            unlink('../../uploads/modals/' . basename($image_path));
        }
        $image_path = handleImageUpload($_FILES['image']);
    }
    
    // Update database
    $stmt = $koneksi->prepare("UPDATE dynamic_modals SET title = ?, description = ?, image_path = ?, target_files = ?, display_frequency = ?, priority = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("sssssiii", $title, $description, $image_path, $target_files, $display_frequency, $priority, $is_active, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Modal berhasil diupdate']);
    } else {
        throw new Exception('Failed to update modal: ' . $koneksi->error);
    }
}

function getModal() {
    global $koneksi;
    
    if (empty($_GET['id'])) {
        throw new Exception('Modal ID is required');
    }
    
    $id = (int)$_GET['id'];
    $stmt = $koneksi->prepare("SELECT * FROM dynamic_modals WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $modal = $result->fetch_assoc();
    
    if ($modal) {
        echo json_encode(['success' => true, 'data' => $modal]);
    } else {
        throw new Exception('Modal not found');
    }
}

function deleteModal() {
    global $koneksi;
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['id'])) {
        throw new Exception('Modal ID is required');
    }
    
    $id = (int)$input['id'];
    
    // Get modal data to delete associated files
    $stmt = $koneksi->prepare("SELECT image_path FROM dynamic_modals WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $modal = $result->fetch_assoc();
    
    if (!$modal) {
        throw new Exception('Modal not found');
    }
    
    // Delete image file if exists
    if ($modal['image_path'] && file_exists('../../uploads/modals/' . basename($modal['image_path']))) {
        unlink('../../uploads/modals/' . basename($modal['image_path']));
    }
    
    // Delete from database
    $delete_stmt = $koneksi->prepare("DELETE FROM dynamic_modals WHERE id = ?");
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Modal berhasil dihapus']);
    } else {
        throw new Exception('Failed to delete modal: ' . $koneksi->error);
    }
}

function handleImageUpload($file) {
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed');
    }
    
    // Check file size (5MB max)
    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        throw new Exception('File size too large. Maximum 5MB allowed');
    }
    
    // Create upload directory if not exists
    $upload_dir = '../../uploads/modals/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'modal_' . uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Failed to upload file');
    }
    
    return 'uploads/modals/' . $filename;
}
?>