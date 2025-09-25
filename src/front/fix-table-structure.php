<?php
require_once '../logic/koneksi.php';

echo "<h1>🔍 Cek Struktur Tabel Users - Debugging Error</h1>";

$conn = getConnection();

if (!$conn) {
    die("❌ Koneksi database gagal: " . mysqli_connect_error());
}

echo "<h2>1. Struktur Tabel Users</h2>";
$sql_structure = "DESCRIBE users";
$result = mysqli_query($conn, $sql_structure);

if (!$result) {
    echo "❌ Error: " . mysqli_error($conn) . "<br>";
    echo "🔧 Coba cek apakah tabel users ada:<br>";
    
    $sql_tables = "SHOW TABLES";
    $tables_result = mysqli_query($conn, $sql_tables);
    echo "<h3>Tabel yang ada di database:</h3>";
    while ($table = mysqli_fetch_row($tables_result)) {
        echo "- " . $table[0] . "<br>";
    }
    exit();
}

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
echo "</tr>";

$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
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
echo "</table>";

echo "<h2>2. Analisis Kolom</h2>";
echo "📊 Total kolom: " . count($columns) . "<br>";
echo "📝 Daftar kolom: " . implode(', ', $columns) . "<br><br>";

// Cek kolom-kolom penting
$required_columns = [
    'id' => '✅ Primary key',
    'username' => '👤 Username user', 
    'email' => '📧 Email user',
    'password' => '🔒 Password hash',
    'nama' => '📝 Nama user (versi 1)',
    'namaLengkap' => '📝 Nama user (versi 2)', 
    'role' => '👥 Role (admin/guru/siswa)',
    'status' => '🔄 Status user',
    'tanggal_registrasi' => '📅 Tanggal daftar',
    'created_at' => '📅 Waktu dibuat (alternatif)',
    'updated_at' => '📅 Waktu update',
    'fotoProfil' => '🖼️ Foto profil'
];

echo "<h3>Status Kolom Penting:</h3>";
echo "<ul>";
foreach ($required_columns as $col => $desc) {
    if (in_array($col, $columns)) {
        echo "<li>✅ <strong>$col</strong> - $desc</li>";
    } else {
        echo "<li>❌ <strong>$col</strong> - $desc <em>(TIDAK ADA)</em></li>";
    }
}
echo "</ul>";

// Buat query yang benar berdasarkan kolom yang ada
echo "<h2>3. Query yang Benar</h2>";

// Tentukan kolom nama
$name_field = 'username'; // default fallback
if (in_array('namaLengkap', $columns)) {
    $name_field = 'namaLengkap';
} elseif (in_array('nama', $columns)) {
    $name_field = 'nama';
}

// Tentukan kolom tanggal
$date_field = 'id'; // fallback ke ID jika tidak ada tanggal
if (in_array('tanggal_registrasi', $columns)) {
    $date_field = 'tanggal_registrasi';
} elseif (in_array('created_at', $columns)) {
    $date_field = 'created_at';
}

echo "🔧 Kolom nama yang digunakan: <strong>$name_field</strong><br>";
echo "🔧 Kolom tanggal yang digunakan: <strong>$date_field</strong><br><br>";

// Buat query yang aman
$safe_query = "SELECT u.id, 
                      u.username,
                      u.email,
                      u.$name_field as nama,
                      u.role";

// Tambahkan kolom opsional jika ada                      
if (in_array('status', $columns)) {
    $safe_query .= ",\n                      COALESCE(u.status, 'active') as status";
} else {
    $safe_query .= ",\n                      'active' as status";
}

if (in_array('fotoProfil', $columns)) {
    $safe_query .= ",\n                      u.fotoProfil";
} else {
    $safe_query .= ",\n                      NULL as fotoProfil";
}

if ($date_field !== 'id') {
    $safe_query .= ",\n                      u.$date_field as tanggal_registrasi";
} else {
    $safe_query .= ",\n                      NOW() as tanggal_registrasi";
}

$safe_query .= "\nFROM users u 
ORDER BY u.id DESC 
LIMIT 5";

echo "<h3>Query yang Aman:</h3>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
echo htmlspecialchars($safe_query);
echo "</pre>";

// Test query
echo "<h2>4. Test Query</h2>";
$test_result = mysqli_query($conn, $safe_query);

if (!$test_result) {
    echo "❌ Error: " . mysqli_error($conn) . "<br>";
} else {
    $users = mysqli_fetch_all($test_result, MYSQLI_ASSOC);
    echo "✅ Query berhasil! Ditemukan: <strong>" . count($users) . "</strong> user<br><br>";
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Username</th><th>Email</th><th>Nama</th><th>Role</th><th>Status</th><th>Foto</th><th>Registrasi</th>";
        echo "</tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . ($user['username'] ?? 'NULL') . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['nama'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . $user['status'] . "</td>";
            echo "<td>" . ($user['fotoProfil'] ?? 'NULL') . "</td>";
            echo "<td>" . $user['tanggal_registrasi'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3>🎉 BERHASIL!</h3>";
        echo "<p>Query berhasil menampilkan data users. Sekarang akan memperbaiki UserLogic class dan admin-users.php dengan query yang benar.</p>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3>⚠️ Data Kosong</h3>";
        echo "<p>Query berhasil tapi tabel users kosong. <a href='populate-users.php'>Klik di sini untuk populate data test</a></p>";
        echo "</div>";
    }
}

echo "<hr>";
echo "<p>";
echo "<a href='admin-users.php' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>← Test Admin Users</a> ";
echo "<a href='populate-users.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-left: 10px;'>Populate Data</a>";
echo "</p>";
?>

<style>
    body { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        margin: 20px; 
        line-height: 1.6; 
        background-color: #f8f9fa;
    }
    h1 { 
        color: #333; 
        border-bottom: 3px solid #007bff; 
        padding-bottom: 10px; 
    }
    h2 { 
        color: #007bff; 
        margin-top: 30px; 
        border-left: 4px solid #007bff;
        padding-left: 15px;
    }
    h3 { 
        color: #6c757d; 
    }
    table { 
        border-collapse: collapse; 
        margin: 10px 0; 
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    th, td { 
        border: 1px solid #ddd; 
        padding: 12px 8px; 
        text-align: left; 
    }
    th { 
        background-color: #f8f9fa; 
        font-weight: bold; 
        color: #495057;
    }
    pre, code { 
        background: #f8f9fa; 
        border: 1px solid #e9ecef;
        border-radius: 4px; 
        font-family: 'Courier New', monospace; 
        font-size: 14px;
    }
    ul { 
        background: white; 
        padding: 15px 30px; 
        border-radius: 5px; 
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    li { 
        margin: 5px 0; 
    }
</style>