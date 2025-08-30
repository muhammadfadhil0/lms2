<?php
// Debug file for assignment report details
session_start();
$_SESSION['user'] = array('id' => 23, 'role' => 'guru');
$_GET['assignment_id'] = 7;

require_once 'src/logic/koneksi.php';

echo "=== DEBUGGING GET-ASSIGNMENT-REPORT.PHP ===\n";

try {
    $assignment_id = $_GET['assignment_id'];
    $guru_id = $_SESSION['user']['id'];
    
    echo "Assignment ID: $assignment_id, Guru ID: $guru_id\n";
    
    // Get assignment details and verify ownership
    $stmt = $pdo->prepare("
        SELECT t.*, k.namaKelas 
        FROM tugas t 
        JOIN kelas k ON t.kelas_id = k.id 
        WHERE t.id = ? AND k.guru_id = ?
    ");
    $stmt->execute([$assignment_id, $guru_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) {
        echo "❌ Assignment not found or access denied\n";
        exit();
    }
    
    echo "✅ Assignment found: " . $assignment['judul'] . " in class " . $assignment['namaKelas'] . "\n";
    
    // Get all students in the class with their submission status
    $stmt = $pdo->prepare("
        SELECT 
            u.id as siswa_id,
            u.namaLengkap as nama_siswa,
            pt.id as submission_id,
            pt.file_path,
            pt.catatan_pengumpulan,
            pt.tanggal_pengumpulan,
            pt.status,
            pt.nilai,
            pt.feedback
        FROM kelas_siswa ks
        JOIN users u ON ks.siswa_id = u.id
        LEFT JOIN pengumpulan_tugas pt ON pt.assignment_id = ? AND pt.siswa_id = u.id
        WHERE ks.kelas_id = ?
        ORDER BY u.namaLengkap
    ");
    $stmt->execute([$assignment_id, $assignment['kelas_id']]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($submissions) . " students\n";
    foreach($submissions as $submission) {
        $status = $submission['status'] ?? 'belum_mengumpulkan';
        echo "- " . $submission['nama_siswa'] . " (Status: $status)\n";
    }
    
    echo "\n=== JSON RESPONSE ===\n";
    echo json_encode(['success' => true, 'assignment' => $assignment, 'submissions' => $submissions]);
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
