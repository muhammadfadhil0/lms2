<?php
session_start();

// Set up a test session for debugging
if (!isset($_SESSION['user'])) {
    // Create a temporary guru session for testing
    $_SESSION['user'] = [
        'id' => 1, // Assume user ID 1 is a guru
        'role' => 'guru',
        'namaLengkap' => 'Test Guru'
    ];
    echo "<p style='color: orange;'>⚠️ Created temporary guru session for testing</p>";
}

require_once 'src/logic/koneksi.php';

echo "<h2>Assignment Grading Debug Test</h2>";

// Test 1: Check if we can connect to database
echo "<h3>1. Database Connection Test</h3>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color: green;'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Get a sample submission
echo "<h3>2. Sample Submission Test</h3>";
try {
    $stmt = $pdo->query("
        SELECT 
            pt.id as submission_id,
            pt.assignment_id,
            pt.siswa_id,
            pt.file_path,
            pt.status,
            pt.nilai,
            u.namaLengkap,
            t.judul as assignment_title
        FROM pengumpulan_tugas pt
        JOIN users u ON pt.siswa_id = u.id  
        JOIN tugas t ON pt.assignment_id = t.id
        LIMIT 1
    ");
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($submission) {
        echo "<p style='color: green;'>✅ Sample submission found:</p>";
        echo "<pre>" . print_r($submission, true) . "</pre>";
        
        $test_submission_id = $submission['submission_id'];
        
        // Test 3: Test get-submission-details.php directly
        echo "<h3>3. Testing get-submission-details.php</h3>";
        $_GET['submission_id'] = $test_submission_id;
        
        ob_start();
        include 'src/logic/get-submission-details.php';
        $output = ob_get_clean();
        
        echo "<p>Output:</p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        
        $data = json_decode($output, true);
        if ($data && $data['success']) {
            echo "<p style='color: green;'>✅ get-submission-details.php working correctly</p>";
            
            // Test 4: Test grade-submission.php
            echo "<h3>4. Testing grade-submission.php</h3>";
            $_POST['submission_id'] = $test_submission_id;
            $_POST['score'] = 85;
            $_POST['feedback'] = 'Test feedback from debug script';
            $_SERVER['REQUEST_METHOD'] = 'POST';
            
            ob_start();
            include 'src/logic/grade-submission.php';
            $grade_output = ob_get_clean();
            
            echo "<p>Grading Output:</p>";
            echo "<pre>" . htmlspecialchars($grade_output) . "</pre>";
            
            $grade_data = json_decode($grade_output, true);
            if ($grade_data && $grade_data['success']) {
                echo "<p style='color: green;'>✅ grade-submission.php working correctly</p>";
            } else {
                echo "<p style='color: red;'>❌ grade-submission.php failed</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ get-submission-details.php failed</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ No submissions found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h3>5. File Path Test</h3>";
if (isset($submission) && $submission['file_path']) {
    $full_path = $_SERVER['DOCUMENT_ROOT'] . '/lms/' . $submission['file_path'];
    echo "<p>File path in DB: " . htmlspecialchars($submission['file_path']) . "</p>";
    echo "<p>Full server path: " . htmlspecialchars($full_path) . "</p>";
    echo "<p>File exists: " . (file_exists($full_path) ? '✅ Yes' : '❌ No') . "</p>";
    echo "<p>URL path: /lms/" . htmlspecialchars($submission['file_path']) . "</p>";
}
?>
