<?php
require_once 'koneksi.php';

$kelas_id = $_GET['kelas_id'] ?? 7;
$file_type = $_GET['file_type'] ?? 'schedule';

try {
    $sql = "SELECT kf.*, u.namaLengkap as guru_nama 
            FROM kelas_files kf
            JOIN users u ON kf.guru_id = u.id
            WHERE kf.kelas_id = ? AND kf.file_type = ?
            ORDER BY kf.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$kelas_id, $file_type]);
    
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'kelas_id' => $kelas_id,
        'file_type' => $file_type,
        'files' => $files,
        'count' => count($files)
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
