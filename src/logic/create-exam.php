<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    header('Location: ../../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../front/buat-ujian-guru.php');
    exit();
}

require_once 'ujian-logic.php';
require_once 'kelas-logic.php';
$ujianLogic = new UjianLogic();
$kelasLogic = new KelasLogic();

$guru_id = $_SESSION['user']['id'];

// Ambil dan sanitasi input
$namaUjian = trim($_POST['exam_name'] ?? '');
$deskripsi = trim($_POST['exam_description'] ?? '');
$kelas_id = (int)($_POST['exam_class'] ?? 0);
$mataPelajaran = trim($_POST['exam_subject'] ?? '');
$topik = trim($_POST['exam_topic'] ?? ''); // opsional
$tanggal = trim($_POST['exam_date'] ?? '');
$waktuMulai = trim($_POST['exam_start_time'] ?? '');
$durasi = (int)($_POST['exam_duration'] ?? 0); // dalam menit (opsional, default 60)

// Validasi dasar
$errors = [];
$required = [
    'Nama Ujian' => $namaUjian,
    'Kelas' => $kelas_id,
    'Mata Pelajaran' => $mataPelajaran,
    'Tanggal' => $tanggal,
    'Waktu Mulai' => $waktuMulai,
];
foreach ($required as $label => $value) {
    if (!$value) $errors[] = "$label wajib diisi";
}

// Validasi kelas milik guru
if ($kelas_id) {
    $kelasDetail = $kelasLogic->getDetailKelas($kelas_id);
    if (!$kelasDetail || (int)$kelasDetail['guru_id'] !== (int)$guru_id) {
        $errors[] = 'Kelas tidak valid';
    }
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    $errors[] = 'Format tanggal tidak valid (YYYY-MM-DD)';
}
if (!preg_match('/^\d{2}:\d{2}$/', $waktuMulai)) {
    $errors[] = 'Format waktu mulai tidak valid (HH:MM)';
}
if ($durasi <= 0) { $durasi = 60; }

if ($errors) {
    $_SESSION['flash_errors'] = $errors;
    $_SESSION['old_exam_form'] = $_POST;
    header('Location: ../front/buat-ujian-guru.php');
    exit();
}

// Hitung waktu selesai
list($h,$m) = explode(':', $waktuMulai);
$start = DateTime::createFromFormat('H:i', $waktuMulai);
$end = clone $start;
$end->modify("+{$durasi} minutes");
$waktuSelesai = $end->format('H:i:00');
$waktuMulaiFull = $start->format('H:i:00');

// Gabungkan topik ke deskripsi jika mau
if ($topik && stripos($deskripsi, $topik) === false) {
    $deskripsi = $topik . ( $deskripsi ? "\n\n" . $deskripsi : '' );
}

// Cek apakah ini update atau create baru
$ujian_id_edit = isset($_POST['ujian_id']) ? (int)$_POST['ujian_id'] : 0;
$autoScore = isset($_POST['auto_score']) ? 1 : 0;
if ($ujian_id_edit > 0) {
    // Attempt update
    $shuffle = isset($_POST['shuffle_questions']) ? 1 : 0;
    $showScore = isset($_POST['show_score']) ? 1 : 0;
    $result = $ujianLogic->updateUjian($ujian_id_edit, $guru_id, $namaUjian, $deskripsi, $kelas_id, $mataPelajaran, $tanggal, $waktuMulaiFull, $waktuSelesai, $durasi, $shuffle, $showScore, $autoScore);
    if ($result['success']) {
        $qs = ($autoScore ? '&autoscore=1' : '') . '&updated=1';
        header('Location: ../front/buat-soal-guru.php?ujian_id=' . $ujian_id_edit . $qs);
        exit();
    } else {
        $_SESSION['flash_errors'] = ['Gagal mengupdate ujian: ' . $result['message']];
        $_SESSION['old_exam_form'] = $_POST;
        header('Location: ../front/buat-ujian-guru.php?ujian_id=' . $ujian_id_edit);
        exit();
    }
}

$result = $ujianLogic->buatUjian($namaUjian, $deskripsi, $kelas_id, $guru_id, $mataPelajaran, $tanggal, $waktuMulaiFull, $waktuSelesai, $durasi);

if ($result['success']) {
    // Simpan pengaturan tambahan jika kolom tersedia
    $shuffle = isset($_POST['shuffle_questions']) ? 1 : 0;
    $showScore = isset($_POST['show_score']) ? 1 : 0;
    $ujian_id_new = (int)$result['ujian_id'];
    // Cek kolom ada dengan DESCRIBE (tidak fatal jika gagal)
    $conn = getConnection();
    $cols = [];
    if ($res = $conn->query("SHOW COLUMNS FROM ujian")) {
        while($c = $res->fetch_assoc()){ $cols[$c['Field']] = true; }
    }
    $sqlUpdate = [];
    $params = [];
    $types = '';
    if(isset($cols['shuffleQuestions'])){ $sqlUpdate[]='shuffleQuestions=?'; $types.='i'; $params[]=$shuffle; }
    if(isset($cols['showScore'])){ $sqlUpdate[]='showScore=?'; $types.='i'; $params[]=$showScore; }
    if(isset($cols['autoScore'])){ $sqlUpdate[]='autoScore=?'; $types.='i'; $params[]=$autoScore; }
    if($sqlUpdate){
        $q = "UPDATE ujian SET ".implode(',', $sqlUpdate)." WHERE id=?"; $types.='i'; $params[]=$ujian_id_new;
        $stmtUp=$conn->prepare($q); if($stmtUp){ $stmtUp->bind_param($types, ...$params); $stmtUp->execute(); }
    }
    // Redirect ke halaman buat soal dengan membawa ujian_id
    header('Location: ../front/buat-soal-guru.php?ujian_id=' . (int)$result['ujian_id'].'&created=1');
    exit();
} else {
    $_SESSION['flash_errors'] = ['Gagal membuat ujian: ' . $result['message']];
    $_SESSION['old_exam_form'] = $_POST;
    header('Location: ../front/buat-ujian-guru.php');
    exit();
}
