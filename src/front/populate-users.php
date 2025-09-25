<?php
require_once '../logic/koneksi.php';

echo "<h2>Populate Test Users</h2>";

// Check if users table is empty
$result = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users");
$row = mysqli_fetch_assoc($result);
$currentCount = $row['total'];

echo "Current user count: " . $currentCount . "<br><br>";

// Sample users data
$testUsers = [
    [
        'namaLengkap' => 'Administrator',
        'email' => 'admin@lms.com',
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin',
        'status' => 'active'
    ],
    [
        'namaLengkap' => 'Budi Santoso',
        'email' => 'budi.guru@lms.com',
        'username' => 'budi_guru',
        'password' => password_hash('guru123', PASSWORD_DEFAULT),
        'role' => 'guru',
        'status' => 'active'
    ],
    [
        'namaLengkap' => 'Siti Nurhaliza',
        'email' => 'siti.guru@lms.com',
        'username' => 'siti_guru',
        'password' => password_hash('guru123', PASSWORD_DEFAULT),
        'role' => 'guru',
        'status' => 'active'
    ],
    [
        'namaLengkap' => 'Ahmad Siswa',
        'email' => 'ahmad.siswa@lms.com',
        'username' => 'ahmad_siswa',
        'password' => password_hash('siswa123', PASSWORD_DEFAULT),
        'role' => 'siswa',
        'status' => 'active'
    ],
    [
        'namaLengkap' => 'Rina Permata',
        'email' => 'rina.siswa@lms.com',
        'username' => 'rina_siswa',
        'password' => password_hash('siswa123', PASSWORD_DEFAULT),
        'role' => 'siswa',
        'status' => 'active'
    ],
    [
        'namaLengkap' => 'Deni Kurniawan',
        'email' => 'deni.siswa@lms.com',
        'username' => 'deni_siswa',
        'password' => password_hash('siswa123', PASSWORD_DEFAULT),
        'role' => 'siswa',
        'status' => 'inactive'
    ]
];

$insertCount = 0;
$errorCount = 0;

foreach ($testUsers as $user) {
    // Check if email already exists
    $checkEmail = mysqli_query($koneksi, "SELECT id FROM users WHERE email = '{$user['email']}'");
    if (mysqli_num_rows($checkEmail) > 0) {
        echo "⚠️ User with email {$user['email']} already exists, skipping...<br>";
        continue;
    }
    
    $sql = "INSERT INTO users (namaLengkap, email, username, password, role, status, tanggal_registrasi, email_verified) 
            VALUES ('{$user['namaLengkap']}', '{$user['email']}', '{$user['username']}', '{$user['password']}', '{$user['role']}', '{$user['status']}', NOW(), 1)";
    
    if (mysqli_query($koneksi, $sql)) {
        echo "✅ Created user: {$user['namaLengkap']} ({$user['email']}) - {$user['role']}<br>";
        $insertCount++;
    } else {
        echo "❌ Failed to create user {$user['namaLengkap']}: " . mysqli_error($koneksi) . "<br>";
        $errorCount++;
    }
}

echo "<br><strong>Summary:</strong><br>";
echo "- Users created: $insertCount<br>";
echo "- Errors: $errorCount<br>";

// Show final count
$result = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users");
$row = mysqli_fetch_assoc($result);
echo "- Total users now: " . $row['total'] . "<br>";

echo "<br><br>";
echo "<h3>Test Credentials:</h3>";
echo "<strong>Admin:</strong> admin@lms.com / admin123<br>";
echo "<strong>Guru:</strong> budi.guru@lms.com / guru123<br>";
echo "<strong>Siswa:</strong> ahmad.siswa@lms.com / siswa123<br>";

echo "<br><br>";
echo "<a href='admin-users.php' target='_blank'>🔗 Open Admin Users Page</a><br>";
echo "<a href='test-users.php' target='_blank'>🔗 Run User Test</a><br>";
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
a { color: #f97316; text-decoration: none; font-weight: bold; }
a:hover { text-decoration: underline; }
</style>