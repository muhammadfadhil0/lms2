<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak diizinkan']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak valid']);
    exit();
}

require_once 'ujian-logic.php';
$ujianLogic = new UjianLogic();

$ujian_id = isset($_POST['ujian_id']) ? (int)$_POST['ujian_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

$allowed = ['draft','aktif','selesai'];
if (!$ujian_id || !in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit();
}

$result = $ujianLogic->updateStatusUjian($ujian_id, $status);

echo json_encode($result);
