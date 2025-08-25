<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "koneksi.php";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Validasi input kosong
    if (empty($username) || empty($password)) {
        header("Location: ../index.php?error=empty");
        exit();
    }
    
    // Prepared statement untuk keamanan
    $stmt = $koneksi->prepare("SELECT id, username, password FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verifikasi password (gunakan password_verify jika password di-hash)
        if (password_verify($password, $user['password']) || $password == $user['password']) {
            // Login berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;

            // Redirect ke beranda
            header("Location: ../front/beranda-user.php");
            exit();
        } else {
            // Password salah
            header("Location: ../../index.php?error=invalid");
            exit();
        }
    } else {
        // Username tidak ditemukan
        header("Location: ../../index.php?error=invalid");
        exit();
    }
    
    $stmt->close();
} else {
    // Akses langsung ke file auth.php
    header("Location: ../index.php");
    exit();
}

$koneksi->close();
?>

