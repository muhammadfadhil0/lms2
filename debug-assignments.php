<?php
// Debug file for assignment reports
session_start();
$_SESSION['user'] = array('id' => 23, 'role' => 'guru');
$_GET['kelas_id'] = 7;

require_once 'src/logic/koneksi.php';

echo "=== DEBUGGING GET-ASSIGNMENTS.PHP ===\n";

try {
    $kelas_id = $_GET['kelas_id'];
    $user_id = $_SESSION['user']['id'];
    $user_role = $_SESSION['user']['role'];
    
    echo "User ID: $user_id, Role: $user_role, Kelas ID: $kelas_id\n";
    
    // Verify user has access to this class
    if ($user_role === 'guru') {
        $stmt = $pdo->prepare("SELECT id FROM kelas WHERE id = ? AND guru_id = ?");
        $stmt->execute([$kelas_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT k.id FROM kelas k JOIN kelas_siswa ks ON k.id = ks.kelas_id WHERE k.id = ? AND ks.siswa_id = ?");
        $stmt->execute([$kelas_id, $user_id]);
    }
    
    $access = $stmt->fetch();
    if (!$access) {
        echo "❌ Access denied - Class verification failed\n";
        exit();
    }
    
    echo "✅ Access granted - proceeding with assignments query\n";
    
    // Get assignments for this class
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            (SELECT COUNT(*) FROM kelas_siswa WHERE kelas_id = t.kelas_id) as total_students,
            (SELECT COUNT(*) FROM pengumpulan_tugas pt WHERE pt.assignment_id = t.id) as submitted_count
        FROM tugas t 
        WHERE t.kelas_id = ? 
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$kelas_id]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($assignments) . " assignments\n";
    foreach($assignments as $assignment) {
        echo "- " . $assignment['judul'] . " (ID: " . $assignment['id'] . ", Students: " . $assignment['total_students'] . ", Submitted: " . $assignment['submitted_count'] . ")\n";
    }
    
    echo "\n=== JSON RESPONSE ===\n";
    echo json_encode(['success' => true, 'assignments' => $assignments]);
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
