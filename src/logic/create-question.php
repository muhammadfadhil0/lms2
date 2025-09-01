<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    exit();
}

require_once 'ujian-logic.php';
require_once 'soal-logic.php';
$ujianLogic = new UjianLogic();
$soalLogic = new SoalLogic();

$guru_id = $_SESSION['user']['id'];
$ujian_id = (int)($_POST['ujian_id'] ?? 0);
$tipe = $_POST['tipe'] ?? '';
$pertanyaan = trim($_POST['pertanyaan'] ?? '');
$kunci = trim($_POST['kunci'] ?? '');
$poin = (int)($_POST['poin'] ?? 10);
$pilihan = $_POST['pilihan'] ?? [];// array A=>text
$kunciPilihan = $_POST['kunci_pilihan'] ?? '';

if (!$ujian_id || !$pertanyaan || !$tipe) {
    echo json_encode(['success'=>false,'message'=>'Data tidak lengkap']);
    exit();
}

// Validasi kepemilikan ujian
$ujian = $ujianLogic->getUjianByIdAndGuru($ujian_id, $guru_id);
if (!$ujian) {
    echo json_encode(['success'=>false,'message'=>'Ujian tidak ditemukan']);
    exit();
}

// Tentukan nomorSoal berikutnya
$conn = getConnection();
$nomor = 1;
$res = $conn->prepare('SELECT COALESCE(MAX(nomorSoal),0)+1 as nextNo FROM soal WHERE ujian_id=?');
$res->bind_param('i',$ujian_id);
$res->execute();
$next = $res->get_result()->fetch_assoc();
$nomor = (int)$next['nextNo'];

if ($tipe === 'multiple_choice') {
    if (!$kunciPilihan || empty($pilihan) || !isset($pilihan[$kunciPilihan])) {
        echo json_encode(['success'=>false,'message'=>'Kunci jawaban pilihan ganda tidak valid']);
        exit();
    }
    // panggil logic
    $result = $soalLogic->buatSoalPilihanGanda($ujian_id, $nomor, $pertanyaan, $pilihan, $kunciPilihan, $poin);
    echo json_encode($result);
    exit();
} else if (in_array($tipe, ['short_answer','long_answer'])) {
    $result = $soalLogic->buatSoalJawaban($ujian_id, $nomor, $pertanyaan, $tipe === 'short_answer' ? 'jawaban_singkat' : 'jawaban_panjang', $kunci, $poin);
    echo json_encode($result);
    exit();
} else {
    echo json_encode(['success'=>false,'message'=>'Tipe soal tidak dikenal']);
    exit();
}
