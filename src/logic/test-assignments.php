<?php
// Minimal test endpoint
while (ob_get_level()) {
    ob_end_clean();
}

session_start();
require_once 'koneksi.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo '{"success":false,"message":"Unauthorized"}';
        exit;
    }

    $kelas_id = intval($_GET['kelas_id'] ?? 0);
    if ($kelas_id <= 0) {
        http_response_code(400);
        echo '{"success":false,"message":"Invalid class ID"}';
        exit;
    }

    $user_id = $_SESSION['user']['id'];
    $user_role = $_SESSION['user']['role'];

    // Simple query for testing
    global $pdo;
    
    $sql = "SELECT t.id, t.judul, t.deskripsi, t.deadline, t.nilai_maksimal, 
                   pt.status as student_status,
                   pt.nilai as student_score,
                   pt.feedback,
                   CASE 
                       WHEN pt.status = 'dinilai' THEN 'graded'
                       WHEN pt.status = 'dikumpulkan' THEN 'submitted'
                       ELSE 'pending'
                   END as submission_status
            FROM tugas t
            LEFT JOIN pengumpulan_tugas pt ON t.id = pt.assignment_id AND pt.siswa_id = ?
            WHERE t.kelas_id = ?
            ORDER BY t.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $kelas_id]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add simple time formatting
    foreach ($assignments as &$assignment) {
        $assignment['time_ago'] = '1 jam yang lalu'; // Static for testing
        if ($assignment['deadline']) {
            $assignment['deadline_formatted'] = date('j M Y', strtotime($assignment['deadline']));
        }
    }

    $response = [
        'success' => true,
        'assignments' => $assignments,
        'total' => count($assignments)
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
