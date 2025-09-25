<?php
session_start();
require_once 'koneksi.php';
require_once 'notification-logic.php';
require_once 'kelas-logic.php';

header('Content-Type: application/json');

// Disable error display in production (but still log errors)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

error_log("✏️ [DEBUG] Starting create assignment process");
error_log("✏️ [DEBUG] POST data: " . json_encode($_POST));
error_log("✏️ [DEBUG] FILES data: " . json_encode($_FILES));

try {
    $kelas_id = $_POST['kelas_id'] ?? null;
    $guru_id = $_SESSION['user']['id'];
    $judul = $_POST['assignmentTitle'] ?? null;
    $deskripsi = $_POST['assignmentDescription'] ?? null;
    $deadline = $_POST['assignmentDeadline'] ?? null;
    $nilai_maksimal = $_POST['maxScore'] ?? null;
    
    error_log("✏️ [DEBUG] Extracted data - kelas_id: $kelas_id, judul: $judul, guru_id: $guru_id");
    
    // Validate input
    if (empty($kelas_id) || empty($judul) || empty($deskripsi) || empty($deadline) || empty($nilai_maksimal)) {
        error_log("✏️ [DEBUG] Validation failed - missing required fields");
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi', 'debug' => $_POST]);
        exit();
    }
    
    // Verify that the teacher owns this class
    $stmt = $pdo->prepare("SELECT id FROM kelas WHERE id = ? AND guru_id = ?");
    $stmt->execute([$kelas_id, $guru_id]);
    if (!$stmt->fetch()) {
        error_log("✏️ [DEBUG] Access denied - teacher doesn't own class $kelas_id");
        echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses ke kelas ini']);
        exit();
    }
    error_log("✏️ [DEBUG] Class ownership verified - teacher $guru_id owns class $kelas_id");
    
    // Handle multiple file uploads if present
    $uploaded_files = [];
    error_log("✏️ [DEBUG] Checking for uploaded files...");

    if (isset($_FILES['assignment_files']) && is_array($_FILES['assignment_files']['name'])) {
        error_log("✏️ [DEBUG] Found assignment files to process: " . count($_FILES['assignment_files']['name']) . " files");
        $upload_dir = '../../uploads/assignments/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                echo json_encode(['success' => false, 'message' => 'Gagal membuat direktori upload']);
                exit();
            }
        }
        
        $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'xls', 'xlsx', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'avi', 'mov'];
        $max_files = 4;
        $max_file_size = 15 * 1024 * 1024; // 15MB
        
        $file_count = count($_FILES['assignment_files']['name']);
        if ($file_count > $max_files) {
            echo json_encode(['success' => false, 'message' => "Maksimal $max_files file yang dapat diupload"]);
            exit();
        }
        
        for ($i = 0; $i < $file_count; $i++) {
            $file_name = $_FILES['assignment_files']['name'][$i];
            $file_tmp = $_FILES['assignment_files']['tmp_name'][$i];
            $file_size = $_FILES['assignment_files']['size'][$i];
            $file_error = $_FILES['assignment_files']['error'][$i];
            
            error_log("✏️ [DEBUG] Processing file $i: $file_name, size: $file_size, error: $file_error");
            
            if ($file_error !== UPLOAD_ERR_OK) {
                $error_messages = [
                    UPLOAD_ERR_INI_SIZE => "File terlalu besar (melebihi batas server)",
                    UPLOAD_ERR_FORM_SIZE => "File terlalu besar (melebihi batas form)", 
                    UPLOAD_ERR_PARTIAL => "File hanya terupload sebagian",
                    UPLOAD_ERR_NO_FILE => "Tidak ada file yang diupload",
                    UPLOAD_ERR_NO_TMP_DIR => "Folder temporary tidak ditemukan",
                    UPLOAD_ERR_CANT_WRITE => "Gagal menulis file ke disk",
                    UPLOAD_ERR_EXTENSION => "Upload dihentikan oleh ekstensi PHP"
                ];
                $error_msg = $error_messages[$file_error] ?? "Error upload tidak dikenal ($file_error)";
                error_log("✏️ [DEBUG] File upload error: $error_msg for file: $file_name");
                
                echo json_encode(['success' => false, 'message' => "Error upload file $file_name: $error_msg"]);
                exit();
            }
            
            if (empty($file_name)) {
                continue; // Skip empty files
            }
            
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            
            if (!in_array(strtolower($file_extension), $allowed_extensions)) {
                echo json_encode(['success' => false, 'message' => "Format file $file_name tidak didukung"]);
                exit();
            }
            
            if ($file_size > $max_file_size) {
                echo json_encode(['success' => false, 'message' => "File $file_name terlalu besar (maksimal 15MB)"]);
                exit();
            }
            
            $filename = uniqid() . '_' . $file_name;
            $full_file_path = $upload_dir . $filename;
            
            if (move_uploaded_file($file_tmp, $full_file_path)) {
                error_log("✏️ [DEBUG] File uploaded successfully: $full_file_path");
                $uploaded_files[] = [
                    'name' => $file_name,
                    'path' => 'uploads/assignments/' . $filename,
                    'size' => $file_size,
                    'type' => $file_extension,
                    'order' => $i + 1
                ];
            }
        }
    }
    
    // Insert assignment (keep file_path for backward compatibility - use first file or null)
    $first_file_path = !empty($uploaded_files) ? $uploaded_files[0]['path'] : null;
    error_log("✏️ [DEBUG] Inserting assignment into database...");
    $stmt = $pdo->prepare("
        INSERT INTO tugas (kelas_id, judul, deskripsi, file_path, deadline, nilai_maksimal, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$kelas_id, $judul, $deskripsi, $first_file_path, $deadline, $nilai_maksimal]);
    
    $assignment_id = $pdo->lastInsertId();
    error_log("✏️ [DEBUG] Assignment created with ID: $assignment_id");
    
    // Insert multiple files into tugas_files table
    if (!empty($uploaded_files)) {
        error_log("✏️ [DEBUG] Inserting " . count($uploaded_files) . " files into tugas_files table");
        
        $stmt = $pdo->prepare("
            INSERT INTO tugas_files (tugas_id, file_name, file_path, file_size, file_type, upload_order, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        foreach ($uploaded_files as $file) {
            $stmt->execute([
                $assignment_id, 
                $file['name'], 
                $file['path'], 
                $file['size'], 
                $file['type'], 
                $file['order']
            ]);
            error_log("✏️ [DEBUG] File inserted: " . $file['name']);
        }
    } else {
        error_log("✏️ [DEBUG] No files to insert into tugas_files table");
    }
    
    // Create a special post for this assignment - only description in content
    $konten_post = "{$deskripsi}";
    error_log("✏️ [DEBUG] Creating postingan for assignment ID: $assignment_id");
    
    $stmt = $pdo->prepare("
        INSERT INTO postingan_kelas (kelas_id, user_id, konten, tipe_postingan, assignment_id, dibuat) 
        VALUES (?, ?, ?, 'assignment', ?, NOW())
    ");
    $stmt->execute([$kelas_id, $guru_id, $konten_post, $assignment_id]);
    
    $postingan_id = $pdo->lastInsertId();
    error_log("✏️ [DEBUG] Postingan created with ID: $postingan_id");
    
    // Send notification to all students in the class
    $notificationLogic = new NotificationLogic();
    $kelasLogic = new KelasLogic();
    
    // Get class info
    $kelasStmt = $pdo->prepare("SELECT namaKelas FROM kelas WHERE id = ?");
    $kelasStmt->execute([$kelas_id]);
    $kelasInfo = $kelasStmt->fetch();
    $className = $kelasInfo['namaKelas'] ?? 'Unknown Class';
    
    // Get all students in this class
    $siswaList = $kelasLogic->getSiswaKelas($kelas_id);
    
    if ($siswaList && count($siswaList) > 0) {
        foreach ($siswaList as $siswa) {
            // Create notification for each student
            $notificationLogic->createTugasBaruNotification(
                $siswa['id'],
                $judul,
                $className,
                $assignment_id,
                $kelas_id
            );
        }
        error_log("✏️ [DEBUG] Sent tugas baru notifications to " . count($siswaList) . " students");
    } else {
        error_log("✏️ [DEBUG] No students found in class $kelas_id, no notifications sent");
    }
    
    error_log("✏️ [DEBUG] Assignment creation completed successfully - Assignment ID: $assignment_id, Postingan ID: $postingan_id");
    echo json_encode(['success' => true, 'message' => 'Tugas berhasil dibuat', 'assignment_id' => $assignment_id]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
