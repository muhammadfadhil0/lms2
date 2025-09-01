<?php
require_once 'src/logic/ujian-logic.php';

$ujianLogic = new UjianLogic();
$siswa_id = 25; // ID siswa Anda

echo "<h2>Test getUjianBySiswa Method</h2>";

$ujianList = $ujianLogic->getUjianBySiswa($siswa_id);

echo "<pre>";
print_r($ujianList);
echo "</pre>";

if (!empty($ujianList)) {
    foreach ($ujianList as $ujian) {
        if ($ujian['id'] == 6) {
            echo "<h3>Ujian ID 6 Detail:</h3>";
            echo "Status ujian: " . $ujian['status_ujian'] . "<br>";
            echo "Tanggal ujian: " . $ujian['tanggalUjian'] . "<br>";
            echo "Waktu mulai: " . $ujian['waktuMulai'] . "<br>";
            echo "Waktu selesai: " . $ujian['waktuSelesai'] . "<br>";
            
            // Test query manual
            $conn = getConnection();
            $testSql = "SELECT 
                CONCAT('2025-08-31', ' ', '21:37:00') as concat_mulai,
                CONCAT('2025-08-31', ' ', '23:07:00') as concat_selesai,
                NOW() as sekarang,
                CASE 
                    WHEN CONCAT('2025-08-31', ' ', '23:07:00') < NOW() THEN 'terlambat'
                    WHEN CONCAT('2025-08-31', ' ', '21:37:00') <= NOW() AND CONCAT('2025-08-31', ' ', '23:07:00') >= NOW() THEN 'dapat_dikerjakan'
                    WHEN CONCAT('2025-08-31', ' ', '21:37:00') > NOW() THEN 'belum_dimulai'
                    ELSE 'belum_dikerjakan'
                END as status_test";
            
            $result = $conn->query($testSql);
            $test = $result->fetch_assoc();
            
            echo "<h4>Manual Query Test:</h4>";
            echo "<pre>";
            print_r($test);
            echo "</pre>";
            
            break;
        }
    }
}
?>
