<?php
$servername = "localhost";
$username ="root";
$password = "";
$dbname = "lms";

// MySQLi connection (for existing code)
$koneksi = mysqli_connect($servername, $username, $password, $dbname, 3306, '/opt/lampp/var/mysql/mysql.sock');

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// PDO connection (for new assignment features)
try {
    $dsn = "mysql:host=localhost;port=3306;dbname=$dbname;charset=utf8mb4;unix_socket=/opt/lampp/var/mysql/mysql.sock";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}

// Fungsi helper untuk mendapatkan koneksi database
function getConnection() {
    global $koneksi;
    return $koneksi;
}

// Fungsi helper untuk mendapatkan PDO connection
function getPDOConnection() {
    global $pdo;
    return $pdo;
}

// Set charset untuk menghindari masalah encoding
mysqli_set_charset($koneksi, "utf8mb4");