<?php
// Migration script untuk memastikan kolom jawaban_siswa lengkap
require_once 'src/logic/koneksi.php';

echo "<h1>Migration: Jawaban Siswa Table Enhancement</h1>";

try {
    $conn = getConnection();
    
    echo "<h2>Checking jawaban_siswa table structure...</h2>";
    
    // Get current columns
    $result = $conn->query("SHOW COLUMNS FROM jawaban_siswa");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
        echo "✓ Column: {$row['Field']} ({$row['Type']})<br>";
    }
    
    echo "<h2>Adding missing columns if needed...</h2>";
    
    // Check and add benar column
    if (!isset($columns['benar'])) {
        echo "Adding 'benar' column...<br>";
        $conn->query("ALTER TABLE jawaban_siswa ADD COLUMN benar TINYINT(1) NULL DEFAULT NULL AFTER jawaban");
        echo "✓ Column 'benar' added<br>";
    } else {
        echo "✓ Column 'benar' already exists<br>";
    }
    
    // Check and add poin column
    if (!isset($columns['poin'])) {
        echo "Adding 'poin' column...<br>";
        $conn->query("ALTER TABLE jawaban_siswa ADD COLUMN poin DECIMAL(5,2) NULL DEFAULT 0.00 AFTER benar");
        echo "✓ Column 'poin' added<br>";
    } else {
        echo "✓ Column 'poin' already exists<br>";
    }
    
    // Check and add pilihanJawaban column
    if (!isset($columns['pilihanJawaban'])) {
        echo "Adding 'pilihanJawaban' column...<br>";
        $conn->query("ALTER TABLE jawaban_siswa ADD COLUMN pilihanJawaban CHAR(1) NULL DEFAULT NULL AFTER jawaban");
        echo "✓ Column 'pilihanJawaban' added<br>";
    } else {
        echo "✓ Column 'pilihanJawaban' already exists<br>";
    }
    
    // Check and fix waktuDijawab column (rename if needed)
    if (!isset($columns['waktuDijawab']) && isset($columns['waktu_jawab'])) {
        echo "Renaming 'waktu_jawab' to 'waktuDijawab'...<br>";
        $conn->query("ALTER TABLE jawaban_siswa CHANGE waktu_jawab waktuDijawab TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "✓ Column renamed to 'waktuDijawab'<br>";
    } elseif (!isset($columns['waktuDijawab'])) {
        echo "Adding 'waktuDijawab' column...<br>";
        $conn->query("ALTER TABLE jawaban_siswa ADD COLUMN waktuDijawab TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "✓ Column 'waktuDijawab' added<br>";
    } else {
        echo "✓ Column 'waktuDijawab' already exists<br>";
    }
    
    echo "<h2>Validating final structure...</h2>";
    
    // Get final structure
    $result = $conn->query("SHOW COLUMNS FROM jawaban_siswa");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Sample Data Check</h2>";
    
    // Check if there's any data
    $result = $conn->query("SELECT COUNT(*) as count FROM jawaban_siswa");
    $count = $result->fetch_assoc()['count'];
    echo "Total jawaban_siswa records: $count<br>";
    
    if ($count > 0) {
        echo "<h3>Sample Records:</h3>";
        $result = $conn->query("SELECT * FROM jawaban_siswa LIMIT 5");
        if ($result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Ujian Siswa ID</th><th>Soal ID</th><th>Jawaban</th><th>Pilihan</th><th>Benar</th><th>Poin</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['ujian_siswa_id']}</td>";
                echo "<td>{$row['soal_id']}</td>";
                echo "<td>" . substr($row['jawaban'] ?? '', 0, 50) . "</td>";
                echo "<td>" . (isset($row['pilihanJawaban']) ? $row['pilihanJawaban'] : 'N/A') . "</td>";
                echo "<td>" . (isset($row['benar']) ? $row['benar'] : 'NULL') . "</td>";
                echo "<td>" . (isset($row['poin']) ? $row['poin'] : '0.00') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    echo "<h2>✅ Migration Completed Successfully!</h2>";
    echo "<p>The jawaban_siswa table is now ready for the hasil-ujian.php features.</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error during migration:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
