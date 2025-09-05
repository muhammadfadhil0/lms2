<?php
require_once '../logic/koneksi.php';

function runMigration() {
    global $koneksi;
    
    try {
        // Baca file SQL
        $sqlFile = __DIR__ . '/settings-migration.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("File migrasi tidak ditemukan: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            throw new Exception("Gagal membaca file migrasi");
        }
        
        // Pisahkan query berdasarkan semicolon
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        $successCount = 0;
        $errors = [];
        
        foreach ($queries as $query) {
            // Skip komentar dan query kosong
            if (empty($query) || strpos($query, '--') === 0) {
                continue;
            }
            
            try {
                if (mysqli_query($koneksi, $query)) {
                    $successCount++;
                    echo "✓ Query berhasil dijalankan\n";
                } else {
                    $error = mysqli_error($koneksi);
                    $errors[] = "Query gagal: $error";
                    echo "✗ Query gagal: $error\n";
                }
            } catch (Exception $e) {
                $errors[] = "Exception: " . $e->getMessage();
                echo "✗ Exception: " . $e->getMessage() . "\n";
            }
        }
        
        // Buat direktori upload jika belum ada
        $uploadDir = __DIR__ . '/../../uploads/profile';
        if (!is_dir($uploadDir)) {
            if (mkdir($uploadDir, 0755, true)) {
                echo "✓ Direktori upload profile berhasil dibuat: $uploadDir\n";
            } else {
                echo "✗ Gagal membuat direktori upload profile: $uploadDir\n";
            }
        } else {
            echo "✓ Direktori upload profile sudah ada: $uploadDir\n";
        }
        
        // Tampilkan ringkasan
        echo "\n=== RINGKASAN MIGRASI ===\n";
        echo "Query berhasil: $successCount\n";
        echo "Query gagal: " . count($errors) . "\n";
        
        if (!empty($errors)) {
            echo "\nError yang terjadi:\n";
            foreach ($errors as $error) {
                echo "- $error\n";
            }
        }
        
        if (count($errors) === 0) {
            echo "\n✓ Migrasi berhasil selesai!\n";
            return true;
        } else {
            echo "\n⚠ Migrasi selesai dengan beberapa error\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "✗ Error fatal: " . $e->getMessage() . "\n";
        return false;
    }
}

// Jalankan migrasi jika file ini dijalankan langsung
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "=== MULAI MIGRASI DATABASE SETTINGS ===\n\n";
    
    if (runMigration()) {
        echo "\nMigrasi selesai! Anda dapat menggunakan fitur settings sekarang.\n";
    } else {
        echo "\nMigrasi gagal! Periksa error di atas dan perbaiki sebelum melanjutkan.\n";
    }
}
?>
