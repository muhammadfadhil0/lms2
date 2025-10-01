<?php
session_start();
require_once '../logic/kelas-logic.php';

header('Content-Type: application/json');

// Check if user is logged in and is a guru
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kelasLogic = new KelasLogic();
    $guru_id = $_SESSION['user']['id'];
    
    // Get form data
    $namaKelas = trim($_POST['namaKelas'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $maxSiswa = intval($_POST['maxSiswa'] ?? 30);
    
    // Validate input
    if (empty($namaKelas)) {
        echo json_encode(['success' => false, 'message' => 'Nama kelas harus diisi']);
        exit();
    }
    
    // Check if guru can create more classes (subscription limit)
    $classLimitCheck = $kelasLogic->canCreateClass($guru_id);
    if (!$classLimitCheck['success']) {
        echo json_encode(['success' => false, 'message' => $classLimitCheck['message']]);
        exit();
    }
    
    if (!$classLimitCheck['can_create']) {
        $role = $classLimitCheck['role'] ?? 'free';
        $maxClasses = $classLimitCheck['max_classes'] ?? 5;
        $currentClasses = $classLimitCheck['current_classes'] ?? 0;
        
        echo json_encode([
            'success' => false, 
            'message' => "Anda telah mencapai batas maksimum {$maxClasses} kelas untuk akun {$role}. Upgrade ke Pro untuk kelas unlimited.",
            'limit_reached' => true,
            'role' => $role,
            'max_classes' => $maxClasses,
            'current_classes' => $currentClasses
        ]);
        exit();
    }
    
    // Create class
    $result = $kelasLogic->buatKelas($namaKelas, $deskripsi, $guru_id, $maxSiswa);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true, 
            'message' => 'Kelas berhasil dibuat dengan kode: ' . $result['kode_kelas'],
            'kode_kelas' => $result['kode_kelas'],
            'kelas_id' => $result['kelas_id']
        ]);
    } else {
        echo json_encode($result);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
