<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "lms";

try {
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>Debug Gambar Postingan</h2>";
    
    // Get sample data from postingan_gambar table
    $result = $conn->query("SELECT * FROM postingan_gambar ORDER BY id DESC LIMIT 10");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Postingan ID</th><th>Nama File</th><th>Path Gambar</th><th>Preview</th></tr>";
        
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['postingan_id'] . "</td>";
            echo "<td>" . $row['nama_file'] . "</td>";
            echo "<td>" . $row['path_gambar'] . "</td>";
            
            // Preview with different path attempts
            $originalPath = $row['path_gambar'];
            $fixedPath = $originalPath;
            
            // Remove duplicate uploads/ if exists
            if (strpos($fixedPath, 'uploads/') === 0) {
                $fixedPath = substr($fixedPath, 8);
            }
            
            echo "<td>";
            echo "<p>Original: uploads/" . $originalPath . "</p>";
            echo "<img src='uploads/" . $originalPath . "' width='100' onerror='this.style.display=\"none\"'>";
            echo "<p>Fixed: uploads/" . $fixedPath . "</p>";
            echo "<img src='uploads/" . $fixedPath . "' width='100' onerror='this.style.display=\"none\"'>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Tidak ada data gambar postingan ditemukan.</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
table { margin: 20px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
img { margin: 5px 0; }
</style>
