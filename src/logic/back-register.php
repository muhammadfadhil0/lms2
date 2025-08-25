<?php

// debug 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require "koneksi.php"; // Adjust path as needed

if (isset($_POST['register'])) {
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    

    // Validate empty fields
    if (empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        header("Location: ../../src/front/register.php?error=empty");
        exit();
    }
    
    // Validate password match
    if ($password !== $confirm_password) {
        header("Location: ../../src/front/register.php?error=password_mismatch");
        exit();
    }
    
    try {
        // Check if username already exists
        $stmt = $koneksi->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            header("Location: ../../src/front/register.php?error=username_exists");
            exit();
        }
        $stmt->close();
        
        // Check if email already exists
        $stmt = $koneksi->prepare("SELECT id FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            header("Location: ../../src/front/register.php?error=email_exists");
            exit();
        }
        $stmt->close();
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $koneksi->prepare("INSERT INTO user (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);
        $result = $stmt->execute();
        
        if ($result) {
            header("Location: ../../index.php?success=registration");
            exit();
        } else {
            header("Location: ../../src/front/register.php?error=registration_failed");
            exit();
        }
        $stmt->close();
        
    } catch (Exception $e) {
        // Log error and redirect
        error_log("Registration error: " . $e->getMessage());
        header("Location: ../../src/front/register.php?error=registration_failed");
        exit();
    }
} else {
    // Redirect if accessed without POST
    header("Location: ../../src/front/register.php");
    exit();
}
?>