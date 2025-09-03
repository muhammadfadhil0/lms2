<?php
require_once 'src/logic/koneksi.php';

echo "<h2>Setup Test Data untuk Hasil Ujian</h2>";

$ujian_id = 7;

// Get ujian_siswa_id dan soal list
$result = $koneksi->query("SELECT id FROM ujian_siswa WHERE ujian_id = $ujian_id");
$ujian_siswa = $result->fetch_assoc();
$ujian_siswa_id = $ujian_siswa['id'];

$result = $koneksi->query("SELECT id, nomorSoal, kunciJawaban, poin FROM soal WHERE ujian_id = $ujian_id ORDER BY nomorSoal");
$soal_list = $result->fetch_all(MYSQLI_ASSOC);

echo "Ujian Siswa ID: $ujian_siswa_id<br>";
echo "Total Soal: " . count($soal_list) . "<br><br>";

// Hapus jawaban existing (kalau ada)
$koneksi->query("DELETE FROM jawaban_siswa WHERE ujian_siswa_id = $ujian_siswa_id");

// Simulasi jawaban siswa
$jawaban_simulasi = ['A', 'B', 'A', 'C', 'B']; // Beberapa benar, beberapa salah
$total_benar = 0;
$total_salah = 0;
$total_poin = 0;

foreach ($soal_list as $index => $soal) {
    $jawaban = $jawaban_simulasi[$index] ?? 'A';
    $benar = ($jawaban == $soal['kunciJawaban']) ? 1 : 0;
    $poin = $benar ? $soal['poin'] : 0;
    
    if ($benar) {
        $total_benar++;
    } else {
        $total_salah++;
    }
    $total_poin += $poin;
    
    // Insert jawaban
    $sql = "INSERT INTO jawaban_siswa (ujian_siswa_id, soal_id, jawaban, pilihanJawaban, benar, poin) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param('iissid', $ujian_siswa_id, $soal['id'], $jawaban, $jawaban, $benar, $poin);
    
    if ($stmt->execute()) {
        $status = $benar ? "✓ BENAR" : "✗ SALAH";
        echo "Soal {$soal['nomorSoal']}: Jawab $jawaban, Kunci {$soal['kunciJawaban']} - $status (Poin: $poin)<br>";
    }
}

// Update ujian_siswa dengan statistik
$sql = "UPDATE ujian_siswa SET jumlahBenar = ?, jumlahSalah = ?, totalNilai = ? WHERE id = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param('iidi', $total_benar, $total_salah, $total_poin, $ujian_siswa_id);
$stmt->execute();

echo "<br><strong>Ringkasan:</strong><br>";
echo "Total Benar: $total_benar<br>";
echo "Total Salah: $total_salah<br>";
echo "Total Poin: $total_poin<br>";

echo "<br><a href='src/front/hasil-ujian.php?ujian_id=$ujian_id'>📊 Lihat Hasil Ujian</a>";
?>
