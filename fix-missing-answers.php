<?php
// Fix missing answers for existing exam data
require_once 'src/logic/koneksi.php';

$conn = getConnection();

echo "<h2>Perbaikan Data Jawaban yang Hilang</h2>";

// Find ujian_siswa records that may have missing answers
$sql = "SELECT us.id as ujian_siswa_id, us.ujian_id, us.siswa_id, u.namaLengkap,
               (SELECT COUNT(*) FROM soal WHERE ujian_id = us.ujian_id) as total_soal,
               (SELECT COUNT(*) FROM jawaban_siswa WHERE ujian_siswa_id = us.id) as jawaban_tersimpan
        FROM ujian_siswa us
        JOIN users u ON us.siswa_id = u.id
        WHERE us.status = 'selesai'
        ORDER BY us.id DESC";

$result = $conn->query($sql);
$fixed = 0;

echo "<table border='1'>";
echo "<tr><th>Ujian Siswa ID</th><th>Nama</th><th>Total Soal</th><th>Jawaban Tersimpan</th><th>Status</th><th>Aksi</th></tr>";

while ($row = $result->fetch_assoc()) {
    $missing = $row['total_soal'] - $row['jawaban_tersimpan'];
    $status = $missing > 0 ? "Kurang $missing jawaban" : "Lengkap";
    
    echo "<tr>";
    echo "<td>{$row['ujian_siswa_id']}</td>";
    echo "<td>{$row['namaLengkap']}</td>";
    echo "<td>{$row['total_soal']}</td>";
    echo "<td>{$row['jawaban_tersimpan']}</td>";
    echo "<td>$status</td>";
    
    if ($missing > 0) {
        // Create missing jawaban_siswa records
        $sql_missing = "SELECT s.id as soal_id 
                       FROM soal s 
                       WHERE s.ujian_id = ? 
                       AND s.id NOT IN (SELECT soal_id FROM jawaban_siswa WHERE ujian_siswa_id = ?)";
        
        $stmt = $conn->prepare($sql_missing);
        $stmt->bind_param('ii', $row['ujian_id'], $row['ujian_siswa_id']);
        $stmt->execute();
        $missing_soal = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $inserted = 0;
        foreach ($missing_soal as $soal) {
            $sql_insert = "INSERT INTO jawaban_siswa (ujian_siswa_id, soal_id, jawaban, benar, poin, waktuDijawab) 
                          VALUES (?, ?, '', 0, 0, NOW())";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param('ii', $row['ujian_siswa_id'], $soal['soal_id']);
            if ($stmt_insert->execute()) {
                $inserted++;
            }
        }
        
        echo "<td>Ditambahkan $inserted jawaban kosong</td>";
        $fixed += $inserted;
    } else {
        echo "<td>-</td>";
    }
    
    echo "</tr>";
}

echo "</table>";
echo "<p><strong>Total jawaban kosong yang ditambahkan: $fixed</strong></p>";
echo "<p>Sekarang semua ujian yang selesai memiliki record jawaban lengkap untuk semua soal.</p>";
?>
