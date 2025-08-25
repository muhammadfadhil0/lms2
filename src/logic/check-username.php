<?php
header('Content-Type: application/json');

// Include database connection
require "koneksi.php";

if (isset($_POST['username'])) {
    $username = trim($_POST['username']);
    
    if (empty($username)) {
        echo json_encode(['status' => 'empty']);
        exit();
    }
    
    try {
        $stmt = $koneksi->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'unavailable']);
        } else {
            echo json_encode(['status' => 'available']);
        }
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error']);
    }
} else {
    echo json_encode(['status' => 'error']);
}
?>
