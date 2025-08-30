<?php
session_start();
require_once 'src/logic/koneksi.php';

header('Content-Type: application/json');

// Get the latest submission for testing
$stmt = $pdo->prepare("
    SELECT 
        pt.id as submission_id,
        pt.assignment_id,
        pt.siswa_id,
        pt.file_path,
        pt.catatan_pengumpulan,
        pt.status,
        pt.nilai,
        pt.feedback,
        u.namaLengkap as nama_siswa,
        u.username as username_siswa,
        t.judul as assignment_title,
        t.nilai_maksimal,
        k.namaKelas
    FROM pengumpulan_tugas pt
    JOIN users u ON pt.siswa_id = u.id
    JOIN tugas t ON pt.assignment_id = t.id
    JOIN kelas k ON t.kelas_id = k.id
    ORDER BY pt.tanggal_pengumpulan DESC
    LIMIT 5
");
$stmt->execute();
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'total_submissions' => count($submissions),
    'submissions' => $submissions,
    'test_url_examples' => [
        'submission_detail' => 'src/logic/get-submission-details.php?submission_id=' . ($submissions[0]['submission_id'] ?? 'NOT_FOUND'),
        'assignment_report' => 'src/logic/get-assignment-report.php?assignment_id=' . ($submissions[0]['assignment_id'] ?? 'NOT_FOUND')
    ]
], JSON_PRETTY_PRINT);
?>
