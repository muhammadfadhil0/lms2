<?php
// Database setup script for assignment functionality

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lms";

try {
    // Create PDO connection with socket path for LAMPP
    $dsn = "mysql:host=localhost;port=3306;dbname=$dbname;charset=utf8mb4;unix_socket=/opt/lampp/var/mysql/mysql.sock";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read SQL file
    $sql = file_get_contents('src/database/create-assignment-tables.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if ($pdo->exec($statement) === false) {
                $error = $pdo->errorInfo();
                throw new Exception("Error executing statement: " . $error[2]);
            }
        }
    }
    
    echo "✅ Database setup completed successfully!\n";
    echo "Assignment tables created:\n";
    echo "- tugas (assignments table)\n";
    echo "- pengumpulan_tugas (assignment submissions table)\n";
    echo "- Modified postingan_kelas table with assignment support\n";
    echo "\nYou can now use the assignment features!\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up database: " . $e->getMessage() . "\n";
    
    // Check if it's a connection error
    if (strpos($e->getMessage(), 'Connection refused') !== false || strpos($e->getMessage(), 'No such file') !== false) {
        echo "\n🔧 Troubleshooting tips:\n";
        echo "1. Make sure XAMPP/LAMPP is running\n";
        echo "2. Check if MySQL service is started\n";
        echo "3. Verify database credentials in the script\n";
        echo "4. Ensure database 'lms' exists\n";
    }
    exit(1);
}
?>
