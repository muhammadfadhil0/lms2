<?php
session_start();
require_once 'user-logic.php';

// Function to verify reCAPTCHA
function verifyRecaptcha($response) {
    // Tentukan environment dan secret key yang sesuai
    $isLocalhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $_SERVER['HTTP_HOST'] === '127.0.0.1');
    $secret = $isLocalhost ? '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe' : '6LfWLcgrAAAAAAWXAlgm4rDXJ9f0mlkCWfQTWcbi';
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    
    $data = array(
        'secret' => $secret,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    );
    
    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        error_log('reCAPTCHA HTTP request failed');
        return false;
    }
    
    $responseData = json_decode($result, true);
    if (!is_array($responseData)) {
        error_log('reCAPTCHA invalid JSON response: ' . substr($result,0,200));
        return false;
    }
    
    // For reCAPTCHA v3, check success and score
    if ($responseData['success'] === true) {
        // Score threshold (0.5 is recommended, higher = more likely human)
        $score = isset($responseData['score']) ? $responseData['score'] : 0;
        
        // Log for debugging
        error_log("reCAPTCHA Score: " . $score . " for IP: " . $_SERVER['REMOTE_ADDR'] . " (Environment: " . ($isLocalhost ? 'localhost' : 'production') . ")");
        
        // Accept if score is above threshold or if using test keys for localhost
        return $score >= 0.5 || ($isLocalhost && $secret === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');
    }
    
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptchaResponse = isset($_POST['recaptcha_response']) ? $_POST['recaptcha_response'] : '';
    if (empty($recaptchaResponse)) {
        error_log('Empty recaptcha_response received in POST');
    }
    
    // Get registration data from session
    if (!isset($_SESSION['pending_registration'])) {
        header('Location: ../../login.php');
        exit();
    }
    
    $registrationData = $_SESSION['pending_registration'];
    
    // Check if session data is not too old (30 minutes limit)
    if (time() - $registrationData['timestamp'] > 1800) {
        unset($_SESSION['pending_registration']);
        header('Location: ../../login.php?error=session_expired');
        exit();
    }
    
    $email = $registrationData['email'];
    $username = $registrationData['username'];
    $namaLengkap = $registrationData['namaLengkap'];
    $password = $registrationData['password'];
    $role = $registrationData['role'];
    
    // Detect environment
    $isLocalhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $_SERVER['HTTP_HOST'] === '127.0.0.1');

    // Validate required fields (kecuali recaptchaResponse di localhost dev bypass)
    if (empty($email) || empty($username) || empty($namaLengkap) || empty($password)) {
        header('Location: ../front/verify-email.php?email=' . urlencode($email) . '&username=' . urlencode($username) . '&name=' . urlencode($namaLengkap) . '&role=' . urlencode($role) . '&error=system_error');
        exit();
    }

    $bypassRecaptcha = false;
    if ($isLocalhost && empty($recaptchaResponse)) {
        // Dev bypass: token kosong di localhost → lanjut dengan warning di log
        error_log('DEV BYPASS: Empty reCAPTCHA token on localhost. Continuing without verification.');
        $bypassRecaptcha = true;
    }
    
    // Verify reCAPTCHA
    if (!$bypassRecaptcha && !verifyRecaptcha($recaptchaResponse)) {
        error_log('reCAPTCHA verification failed for email ' . $email . ' token length=' . strlen($recaptchaResponse));
        header('Location: ../front/verify-email.php?email=' . urlencode($email) . '&username=' . urlencode($username) . '&name=' . urlencode($namaLengkap) . '&role=' . urlencode($role) . '&error=recaptcha_failed');
        exit();
    }
    
    try {
        // Create user account with active status
        $userLogic = new UserLogic();
        
        // Create user directly with 'aktif' status using original password
        $result = $userLogic->createVerifiedUser($email, $username, $namaLengkap, $password, $role);
        
        if ($result['success']) {
            // Clear pending registration data
            unset($_SESSION['pending_registration']);
            
            // Set session for auto-login
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['email'] = $email;
            $_SESSION['username'] = $username;
            $_SESSION['nama_lengkap'] = $namaLengkap;
            $_SESSION['role'] = $role;
            $_SESSION['status'] = 'aktif';
            
            // Log successful registration
            error_log("Registration successful for: " . $email . " with reCAPTCHA verification");
            
            // Redirect to dashboard
            header('Location: ../../login.php?welcome=1');
            exit();
            
        } else {
            // Log the error
            error_log("Registration failed for: " . $email . " - " . $result['message']);
            header('Location: ../front/verify-email.php?email=' . urlencode($email) . '&username=' . urlencode($username) . '&name=' . urlencode($namaLengkap) . '&role=' . urlencode($role) . '&error=system_error');
            exit();
        }
        
    } catch (Exception $e) {
        error_log("Exception during registration: " . $e->getMessage());
        header('Location: ../front/verify-email.php?email=' . urlencode($email) . '&username=' . urlencode($username) . '&name=' . urlencode($namaLengkap) . '&role=' . urlencode($role) . '&error=system_error');
        exit();
    }
    
} else {
    // Invalid request method
    header('Location: ../../login.php');
    exit();
}
?>