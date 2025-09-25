<?php
session_start();

// Koneksi database
require_once 'koneksi.php'; // Menggunakan file koneksi yang sudah ada

// Helper function untuk mengirim email OTP
function sendOTPEmail($email, $otp) {
    // Gunakan PHPMailer atau fungsi mail() sesuai setup
    // Untuk sementara kita return true, nanti bisa implementasi sesuai setup email
    
    $subject = "Kode OTP Reset Username - Point LMS";
    $message = "
    <html>
    <head>
        <title>Kode OTP Reset Username</title>
    </head>
    <body>
        <h2>Kode Verifikasi Username</h2>
        <p>Anda telah meminta reset username untuk akun Point LMS.</p>
        <p>Gunakan kode berikut untuk melanjutkan proses:</p>
        <h3 style='background-color: #f0f0f0; padding: 10px; text-align: center; font-size: 24px; letter-spacing: 5px; border-radius: 5px;'>$otp</h3>
        <p><strong>Catatan:</strong></p>
        <ul>
            <li>Kode ini berlaku selama 5 menit</li>
            <li>Jangan bagikan kode ini kepada siapapun</li>
            <li>Jika Anda tidak meminta reset username, abaikan email ini</li>
        </ul>
        <hr>
        <p style='font-size: 12px; color: #666;'>Email otomatis dari Point LMS</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Point LMS <noreply@pointlms.com>" . "\r\n";
    
    // Untuk development, kita bisa log OTP ke file atau return true
    // return mail($email, $subject, $message, $headers);
    
    // Untuk testing, simpan OTP ke session atau log file
    error_log("OTP for $email: $otp", 3, "/tmp/otp_log.txt");
    return true;
}

// Helper function untuk generate OTP
function generateOTP() {
    return sprintf("%06d", mt_rand(1, 999999));
}

// Helper function untuk validasi email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Helper function untuk validasi username
function isValidUsername($username) {
    return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{2,19}$/', $username);
}

