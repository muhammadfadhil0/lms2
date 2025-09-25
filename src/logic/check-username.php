<?php
header('Content-Type: application/json');

// Include database connection
require "koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    
    // Validasi format username
    if (empty($username) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{2,19}$/', $username)) {
        echo json_encode(['available' => false, 'message' => 'Format username tidak valid']);
        exit();
    }
    
    try {
        // Cek apakah username sudah digunakan
        $stmt = $koneksi->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['available' => false, 'message' => 'Username sudah digunakan']);
        } else {
            echo json_encode(['available' => true, 'message' => 'Username tersedia']);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['available' => false, 'message' => 'Terjadi kesalahan sistem']);
    }
} else {
    echo json_encode(['available' => false, 'message' => 'Request tidak valid']);
}
?>
