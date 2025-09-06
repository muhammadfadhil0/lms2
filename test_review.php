<?php
// Simulasi GET parameter
$_GET['ujian_id'] = 11;

// Simulasi session
session_start();
$_SESSION['user'] = [
    'id' => 25,
    'username' => 'siswa',
    'namaLengkap' => 'siswa',
    'role' => 'siswa'
];

echo "Testing review-ujian.php logic...\n";

require_once 'src/logic/ujian-logic.php';

$ujianLogic = new UjianLogic();
$siswa_id = $_SESSION['user']['id'];
$ujian_id = isset($_GET['ujian_id']) ? (int)$_GET['ujian_id'] : 0;

echo "siswa_id: $siswa_id\n";
echo "ujian_id: $ujian_id\n";

// Test method
$reviewData = $ujianLogic->getReviewUjianSiswa($ujian_id, $siswa_id);

echo "Review data result:\n";
var_dump($reviewData);
?>
