<?php
session_start();

// Simulasi debug untuk ujian ID 6
require_once 'src/logic/ujian-logic.php';

$ujianLogic = new UjianLogic();
$ujian_id = 6;
$siswa_id = 25; // Ganti dengan ID siswa Anda

echo "<h2>Debug Waktu Ujian</h2>";
echo "<strong>Server Time:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<strong>Server Timestamp:</strong> " . time() . "<br><br>";

// Ambil data ujian
$sql = "SELECT * FROM ujian WHERE id = ?";
$conn = getConnection();
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ujian_id);
$stmt->execute();
$ujian = $stmt->get_result()->fetch_assoc();

if ($ujian) {
    echo "<h3>Data Ujian:</h3>";
    echo "Nama: " . $ujian['namaUjian'] . "<br>";
    echo "Tanggal: " . $ujian['tanggalUjian'] . "<br>";
    echo "Waktu Mulai: " . $ujian['waktuMulai'] . "<br>";
    echo "Waktu Selesai: " . $ujian['waktuSelesai'] . "<br>";
    echo "Durasi: " . $ujian['durasi'] . " menit<br><br>";
    
    // Hitung timestamp
    $tanggalUjian = $ujian['tanggalUjian'];
    $waktuMulai = $ujian['waktuMulai'];
    $waktuSelesai = $ujian['waktuSelesai'];
    
    $waktuMulaiUjian = strtotime($tanggalUjian . ' ' . $waktuMulai);
    $waktuSelesaiUjian = strtotime($tanggalUjian . ' ' . $waktuSelesai);
    $waktuSekarang = time();
    
    echo "<h3>Timestamp Calculation:</h3>";
    echo "String waktu mulai: '{$tanggalUjian} {$waktuMulai}'<br>";
    echo "String waktu selesai: '{$tanggalUjian} {$waktuSelesai}'<br>";
    echo "Timestamp mulai ujian: {$waktuMulaiUjian} = " . date('Y-m-d H:i:s', $waktuMulaiUjian) . "<br>";
    echo "Timestamp selesai ujian: {$waktuSelesaiUjian} = " . date('Y-m-d H:i:s', $waktuSelesaiUjian) . "<br>";
    echo "Timestamp sekarang: {$waktuSekarang} = " . date('Y-m-d H:i:s', $waktuSekarang) . "<br><br>";
    
    // Toleransi 5 menit
    $toleransiMulai = $waktuMulaiUjian - 300;
    echo "Toleransi mulai (-5 menit): {$toleransiMulai} = " . date('Y-m-d H:i:s', $toleransiMulai) . "<br><br>";
    
    echo "<h3>Validasi:</h3>";
    if ($waktuSekarang < $toleransiMulai) {
        echo "❌ Ujian belum dimulai (sekarang < toleransi mulai)<br>";
        echo "Selisih: " . ($toleransiMulai - $waktuSekarang) . " detik lagi<br>";
    } elseif ($waktuSekarang > $waktuSelesaiUjian) {
        echo "❌ Waktu ujian telah berakhir (sekarang > selesai)<br>";
        echo "Terlambat: " . ($waktuSekarang - $waktuSelesaiUjian) . " detik<br>";
    } else {
        echo "✅ Ujian dapat dimulai!<br>";
        echo "Sudah berjalan: " . ($waktuSekarang - $waktuMulaiUjian) . " detik<br>";
        echo "Sisa waktu: " . ($waktuSelesaiUjian - $waktuSekarang) . " detik<br>";
    }
    
    // Test mulai ujian
    echo "<h3>Test Method mulaiUjian:</h3>";
    $result = $ujianLogic->mulaiUjian($ujian_id, $siswa_id);
    echo "Success: " . ($result['success'] ? 'true' : 'false') . "<br>";
    echo "Message: " . $result['message'] . "<br>";
    if (isset($result['ujian_siswa_id'])) {
        echo "Ujian Siswa ID: " . $result['ujian_siswa_id'] . "<br>";
    }
    
} else {
    echo "❌ Ujian tidak ditemukan!";
}
?>
