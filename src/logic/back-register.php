<?php
// Jangan start session jika sudah ada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

    // Log untuk debugging - data input
    error_log("Registration attempt - Email: $email, Username: $username, Name: $namaLengkap, Role: $role", 3, "/tmp/registration_debug.log");

    // Validate empty fields
    if (empty($email) || empty($username) || empty($password) || empty($confirm_password) || empty($namaLengkap)) {
        error_log("Registration failed - empty fields", 3, "/tmp/registration_debug.log");
        header("Location: ../front/register.php?role=" . urlencode($role) . "&error=empty");
        exit();
    }
    
    // Validate password match
    if ($password !== $confirm_password) {
        header("Location: ../front/register.php?role=" . urlencode($role) . "&error=password_mismatch");
        exit();
    }
    
    // Validate password length
    if (strlen($password) < 6) {
        header("Location: ../front/register.php?role=" . urlencode($role) . "&error=password_short");
        exit();
    }
    
    // Validate basic input data only, create user after reCAPTCHA verification
    // Don't create user yet, just validate and redirect to reCAPTCHA
    
    // Check if username already exists
    if ($userLogic->isUsernameExists($username)) {
        error_log("Registration failed - username exists: $username", 3, "/tmp/registration_debug.log");
        header("Location: ../front/register.php?role=" . urlencode($role) . "&error=username_exists");
        exit();
    }
    
    // Check if email already exists
    if ($userLogic->isEmailExists($email)) {
        error_log("Registration failed - email exists: $email", 3, "/tmp/registration_debug.log");
        header("Location: ../front/register.php?role=" . urlencode($role) . "&error=email_exists");
        exit();
    }
    
    // Store registration data in session temporarily
    $_SESSION['pending_registration'] = [
        'email' => $email,
        'username' => $username,
        'namaLengkap' => $namaLengkap,
        'password' => $password,
        'role' => $role,
        'timestamp' => time()
    ];
    
    error_log("Redirecting to reCAPTCHA verification for: $email", 3, "/tmp/registration_debug.log");
    
    // Redirect to reCAPTCHA verification page
    header("Location: ../front/verify-email.php?email=" . urlencode($email) . "&username=" . urlencode($username) . "&name=" . urlencode($namaLengkap) . "&role=" . urlencode($role));
    exit();
} else {
    // Redirect if accessed without POST
    header("Location: ../front/register.php");
    exit();
}
?>