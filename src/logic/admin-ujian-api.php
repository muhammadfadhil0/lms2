<?php
session_start();
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'ujian-logic.php';

try {
    $ujianLogic = new UjianLogic();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'get_ujian':
            $ujian_id = (int)($_POST['ujian_id'] ?? 0);
            
            if ($ujian_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID ujian tidak valid']);
                break;
            }

            $ujian = $ujianLogic->getUjianByIdAdmin($ujian_id);
            
            if ($ujian) {
                echo json_encode(['success' => true, 'data' => $ujian]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ujian tidak ditemukan']);
            }
            break;

        case 'create_ujian':
            $required_fields = ['judul', 'mata_pelajaran', 'kelas_id', 'guru_id', 'durasi', 'status'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Field wajib tidak boleh kosong: ' . implode(', ', $missing_fields)
                ]);
                break;
            }

            // Validate durasi
            $durasi = (int)$_POST['durasi'];
            if ($durasi < 1) {
                echo json_encode(['success' => false, 'message' => 'Durasi harus minimal 1 menit']);
                break;
            }

            // Validate guru_id exists
            $guru_id = (int)$_POST['guru_id'];
            $teachers = $ujianLogic->getTeachers();
            $guru_exists = false;
            foreach ($teachers as $teacher) {
                if ($teacher['id'] == $guru_id) {
                    $guru_exists = true;
                    break;
                }
            }
            
            if (!$guru_exists) {
                echo json_encode(['success' => false, 'message' => 'Guru tidak ditemukan']);
                break;
            }

            // Validate kelas_id exists
            $kelas_id = (int)$_POST['kelas_id'];
            
            $data = [
                'judul' => trim($_POST['judul']),
                'mata_pelajaran' => trim($_POST['mata_pelajaran']),
                'kelas_id' => $kelas_id,
                'guru_id' => $guru_id,
                'deskripsi' => trim($_POST['deskripsi'] ?? ''),
                'durasi' => $durasi,
                'status' => $_POST['status'],
                'tanggal_mulai' => !empty($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : null
            ];

            $result = $ujianLogic->createUjianAdmin($data);
            echo json_encode($result);
            break;

        case 'update_ujian':
            $ujian_id = (int)($_POST['ujian_id'] ?? 0);
            
            if ($ujian_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID ujian tidak valid']);
                break;
            }

            $required_fields = ['judul', 'mata_pelajaran', 'kelas_id', 'guru_id', 'durasi', 'status'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Field wajib tidak boleh kosong: ' . implode(', ', $missing_fields)
                ]);
                break;
            }

            // Validate durasi
            $durasi = (int)$_POST['durasi'];
            if ($durasi < 1) {
                echo json_encode(['success' => false, 'message' => 'Durasi harus minimal 1 menit']);
                break;
            }

            // Validate guru_id exists
            $guru_id = (int)$_POST['guru_id'];
            $teachers = $ujianLogic->getTeachers();
            $guru_exists = false;
            foreach ($teachers as $teacher) {
                if ($teacher['id'] == $guru_id) {
                    $guru_exists = true;
                    break;
                }
            }
            
            if (!$guru_exists) {
                echo json_encode(['success' => false, 'message' => 'Guru tidak ditemukan']);
                break;
            }

            // Validate kelas_id exists
            $kelas_id = (int)$_POST['kelas_id'];

            $data = [
                'judul' => trim($_POST['judul']),
                'mata_pelajaran' => trim($_POST['mata_pelajaran']),
                'kelas_id' => $kelas_id,
                'guru_id' => $guru_id,
                'deskripsi' => trim($_POST['deskripsi'] ?? ''),
                'durasi' => $durasi,
                'status' => $_POST['status'],
                'tanggal_mulai' => !empty($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : null
            ];

            $result = $ujianLogic->updateUjianAdmin($ujian_id, $data);
            echo json_encode($result);
            break;

        case 'delete_ujian':
            $ujian_id = (int)($_POST['ujian_id'] ?? 0);
            
            if ($ujian_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID ujian tidak valid']);
                break;
            }

            $result = $ujianLogic->deleteUjianAdmin($ujian_id);
            echo json_encode($result);
            break;

        case 'update_field':
            $ujian_id = (int)($_POST['ujian_id'] ?? 0);
            $field = $_POST['field'] ?? '';
            $value = $_POST['value'] ?? '';
            
            if ($ujian_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID ujian tidak valid']);
                break;
            }
            
            if (empty($field)) {
                echo json_encode(['success' => false, 'message' => 'Field tidak boleh kosong']);
                break;
            }

            // Special validation for specific fields
            if ($field === 'durasi') {
                $value = (int)$value;
                if ($value < 1) {
                    echo json_encode(['success' => false, 'message' => 'Durasi harus minimal 1 menit']);
                    break;
                }
            }

            if ($field === 'status' && !in_array($value, ['draft', 'aktif', 'selesai', 'arsip'])) {
                echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
                break;
            }

            $result = $ujianLogic->updateField($ujian_id, $field, $value);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
            break;
    }

} catch (Exception $e) {
    error_log('Admin Ujian API Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
    ]);
}
?>