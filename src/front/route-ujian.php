<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    header('Location: ../../login.php');
    exit();
}
$ujian_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$ujian_id){
    header('Location: ujian-guru.php');
    exit();
}
require_once '../logic/ujian-logic.php';
require_once '../logic/soal-logic.php';
$ujianLogic = new UjianLogic();
$soalLogic = new SoalLogic();
$guru_id = $_SESSION['user']['id'];
$ujian = $ujianLogic->getUjianByIdAndGuru($ujian_id, $guru_id);
if(!$ujian){
    header('Location: ujian-guru.php');
    exit();
}
// Cek jumlah soal (prefer kolom totalSoal, fallback query)
$jumlahSoal = isset($ujian['totalSoal']) ? (int)$ujian['totalSoal'] : 0;
if ($jumlahSoal === 0) {
    // Belum ada soal -> langsung ke halaman pembuatan soal
    header('Location: buat-soal-guru.php?ujian_id=' . $ujian_id);
    exit();
}
// Sudah ada soal -> ke detail
header('Location: detail-ujian-guru.php?id=' . $ujian_id);
exit();
