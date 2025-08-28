<?php
require_once 'src/logic/koneksi.php';

try {
    $conn = getConnection();
    if ($conn) {
        echo "Database connection successful!<br>";
        
        // Test basic query
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "Total users in database: " . $row['count'] . "<br>";
        }
        
        // Test kelas table
        $result = $conn->query("SELECT COUNT(*) as count FROM kelas");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "Total kelas in database: " . $row['count'] . "<br>";
        }
    } else {
        echo "Database connection failed!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
