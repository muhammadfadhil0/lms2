<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "user-logic.php";

if (isset($_POST['login'])) {
    $userLogic = new UserLogic();
    
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Validasi input kosong
    if (empty($username) || empty($password)) {
        header("Location: ../../login.php?error=empty");
        exit();
    }
    
    // Login using UserLogic
    $result = $userLogic->login($username, $password);
    
    if ($result['success']) {
        // Login berhasil - simpan data user ke session
        $_SESSION['user'] = $result['user'];
        $_SESSION['login_time'] = time();
        
        // Check if there's a redirect parameter for shared post
        if (isset($_GET['redirect']) && $_GET['redirect'] === 'shared-post' && 
            isset($_GET['post']) && isset($_GET['kelas'])) {
            $postId = intval($_GET['post']);
            $kelasId = intval($_GET['kelas']);
            $userId = $result['user']['id'];
            $userRole = $result['user']['role'];
            
            // Check if user has access to the class or is admin/guru
            require_once 'koneksi.php';
            $conn = getConnection();
            
            $hasAccess = false;
            
            if ($userRole === 'admin') {
                // Admin has access to all classes
                $hasAccess = true;
            } elseif ($userRole === 'guru') {
                // Check if guru teaches this class
                $sql = "SELECT id FROM kelas WHERE id = ? AND guru_id = ? AND status = 'aktif'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $kelasId, $userId);
                $stmt->execute();
                $result_check = $stmt->get_result();
                $hasAccess = $result_check->num_rows > 0;
            } elseif ($userRole === 'siswa') {
                // Check if siswa is enrolled in this class
                $sql = "SELECT id FROM kelas_siswa WHERE kelas_id = ? AND siswa_id = ? AND status = 'aktif'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $kelasId, $userId);
                $stmt->execute();
                $result_check = $stmt->get_result();
                $hasAccess = $result_check->num_rows > 0;
            }
            
            if ($hasAccess) {
                // User has access, redirect to class
                header("Location: ../front/kelas-user.php?id=" . $kelasId);
                exit();
            }
            // If no access, continue to default redirect
        }
        
        // Default redirect berdasarkan role
        switch ($result['user']['role']) {
            case 'admin':
                header("Location: ../front/beranda-admin.php");
                break;
            case 'guru':
                header("Location: ../front/beranda-guru.php");
                break;
            case 'siswa':
                header("Location: ../front/beranda-user.php");
                break;
            default:
                header("Location: ../front/beranda-user.php");
        }
        exit();
    } else {
        // Login gagal
        if (isset($result['requires_verification']) && $result['requires_verification']) {
            // User belum verifikasi email
            header("Location: ../../login.php?error=email_not_verified&email=" . urlencode($result['email']));
        } elseif (strpos($result['message'], 'tidak ditemukan') !== false) {
            header("Location: ../../login.php?error=user_not_found");
        } elseif (strpos($result['message'], 'Password') !== false) {
            header("Location: ../../login.php?error=wrong_password");
        } elseif (strpos($result['message'], 'aktif') !== false) {
            header("Location: ../../login.php?error=account_inactive");
        } else {
            header("Location: ../../login.php?error=login_failed");
        }
        exit();
    }
} else {
    // Akses langsung ke file login.php
    header("Location: ../../login.php");
    exit();
}
?>

