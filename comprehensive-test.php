<?php
// Comprehensive testing tool untuk bug fix ujian selesai
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'siswa') {
    die('Login sebagai siswa diperlukan');
}

require_once 'src/logic/ujian-logic.php';
require_once 'src/logic/koneksi.php';

$ujianLogic = new UjianLogic();
$conn = getConnection();
$siswa_id = $_SESSION['user']['id'];

echo "<h1>🧪 Comprehensive Test Tool - Bug Fix Ujian Selesai</h1>";
echo "<p>Siswa ID: $siswa_id</p>";

// Action handlers
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $ujian_siswa_id = isset($_GET['ujian_siswa_id']) ? (int)$_GET['ujian_siswa_id'] : 0;
    
    echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border: 1px solid #bee5eb; border-radius: 5px;'>";
    
    switch ($action) {
        case 'reset_to_mengerjakan':
            echo "<h3>🔄 Reset ke 'sedang_mengerjakan'</h3>";
            $sql = "UPDATE ujian_siswa SET status = 'sedang_mengerjakan', waktuSelesai = NULL, totalNilai = NULL WHERE id = ? AND siswa_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_siswa_id, $siswa_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo "<p style='color: green;'>✅ Status berhasil direset!</p>";
            } else {
                echo "<p style='color: red;'>❌ Gagal reset status</p>";
            }
            break;
            
        case 'manual_finish':
            echo "<h3>🏁 Manual Finish Ujian</h3>";
            $result = $ujianLogic->selesaiUjian($ujian_siswa_id);
            if ($result['success']) {
                echo "<p style='color: green;'>✅ Ujian berhasil diselesaikan!</p>";
                echo "<p>Message: " . htmlspecialchars($result['message']) . "</p>";
            } else {
                echo "<p style='color: red;'>❌ Gagal menyelesaikan ujian</p>";
                echo "<p>Error: " . htmlspecialchars($result['message']) . "</p>";
            }
            break;
            
        case 'verify_fix':
            echo "<h3>🔍 Verify Bug Fix</h3>";
            
            // Test 1: Status di database
            $sql = "SELECT status, waktuSelesai FROM ujian_siswa WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $ujian_siswa_id);
            $stmt->execute();
            $db_status = $stmt->get_result()->fetch_assoc();
            
            echo "<p><strong>Test 1 - Database Status:</strong></p>";
            echo "<ul>";
            echo "<li>Status: " . htmlspecialchars($db_status['status'] ?? 'NULL') . "</li>";
            echo "<li>Waktu Selesai: " . htmlspecialchars($db_status['waktuSelesai'] ?? 'NULL') . "</li>";
            echo "</ul>";
            
            // Test 2: getUjianBySiswa result
            $ujianList = $ujianLogic->getUjianBySiswa($siswa_id, true);
            echo "<p><strong>Test 2 - getUjianBySiswa Status:</strong></p>";
            foreach ($ujianList as $ujian) {
                if ($ujian['ujian_siswa_id'] == $ujian_siswa_id) {
                    echo "<ul>";
                    echo "<li>Status Pengerjaan: " . htmlspecialchars($ujian['statusPengerjaan'] ?? 'NULL') . "</li>";
                    echo "<li>Status Ujian: " . htmlspecialchars($ujian['status_ujian']) . "</li>";
                    echo "</ul>";
                    break;
                }
            }
            
            // Test 3: Akses ujian validation
            echo "<p><strong>Test 3 - Validation Test:</strong></p>";
            $test_ujian_siswa = $ujianLogic->getUjianSiswaById($ujian_siswa_id);
            if ($test_ujian_siswa && $test_ujian_siswa['status'] === 'selesai') {
                echo "<p style='color: green;'>✅ Validation bekerja - ujian sudah selesai</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Ujian masih bisa diakses</p>";
            }
            break;
    }
    
    echo "</div>";
    echo "<hr>";
}

