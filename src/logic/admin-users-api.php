<?php
session_start();
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

require_once 'user-logic.php';

$userLogic = new UserLogic();
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_user':
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            break;
        }
        
        $user = $userLogic->getUserForAdmin($userId);
        if ($user) {
            echo json_encode(['success' => true, 'data' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
        break;
        
    case 'create_user':
        $data = [
            'nama' => trim($_POST['nama'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role' => $_POST['role'] ?? '',
            'status' => $_POST['status'] ?? 'active'
        ];
        
        // Validate required fields
        if (empty($data['nama']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
            echo json_encode(['success' => false, 'message' => 'Semua field wajib harus diisi']);
            break;
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
            break;
        }
        
        // Validate password length
        if (strlen($data['password']) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
            break;
        }
        
        // Validate role
        $allowed_roles = ['admin', 'guru', 'siswa'];
        if (!in_array($data['role'], $allowed_roles)) {
            echo json_encode(['success' => false, 'message' => 'Role tidak valid']);
            break;
        }
        
        $result = $userLogic->createUser($data);
        echo json_encode($result);
        break;
        
    case 'update_user':
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            break;
        }
        
        $data = [
            'nama' => trim($_POST['nama'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role' => $_POST['role'] ?? '',
            'status' => $_POST['status'] ?? 'active'
        ];
        
        // Validate required fields
        if (empty($data['nama']) || empty($data['email']) || empty($data['role'])) {
            echo json_encode(['success' => false, 'message' => 'Nama, email, dan role wajib diisi']);
            break;
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
            break;
        }
        
        // Validate password length if provided
        if (!empty($data['password']) && strlen($data['password']) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
            break;
        }
        
        // Validate role
        $allowed_roles = ['admin', 'guru', 'siswa'];
        if (!in_array($data['role'], $allowed_roles)) {
            echo json_encode(['success' => false, 'message' => 'Role tidak valid']);
            break;
        }
        
        $result = $userLogic->updateUser($userId, $data);
        echo json_encode($result);
        break;
        
    case 'toggle_status':
        $userId = (int)($_POST['user_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            break;
        }
        
        // Don't allow admin to deactivate themselves
        if ($userId === $_SESSION['user']['id'] && $status !== 'active') {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat menonaktifkan akun sendiri']);
            break;
        }
        
        $allowed_statuses = ['active', 'inactive', 'pending'];
        if (!in_array($status, $allowed_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
            break;
        }
        
        $result = $userLogic->toggleUserStatus($userId, $status);
        echo json_encode($result);
        break;
        
    case 'delete_user':
        $userId = (int)($_POST['user_id'] ?? 0);
        
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            break;
        }
        
        // Don't allow admin to delete themselves
        if ($userId === $_SESSION['user']['id']) {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri']);
            break;
        }
        
        $result = $userLogic->deleteUser($userId);
        echo json_encode($result);
        break;

    case 'update_user_field':
        $userId = (int)($_POST['user_id'] ?? 0);
        $field = $_POST['field'] ?? '';
        $value = trim($_POST['value'] ?? '');
        
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            break;
        }
        
        // Define allowed fields for inline editing
        $allowed_fields = ['nama', 'email', 'role', 'status', 'pro_status'];
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['success' => false, 'message' => 'Field tidak diizinkan untuk diedit']);
            break;
        }
        
        // Validate value based on field
        switch ($field) {
            case 'nama':
                if (empty($value)) {
                    echo json_encode(['success' => false, 'message' => 'Nama tidak boleh kosong']);
                    exit();
                }
                break;
                
            case 'email':
                if (empty($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Email tidak valid']);
                    exit();
                }
                break;
                
            case 'role':
                $allowed_roles = ['admin', 'guru', 'siswa'];
                if (!in_array($value, $allowed_roles)) {
                    echo json_encode(['success' => false, 'message' => 'Role tidak valid']);
                    exit();
                }
                break;
                
            case 'status':
                $allowed_statuses = ['aktif', 'nonaktif', 'pending', 'unverified'];
                if (!in_array($value, $allowed_statuses)) {
                    echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
                    exit();
                }
                
                // Don't allow admin to deactivate themselves
                if ($userId === $_SESSION['user']['id'] && $value !== 'aktif') {
                    echo json_encode(['success' => false, 'message' => 'Tidak dapat menonaktifkan akun sendiri']);
                    exit();
                }
                break;
                
            case 'pro_status':
                $allowed_pro_status = ['free', 'pro'];
                if (!in_array($value, $allowed_pro_status)) {
                    echo json_encode(['success' => false, 'message' => 'Pro status tidak valid']);
                    exit();
                }
                break;
        }
        
        $result = $userLogic->updateUserField($userId, $field, $value);
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>