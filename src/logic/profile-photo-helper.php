<?php
require_once 'koneksi.php';

// Function to get fresh user profile photo from database
function getFreshUserProfilePhoto($user_id) {
    $conn = getConnection();
    
    $sql = "SELECT fotoProfil FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result ? $result['fotoProfil'] : null;
}

// Function to get user profile photo URL
function getUserProfilePhotoUrl($user_id) {
    $fotoProfil = getFreshUserProfilePhoto($user_id);
    
    if ($fotoProfil && !empty($fotoProfil)) {
        // Check if it already contains the full path
        if (strpos($fotoProfil, 'uploads/profile/') === 0) {
            return '../../' . $fotoProfil;
        } else {
            return '../../uploads/profile/' . $fotoProfil;
        }
    }
    
    return null;
}
?>
