<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Assignment Grading Diagnostic</h1>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

// Check if files exist
$files_to_check = [
    'src/logic/get-submission-details.php',
    'src/logic/grade-submission.php', 
    'src/logic/get-assignment-report.php',
    'src/front/assignment-reports.php'
];

echo "<h2>File Existence Check</h2>";
foreach ($files_to_check as $file) {
    $path = "/opt/lampp/htdocs/lms/$file";
    echo "<p>$file: " . (file_exists($path) ? '✅ Exists' : '❌ Missing') . "</p>";
}

// Check database connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once 'src/logic/koneksi.php';
    echo "<p>✅ Database connection successful</p>";
    
    // Check table structures
    echo "<h3>Table Structure Verification</h3>";
    
    $tables = ['pengumpulan_tugas', 'tugas', 'users', 'kelas'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "<p>✅ Table $table: $count records</p>";
        } catch (Exception $e) {
            echo "<p>❌ Table $table: Error - " . $e->getMessage() . "</p>";
        }
    }
    
    // Check for recent submissions
    echo "<h3>Recent Submissions Check</h3>";
    try {
        $stmt = $pdo->query("
            SELECT 
                pt.id,
                pt.assignment_id,
                pt.siswa_id,
                pt.file_path,
                pt.status,
                u.namaLengkap,
                t.judul
            FROM pengumpulan_tugas pt
            JOIN users u ON pt.siswa_id = u.id
            JOIN tugas t ON pt.assignment_id = t.id
            ORDER BY pt.tanggal_pengumpulan DESC
            LIMIT 3
        ");
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($submissions) {
            echo "<p>✅ Found " . count($submissions) . " recent submissions:</p>";
            foreach ($submissions as $sub) {
                echo "<p>ID: {$sub['id']}, Student: {$sub['namaLengkap']}, Assignment: {$sub['judul']}, Status: {$sub['status']}</p>";
            }
        } else {
            echo "<p>❌ No submissions found</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking submissions: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Check PHP error log
echo "<h2>Recent PHP Errors</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $errors = file_get_contents($error_log);
    $recent_errors = explode("\n", $errors);
    $recent_errors = array_slice($recent_errors, -10); // Last 10 lines
    
    echo "<pre>";
    foreach ($recent_errors as $error) {
        if (strpos($error, 'get-submission-details') !== false || 
            strpos($error, 'grade-submission') !== false ||
            strpos($error, 'assignment') !== false) {
            echo htmlspecialchars($error) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>Error log not accessible or not configured</p>";
}

// Test specific functions with error handling
echo "<h2>Function Test with Error Handling</h2>";

// Create a minimal test session
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'role' => 'guru'];
}

// Test get-submission-details with a known ID
if (isset($submissions) && count($submissions) > 0) {
    $test_id = $submissions[0]['id'];
    echo "<h3>Testing with submission ID: $test_id</h3>";
    
    // Test get-submission-details
    echo "<h4>Testing get-submission-details.php</h4>";
    $url = "http://localhost/lms/src/logic/get-submission-details.php?submission_id=$test_id";
    
    $context = stream_context_create([
        'http' => [
            'header' => "Cookie: " . session_name() . "=" . session_id() . "\r\n"
        ]
    ]);
    
    $result = file_get_contents($url, false, $context);
    echo "<p>Response:</p>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    
    $json = json_decode($result, true);
    if ($json && $json['success']) {
        echo "<p style='color: green;'>✅ API call successful</p>";
    } else {
        echo "<p style='color: red;'>❌ API call failed</p>";
    }
}

echo "<h2>Browser Console Instructions</h2>";
echo "<p>To debug in assignment reports page:</p>";
echo "<ol>";
echo "<li>Go to assignment reports page</li>";
echo "<li>Open browser console (F12)</li>";
echo "<li>Click on a submission's 'Nilai' button</li>";
echo "<li>Check console for any JavaScript errors</li>";
echo "<li>Check Network tab for failed API calls</li>";
echo "</ol>";
?>