// Proses berdasarkan action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Step 1: Verifikasi Email
    if (isset($_POST['verify_email'])) {
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            header('Location: ../front/forgot-username-step1.php?error=empty_email');
            exit();
        }
        
        if (!isValidEmail($email)) {
            header('Location: ../front/forgot-username-step1.php?error=invalid_email');
            exit();
        }
        
        // Cek apakah email ada di database
        try {
            $stmt = $koneksi->prepare("SELECT id, username FROM users WHERE email = ? AND status = 'active'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) {
                header('Location: ../front/forgot-username-step1.php?error=email_not_found');
                exit();
            }
            
            // Simpan email ke session dan redirect ke step 2
            $_SESSION['forgot_username_email'] = $email;
            $_SESSION['forgot_username_user_id'] = $user['id'];
            header('Location: ../front/forgot-username-step2.php');
            exit();
            
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            header('Location: ../front/forgot-username-step1.php?error=system_error');
            exit();
        }
    }
    
    // Step 2: Kirim OTP
    elseif (isset($_POST['send_otp'])) {
        $email = $_POST['email'];
        
        if (!isset($_SESSION['forgot_username_email']) || $_SESSION['forgot_username_email'] !== $email) {
            header('Location: ../front/forgot-username-step1.php');
            exit();
        }
        
        // Generate OTP
        $otp = generateOTP();
        $otpExpiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        // Simpan OTP ke database atau session
        try {
            // Hapus OTP lama jika ada
            $stmt = $koneksi->prepare("DELETE FROM password_resets WHERE email = ? AND type = 'username_reset'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            // Simpan OTP baru
            $stmt = $koneksi->prepare("INSERT INTO password_resets (email, token, expires_at, type, created_at) VALUES (?, ?, ?, 'username_reset', NOW())");
            $stmt->bind_param("sss", $email, $otp, $otpExpiry);
            $stmt->execute();
            
            // Kirim email OTP
            if (sendOTPEmail($email, $otp)) {
                $_SESSION['otp_sent'] = true;
                $_SESSION['otp_sent_time'] = time();
                header('Location: ../front/forgot-username-step3.php');
                exit();
            } else {
                header('Location: ../front/forgot-username-step2.php?error=send_failed');
                exit();
            }
            
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            header('Location: ../front/forgot-username-step2.php?error=system_error');
            exit();
        }
    }
    
    // Step 3: Verifikasi OTP
    elseif (isset($_POST['verify_otp'])) {
        $email = $_POST['email'];
        $otpCode = $_POST['otp_code'];
        
        if (!isset($_SESSION['forgot_username_email']) || $_SESSION['forgot_username_email'] !== $email) {
            header('Location: ../front/forgot-username-step1.php');
            exit();
        }
        
        if (empty($otpCode) || strlen($otpCode) !== 6) {
            header('Location: ../front/forgot-username-step3.php?error=empty_otp');
            exit();
        }
        
        try {
            // Verifikasi OTP
            $stmt = $koneksi->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND type = 'username_reset' AND expires_at > NOW()");
            $stmt->bind_param("ss", $email, $otpCode);
            $stmt->execute();
            $result = $stmt->get_result();
            $otpRecord = $result->fetch_assoc();
            
            if (!$otpRecord) {
                header('Location: ../front/forgot-username-step3.php?error=invalid_otp');
                exit();
            }
            
            // OTP valid, hapus dari database dan lanjut ke step 4
            $stmt = $koneksi->prepare("DELETE FROM password_resets WHERE id = ?");
            $stmt->bind_param("i", $otpRecord['id']);
            $stmt->execute();
            
            $_SESSION['otp_verified'] = true;
            header('Location: ../front/forgot-username-step4.php');
            exit();
            
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            header('Location: ../front/forgot-username-step3.php?error=system_error');
            exit();
        }
    }
    
    // Step 4: Update Username
    elseif (isset($_POST['update_username'])) {
        $email = $_POST['email'];
        $newUsername = trim($_POST['new_username']);
        
        if (!isset($_SESSION['forgot_username_email']) || $_SESSION['forgot_username_email'] !== $email || !isset($_SESSION['otp_verified'])) {
            header('Location: ../front/forgot-username-step1.php');
            exit();
        }
        
        if (empty($newUsername)) {
            header('Location: ../front/forgot-username-step4.php?error=empty_username');
            exit();
        }
        
        if (!isValidUsername($newUsername)) {
            header('Location: ../front/forgot-username-step4.php?error=invalid_username');
            exit();
        }
        
        try {
            // Cek apakah username sudah digunakan
            $stmt = $koneksi->prepare("SELECT id FROM users WHERE username = ? AND email != ?");
            $stmt->bind_param("ss", $newUsername, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->fetch_assoc()) {
                header('Location: ../front/forgot-username-step4.php?error=username_exists');
                exit();
            }
            
            // Update username
            $stmt = $koneksi->prepare("UPDATE users SET username = ?, updated_at = NOW() WHERE email = ?");
            $stmt->bind_param("ss", $newUsername, $email);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $_SESSION['new_username'] = $newUsername;
                header('Location: ../front/forgot-username-step5.php');
                exit();
            } else {
                header('Location: ../front/forgot-username-step4.php?error=update_failed');
                exit();
            }
            
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            header('Location: ../front/forgot-username-step4.php?error=system_error');
            exit();
        }
    }
    
    // Step 5: Verifikasi Final Username
    elseif (isset($_POST['verify_final_username'])) {
        $email = $_POST['email'];
        $verifyUsername = trim($_POST['verify_username']);
        $expectedUsername = $_POST['expected_username'];
        
        if (!isset($_SESSION['forgot_username_email']) || $_SESSION['forgot_username_email'] !== $email || !isset($_SESSION['new_username'])) {
            header('Location: ../front/forgot-username-step1.php');
            exit();
        }
        
        if (empty($verifyUsername)) {
            header('Location: ../front/forgot-username-step5.php?error=empty_username');
            exit();
        }
        
        if ($verifyUsername !== $expectedUsername) {
            header('Location: ../front/forgot-username-step5.php?error=wrong_username');
            exit();
        }
        
        // Semua verifikasi berhasil, bersihkan session dan redirect ke login
        unset($_SESSION['forgot_username_email']);
        unset($_SESSION['forgot_username_user_id']);
        unset($_SESSION['otp_sent']);
        unset($_SESSION['otp_sent_time']);
        unset($_SESSION['otp_verified']);
        unset($_SESSION['new_username']);
        
        // Redirect ke halaman login dengan pesan sukses
        header('Location: ../../login.php?success=username_updated&new_username=' . urlencode($expectedUsername));
        exit();
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // Handle resend OTP
    if (isset($_GET['action']) && $_GET['action'] === 'resend_otp') {
        if (!isset($_SESSION['forgot_username_email'])) {
            header('Location: ../front/forgot-username-step1.php');
            exit();
        }
        
        $email = $_SESSION['forgot_username_email'];
        
        // Generate OTP baru
        $otp = generateOTP();
        $otpExpiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        try {
            // Hapus OTP lama
            $stmt = $koneksi->prepare("DELETE FROM password_resets WHERE email = ? AND type = 'username_reset'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            // Simpan OTP baru
            $stmt = $koneksi->prepare("INSERT INTO password_resets (email, token, expires_at, type, created_at) VALUES (?, ?, ?, 'username_reset', NOW())");
            $stmt->bind_param("sss", $email, $otp, $otpExpiry);
            $stmt->execute();
            
            // Kirim email OTP
            if (sendOTPEmail($email, $otp)) {
                $_SESSION['otp_sent'] = true;
                $_SESSION['otp_sent_time'] = time();
                header('Location: ../front/forgot-username-step3.php?success=otp_sent');
                exit();
            } else {
                header('Location: ../front/forgot-username-step3.php?error=send_failed');
                exit();
            }
            
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            header('Location: ../front/forgot-username-step3.php?error=system_error');
            exit();
        }
    }
}

// Jika tidak ada action yang sesuai, redirect ke step 1
header('Location: ../front/forgot-username-step1.php');
exit();
?>