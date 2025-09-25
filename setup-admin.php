<?php
session_start();

// Debug session
echo "<h2>Current Session:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if logged in as admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo "<h3>Not logged in as admin. Need to set admin session.</h3>";
    
    // Check if we have admin in database
    require_once 'src/logic/koneksi.php';
    
    $admin_query = "SELECT * FROM users WHERE role = 'admin' LIMIT 1";
    $result = $koneksi->query($admin_query);
    
    if ($result && $admin = $result->fetch_assoc()) {
        echo "<h4>Found admin user:</h4>";
        echo "<pre>";
        print_r($admin);
        echo "</pre>";
        
        // Set session for testing
        $_SESSION['user'] = [
            'id' => $admin['id'],
            'namaLengkap' => $admin['namaLengkap'],
            'email' => $admin['email'],
            'role' => $admin['role']
        ];
        $_SESSION['id'] = $admin['id'];
        $_SESSION['role'] = $admin['role'];
        $_SESSION['namaLengkap'] = $admin['namaLengkap'];
        
        echo "<h4>Session set! Admin logged in for testing.</h4>";
        echo '<a href="src/front/admin-settings.php">Go to Admin Settings</a>';
    } else {
        echo "<h4>No admin user found in database!</h4>";
        
        // Create admin user for testing
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $create_admin = "INSERT INTO users (namaLengkap, email, password, role, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $koneksi->prepare($create_admin);
        $nama = 'Administrator';
        $email = 'admin@lms.com';
        $role = 'admin';
        $status = 'aktif';
        
        if ($stmt && $stmt->bind_param('sssss', $nama, $email, $password, $role, $status) && $stmt->execute()) {
            echo "<h4>Admin user created successfully!</h4>";
            echo "<p>Email: admin@lms.com</p>";
            echo "<p>Password: admin123</p>";
            
            // Set session
            $admin_id = $koneksi->insert_id;
            $_SESSION['user'] = [
                'id' => $admin_id,
                'namaLengkap' => $nama,
                'email' => $email,
                'role' => $role
            ];
            $_SESSION['id'] = $admin_id;
            $_SESSION['role'] = $role;
            $_SESSION['namaLengkap'] = $nama;
            
            echo '<a href="src/front/admin-settings.php">Go to Admin Settings</a>';
        } else {
            echo "<h4>Failed to create admin user!</h4>";
        }
    }
} else {
    echo "<h3>✅ Logged in as admin!</h3>";
    echo '<a href="src/front/admin-settings.php">Go to Admin Settings</a>';
}
?>