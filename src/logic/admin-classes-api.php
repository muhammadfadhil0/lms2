<?php
session_start();
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

require_once 'koneksi.php';

try {
    $db = getPDOConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_class':
        getClass($db);
        break;
    case 'create_class':
        createClass($db);
        break;
    case 'update_class':
        updateClass($db);
        break;
    case 'update_class_field':
        updateClassField($db);
        break;
    case 'delete_class':
        deleteClass($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getClass($db) {
    $classId = $_POST['class_id'] ?? '';
    
    if (empty($classId)) {
        echo json_encode(['success' => false, 'message' => 'Class ID required']);
        return;
    }
    
    try {
        $sql = "
            SELECT 
                k.*,
                u.namaLengkap as nama_guru,
                u.email as email_guru
            FROM kelas k
            LEFT JOIN users u ON k.guru_id = u.id
            WHERE k.id = ?
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($class) {
            echo json_encode(['success' => true, 'data' => $class]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Class not found']);
        }
    } catch (Exception $e) {
        error_log("Get Class Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to get class data']);
    }
}

function createClass($db) {
    $namaKelas = $_POST['namaKelas'] ?? '';
    $mataPelajaran = $_POST['mataPelajaran'] ?? '';
    $kodeKelas = $_POST['kodeKelas'] ?? '';
    $idGuru = $_POST['guru_id'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $maxSiswa = $_POST['maxSiswa'] ?? 30;
    $status = $_POST['status'] ?? 'aktif';
    
    // Validation
    if (empty($namaKelas)) {
        echo json_encode(['success' => false, 'message' => 'Nama kelas tidak boleh kosong']);
        return;
    }
    
    if (empty($mataPelajaran)) {
        echo json_encode(['success' => false, 'message' => 'Mata pelajaran tidak boleh kosong']);
        return;
    }
    
    if (empty($kodeKelas)) {
        echo json_encode(['success' => false, 'message' => 'Kode kelas tidak boleh kosong']);
        return;
    }
    
    if (empty($idGuru)) {
        echo json_encode(['success' => false, 'message' => 'Guru pengajar harus dipilih']);
        return;
    }
    
    // Validate teacher exists
    try {
        $checkTeacher = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'guru'");
        $checkTeacher->execute([$idGuru]);
        if (!$checkTeacher->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Guru tidak valid']);
            return;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error validating teacher']);
        return;
    }
    
    // Check if kodeKelas already exists
    try {
        $checkKode = $db->prepare("SELECT id FROM kelas WHERE kodeKelas = ?");
        $checkKode->execute([$kodeKelas]);
        if ($checkKode->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Kode kelas sudah digunakan']);
            return;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error checking duplicate code']);
        return;
    }
    
    try {
        $sql = "
            INSERT INTO kelas (namaKelas, mataPelajaran, kodeKelas, guru_id, deskripsi, maxSiswa, status, dibuat, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$namaKelas, $mataPelajaran, $kodeKelas, $idGuru, $deskripsi, $maxSiswa, $status]);
        
        if ($result) {
            $classId = $db->lastInsertId();
            echo json_encode([
                'success' => true, 
                'message' => 'Kelas berhasil dibuat',
                'class_id' => $classId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal membuat kelas']);
        }
    } catch (Exception $e) {
        error_log("Create Class Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error creating class']);
    }
}

function updateClass($db) {
    $classId = $_POST['class_id'] ?? '';
    $namaKelas = $_POST['namaKelas'] ?? '';
    $mataPelajaran = $_POST['mataPelajaran'] ?? '';
    $kodeKelas = $_POST['kodeKelas'] ?? '';
    $idGuru = $_POST['guru_id'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $maxSiswa = $_POST['maxSiswa'] ?? 30;
    $status = $_POST['status'] ?? '';
    
    // Validation
    if (empty($classId)) {
        echo json_encode(['success' => false, 'message' => 'Class ID required']);
        return;
    }
    
    if (empty($namaKelas)) {
        echo json_encode(['success' => false, 'message' => 'Nama kelas tidak boleh kosong']);
        return;
    }
    
    if (empty($mataPelajaran)) {
        echo json_encode(['success' => false, 'message' => 'Mata pelajaran tidak boleh kosong']);
        return;
    }
    
    if (empty($kodeKelas)) {
        echo json_encode(['success' => false, 'message' => 'Kode kelas tidak boleh kosong']);
        return;
    }
    
    if (empty($idGuru)) {
        echo json_encode(['success' => false, 'message' => 'Guru pengajar harus dipilih']);
        return;
    }
    
    // Validate teacher exists
    try {
        $checkTeacher = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'guru'");
        $checkTeacher->execute([$idGuru]);
        if (!$checkTeacher->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Guru tidak valid']);
            return;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error validating teacher']);
        return;
    }
    
    // Check if kodeKelas already exists (excluding current class)
    try {
        $checkKode = $db->prepare("SELECT id FROM kelas WHERE kodeKelas = ? AND id != ?");
        $checkKode->execute([$kodeKelas, $classId]);
        if ($checkKode->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Kode kelas sudah digunakan']);
            return;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error checking duplicate code']);
        return;
    }
    
    try {
        $sql = "
            UPDATE kelas 
            SET namaKelas = ?, mataPelajaran = ?, kodeKelas = ?, guru_id = ?, deskripsi = ?, maxSiswa = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$namaKelas, $mataPelajaran, $kodeKelas, $idGuru, $deskripsi, $maxSiswa, $status, $classId]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Kelas berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui kelas']);
        }
    } catch (Exception $e) {
        error_log("Update Class Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating class']);
    }
}

function updateClassField($db) {
    $classId = $_POST['class_id'] ?? '';
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    
    // Validation
    if (empty($classId)) {
        echo json_encode(['success' => false, 'message' => 'Class ID required']);
        return;
    }
    
    $allowedFields = ['namaKelas', 'deskripsi', 'status'];
    if (!in_array($field, $allowedFields)) {
        echo json_encode(['success' => false, 'message' => 'Invalid field']);
        return;
    }
    
    // Field-specific validation
    if ($field === 'namaKelas' && empty($value)) {
        echo json_encode(['success' => false, 'message' => 'Nama kelas tidak boleh kosong']);
        return;
    }
    
    if ($field === 'status' && !in_array($value, ['aktif', 'nonaktif', 'arsip'])) {
        echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
        return;
    }
    
    try {
        // Check if class exists
        $checkClass = $db->prepare("SELECT id, guru_id FROM kelas WHERE id = ?");
        $checkClass->execute([$classId]);
        $class = $checkClass->fetch();
        
        if (!$class) {
            echo json_encode(['success' => false, 'message' => 'Kelas tidak ditemukan']);
            return;
        }
        
        $sql = "UPDATE kelas SET {$field} = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$value, $classId]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Field berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui field']);
        }
    } catch (Exception $e) {
        error_log("Update Class Field Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating field']);
    }
}

function deleteClass($db) {
    $classId = $_POST['class_id'] ?? '';
    
    if (empty($classId)) {
        echo json_encode(['success' => false, 'message' => 'Class ID required']);
        return;
    }
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Check if class exists
        $checkClass = $db->prepare("SELECT id FROM kelas WHERE id = ?");
        $checkClass->execute([$classId]);
        if (!$checkClass->fetch()) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Kelas tidak ditemukan']);
            return;
        }
        
        // Check if class has students
        $checkStudents = $db->prepare("SELECT COUNT(*) FROM kelas_siswa WHERE kelas_id = ? AND status = 'aktif'");
        $checkStudents->execute([$classId]);
        $studentCount = $checkStudents->fetchColumn();
        
        if ($studentCount > 0) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => "Tidak dapat menghapus kelas. Masih ada {$studentCount} siswa terdaftar."]);
            return;
        }
        
        // Check if class has assignments/exams
        $checkAssignments = $db->prepare("SELECT COUNT(*) FROM ujian WHERE kelas_id = ?");
        $checkAssignments->execute([$classId]);
        $assignmentCount = $checkAssignments->fetchColumn();
        
        if ($assignmentCount > 0) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => "Tidak dapat menghapus kelas. Masih ada {$assignmentCount} ujian."]);
            return;
        }
        
        // Delete related data first
        // Delete student enrollments
        $deleteStudents = $db->prepare("DELETE FROM kelas_siswa WHERE kelas_id = ?");
        $deleteStudents->execute([$classId]);
        
        // Delete class posts/materials
        $deletePosts = $db->prepare("DELETE FROM postingan WHERE kelas_id = ?");
        $deletePosts->execute([$classId]);
        
        // Delete the class
        $deleteClass = $db->prepare("DELETE FROM kelas WHERE id = ?");
        $result = $deleteClass->execute([$classId]);
        
        if ($result) {
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Kelas berhasil dihapus']);
        } else {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus kelas']);
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Delete Class Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error deleting class']);
    }
}

// Helper function to validate status values
function isValidStatus($status) {
    return in_array($status, ['aktif', 'nonaktif', 'draft']);
}

// Helper function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>