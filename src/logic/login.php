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
        
        // Redirect berdasarkan role
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

$koneksi->close();
?>

