<?php
require_once 'src/logic/koneksi.php';

try {
    $conn = getConnection();
    
    echo "Menambahkan kolom is_edited...\n";
    $sql1 = "ALTER TABLE `postingan_kelas` ADD COLUMN `is_edited` TINYINT(1) DEFAULT 0 COMMENT 'Flag to indicate if post has been edited'";
    
    if ($conn->query($sql1)) {
        echo "✓ Kolom is_edited berhasil ditambahkan\n";
    } else {
        echo "✗ Error adding is_edited: " . $conn->error . "\n";
    }
    
    echo "Menambahkan kolom diupdate...\n";
    $sql2 = "ALTER TABLE `postingan_kelas` ADD COLUMN `diupdate` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp'";
    
    if ($conn->query($sql2)) {
        echo "✓ Kolom diupdate berhasil ditambahkan\n";
    } else {
        echo "✗ Error adding diupdate: " . $conn->error . "\n";
    }
    
    echo "Menambahkan index...\n";
    $sql3 = "ALTER TABLE `postingan_kelas` ADD INDEX `idx_is_edited` (`is_edited`)";
    
    if ($conn->query($sql3)) {
        echo "✓ Index idx_is_edited berhasil ditambahkan\n";
    } else {
        echo "✗ Error adding index: " . $conn->error . "\n";
    }
    
    echo "\nSelesai! Skema database berhasil diupdate.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
