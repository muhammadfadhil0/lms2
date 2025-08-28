<?php
session_start();
require_once 'src/logic/dashboard-logic.php';

// Simulasi user login sebagai guru
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 2, // ID guru dari sample data
        'role' => 'guru',
        'namaLengkap' => 'Test Guru'
    ];
}

$dashboardLogic = new DashboardLogic();
$guru_id = $_SESSION['user']['id'];

echo "<h2>Testing Dashboard Logic</h2>";
echo "<p>Guru ID: " . $guru_id . "</p>";

$dashboardData = $dashboardLogic->getDashboardGuru($guru_id);

echo "<h3>Dashboard Data:</h3>";
echo "<pre>";
print_r($dashboardData);
echo "</pre>";

if (isset($dashboardData['kelasTerbaru'])) {
    echo "<h3>Kelas Terbaru (" . count($dashboardData['kelasTerbaru']) . " kelas):</h3>";
    foreach ($dashboardData['kelasTerbaru'] as $kelas) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<strong>" . htmlspecialchars($kelas['namaKelas']) . "</strong><br>";
        echo "Mata Pelajaran: " . htmlspecialchars($kelas['mataPelajaran']) . "<br>";
        echo "Kode Kelas: " . htmlspecialchars($kelas['kodeKelas']) . "<br>";
        echo "Jumlah Siswa: " . ($kelas['jumlahSiswa'] ?? 0) . "<br>";
        echo "Jumlah Ujian: " . ($kelas['jumlahUjian'] ?? 0) . "<br>";
        echo "</div>";
    }
} else {
    echo "<p>Tidak ada data kelas terbaru</p>";
}
?>
