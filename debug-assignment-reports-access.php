<?php
// Quick debug script for assignment reports
session_start();

// Check session
echo "=== SESSION DEBUG ===\n";
if (isset($_SESSION['user'])) {
    echo "✅ User logged in: ID=" . $_SESSION['user']['id'] . ", Role=" . $_SESSION['user']['role'] . "\n";
} else {
    echo "❌ No user session found\n";
}

// Test assignment reports URL
echo "\n=== URL PARAMETERS ===\n";
if (isset($_GET['id'])) {
    echo "✅ Kelas ID: " . $_GET['id'] . "\n";
} else {
    echo "❌ No kelas ID in URL\n";
}

if (isset($_GET['assignment_id'])) {
    echo "✅ Assignment ID: " . $_GET['assignment_id'] . "\n";
} else {
    echo "⚠️ No assignment ID in URL (optional)\n";
}

// Test assignment-reports.php access
echo "\n=== TESTING ASSIGNMENT REPORTS ACCESS ===\n";

try {
    // Simulate what assignment-reports.php does
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
        throw new Exception("Not authorized as guru");
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("Kelas ID not provided or invalid");
    }

    require_once 'src/logic/kelas-logic.php';
    
    $kelasLogic = new KelasLogic();
    $kelas_id = intval($_GET['id']);
    $guru_id = $_SESSION['user']['id'];

    // Get class details
    $detailKelas = $kelasLogic->getDetailKelas($kelas_id);

    // Check if class exists and belongs to this guru
    if (!$detailKelas || $detailKelas['guru_id'] != $guru_id) {
        throw new Exception("Class not found or access denied. Class guru_id: " . ($detailKelas['guru_id'] ?? 'NULL') . ", Current user: $guru_id");
    }

    echo "✅ Assignment reports access test passed\n";
    echo "Class: " . $detailKelas['namaKelas'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Assignment reports access failed: " . $e->getMessage() . "\n";
}

echo "\n=== RECOMMENDATION ===\n";
echo "Please check:\n";
echo "1. Are you logged in as a guru?\n";
echo "2. Is the URL correct? Should be: assignment-reports.php?id=KELAS_ID\n";
echo "3. Do you own the class you're trying to access?\n";
?>
