<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../front/buat-ujian-guru.php');
    exit();
}

require_once 'ujian-logic.php';
require_once 'kelas-logic.php';
require_once 'notification-logic.php';
$ujianLogic = new UjianLogic();
$kelasLogic = new KelasLogic();
$notificationLogic = new NotificationLogic();

$guru_id = $_SESSION['user']['id'];

// Ambil dan sanitasi input
$namaUjian = trim($_POST['exam_name'] ?? '');
$deskripsi = trim($_POST['exam_description'] ?? '');
$kelas_id = (int)($_POST['exam_class'] ?? 0);

// New system: separate start and end dates/times
$tanggalMulai = trim($_POST['exam_start_date'] ?? '');
$waktuMulai = trim($_POST['exam_start_time'] ?? '');
$tanggalAkhir = trim($_POST['exam_end_date'] ?? '');
$waktuAkhir = trim($_POST['exam_end_time'] ?? '');
$durasi = (int)($_POST['exam_duration'] ?? 0); // dalam menit, dihitung otomatis

// Backward compatibility
$tanggal = $tanggalMulai; // untuk backward compatibility

// Handle multiple topics
$topik = '';
if (isset($_POST['exam_topics']) && is_array($_POST['exam_topics'])) {
    $topics = array_filter(array_map('trim', $_POST['exam_topics']));
    $topik = implode(', ', $topics);
} elseif (isset($_POST['exam_topic'])) {
    $topik = trim($_POST['exam_topic']);
}

// Set mata pelajaran default (karena kolom mataPelajaran sudah dihapus dari tabel kelas)
$mataPelajaran = 'Umum';
$kelasDetail = null;
if ($kelas_id) {
    $kelasDetail = $kelasLogic->getDetailKelas($kelas_id);
    if ($kelasDetail && (int)$kelasDetail['guru_id'] === (int)$guru_id) {
        // Mata pelajaran sekarang tidak diambil dari kelas, menggunakan default 'Umum'
        $mataPelajaran = 'Umum';
    }
}

// Validasi dasar
$errors = [];
$required = [
    'Nama Ujian' => $namaUjian,
    'Kelas' => $kelas_id,
    'Tanggal Mulai' => $tanggalMulai,
    'Waktu Mulai' => $waktuMulai,
    'Tanggal Akhir' => $tanggalAkhir,
    'Waktu Akhir' => $waktuAkhir,
];
foreach ($required as $label => $value) {
    if (!$value) $errors[] = "$label wajib diisi";
}

// Validasi kelas milik guru (menggunakan data yang sudah diambil)
if ($kelas_id && (!$kelasDetail || (int)$kelasDetail['guru_id'] !== (int)$guru_id)) {
    $errors[] = 'Kelas tidak valid';
}

// Validasi format tanggal dan waktu
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalMulai)) {
    $errors[] = 'Format tanggal mulai tidak valid (YYYY-MM-DD)';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalAkhir)) {
    $errors[] = 'Format tanggal akhir tidak valid (YYYY-MM-DD)';
}
if (!preg_match('/^\d{2}:\d{2}$/', $waktuMulai)) {
    $errors[] = 'Format waktu mulai tidak valid (HH:MM)';
}
if (!preg_match('/^\d{2}:\d{2}$/', $waktuAkhir)) {
    $errors[] = 'Format waktu akhir tidak valid (HH:MM)';
}

// Validasi bahwa waktu akhir setelah waktu mulai
if ($tanggalMulai && $waktuMulai && $tanggalAkhir && $waktuAkhir) {
    $start = new DateTime($tanggalMulai . ' ' . $waktuMulai);
    $end = new DateTime($tanggalAkhir . ' ' . $waktuAkhir);
    
    if ($end <= $start) {
        $errors[] = 'Waktu akhir ujian harus setelah waktu mulai ujian';
    } else {
        // Hitung durasi otomatis
        $interval = $start->diff($end);
        $durasi = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    }
}

if ($errors) {
    $_SESSION['flash_errors'] = $errors;
    $_SESSION['old_exam_form'] = $_POST;
    header('Location: ../front/buat-ujian-guru.php');
    exit();
}

// Use the new format: waktuAkhir instead of calculating from duration
$waktuSelesai = $waktuAkhir . ':00'; // Add seconds for time format
$waktuMulaiFull = $waktuMulai . ':00'; // Add seconds for time format

// TIDAK lagi gabungkan topik ke deskripsi - simpan terpisah
// Biarkan deskripsi tetap sebagai deskripsi murni

// Cek apakah ini update atau create baru
$ujian_id_edit = isset($_POST['ujian_id']) ? (int)$_POST['ujian_id'] : 0;
$autoScore = isset($_POST['auto_score']) ? 1 : 0;
if ($ujian_id_edit > 0) {
    // Attempt update
    $shuffle = isset($_POST['shuffle_questions']) ? 1 : 0;
    $showScore = isset($_POST['show_score']) ? 1 : 0;
    $result = $ujianLogic->updateUjian($ujian_id_edit, $guru_id, $namaUjian, $deskripsi, $kelas_id, $mataPelajaran, $tanggal, $waktuMulaiFull, $waktuSelesai, $durasi, $shuffle, $showScore, $autoScore, $topik, $tanggalAkhir);
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

$result = $ujianLogic->buatUjian($namaUjian, $deskripsi, $kelas_id, $guru_id, $mataPelajaran, $tanggal, $waktuMulaiFull, $waktuSelesai, $durasi, $topik, $tanggalAkhir);

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
    
    // Send notification to all students in the class
    if ($kelasDetail) {
        $siswaList = $kelasLogic->getSiswaKelas($kelas_id);
        
        if ($siswaList && count($siswaList) > 0) {
            foreach ($siswaList as $siswa) {
                // Create notification for each student
                $notificationLogic->createUjianBaruNotification(
                    $siswa['id'],
                    $namaUjian,
                    $kelasDetail['namaKelas'],
                    $ujian_id_new,
                    $kelas_id
                );
            }
        }
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
