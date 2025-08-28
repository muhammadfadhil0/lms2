<?php
session_start();
require_once 'postingan-logic.php';

header('Content-Type: application/json');

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
    
    // Create post
    $result = $postinganLogic->buatPostingan($kelas_id, $user_id, $konten, $tipePost, $deadline);
    
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
