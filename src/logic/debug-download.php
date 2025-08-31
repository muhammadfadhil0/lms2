<?php
require_once 'koneksi.php';

$file_id = $_GET['file_id'] ?? 3;

try {
    // Get file info
    $sql = "SELECT * FROM kelas_files WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$file_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        die('File not found in database');
    }
    
    echo "<h2>File Info:</h2>";
    echo "<pre>";
    print_r($file);
    echo "</pre>";
    
    echo "<h2>File Path Check:</h2>";
    echo "Database path: " . htmlspecialchars($file['file_path']) . "<br>";
    echo "File exists: " . (file_exists($file['file_path']) ? 'YES' : 'NO') . "<br>";
    
    if (file_exists($file['file_path'])) {
        echo "File size: " . filesize($file['file_path']) . " bytes<br>";
        echo "Real path: " . realpath($file['file_path']) . "<br>";
    }
    
    echo "<h2>Current Directory:</h2>";
    echo "Current working directory: " . getcwd() . "<br>";
    echo "__DIR__: " . __DIR__ . "<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