// Display current status
$sql = "SELECT us.*, u.namaUjian FROM ujian_siswa us JOIN ujian u ON us.ujian_id = u.id WHERE us.siswa_id = ? ORDER BY us.waktuMulai DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $siswa_id);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<h2>📊 Status Ujian Terkini</h2>";
if (empty($results)) {
    echo "<p>Tidak ada data ujian.</p>";
} else {
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th>ID</th><th>Ujian</th><th>Status</th><th>Waktu Selesai</th><th>Nilai</th><th>Actions</th>";
    echo "</tr>";
    
    foreach ($results as $row) {
        $status_color = $row['status'] === 'selesai' ? '#28a745' : ($row['status'] === 'sedang_mengerjakan' ? '#fd7e14' : '#6c757d');
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['namaUjian']) . "</td>";
        echo "<td><span style='color: $status_color; font-weight: bold;'>" . htmlspecialchars($row['status']) . "</span></td>";
        echo "<td>" . htmlspecialchars($row['waktuSelesai'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['totalNilai'] ?? '-') . "</td>";
        echo "<td>";
        
        $id = $row['id'];
        if ($row['status'] === 'selesai') {
            echo "<a href='?action=reset_to_mengerjakan&ujian_siswa_id=$id' style='color: #fd7e14; text-decoration: none;'>🔄 Reset</a> | ";
            echo "<a href='?action=verify_fix&ujian_siswa_id=$id' style='color: #17a2b8; text-decoration: none;'>🔍 Verify</a>";
        } elseif ($row['status'] === 'sedang_mengerjakan') {
            echo "<a href='?action=manual_finish&ujian_siswa_id=$id' style='color: #28a745; text-decoration: none;'>🏁 Finish</a> | ";
            echo "<a href='src/front/kerjakan-ujian.php?us_id=$id&debug=1' target='_blank' style='color: #007bff; text-decoration: none;'>🧪 Test</a>";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>🎯 Test Scenarios</h2>";
echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h3>📝 Scenario 1: Test Finish Process</h3>";
echo "<ol>";
echo "<li>Reset ujian ke 'sedang_mengerjakan'</li>";
echo "<li>Klik 'Test' untuk akses ujian</li>";
echo "<li>Klik 'Selesai' dan cek console</li>";
echo "<li>Expected: JSON response dengan success=true</li>";
echo "<li>Verify status berubah ke 'selesai'</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h3>🔒 Scenario 2: Test Access Prevention</h3>";
echo "<ol>";
echo "<li>Pastikan ujian berstatus 'selesai'</li>";
echo "<li>Coba akses ujian lagi</li>";
echo "<li>Expected: Modal 'Ujian Sudah Selesai'</li>";
echo "<li>Cek halaman ujian-user.php</li>";
echo "<li>Expected: Status 'SELESAI' + button disabled</li>";
echo "</ol>";
echo "</div>";

echo "</div>";

echo "<h2>🔗 Quick Links</h2>";
echo "<ul>";
echo "<li><a href='src/front/ujian-user.php?debug=1' target='_blank'>📋 Halaman Ujian (Debug Mode)</a></li>";
echo "<li><a href='test-ajax-finish.php' target='_blank'>🧪 Test AJAX Endpoint</a></li>";
echo "<li><a href='test-direct-ajax.php?ujian_siswa_id=3' target='_blank'>📡 Direct AJAX Test</a></li>";
echo "<li><a href='debug-status-ujian-siswa.php' target='_blank'>🔍 Debug Status Database</a></li>";
echo "</ul>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
echo "<h3>✅ Success Indicators</h3>";
echo "<ul>";
echo "<li><strong>Console:</strong> Response headers: application/json (bukan text/html)</li>";
echo "<li><strong>Console:</strong> Parsed JSON dengan success=true</li>";
echo "<li><strong>UI:</strong> Redirect ke ujian-user.php dengan alert sukses</li>";
echo "<li><strong>Status:</strong> Berubah dari 'sedang_mengerjakan' ke 'selesai'</li>";
echo "<li><strong>Access:</strong> Modal 'Ujian Sudah Selesai' muncul saat coba akses lagi</li>";
echo "</ul>";
echo "</div>";
?>
