<?php
// Demo login untuk testing settings feature
session_start();

// Set demo user data ke session
$_SESSION['user'] = [
    'id' => 25,
    'username' => 'siswa',
    'email' => 'siswa@gmail.com',
    'namaLengkap' => 'siswa',
    'role' => 'siswa',
    'status' => 'aktif'
];

echo "<h1>Demo Login Berhasil</h1>";
echo "<p>User demo telah login dengan data:</p>";
echo "<ul>";
echo "<li>ID: " . $_SESSION['user']['id'] . "</li>";
echo "<li>Username: " . $_SESSION['user']['username'] . "</li>";
echo "<li>Email: " . $_SESSION['user']['email'] . "</li>";
echo "<li>Nama: " . $_SESSION['user']['namaLengkap'] . "</li>";
echo "<li>Role: " . $_SESSION['user']['role'] . "</li>";
echo "</ul>";

echo "<p><a href='settings.php' style='background-color: #ff6347; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Buka Settings Page</a></p>";
echo "<p><a href='test-settings.php'>Test Settings Feature</a></p>";
?>
