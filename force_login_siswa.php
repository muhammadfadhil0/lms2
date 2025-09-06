<?php
session_start();

// Force login sebagai siswa dengan ID 25
$_SESSION['user'] = [
    'id' => 25,
    'username' => 'siswa',
    'namaLengkap' => 'siswa',
    'role' => 'siswa',
    'email' => 'siswa@test.com'
];

echo "Forced login as siswa successful!<br>";
echo "Session data: <pre>" . print_r($_SESSION['user'], true) . "</pre>";
echo '<a href="src/front/ujian-user.php">Go to Ujian User</a><br>';
echo '<a href="src/front/review-ujian.php?ujian_id=11">Go to Review Ujian</a>';
?>
