<?php
require_once __DIR__ . '/../logic/koneksi.php';

$koneksi = getConnection();
$table = 'jawaban_siswa';
$columnsNeeded = [
    'pilihanJawaban' => "VARCHAR(255) DEFAULT NULL",
    'benar' => "TINYINT(1) DEFAULT NULL",
    'poin' => "DOUBLE DEFAULT NULL",
    'waktuDijawab' => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP"
];

$existing = [];
$res = mysqli_query($koneksi, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $existing[$row['COLUMN_NAME']] = true;
    }
}

$queries = [];
foreach ($columnsNeeded as $col => $def) {
    if (!isset($existing[$col])) {
        $queries[] = "ALTER TABLE `$table` ADD COLUMN `$col` $def";
    }
}

if (empty($queries)) {
    echo "No changes needed. All columns exist.\n";
    exit(0);
}

foreach ($queries as $q) {
    if (mysqli_query($koneksi, $q)) {
        echo "Applied: $q\n";
    } else {
        echo "Failed: $q -> " . mysqli_error($koneksi) . "\n";
    }
}

echo "Migration finished.\n";
