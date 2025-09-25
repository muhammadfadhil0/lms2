<?php
session_start();
header('Content-Type: application/json');

if (!isset($_GET['kelas_id']) || !is_numeric($_GET['kelas_id'])) {
    echo json_encode(['success' => false, 'message' => 'kelas_id tidak valid']);
    exit();
}

$kelasId = intval($_GET['kelas_id']);

require_once 'koneksi.php';
require_once 'kelas-logic.php';

try {
    $kelasLogic = new KelasLogic();
    $detail = $kelasLogic->getDetailKelas($kelasId);
    if (!$detail) {
        echo json_encode(['success' => false, 'message' => 'Kelas tidak ditemukan']);
        exit();
    }
    $students = $kelasLogic->getSiswaKelas($kelasId);

    $guru = [
        'id' => $detail['guru_id'],
        'namaLengkap' => $detail['namaGuru'],
        'email' => $detail['emailGuru'],
        'role' => 'guru'
    ];

    $students = array_map(function($s){ $s['role'] = 'siswa'; return $s; }, $students);

    echo json_encode([
        'success' => true,
        'guru' => $guru,
        'students' => $students
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
