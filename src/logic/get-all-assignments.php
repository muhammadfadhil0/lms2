<?php
require_once 'koneksi.php';

// Set content type
header('Content-Type: application/json');

// Start session
session_start();

// Response function
function sendResponse($success, $message = '', $assignments = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'assignments' => $assignments
    ]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    sendResponse(false, 'User tidak terautentikasi');
}

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'] ?? '';

try {
    $pdo = getPDOConnection();
    
    // Base query to get assignments with submission status
    $sql = "
        SELECT DISTINCT
            t.id,
            t.judul as title,
            t.deskripsi as description,
            t.deadline,
            t.created_at,
            k.id as class_id,
            k.namaKelas as class_name,
            pt.status,
            pt.tanggal_pengumpulan as submitted_at,
            pt.file_path as submission_file
        FROM tugas t
        INNER JOIN kelas k ON t.kelas_id = k.id
    ";
    
    // Add role-specific conditions
    if ($userRole === 'siswa') {
        // For students, show assignments from classes they're enrolled in
        $sql .= "
            INNER JOIN kelas_siswa ks ON k.id = ks.kelas_id AND ks.siswa_id = :user_id AND ks.status = 'aktif'
            LEFT JOIN pengumpulan_tugas pt ON t.id = pt.assignment_id AND pt.siswa_id = :user_id
            WHERE k.status = 'aktif'
        ";
    } elseif ($userRole === 'guru') {
        // For teachers, show assignments from their classes
        $sql .= "
            LEFT JOIN pengumpulan_tugas pt ON t.id = pt.assignment_id
            WHERE k.guru_id = :user_id
        ";
    } else {
        // For admin, show all assignments
        $sql .= "
            LEFT JOIN pengumpulan_tugas pt ON t.id = pt.assignment_id
        ";
    }
    
    $sql .= " ORDER BY t.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters based on role
    if ($userRole === 'siswa' || $userRole === 'guru') {
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process assignments data
    $processedAssignments = [];
    foreach ($assignments as $assignment) {
        // For students, keep only their own submissions
        if ($userRole === 'siswa') {
            $processedAssignments[] = $assignment;
        } 
        // For teachers and admins, group submissions or show assignment without specific student data
        else {
            // Check if assignment already exists in processed array
            $existingIndex = -1;
            foreach ($processedAssignments as $index => $existing) {
                if ($existing['id'] === $assignment['id']) {
                    $existingIndex = $index;
                    break;
                }
            }
            
            if ($existingIndex === -1) {
                // Add new assignment without student-specific data
                $processedAssignment = $assignment;
                // Remove student-specific fields for teachers/admins
                unset($processedAssignment['score']);
                unset($processedAssignment['status']);
                unset($processedAssignment['submitted_at']);
                unset($processedAssignment['submission_file']);
                $processedAssignments[] = $processedAssignment;
            }
        }
    }
    
    sendResponse(true, 'Tugas berhasil dimuat', $processedAssignments);
    
} catch (Exception $e) {
    error_log("Error in get-all-assignments.php: " . $e->getMessage());
    sendResponse(false, 'Terjadi kesalahan server: ' . $e->getMessage());
}
?>