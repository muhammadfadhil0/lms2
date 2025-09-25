<?php
session_start();

// Check if user is logged in and is a guru
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['kelas_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kelas ID is required']);
    exit();
}

$kelas_id = intval($input['kelas_id']);
$guru_id = $_SESSION['user']['id'];

try {
    // Debug: write incoming request to temp log for troubleshooting
    @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Incoming delete request: " . PHP_EOL, FILE_APPEND);
    @file_put_contents('/tmp/delete-class-debug.log', "RAW_INPUT: " . file_get_contents('php://input') . PHP_EOL, FILE_APPEND);
    @file_put_contents('/tmp/delete-class-debug.log', "kelas_id: $kelas_id, guru_id: $guru_id" . PHP_EOL . PHP_EOL, FILE_APPEND);

    // Database connection (use existing koneksi.php which provides PDO)
    require_once __DIR__ . '/koneksi.php';
    $conn = getPDOConnection();
    // Helper to check if a table exists in current database
    $tableExists = function($tableName) use ($conn) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$tableName]);
        return $stmt->fetchColumn() > 0;
    };
    
    header('Content-Type: application/json; charset=utf-8');
    // Start transaction
    $conn->beginTransaction();
    
    // First, verify that this class belongs to the current guru
    $verifyStmt = $conn->prepare("SELECT id, namaKelas FROM kelas WHERE id = ? AND guru_id = ?");
    $verifyStmt->execute([$kelas_id, $guru_id]);
    $kelas = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kelas) {
        $conn->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Kelas not found or you do not have permission to delete this class']);
        exit();
    }
    
    // Delete related data in the correct order to avoid foreign key constraints
    
    // 1. Delete jawaban siswa (answers) first
    if ($tableExists('jawaban_siswa') && $tableExists('soal') && $tableExists('ujian')) {
        $conn->prepare("DELETE js FROM jawaban_siswa js 
                       INNER JOIN soal s ON js.soal_id = s.id 
                       INNER JOIN ujian u ON s.ujian_id = u.id 
                       WHERE u.kelas_id = ?")->execute([$kelas_id]);
    } else {
        @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Skipping delete jawaban_siswa/soal/ujian join - table missing" . PHP_EOL, FILE_APPEND);
    }
    
    // 2. Delete soal (questions)
    if ($tableExists('soal') && $tableExists('ujian')) {
        $conn->prepare("DELETE s FROM soal s 
                       INNER JOIN ujian u ON s.ujian_id = u.id 
                       WHERE u.kelas_id = ?")->execute([$kelas_id]);
    } else {
        @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Skipping delete soal - table missing" . PHP_EOL, FILE_APPEND);
    }
    
    // 3. Delete ujian (exams)
    if ($tableExists('ujian')) {
        $conn->prepare("DELETE FROM ujian WHERE kelas_id = ?")->execute([$kelas_id]);
    } else {
        @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Skipping delete ujian - table missing" . PHP_EOL, FILE_APPEND);
    }
    
    // 4. Delete assignment submissions
    if ($tableExists('assignment_submissions') && $tableExists('assignments')) {
        $conn->prepare("DELETE asu FROM assignment_submissions asu 
                       INNER JOIN assignments a ON asu.assignment_id = a.id 
                       WHERE a.kelas_id = ?")->execute([$kelas_id]);
    } else {
        @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Skipping delete assignment_submissions - table missing" . PHP_EOL, FILE_APPEND);
    }
    
    // 5. Delete assignments
    if ($tableExists('assignments')) {
        $conn->prepare("DELETE FROM assignments WHERE kelas_id = ?")->execute([$kelas_id]);
    } else {
        @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Skipping delete assignments - table missing" . PHP_EOL, FILE_APPEND);
    }
    
    // 6. Delete postingan files
    if ($tableExists('postingan_files') && $tableExists('postingan')) {
        $conn->prepare("DELETE pf FROM postingan_files pf 
                       INNER JOIN postingan p ON pf.postingan_id = p.id 
                       WHERE p.kelas_id = ?")->execute([$kelas_id]);
    } else {
        @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Skipping delete postingan_files - table missing" . PHP_EOL, FILE_APPEND);
    }
    
    // 7. Delete postingan gambar
    if ($tableExists('postingan_gambar') && $tableExists('postingan')) {
        $conn->prepare("DELETE pg FROM postingan_gambar pg 
                       INNER JOIN postingan p ON pg.postingan_id = p.id 
                       WHERE p.kelas_id = ?")->execute([$kelas_id]);
    } else {
        @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Skipping delete postingan_gambar - table missing" . PHP_EOL, FILE_APPEND);
    }
    
    // 8. Delete komentar
    if ($tableExists('komentar') && $tableExists('postingan')) {
        $conn->prepare("DELETE k FROM komentar k 
                       INNER JOIN postingan p ON k.postingan_id = p.id 
                       WHERE p.kelas_id = ?")->execute([$kelas_id]);
    } else {
        @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Skipping delete komentar - table missing" . PHP_EOL, FILE_APPEND);
    }
    
    // 9. Delete postingan
    if ($tableExists('postingan')) {
        $conn->prepare("DELETE FROM postingan WHERE kelas_id = ?")->execute([$kelas_id]);
    } else {
        @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Skipping delete postingan - table missing" . PHP_EOL, FILE_APPEND);
    }
    
    // 10. Delete kelas files
    if ($tableExists('kelas_files')) {
        $conn->prepare("DELETE FROM kelas_files WHERE kelas_id = ?")->execute([$kelas_id]);
    } else {
        @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Skipping delete kelas_files - table missing" . PHP_EOL, FILE_APPEND);
    }
    
    // 11. Delete class members (siswa_kelas)
    if ($tableExists('siswa_kelas')) {
        $conn->prepare("DELETE FROM siswa_kelas WHERE kelas_id = ?")->execute([$kelas_id]);
    } else {
        @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Skipping delete siswa_kelas - table missing" . PHP_EOL, FILE_APPEND);
    }
    
    // 12. Finally, delete the class itself
    if ($tableExists('kelas')) {
        $conn->prepare("DELETE FROM kelas WHERE id = ?")->execute([$kelas_id]);
    } else {
        @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Skipping delete kelas - table missing" . PHP_EOL, FILE_APPEND);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Kelas "' . $kelas['namaKelas'] . '" berhasil dihapus',
        'kelas_name' => $kelas['namaKelas']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }

    $errMsg = $e->getMessage();
    // Debug: write exception to temp log as well
    @file_put_contents('/tmp/delete-class-debug.log', date('[Y-m-d H:i:s] ') . "Exception: " . $errMsg . PHP_EOL . PHP_EOL, FILE_APPEND);
    error_log("Error deleting class: " . $errMsg);
    http_response_code(500);
    // Provide minimal error details in JSON for easier debugging in dev environment
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat menghapus kelas. Silakan coba lagi.',
        'error' => substr($errMsg, 0, 200)
    ]);
}
?>