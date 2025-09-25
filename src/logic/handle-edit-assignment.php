<?php
session_start();
require_once 'koneksi.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user_id = $_SESSION['user']['id'];

try {
    switch ($action) {
        case 'get_assignment_detail':
            handleGetAssignmentDetail($pdo, $user_id);
            break;
            
        case 'edit_assignment':
            handleEditAssignment($pdo, $user_id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
            break;
    }
} catch (Exception $e) {
    error_log("Error in handle-edit-assignment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server']);
}

function handleGetAssignmentDetail($pdo, $user_id) {
    $assignment_id = $_GET['assignment_id'] ?? '';
    
    if (empty($assignment_id)) {
        echo json_encode(['success' => false, 'message' => 'Assignment ID diperlukan']);
        return;
    }
    
    try {
        // Get assignment details with authorization check
        $stmt = $pdo->prepare("
            SELECT t.*, k.namaKelas, k.guru_id 
            FROM tugas t 
            JOIN kelas k ON t.kelas_id = k.id 
            WHERE t.id = ? AND k.guru_id = ?
        ");
        $stmt->execute([$assignment_id, $user_id]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add file_name from file_path if exists (for backward compatibility)
        if ($assignment && $assignment['file_path']) {
            $assignment['file_name'] = basename($assignment['file_path']);
        } else if ($assignment) {
            $assignment['file_name'] = null;
        }
        
        // Get multiple files for this assignment
        if ($assignment) {
            $stmt = $pdo->prepare("
                SELECT id, file_name, file_path, file_size, file_type, upload_order 
                FROM tugas_files 
                WHERE tugas_id = ? 
                ORDER BY upload_order ASC, id ASC
            ");
            $stmt->execute([$assignment_id]);
            $assignment['files'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if (!$assignment) {
            echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan atau Anda tidak memiliki akses']);
            return;
        }
        
        echo json_encode([
            'success' => true, 
            'assignment' => $assignment
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in get_assignment_detail: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error database']);
    }
}

function handleEditAssignment($pdo, $user_id) {
    $assignment_id = $_POST['assignment_id'] ?? '';
    $judul = trim($_POST['assignmentTitle'] ?? '');
    $deskripsi = trim($_POST['assignmentDescription'] ?? '');
    $deadline = $_POST['assignmentDeadline'] ?? '';
    $nilai_maksimal = $_POST['maxScore'] ?? 100;
    $delete_current_file = $_POST['delete_current_file'] ?? '0';
    
    // Validation
    if (empty($assignment_id) || empty($judul) || empty($deskripsi) || empty($deadline)) {
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
        return;
    }
    
    if (!is_numeric($nilai_maksimal) || $nilai_maksimal < 1 || $nilai_maksimal > 1000) {
        echo json_encode(['success' => false, 'message' => 'Nilai maksimal harus antara 1-1000']);
        return;
    }
    
    try {
        // Check assignment ownership
        $stmt = $pdo->prepare("
            SELECT t.*, k.guru_id 
            FROM tugas t 
            JOIN kelas k ON t.kelas_id = k.id 
            WHERE t.id = ? AND k.guru_id = ?
        ");
        $stmt->execute([$assignment_id, $user_id]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$assignment) {
            echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan atau Anda tidak memiliki akses']);
            return;
        }
        
        $pdo->beginTransaction();
        
        // Handle file operations
        $file_path = $assignment['file_path'];
        $file_name = $assignment['file_name'];
        
        // Handle files to delete
        $files_to_delete = isset($_POST['files_to_delete']) ? json_decode($_POST['files_to_delete'], true) : [];
        if (!empty($files_to_delete)) {
            foreach ($files_to_delete as $file_id) {
                // Get file info before deleting
                $stmt = $pdo->prepare("SELECT file_path FROM tugas_files WHERE id = ? AND tugas_id = ?");
                $stmt->execute([$file_id, $assignment_id]);
                $file_info = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($file_info) {
                    // Delete physical file
                    $full_file_path = '../../../' . $file_info['file_path'];
                    if (file_exists($full_file_path)) {
                        unlink($full_file_path);
                    }
                    
                    // Delete from database
                    $stmt = $pdo->prepare("DELETE FROM tugas_files WHERE id = ? AND tugas_id = ?");
                    $stmt->execute([$file_id, $assignment_id]);
                }
            }
        }
        
        // Handle new file uploads
        $uploaded_files = [];
        if (isset($_FILES['assignment_files']) && is_array($_FILES['assignment_files']['name'])) {
            // Get current file count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tugas_files WHERE tugas_id = ?");
            $stmt->execute([$assignment_id]);
            $current_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $new_file_count = count(array_filter($_FILES['assignment_files']['name']));
            $total_files = $current_count + $new_file_count;
            
            if ($total_files > 4) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Maksimal 4 file total yang dapat diupload']);
                return;
            }
            
            $upload_result = handleMultipleFileUpload($_FILES['assignment_files'], $assignment_id);
            if (!$upload_result['success']) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $upload_result['message']]);
                return;
            }
            $uploaded_files = $upload_result['files'];
        }
        
        // Update main file_path for backward compatibility (use first file if available)
        if (!empty($uploaded_files)) {
            $file_path = $uploaded_files[0]['path'];
        } else {
            // Check if we still have files in tugas_files
            $stmt = $pdo->prepare("SELECT file_path FROM tugas_files WHERE tugas_id = ? ORDER BY upload_order ASC, id ASC LIMIT 1");
            $stmt->execute([$assignment_id]);
            $first_file = $stmt->fetch(PDO::FETCH_ASSOC);
            $file_path = $first_file ? $first_file['file_path'] : null;
        }
        
        // Update assignment (remove file_name as it's now handled by tugas_files table)
        $stmt = $pdo->prepare("
            UPDATE tugas 
            SET judul = ?, deskripsi = ?, deadline = ?, nilai_maksimal = ?, file_path = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $judul,
            $deskripsi, 
            $deadline,
            $nilai_maksimal,
            $file_path,
            $assignment_id
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tugas berhasil diperbarui'
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Database error in edit_assignment: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error database']);
    }
}

function handleFileUpload($file, $assignment_id) {
    $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'xls', 'xlsx', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'avi', 'mov'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    $file_name = $file['name'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Validate file type
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'message' => 'Tipe file tidak didukung. Format yang didukung: PDF, DOC, DOCX, PPT, PPTX, TXT, XLS, XLSX, ZIP, RAR, JPG, PNG, GIF, MP4, MP3, AVI, MOV'];
    }
    
    // Validate file size
    if ($file_size > $max_size) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (maksimal 10MB)'];
    }
    
    // Create upload directory if not exists
    $upload_dir = '../../../uploads/assignments/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $new_filename = 'assignment_' . $assignment_id . '_' . time() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_filename;
    $relative_path = 'uploads/assignments/' . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file_tmp, $upload_path)) {
        return [
            'success' => true,
            'file_path' => $relative_path,
            'file_name' => $file_name
        ];
    } else {
        return ['success' => false, 'message' => 'Gagal mengupload file'];
    }
}

function handleMultipleFileUpload($files, $assignment_id) {
    global $pdo;
    
    $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'xls', 'xlsx', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'avi', 'mov'];
    $max_size = 10 * 1024 * 1024; // 10MB
    $uploaded_files = [];
    
    // Create upload directory if not exists
    $upload_dir = '../../../uploads/assignments/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_count = count($files['name']);
    
    for ($i = 0; $i < $file_count; $i++) {
        $file_name = $files['name'][$i];
        $file_size = $files['size'][$i];
        $file_tmp = $files['tmp_name'][$i];
        $file_error = $files['error'][$i];
        
        if ($file_error !== UPLOAD_ERR_OK) {
            continue; // Skip files with errors
        }
        
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file type
        if (!in_array($file_ext, $allowed_types)) {
            return ['success' => false, 'message' => "Tipe file $file_name tidak didukung"];
        }
        
        // Validate file size
        if ($file_size > $max_size) {
            return ['success' => false, 'message' => "File $file_name terlalu besar (maksimal 10MB)"];
        }
        
        // Generate unique filename
        $new_filename = 'assignment_' . $assignment_id . '_' . time() . '_' . $i . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;
        $relative_path = 'uploads/assignments/' . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($file_tmp, $upload_path)) {
            // Insert into database
            $stmt = $pdo->prepare("
                INSERT INTO tugas_files (tugas_id, file_name, file_path, file_size, file_type, upload_order, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$assignment_id, $file_name, $relative_path, $file_size, $file_ext, $i + 1]);
            
            $uploaded_files[] = [
                'name' => $file_name,
                'path' => $relative_path,
                'size' => $file_size,
                'type' => $file_ext,
                'order' => $i + 1
            ];
        } else {
            return ['success' => false, 'message' => "Gagal mengupload file $file_name"];
        }
    }
    
    return ['success' => true, 'files' => $uploaded_files];
}
?>