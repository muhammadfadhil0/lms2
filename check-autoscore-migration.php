<?php
require_once 'src/logic/koneksi.php';

try {
    $conn = getConnection();
    
    // Show current ujian table structure first
    echo "Current ujian table structure:\n";
    $result = $conn->query("SHOW COLUMNS FROM ujian");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // Check if showScore column exists
    if (!in_array('showScore', $columns)) {
        echo "\nshowScore column not found, adding it...\n";
        $conn->query("ALTER TABLE ujian ADD COLUMN showScore TINYINT(1) NOT NULL DEFAULT 1");
        echo "showScore column added successfully!\n";
    } else {
        echo "\nshowScore column already exists.\n";
    }
    
    // Check if shuffleQuestions column exists
    if (!in_array('shuffleQuestions', $columns)) {
        echo "shuffleQuestions column not found, adding it...\n";
        $conn->query("ALTER TABLE ujian ADD COLUMN shuffleQuestions TINYINT(1) NOT NULL DEFAULT 0");
        echo "shuffleQuestions column added successfully!\n";
    } else {
        echo "shuffleQuestions column already exists.\n";
    }
    
    // Check if autoScore column exists
    if (!in_array('autoScore', $columns)) {
        echo "autoScore column not found, adding it...\n";
        $conn->query("ALTER TABLE ujian ADD COLUMN autoScore TINYINT(1) NOT NULL DEFAULT 0");
        echo "autoScore column added successfully!\n";
    } else {
        echo "autoScore column already exists.\n";
    }
    
    // Check existing ujian records
    echo "\nExisting ujian records:\n";
    $result = $conn->query("SELECT id, namaUjian, COALESCE(autoScore, 0) as autoScore FROM ujian LIMIT 5");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "- ID: " . $row['id'] . ", Name: " . $row['namaUjian'] . ", AutoScore: " . $row['autoScore'] . "\n";
        }
    } else {
        echo "No ujian records found.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
