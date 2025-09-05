<?php
session_start();
require_once 'settings-logic.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Disable error display untuk JSON response yang bersih
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Fungsi untuk mengirim response JSON
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    sendResponse(false, 'Anda harus login terlebih dahulu');
}

$user_id = $_SESSION['user']['id'];
$settingsLogic = new SettingsLogic();

// Validasi akses user
if (!$settingsLogic->validateUserAccess($user_id)) {
    sendResponse(false, 'Akses tidak diizinkan');
}

// Tentukan action berdasarkan request method dan parameter
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_profile':
            $profile = $settingsLogic->getProfilLengkap($user_id);
            if ($profile) {
                sendResponse(true, 'Data profil berhasil diambil', $profile);
            } else {
                sendResponse(false, 'Gagal mengambil data profil');
            }
            break;
            
        case 'update_profile':
            // Validasi method POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Method tidak diizinkan');
            }
            
            $data = [
                'namaLengkap' => trim($_POST['namaLengkap'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'bio' => trim($_POST['bio'] ?? ''),
                'nomorTelpon' => trim($_POST['nomorTelpon'] ?? ''),
                'tanggalLahir' => $_POST['tanggalLahir'] ?? null
            ];
            
            $result = $settingsLogic->updateProfil($user_id, $data);
            
            if ($result['success']) {
                $settingsLogic->logActivity($user_id, 'update_profile', 'User updated profile information');
            }
            
            sendResponse($result['success'], $result['message']);
            break;
            
        case 'update_username':
            // Validasi method POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Method tidak diizinkan');
            }
            
            $data = [
                'namaLengkap' => trim($_POST['namaLengkap'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'bio' => trim($_POST['bio'] ?? '')
            ];
            
            $result = $settingsLogic->updateUsernameInfo($user_id, $data);
            
            if ($result['success']) {
                $settingsLogic->logActivity($user_id, 'update_username', 'User updated username and personal info');
            }
            
            sendResponse($result['success'], $result['message']);
            break;
            
        case 'update_contact':
            // Validasi method POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Method tidak diizinkan');
            }
            
            $data = [
                'email' => trim($_POST['email'] ?? ''),
                'nomorTelpon' => trim($_POST['nomorTelpon'] ?? ''),
                'tanggalLahir' => $_POST['tanggalLahir'] ?? null
            ];
            
            $result = $settingsLogic->updateContactInfo($user_id, $data);
            
            if ($result['success']) {
                $settingsLogic->logActivity($user_id, 'update_contact', 'User updated contact information');
            }
            
            sendResponse($result['success'], $result['message']);
            break;
            
        case 'upload_photo':
            // Validasi method POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Method tidak diizinkan');
            }
            
            // Validasi file upload
            if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
                $errorMsg = 'File tidak ditemukan atau error upload';
                
                if (isset($_FILES['profile_photo']['error'])) {
                    $error_code = $_FILES['profile_photo']['error'];
                    switch ($error_code) {
                        case UPLOAD_ERR_INI_SIZE:
                            $errorMsg = 'File terlalu besar (melebihi upload_max_filesize)';
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $errorMsg = 'File terlalu besar (melebihi MAX_FILE_SIZE)';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $errorMsg = 'File hanya terupload sebagian';
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $errorMsg = 'Tidak ada file yang diupload';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $errorMsg = 'Tidak ada folder temporary';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $errorMsg = 'Gagal menulis file ke disk';
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $errorMsg = 'Upload dihentikan oleh extension';
                            break;
                        default:
                            $errorMsg = 'Unknown upload error (code: ' . $error_code . ')';
                            break;
                    }
                }
                
                sendResponse(false, $errorMsg);
            }
            
            // Upload menggunakan SettingsLogic
            $result = $settingsLogic->uploadFotoProfil($user_id, $_FILES['profile_photo']);
            
            if ($result['success']) {
                $settingsLogic->logActivity($user_id, 'upload_photo', 'User uploaded profile photo');
            }
            
            sendResponse($result['success'], $result['message'], $result);
            break;
            
        case 'delete_photo':
            // Validasi method POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Method tidak diizinkan');
            }
            
            $result = $settingsLogic->deleteFotoProfil($user_id);
            
            if ($result['success']) {
                $settingsLogic->logActivity($user_id, 'delete_photo', 'User deleted profile photo');
            }
            
            sendResponse($result['success'], $result['message']);
            break;
            
        case 'change_password':
            // Validasi method POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Method tidak diizinkan');
            }
            
            $passwordLama = $_POST['password_lama'] ?? '';
            $passwordBaru = $_POST['password_baru'] ?? '';
            $konfirmasiPassword = $_POST['konfirmasi_password'] ?? '';
            
            $result = $settingsLogic->gantiPassword($user_id, $passwordLama, $passwordBaru, $konfirmasiPassword);
            
            if ($result['success']) {
                $settingsLogic->logActivity($user_id, 'change_password', 'User changed password');
            }
            
            sendResponse($result['success'], $result['message']);
            break;
            
        case 'get_security_stats':
            $stats = $settingsLogic->getStatistikKeamanan($user_id);
            sendResponse(true, 'Statistik keamanan berhasil diambil', $stats);
            break;
            
        case 'check_username':
            $username = trim($_GET['username'] ?? '');
            if (empty($username)) {
                sendResponse(false, 'Username tidak boleh kosong');
            }
            
            $isAvailable = $settingsLogic->checkUsernameAvailability($user_id, $username);
            sendResponse(true, 'Username check completed', ['available' => $isAvailable]);
            break;
            
        case 'check_email':
            $email = trim($_GET['email'] ?? '');
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendResponse(false, 'Email tidak valid');
            }
            
            $isAvailable = $settingsLogic->checkEmailAvailability($user_id, $email);
            sendResponse(true, 'Email check completed', ['available' => $isAvailable]);
            break;
            
        default:
            sendResponse(false, 'Action tidak valid');
            break;
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Settings API Error: " . $e->getMessage());
    sendResponse(false, 'Terjadi kesalahan sistem');
}
