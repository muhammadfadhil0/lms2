<?php
session_start();
require_once 'postingan-logic.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$postinganLogic = new PostinganLogic();
$user_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_comment':
            $postingan_id = intval($_POST['postingan_id'] ?? 0);
            $komentar = trim($_POST['komentar'] ?? '');
            
            if ($postingan_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID postingan tidak valid']);
                exit();
            }
            
            if (empty($komentar)) {
                echo json_encode(['success' => false, 'message' => 'Komentar tidak boleh kosong']);
                exit();
            }
            
            $result = $postinganLogic->tambahKomentar($postingan_id, $user_id, $komentar);
            
            if ($result['success']) {
                // Get the newly created comment with user info
                $newComment = $postinganLogic->getKomentarById($result['komentar_id']);
                $result['comment_data'] = $newComment;
            }
            
            echo json_encode($result);
            break;
            
        case 'get_comments':
            $postingan_id = intval($_POST['postingan_id'] ?? 0);
            
            if ($postingan_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID postingan tidak valid']);
                exit();
            }
            
            $comments = $postinganLogic->getKomentarPostingan($postingan_id);
            echo json_encode(['success' => true, 'comments' => $comments]);
            break;
            
        case 'delete_comment':
            $komentar_id = intval($_POST['komentar_id'] ?? 0);
            
            if ($komentar_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID komentar tidak valid']);
                exit();
            }
            
            $result = $postinganLogic->hapusKomentar($komentar_id, $user_id);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
