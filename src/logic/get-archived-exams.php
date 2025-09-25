<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak diizinkan']);
    exit();
}

require_once 'ujian-logic.php';
$ujianLogic = new UjianLogic();

try {
    $guru_id = $_SESSION['user']['id'];
    $archivedExams = $ujianLogic->getArchivedUjianByGuru($guru_id);
    
    echo json_encode([
        'success' => true,
        'data' => $archivedExams
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memuat arsip ujian: ' . $e->getMessage()
    ]);
}
?>
