<?php
session_start();

// debug 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include UserLogic
require_once "user-logic.php";

if (isset($_POST['register'])) {
    $userLogic = new UserLogic();
    
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $namaLengkap = trim($_POST['namaLengkap'] ?? $_POST['full_name'] ?? '');
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? 'siswa'; // Default role siswa

    // Validate empty fields
    if (empty($email) || empty($username) || empty($password) || empty($confirm_password) || empty($namaLengkap)) {
        header("Location: ../front/register.php?error=empty");
        exit();
    }
    
    // Validate password match
    if ($password !== $confirm_password) {
        header("Location: ../front/register.php?error=password_mismatch");
        exit();
    }
    
    // Validate password length
    if (strlen($password) < 6) {
        header("Location: ../front/register.php?error=password_short");
        exit();
    }
    
    // Register user using UserLogic
    $result = $userLogic->register($username, $email, $password, $namaLengkap, $role);
    
    if ($result['success']) {
        header("Location: ../front/register.php?success=1");
        exit();
    } else {
        // Parse error message for specific redirects
        if (strpos($result['message'], 'Username') !== false) {
            header("Location: ../front/register.php?error=username_exists");
        } elseif (strpos($result['message'], 'Email') !== false) {
            header("Location: ../front/register.php?error=email_exists");
        } else {
            header("Location: ../front/register.php?error=registration_failed");
        }
        exit();
    }
} else {
    // Redirect if accessed without POST
    header("Location: ../front/register.php");
    exit();
}
?>