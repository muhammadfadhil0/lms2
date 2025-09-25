<?php
require_once '../logic/koneksi.php';

echo "<h1>Cek Struktur Tabel Users</h1>";

$conn = getConnection();

// 1. Cek struktur tabel
echo "<h2>1. Struktur Kolom Tabel Users</h2>";
$sql_desc = "DESCRIBE users";
$result_desc = mysqli_query($conn, $sql_desc);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
echo "</tr>";

$columns = [];
while ($row = mysqli_fetch_assoc($result_desc)) {
    echo "<tr>";
    echo "<td><strong>" . $row['Field'] . "</strong></td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . ($row['Extra'] ?? '') . "</td>";
    echo "</tr>";
    
    $columns[] = $row['Field'];
}
echo "</table><br>";

// 2. Cek apakah ada kolom namaLengkap atau nama
echo "<h2>2. Pengecekan Kolom Nama</h2>";
$name_columns = [];
if (in_array('namaLengkap', $columns)) {
    echo "✅ Kolom 'namaLengkap' ditemukan<br>";
    $name_columns[] = 'namaLengkap';
}
if (in_array('nama', $columns)) {
    echo "✅ Kolom 'nama' ditemukan<br>";
    $name_columns[] = 'nama';
}
if (in_array('name', $columns)) {
    echo "✅ Kolom 'name' ditemukan<br>";
    $name_columns[] = 'name';
}

if (empty($name_columns)) {
    echo "❌ Tidak ada kolom untuk nama yang ditemukan!<br>";
} else {
    echo "📊 Kolom nama yang tersedia: " . implode(', ', $name_columns) . "<br>";
}

// 3. Cek sample data
echo "<h2>3. Sample Data (3 user pertama)</h2>";
$sql_sample = "SELECT * FROM users LIMIT 3";
$result_sample = mysqli_query($conn, $sql_sample);

if (mysqli_num_rows($result_sample) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    
    // Header
    echo "<tr style='background: #f0f0f0;'>";
    foreach ($columns as $col) {
        echo "<th>$col</th>";
    }
    echo "</tr>";
    
    // Data
    while ($row = mysqli_fetch_assoc($result_sample)) {
        echo "<tr>";
        foreach ($columns as $col) {
            $value = $row[$col] ?? 'NULL';
            if (strlen($value) > 30) {
                $value = substr($value, 0, 30) . '...';
            }
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Tidak ada data users<br>";
}

// 4. Test query yang diperbaiki
echo "<h2>4. Test Query Diperbaiki</h2>";

// Tentukan kolom nama yang akan digunakan
$name_field = 'namaLengkap';
if (!in_array('namaLengkap', $columns)) {
    if (in_array('nama', $columns)) {
        $name_field = 'nama';
    } elseif (in_array('name', $columns)) {
        $name_field = 'name';
    }
}

echo "🔧 Menggunakan kolom nama: <strong>$name_field</strong><br><br>";

// Query yang diperbaiki
$sql_fixed = "SELECT u.id, 
                     u.username,
                     u.email,
                     u.$name_field as nama,
                     u.role,
                     COALESCE(u.status, 'active') as status,
                     u.tanggal_registrasi,
                     u.fotoProfil
              FROM users u 
              WHERE 1=1
              ORDER BY u.id DESC 
              LIMIT 10";

echo "<p>Query yang diperbaiki:</p>";
echo "<code style='background: #f5f5f5; padding: 10px; display: block; white-space: pre-line; font-family: monospace;'>";
echo htmlspecialchars($sql_fixed);
echo "</code>";

$result_fixed = mysqli_query($conn, $sql_fixed);

if (!$result_fixed) {
    echo "❌ Error: " . mysqli_error($conn) . "<br>";
} else {
    $users_fixed = mysqli_fetch_all($result_fixed, MYSQLI_ASSOC);
    echo "✅ Query berhasil! Ditemukan: " . count($users_fixed) . " user<br><br>";
    
    if (count($users_fixed) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Username</th><th>Email</th><th>Nama</th><th>Role</th><th>Status</th><th>Registrasi</th>";
        echo "</tr>";
        
        foreach ($users_fixed as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . ($user['username'] ?? 'NULL') . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['nama'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . $user['status'] . "</td>";
            echo "<td>" . $user['tanggal_registrasi'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 20px 0;'>";
        echo "<h3>✅ SOLUSI DITEMUKAN!</h3>";
        echo "<p>Query diperbaiki berhasil menampilkan data. Akan memperbaiki UserLogic class...</p>";
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><a href='admin-users.php'>← Test Admin Users</a> | ";
echo "<a href='debug-database.php'>Debug Database</a></p>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    h1 { color: #333; }
    h2 { color: #007bff; margin-top: 30px; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f8f9fa; font-weight: bold; }
    code { background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; }
</style>