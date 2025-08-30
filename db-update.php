<!DOCTYPE html>
<html>
<head>
    <title>Database Update</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h2>Database Schema Update</h2>
    
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
        
        echo "<p class='success'>✓ Connected to database successfully</p>";
        
        // Check if table exists
        $result = $conn->query("SHOW TABLES LIKE 'postingan_kelas'");
        if ($result->num_rows == 0) {
            throw new Exception("Table postingan_kelas does not exist!");
        }
        
        echo "<p class='info'>Checking current table structure...</p>";
        
        // Check current columns
        $result = $conn->query("DESCRIBE postingan_kelas");
        $columns = [];
        while($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        echo "<p><strong>Current columns:</strong> " . implode(', ', $columns) . "</p>";
        
        // Add is_edited column if not exists
        if (!in_array('is_edited', $columns)) {
            echo "<p class='info'>Adding is_edited column...</p>";
            $sql = "ALTER TABLE postingan_kelas ADD COLUMN is_edited TINYINT(1) DEFAULT 0";
            if ($conn->query($sql) === TRUE) {
                echo "<p class='success'>✓ Successfully added is_edited column</p>";
            } else {
                echo "<p class='error'>✗ Error adding is_edited: " . $conn->error . "</p>";
            }
        } else {
            echo "<p class='success'>✓ Column is_edited already exists</p>";
        }
        
        // Add diupdate column if not exists
        if (!in_array('diupdate', $columns)) {
            echo "<p class='info'>Adding diupdate column...</p>";
            $sql = "ALTER TABLE postingan_kelas ADD COLUMN diupdate TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP";
            if ($conn->query($sql) === TRUE) {
                echo "<p class='success'>✓ Successfully added diupdate column</p>";
            } else {
                echo "<p class='error'>✗ Error adding diupdate: " . $conn->error . "</p>";
            }
        } else {
            echo "<p class='success'>✓ Column diupdate already exists</p>";
        }
        
        // Show final structure
        echo "<p class='info'>Final table structure:</p>";
        $result = $conn->query("DESCRIBE postingan_kelas");
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $conn->close();
        echo "<p class='success'><strong>Database update completed successfully!</strong></p>";
        
    } catch (Exception $e) {
        echo "<p class='error'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    }
    ?>
    
    <hr>
    <p><a href="src/front/kelas-guru.php">← Back to application</a></p>
</body>
</html>
