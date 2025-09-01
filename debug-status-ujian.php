<?php
session_start();

// Debug status ujian untuk siswa
require_once 'src/logic/ujian-logic.php';

$ujianLogic = new UjianLogic();
$siswa_id = 25; // Ganti dengan ID siswa Anda

echo "<h2>Debug Status Ujian untuk Siswa ID: {$siswa_id}</h2>";
echo "<strong>Server Time:</strong> " . date('Y-m-d H:i:s') . "<br><br>";

$ujianList = $ujianLogic->getUjianBySiswa($siswa_id);

if (empty($ujianList)) {
    echo "❌ Tidak ada ujian ditemukan untuk siswa ini.";
} else {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Nama Ujian</th>";
    echo "<th>Tanggal</th>";
    echo "<th>Waktu</th>";
    echo "<th>Status Ujian</th>";
    echo "<th>Ujian Siswa ID</th>";
    echo "<th>Validasi Waktu</th>";
    echo "</tr>";
    
    foreach ($ujianList as $ujian) {
        $tanggalUjian = $ujian['tanggalUjian'];
        $waktuMulai = $ujian['waktuMulai'];
        $waktuSelesai = $ujian['waktuSelesai'];
        
        $waktuMulaiUjian = strtotime($tanggalUjian . ' ' . $waktuMulai);
        $waktuSelesaiUjian = strtotime($tanggalUjian . ' ' . $waktuSelesai);
        $waktuSekarang = time();
        
        // Validasi manual
        $validasi = '';
        if ($waktuSekarang < $waktuMulaiUjian) {
            $validasi = "Belum dimulai (sisa: " . round(($waktuMulaiUjian - $waktuSekarang) / 60) . " menit)";
        } elseif ($waktuSekarang > $waktuSelesaiUjian) {
            $validasi = "Sudah berakhir (terlambat: " . round(($waktuSekarang - $waktuSelesaiUjian) / 60) . " menit)";
        } else {
            $sisaWaktu = round(($waktuSelesaiUjian - $waktuSekarang) / 60);
            $sudahBerjalan = round(($waktuSekarang - $waktuMulaiUjian) / 60);
            $validasi = "Sedang berlangsung (sudah: {$sudahBerjalan} menit, sisa: {$sisaWaktu} menit)";
        }
        
        echo "<tr>";
        echo "<td>" . $ujian['id'] . "</td>";
        echo "<td>" . htmlspecialchars($ujian['namaUjian']) . "</td>";
        echo "<td>" . $ujian['tanggalUjian'] . "</td>";
        echo "<td>" . $ujian['waktuMulai'] . " - " . $ujian['waktuSelesai'] . "</td>";
        echo "<td style='background: " . ($ujian['status_ujian'] === 'dapat_dikerjakan' ? '#d4fcd4' : '#ffd4d4') . "'>" . $ujian['status_ujian'] . "</td>";
        echo "<td>" . ($ujian['ujian_siswa_id'] ?? 'NULL') . "</td>";
        echo "<td>" . $validasi . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}
?>
