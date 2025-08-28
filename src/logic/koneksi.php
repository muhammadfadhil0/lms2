<?php
$servername = "localhost";
$username ="root";
$password = "";
$dbname = "lms";

$koneksi = mysqli_connect($servername, $username, $password, $dbname);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Fungsi helper untuk mendapatkan koneksi database
function getConnection() {
    global $koneksi;
    return $koneksi;
}

// Set charset untuk menghindari masalah encoding
mysqli_set_charset($koneksi, "utf8mb4");