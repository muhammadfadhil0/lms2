<?php
// Simple script to add columns to postingan_kelas table
$host = "localhost";
$username = "root";
$password = "";
$database = "lms";

$conn = new mysqli($host, $username, $password, $database, 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully to database.\n\n";

// Check if table exists first
$result = $conn->query("SHOW TABLES LIKE 'postingan_kelas'");
if ($result->num_rows == 0) {
    die("Table postingan_kelas does not exist!\n");
}

// Check current table structure
echo "Current table structure:\n";
$result = $conn->query("DESCRIBE postingan_kelas");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
echo "\n";

// Add is_edited column
echo "Adding is_edited column...\n";
$sql = "ALTER TABLE postingan_kelas ADD COLUMN is_edited TINYINT(1) DEFAULT 0";
if ($conn->query($sql) === TRUE) {
    echo "✓ Successfully added is_edited column\n";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "✓ Column is_edited already exists\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
}

// Add diupdate column
echo "Adding diupdate column...\n";
$sql = "ALTER TABLE postingan_kelas ADD COLUMN diupdate TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP";
if ($conn->query($sql) === TRUE) {
    echo "✓ Successfully added diupdate column\n";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "✓ Column diupdate already exists\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
}

echo "\nFinal table structure:\n";
$result = $conn->query("DESCRIBE postingan_kelas");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

$conn->close();
echo "\nDone!\n";
?>
