<?php
session_start();
require_once '../logic/kelas-logic.php';

header('Content-Type: application/json');

// Check if user is logged in and is a siswa
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'siswa') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kelasLogic = new KelasLogic();
    $siswa_id = $_SESSION['user']['id'];
    
    // Get form data
    $kodeKelas = trim($_POST['kodeKelas'] ?? '');
    
    // Validate input
    if (empty($kodeKelas)) {
        echo json_encode(['success' => false, 'message' => 'Kode kelas harus diisi']);
        exit();
    }
    
    // Join class
    $result = $kelasLogic->joinKelas($siswa_id, $kodeKelas);
    
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
