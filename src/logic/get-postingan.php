<?php
session_start();
require_once 'postingan-logic.php';
require_once 'kelas-logic.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $postinganLogic = new PostinganLogic();
    $kelasLogic = new KelasLogic();
    $user_id = $_SESSION['user']['id'];
    
    // Get parameters
    $kelas_id = intval($_GET['kelas_id'] ?? 0);
    $limit = intval($_GET['limit'] ?? 5); // Reduced default from 10 to 5
    $offset = intval($_GET['offset'] ?? 0);
    
    if ($kelas_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID kelas tidak valid']);
        exit();
    }
    
    // Validate user access to class
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
    
    // Get posts
    $postingan = $postinganLogic->getPostinganByKelas($kelas_id, $limit, $offset, $user_id);
    
    echo json_encode([
        'success' => true, 
        'data' => $postingan,
        'user_id' => $user_id,
        'user_role' => $_SESSION['user']['role'],
        'total_posts' => count($postingan),
        'timestamp' => time()
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
