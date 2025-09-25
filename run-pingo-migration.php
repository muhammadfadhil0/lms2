<?php
/**
 * Database Migration Script for Pingo Attachment Support
 * Run this script to add attachment_data column to pingo_chat_history table
 */

// Database configuration
$host = 'localhost';
$dbname = 'lms';
$username = 'root';
$password = '';

try {
    echo "🚀 Starting Pingo attachment migration...\n";
    
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful\n";
    
    // Check if column already exists
    $checkSql = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'pingo_chat_history' AND COLUMN_NAME = 'attachment_data'";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([$dbname]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "ℹ️  Column 'attachment_data' already exists in pingo_chat_history table\n";
    } else {
        echo "📝 Adding attachment_data column...\n";
        
        // Add attachment_data column
        $alterSql = "ALTER TABLE `pingo_chat_history` 
                     ADD COLUMN `attachment_data` JSON DEFAULT NULL 
                     COMMENT 'Stores attachment information as JSON' 
                     AFTER `message`";
        $pdo->exec($alterSql);
        
        echo "✅ Column 'attachment_data' added successfully\n";
        
        // Add index for better performance
        echo "📝 Adding index for attachment queries...\n";
        try {
            // Use a simpler index approach compatible with older MySQL versions
            $indexSql = "ALTER TABLE `pingo_chat_history` 
                         ADD INDEX `idx_attachment_data` (`attachment_data`(1))";
            $pdo->exec($indexSql);
            echo "✅ Index 'idx_attachment_data' added successfully\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "ℹ️  Index already exists\n";
            } else {
                echo "⚠️  Could not add index (non-critical): " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Verify table structure
    echo "📋 Verifying table structure...\n";
    $descSql = "DESCRIBE pingo_chat_history";
    $stmt = $pdo->prepare($descSql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Current table structure:\n";
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "📌 You can now upload documents and they will persist after page refresh.\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>