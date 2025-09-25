<?php
/**
 * SETUP SCRIPT - Add missing status column to users table
 * Run this if status column is missing from users table
 * URL: http://localhost/lms/setup-status-column.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'src/logic/koneksi.php';

// Security: Only allow in development environment
$allowed_hosts = ['localhost', '127.0.0.1', '::1'];
if (!in_array($_SERVER['HTTP_HOST'], $allowed_hosts)) {
    die('⛔ Access denied. This script only works on localhost.');
}

echo "<h2>🔧 Setup Status Column Script</h2>";

try {
    $conn = getConnection();
    
    echo "<h3>📋 Checking current table structure...</h3>";
    
    // Check current table structure
    $structure = $conn->query("DESCRIBE users");
    $columns = [];
    $hasStatusColumn = false;
    
    if ($structure) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        while ($row = $structure->fetch_assoc()) {
            $columns[] = $row['Field'];
            if ($row['Field'] === 'status') {
                $hasStatusColumn = true;
            }
            
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>🔍 Status Column Check:</h3>";
    
    if ($hasStatusColumn) {
        echo "<div style='color: green; font-weight: bold;'>✅ Status column already exists!</div>";
        
        // Check current status values
        $statusCheck = $conn->query("SELECT id, namaLengkap, status FROM users LIMIT 10");
        if ($statusCheck && $statusCheck->num_rows > 0) {
            echo "<h4>📊 Current Status Values (sample):</h4>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Nama</th><th>Status</th></tr>";
            
            while ($row = $statusCheck->fetch_assoc()) {
                $status = $row['status'] ?? 'NULL';
                $statusColor = 'black';
                if ($status === 'active') $statusColor = 'green';
                elseif ($status === 'inactive') $statusColor = 'red';
                elseif ($status === '') $statusColor = 'orange';
                elseif ($status === 'NULL' || $status === null) $statusColor = 'gray';
                
                echo "<tr>";
                echo "<td>#" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['namaLengkap']) . "</td>";
                echo "<td style='color: $statusColor; font-weight: bold;'>" . htmlspecialchars($status) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<div style='color: red; font-weight: bold;'>❌ Status column does NOT exist!</div>";
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_column'])) {
            echo "<h3>🔄 Adding status column...</h3>";
            
            $alterSql = "ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active'";
            
            if ($conn->query($alterSql)) {
                echo "<div style='color: green; font-weight: bold;'>✅ Status column added successfully!</div>";
                
                // Update all existing users to have 'active' status
                $updateSql = "UPDATE users SET status = 'active' WHERE status IS NULL OR status = ''";
                $updateResult = $conn->query($updateSql);
                
                if ($updateResult) {
                    echo "<div style='color: green;'>✅ Updated " . $conn->affected_rows . " existing users to 'active' status</div>";
                } else {
                    echo "<div style='color: orange;'>⚠️ Column added but failed to update existing users: " . $conn->error . "</div>";
                }
                
                echo "<script>setTimeout(() => window.location.reload(), 2000);</script>";
                
            } else {
                echo "<div style='color: red; font-weight: bold;'>❌ Failed to add status column: " . $conn->error . "</div>";
            }
        } else {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
            echo "<strong>⚠️ Action Required:</strong><br>";
            echo "The status column is missing from the users table. This is needed for the admin user management system.";
            echo "<br><br>";
            echo "<form method='POST'>";
            echo "<button type='submit' name='add_column' style='background: green; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;'>";
            echo "🔧 Add Status Column";
            echo "</button>";
            echo "</form>";
            echo "</div>";
        }
    }
    
    // Test status update functionality
    if ($hasStatusColumn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_update'])) {
        $testUserId = (int)$_POST['test_user_id'];
        $testStatus = $_POST['test_status'];
        
        echo "<h3>🧪 Test Status Update Result:</h3>";
        
        $testSql = "UPDATE users SET status = ? WHERE id = ?";
        $testStmt = $conn->prepare($testSql);
        $testStmt->bind_param("si", $testStatus, $testUserId);
        
        if ($testStmt->execute()) {
            if ($testStmt->affected_rows > 0) {
                echo "<div style='color: green; font-weight: bold;'>✅ Test update successful! User #$testUserId status changed to '$testStatus'</div>";
            } else {
                echo "<div style='color: orange; font-weight: bold;'>⚠️ Test update had no effect (0 rows affected)</div>";
            }
        } else {
            echo "<div style='color: red; font-weight: bold;'>❌ Test update failed: " . $conn->error . "</div>";
        }
        
        echo "<script>setTimeout(() => window.location.reload(), 2000);</script>";
    }
    
    // Show test form if column exists
    if ($hasStatusColumn) {
        echo "<hr>";
        echo "<h3>🧪 Test Status Update:</h3>";
        echo "<form method='POST' style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
        echo "<label>User ID: <input type='number' name='test_user_id' value='1' min='1' style='margin-left: 10px; padding: 5px;'></label><br><br>";
        echo "<label>New Status: ";
        echo "<select name='test_status' style='margin-left: 10px; padding: 5px;'>";
        echo "<option value='active'>Active</option>";
        echo "<option value='inactive'>Inactive</option>";
        echo "<option value='pending'>Pending</option>";
        echo "</select>";
        echo "</label><br><br>";
        echo "<button type='submit' name='test_update' style='background: blue; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer;'>🧪 Test Update</button>";
        echo "</form>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Database error: " . $e->getMessage() . "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 20px auto;
    padding: 20px;
    background: #f5f5f5;
}

table {
    width: 100%;
    background: white;
    margin: 10px 0;
}

th, td {
    padding: 8px;
    text-align: left;
    border: 1px solid #ddd;
}

button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

hr {
    margin: 30px 0;
}
</style>

<div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 20px 0; border-radius: 5px;">
    <strong>📝 Instructions:</strong>
    <ol>
        <li>Check if the status column exists in the users table</li>
        <li>If missing, click "Add Status Column" to create it</li>
        <li>Test the status update functionality</li>
        <li>Delete this file after setup: <code>setup-status-column.php</code></li>
    </ol>
</div>