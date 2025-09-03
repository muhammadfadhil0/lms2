<?php
// Script untuk reset status ujian ke "sedang_mengerjakan" untuk testing
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'siswa') {
    die('Login sebagai siswa diperlukan');
}

require_once 'src/logic/ujian-logic.php';
require_once 'src/logic/koneksi.php';

$ujianLogic = new UjianLogic();
$conn = getConnection();
$siswa_id = $_SESSION['user']['id'];

// Reset ujian ke status sedang_mengerjakan
if (isset($_GET['action']) && $_GET['action'] === 'reset' && isset($_GET['ujian_siswa_id'])) {
    $ujian_siswa_id = (int)$_GET['ujian_siswa_id'];
    
    echo "<h3>Reset Status Ujian</h3>";
    echo "<p>Ujian Siswa ID: $ujian_siswa_id</p>";
    
    // Update status kembali ke sedang_mengerjakan
    $sql = "UPDATE ujian_siswa SET status = 'sedang_mengerjakan', waktuSelesai = NULL, totalNilai = NULL WHERE id = ? AND siswa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $ujian_siswa_id, $siswa_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<p style='color: green;'>✓ Status berhasil direset ke 'sedang_mengerjakan'</p>";
            
            // Verify update
            $verify_sql = "SELECT status, waktuSelesai, totalNilai FROM ujian_siswa WHERE id = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param("i", $ujian_siswa_id);
            $verify_stmt->execute();
            $result = $verify_stmt->get_result()->fetch_assoc();
            
            echo "<p><strong>Status setelah reset:</strong></p>";
            echo "<ul>";
            echo "<li>Status: " . htmlspecialchars($result['status']) . "</li>";
            echo "<li>Waktu Selesai: " . htmlspecialchars($result['waktuSelesai'] ?? 'NULL') . "</li>";
            echo "<li>Total Nilai: " . htmlspecialchars($result['totalNilai'] ?? 'NULL') . "</li>";
            echo "</ul>";
            
            echo "<p><strong>Sekarang Anda bisa test lagi:</strong></p>";
            echo "<p><a href='src/front/kerjakan-ujian.php?us_id=$ujian_siswa_id&debug=1' target='_blank'>Test Ujian (Debug Mode)</a></p>";
            echo "<p><a href='src/front/ujian-user.php?debug=1' target='_blank'>Halaman Ujian (Debug Mode)</a></p>";
            
        } else {
            echo "<p style='color: red;'>✗ Tidak ada record yang direset (ujian_siswa_id tidak ditemukan atau bukan milik Anda)</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Error SQL: " . $stmt->error . "</p>";
    }
    
    echo "<hr>";
}

// Tampilkan data ujian_siswa untuk siswa ini
$sql = "SELECT us.*, u.namaUjian FROM ujian_siswa us JOIN ujian u ON us.ujian_id = u.id WHERE us.siswa_id = ? ORDER BY us.waktuMulai DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $siswa_id);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<h2>Reset Status Ujian untuk Testing</h2>";
echo "<p>Siswa ID: $siswa_id</p>";

if (empty($results)) {
    echo "<p>Tidak ada data ujian untuk direset.</p>";
} else {
    echo "<h3>Daftar Ujian Siswa:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Ujian</th><th>Status</th><th>Waktu Mulai</th><th>Waktu Selesai</th><th>Nilai</th><th>Aksi</th>";
    echo "</tr>";
    
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['namaUjian']) . "</td>";
        echo "<td><strong style='color: " . ($row['status'] === 'selesai' ? 'blue' : ($row['status'] === 'sedang_mengerjakan' ? 'orange' : 'gray')) . ";'>" . htmlspecialchars($row['status']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['waktuMulai']) . "</td>";
        echo "<td>" . htmlspecialchars($row['waktuSelesai'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['totalNilai'] ?? 'NULL') . "</td>";
        echo "<td>";
        if ($row['status'] === 'selesai') {
            echo "<a href='?action=reset&ujian_siswa_id=" . $row['id'] . "' style='color: orange;'>Reset ke Mengerjakan</a>";
        } else {
            echo "N/A";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Langkah Testing Setelah Reset:</h3>";
echo "<ol>";
echo "<li>Klik 'Reset ke Mengerjakan' pada ujian yang statusnya 'selesai'</li>";
echo "<li>Akses ujian melalui link yang diberikan</li>";
echo "<li>Test proses finish exam lagi</li>";
echo "<li>Cek console untuk memastikan response JSON yang benar</li>";
echo "<li>Verifikasi status berubah ke 'selesai' di halaman ujian</li>";
echo "</ol>";

echo "<h3>Expected Console Output (setelah fix):</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px;'>";
echo "Starting finishExam... 3
Sending finish exam request...
Response status: 200
Response headers: application/json; charset=utf-8
Raw response text: {\"success\":true,\"message\":\"Ujian selesai\",\"finished\":true,\"ujian_siswa_id\":3}
Parsed JSON response: {success: true, message: \"Ujian selesai\", finished: true, ujian_siswa_id: 3}
Ujian berhasil diselesaikan, redirecting...";
echo "</pre>";

echo "<p><strong>Note:</strong> Tool ini hanya untuk testing/debugging. Jangan gunakan di production!</p>";
?>
