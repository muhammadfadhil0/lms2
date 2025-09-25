<?php
session_start();
require_once 'dashboard-logic.php';

header('Content-Type: application/json');

// Check if user is logged in and is a siswa
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'siswa') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $dashboardLogic = new DashboardLogic();
    $siswa_id = $_SESSION['user']['id'];
    
    // Get parameters
    $limit = intval($_GET['limit'] ?? 5);
    $offset = intval($_GET['offset'] ?? 0);
    
    try {
        // Get posts with pagination
        $posts = $dashboardLogic->getPostinganTerbaruSiswa($siswa_id, $limit, $offset);
        
        echo json_encode([
            'success' => true, 
            'posts' => $posts,
            'count' => count($posts),
            'offset' => $offset,
            'limit' => $limit,
            'timestamp' => time()
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
