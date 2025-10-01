<?php
/**
 * API Endpoint untuk mengambil daftar tugas berdasarkan kelas yang diikuti user
 */
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../logic/koneksi.php';

try {
    $pdo = getPDOConnection();
    $userId = $_SESSION['user']['id'];
    $userRole = $_SESSION['user']['role'];
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_assignments':
            getAssignments($pdo, $userId, $userRole);
            break;
        case 'get_classes':
            getClasses($pdo, $userId, $userRole);
            break;
        case 'get_assignment_details':
            getAssignmentDetails($pdo, $userId, $userRole);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Fungsi untuk mengambil daftar tugas
 */
function getAssignments($pdo, $userId, $userRole) {
    try {
        $classFilter = $_GET['class_id'] ?? '';
        
        if ($userRole === 'guru') {
            // Query untuk guru - tugas dari kelas yang diajar
            $sql = "SELECT 
                        t.id,
                        t.judul,
                        t.deskripsi,
                        t.deadline,
                        t.nilai_maksimal,
                        t.created_at,
                        k.namaKelas,
                        k.id as kelas_id,
                        COUNT(pt.id) as total_submissions,
                        COUNT(CASE WHEN pt.status = 'dinilai' THEN 1 END) as graded_submissions
                    FROM tugas t
                    INNER JOIN kelas k ON t.kelas_id = k.id
                    LEFT JOIN pengumpulan_tugas pt ON t.id = pt.assignment_id
                    WHERE k.guru_id = ?";
            
            $params = [$userId];
            $types = 'i';
            
            if (!empty($classFilter)) {
                $sql .= " AND k.id = ?";
                $params[] = $classFilter;
                $types .= 'i';
            }
            
            $sql .= " GROUP BY t.id, t.judul, t.deskripsi, t.deadline, t.nilai_maksimal, t.created_at, k.namaKelas, k.id
                     ORDER BY t.deadline ASC, t.created_at DESC";
            
        } else {
            // Query untuk siswa - tugas dari kelas yang diikuti
            $sql = "SELECT 
                        t.id,
                        t.judul,
                        t.deskripsi,
                        t.deadline,
                        t.nilai_maksimal,
                        t.created_at,
                        k.namaKelas,
                        k.id as kelas_id,
                        pt.id as submission_id,
                        pt.status as submission_status,
                        pt.tanggal_pengumpulan,
                        pt.nilai,
                        pt.feedback
                    FROM tugas t
                    INNER JOIN kelas k ON t.kelas_id = k.id
                    INNER JOIN kelas_siswa ks ON k.id = ks.kelas_id
                    LEFT JOIN pengumpulan_tugas pt ON t.id = pt.assignment_id AND pt.siswa_id = ?
                    WHERE ks.siswa_id = ? AND ks.status = 'aktif'";
            
            $params = [$userId, $userId];
            $types = 'ii';
            
            if (!empty($classFilter)) {
                $sql .= " AND k.id = ?";
                $params[] = $classFilter;
                $types .= 'i';
            }
            
            $sql .= " ORDER BY t.deadline ASC, t.created_at DESC";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data untuk response
        $formattedAssignments = [];
        foreach ($assignments as $assignment) {
            $deadline = new DateTime($assignment['deadline']);
            $now = new DateTime();
            $interval = $now->diff($deadline);
            
            // Hitung status deadline
            $deadlineStatus = 'normal';
            if ($deadline < $now) {
                $deadlineStatus = 'overdue';
            } elseif ($interval->days <= 1) {
                $deadlineStatus = 'urgent';
            } elseif ($interval->days <= 3) {
                $deadlineStatus = 'soon';
            }
            
            $formattedAssignment = [
                'id' => $assignment['id'],
                'judul' => $assignment['judul'],
                'deskripsi' => $assignment['deskripsi'],
                'deadline' => $assignment['deadline'],
                'deadline_formatted' => $deadline->format('d/m/Y H:i'),
                'deadline_status' => $deadlineStatus,
                'nilai_maksimal' => $assignment['nilai_maksimal'],
                'created_at' => $assignment['created_at'],
                'kelas' => [
                    'id' => $assignment['kelas_id'],
                    'nama' => $assignment['namaKelas']
                ]
            ];
            
            if ($userRole === 'guru') {
                $formattedAssignment['submissions'] = [
                    'total' => (int)$assignment['total_submissions'],
                    'graded' => (int)$assignment['graded_submissions']
                ];
            } else {
                $formattedAssignment['submission'] = [
                    'id' => $assignment['submission_id'],
                    'status' => $assignment['submission_status'],
                    'tanggal_pengumpulan' => $assignment['tanggal_pengumpulan'],
                    'nilai' => $assignment['nilai'],
                    'feedback' => $assignment['feedback'],
                    'is_submitted' => !empty($assignment['submission_id'])
                ];
            }
            
            $formattedAssignments[] = $formattedAssignment;
        }
        
        echo json_encode([
            'success' => true,
            'assignments' => $formattedAssignments,
            'total' => count($formattedAssignments)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching assignments: ' . $e->getMessage()
        ]);
    }
}

/**
 * Fungsi untuk mengambil daftar kelas
 */
function getClasses($pdo, $userId, $userRole) {
    try {
        if ($userRole === 'guru') {
            // Query untuk guru - kelas yang diajar
            $sql = "SELECT id, namaKelas 
                    FROM kelas 
                    WHERE guru_id = ? AND status = 'aktif'
                    ORDER BY namaKelas ASC";
            $params = [$userId];
        } else {
            // Query untuk siswa - kelas yang diikuti
            $sql = "SELECT k.id, k.namaKelas
                    FROM kelas k
                    INNER JOIN kelas_siswa ks ON k.id = ks.kelas_id
                    WHERE ks.siswa_id = ? AND ks.status = 'aktif' AND k.status = 'aktif'
                    ORDER BY k.namaKelas ASC";
            $params = [$userId];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'classes' => $classes
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching classes: ' . $e->getMessage()
        ]);
    }
}

/**
 * Fungsi untuk mengambil detail lengkap tugas untuk AI analysis
 */
function getAssignmentDetails($pdo, $userId, $userRole) {
    try {
        error_log("👍 DEBUG API: getAssignmentDetails called");
        error_log("👍 DEBUG API: userId=$userId, userRole=$userRole");
        
        $assignmentId = $_GET['assignment_id'] ?? '';
        error_log("👍 DEBUG API: assignmentId=$assignmentId");
        
        if (empty($assignmentId)) {
            error_log("👍 DEBUG API: Empty assignment ID, returning error");
            echo json_encode([
                'success' => false,
                'message' => 'Assignment ID is required'
            ]);
            return;
        }
        
        // Get assignment details
        $assignmentSql = "SELECT 
                            t.id,
                            t.judul,
                            t.deskripsi,
                            t.deadline,
                            t.nilai_maksimal,
                            t.created_at,
                            k.namaKelas,
                            k.deskripsi as kelas_deskripsi,
                            k.id as kelas_id,
                            u.namaLengkap as guru_nama
                        FROM tugas t
                        INNER JOIN kelas k ON t.kelas_id = k.id
                        INNER JOIN users u ON k.guru_id = u.id
                        WHERE t.id = ?";
        
        // Check access permission
        if ($userRole === 'siswa') {
            $assignmentSql .= " AND EXISTS (
                SELECT 1 FROM kelas_siswa ks 
                WHERE ks.kelas_id = k.id AND ks.siswa_id = ? AND ks.status = 'aktif'
            )";
            $assignmentParams = [$assignmentId, $userId];
        } else {
            $assignmentSql .= " AND k.guru_id = ?";
            $assignmentParams = [$assignmentId, $userId];
        }
        
        $stmt = $pdo->prepare($assignmentSql);
        $stmt->execute($assignmentParams);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$assignment) {
            echo json_encode([
                'success' => false,
                'message' => 'Assignment not found or access denied'
            ]);
            return;
        }
        
        // Get today's date for context
        $today = new DateTime();
        $deadline = new DateTime($assignment['deadline']);
        $daysUntilDeadline = $today->diff($deadline)->days;
        $isOverdue = $today > $deadline;
        
        // Get posts before and after the assignment (from class posts)
        $postsSql = "SELECT 
                        p.id,
                        p.konten,
                        p.tipePost,
                        p.dibuat,
                        p.assignment_id,
                        u.namaLengkap as penulis_nama,
                        CASE 
                            WHEN p.dibuat < ? THEN 'before'
                            WHEN p.dibuat > ? THEN 'after'
                            ELSE 'same_time'
                        END as timing_relative_to_assignment
                    FROM postingan_kelas p
                    INNER JOIN users u ON p.user_id = u.id
                    WHERE p.kelas_id = ?
                    AND p.dibuat BETWEEN DATE_SUB(?, INTERVAL 7 DAY) AND DATE_ADD(?, INTERVAL 7 DAY)
                    ORDER BY p.dibuat ASC";
        
        $assignmentCreated = $assignment['created_at'];
        $postsParams = [
            $assignmentCreated, 
            $assignmentCreated, 
            $assignment['kelas_id'], 
            $assignmentCreated, 
            $assignmentCreated
        ];
        
        $stmt = $pdo->prepare($postsSql);
        $stmt->execute($postsParams);
        $allPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Separate posts
        $postsBefore = array_filter($allPosts, fn($post) => $post['timing_relative_to_assignment'] === 'before');
        $postsAfter = array_filter($allPosts, fn($post) => $post['timing_relative_to_assignment'] === 'after');
        
        // Get submission info if user is student
        $submissionInfo = null;
        if ($userRole === 'siswa') {
            $submissionSql = "SELECT 
                                pt.id,
                                pt.file_path,
                                pt.catatan_pengumpulan,
                                pt.tanggal_pengumpulan,
                                pt.status,
                                pt.nilai,
                                pt.feedback
                            FROM pengumpulan_tugas pt
                            WHERE pt.assignment_id = ? AND pt.siswa_id = ?";
            
            $stmt = $pdo->prepare($submissionSql);
            $stmt->execute([$assignmentId, $userId]);
            $submissionInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Get class statistics for context
        $statsSql = "SELECT 
                        COUNT(DISTINCT ks.siswa_id) as total_students,
                        COUNT(DISTINCT pt.siswa_id) as submitted_count,
                        COUNT(CASE WHEN pt.status = 'dinilai' THEN 1 END) as graded_count
                    FROM kelas_siswa ks
                    LEFT JOIN pengumpulan_tugas pt ON ks.siswa_id = pt.siswa_id AND pt.assignment_id = ?
                    WHERE ks.kelas_id = ? AND ks.status = 'aktif'";
        
        $stmt = $pdo->prepare($statsSql);
        $stmt->execute([$assignmentId, $assignment['kelas_id']]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Format response
        $response = [
            'success' => true,
            'data' => [
                'id' => $assignment['id'],
                'judul' => $assignment['judul'],
                'deskripsi' => $assignment['deskripsi'],
                'deadline' => $assignment['deadline'],
                'deadline_formatted' => $deadline->format('d/m/Y H:i'),
                'nilai_maksimal' => $assignment['nilai_maksimal'],
                'created_at' => $assignment['created_at'],
                'days_until_deadline' => $daysUntilDeadline,
                'is_overdue' => $isOverdue,
                'kelas' => [
                    'id' => $assignment['kelas_id'],
                    'nama' => $assignment['namaKelas'],
                    'deskripsi' => $assignment['kelas_deskripsi'],
                    'guru_nama' => $assignment['guru_nama']
                ]
            ],
            'context' => [
                'today' => $today->format('Y-m-d'),
                'today_formatted' => $today->format('d/m/Y'),
                'day_name' => $today->format('l'),
                'posts_before' => array_values($postsBefore),
                'posts_after' => array_values($postsAfter),
                'class_stats' => $stats
            ],
            'submission' => $submissionInfo,
            'user_role' => $userRole
        ];
        
        error_log("👍 DEBUG API: Final response structure: " . print_r($response, true));
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching assignment details: ' . $e->getMessage()
        ]);
    }
}
?>