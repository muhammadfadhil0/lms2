<?php
session_start();
require_once 'postingan-logic.php';

header('Content-Type: application/json');

/**
 * Handle multiple image uploads for postingan
 */
function handleImageUploads($files, $kelas_id, $user_id) {
    $uploadedImages = [];
    $maxFiles = 4;
    $maxFileSize = 5 * 1024 * 1024; // 5MB per file
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../../uploads/postingan/' . $kelas_id . '/';
    
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            return ['error' => 'Gagal membuat direktori upload. Periksa permisi direktori.'];
        }
        chmod($uploadDir, 0777); // Ensure proper permissions
    }
    
    $fileCount = count($files['name']);
    if ($fileCount > $maxFiles) {
        return ['error' => 'Maksimal 4 gambar yang dapat diunggah'];
    }
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            // Validate file size
            if ($files['size'][$i] > $maxFileSize) {
                return ['error' => 'Ukuran file maksimal 5MB'];
            }
            
            // Validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $files['tmp_name'][$i]);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                return ['error' => 'Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF'];
            }
            
            // Generate unique filename
            $extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $filename;
            
            if (move_uploaded_file($files['tmp_name'][$i], $filePath)) {
                $uploadedImages[] = [
                    'nama_file' => $files['name'][$i],
                    'path_gambar' => 'uploads/postingan/' . $kelas_id . '/' . $filename,
                    'ukuran_file' => $files['size'][$i],
                    'tipe_file' => $mimeType,
                    'urutan' => $i + 1
                ];
            } else {
                return ['error' => 'Gagal mengunggah file: ' . $files['name'][$i]];
            }
        } else if ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
            return ['error' => 'Error upload: ' . $files['name'][$i]];
        }
    }
    
    return $uploadedImages;
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $postinganLogic = new PostinganLogic();
    $user_id = $_SESSION['user']['id'];
    
    // Check if this is a delete action
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if ($post_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID postingan tidak valid']);
            exit();
        }
        
        // Get post details to verify ownership
        $postDetail = $postinganLogic->getPostinganById($post_id);
        if (!$postDetail) {
            echo json_encode(['success' => false, 'message' => 'Postingan tidak ditemukan']);
            exit();
        }
        
        // Check if user is the owner of the post
        if ($postDetail['user_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus postingan ini']);
            exit();
        }
        
        // Delete the post
        $result = $postinganLogic->hapusPostingan($post_id, $user_id);
        echo json_encode($result);
        exit();
    }
    
    // Original posting logic
    // Get form data
    $kelas_id = intval($_POST['kelas_id'] ?? 0);
    $konten = trim($_POST['konten'] ?? '');
    $tipePost = trim($_POST['tipePost'] ?? 'umum');
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    
    // Validate input
    if (empty($konten)) {
        echo json_encode(['success' => false, 'message' => 'Konten postingan tidak boleh kosong']);
        exit();
    }
    
    if ($kelas_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID kelas tidak valid']);
        exit();
    }
    
    // Validate user access to class
    require_once 'kelas-logic.php';
    $kelasLogic = new KelasLogic();
    
    if ($_SESSION['user']['role'] == 'guru') {
        // Check if guru owns the class
        $detailKelas = $kelasLogic->getDetailKelas($kelas_id);
        if (!$detailKelas || $detailKelas['guru_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses ke kelas ini']);
            exit();
        }
    } else if ($_SESSION['user']['role'] == 'siswa') {
        // Check if student is enrolled in class
        $userClasses = $kelasLogic->getKelasBySiswa($user_id);
        $isEnrolled = false;
        foreach ($userClasses as $userClass) {
            if ($userClass['id'] == $kelas_id) {
                $isEnrolled = true;
                break;
            }
        }
        if (!$isEnrolled) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak terdaftar dalam kelas ini']);
            exit();
        }
    }
    
    // Handle image uploads
    $uploadedImages = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $uploadedImages = handleImageUploads($_FILES['images'], $kelas_id, $user_id);
        if (isset($uploadedImages['error'])) {
            echo json_encode(['success' => false, 'message' => $uploadedImages['error']]);
            exit();
        }
    }
    
    // Create post
    $result = $postinganLogic->buatPostingan($kelas_id, $user_id, $konten, $tipePost, $deadline, $uploadedImages);
    
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
