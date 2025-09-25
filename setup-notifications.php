<?php
// Test script to check if notification tables exist and create them if needed
require_once 'src/logic/koneksi.php';

echo "<h2>Checking Global Notifications Database Tables</h2>";

// Check if global_notifications table exists
$check_notifications = "SHOW TABLES LIKE 'global_notifications'";
$result1 = $koneksi->query($check_notifications);

if ($result1->num_rows == 0) {
    echo "<p>❌ global_notifications table does not exist. Creating...</p>";
    
    $create_notifications = "
    CREATE TABLE `global_notifications` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `title` varchar(255) NOT NULL,
      `description` text NOT NULL,
      `icon` varchar(50) DEFAULT 'info',
      `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
      `target_roles` json DEFAULT NULL COMMENT 'NULL = all users, otherwise array of roles',
      `expires_at` datetime DEFAULT NULL COMMENT 'NULL = never expires',
      `created_by` int(11) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `is_active` tinyint(1) DEFAULT 1,
      PRIMARY KEY (`id`),
      KEY `idx_target_roles` (`target_roles`),
      KEY `idx_expires_at` (`expires_at`),
      KEY `idx_created_at` (`created_at`),
      KEY `idx_is_active` (`is_active`),
      KEY `fk_created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($koneksi->query($create_notifications)) {
        echo "<p>✅ global_notifications table created successfully</p>";
    } else {
        echo "<p>❌ Error creating global_notifications: " . $koneksi->error . "</p>";
    }
} else {
    echo "<p>✅ global_notifications table exists</p>";
}

// Check if notification_reads table exists
$check_reads = "SHOW TABLES LIKE 'notification_reads'";
$result2 = $koneksi->query($check_reads);

if ($result2->num_rows == 0) {
    echo "<p>❌ notification_reads table does not exist. Creating...</p>";
    
    $create_reads = "
    CREATE TABLE `notification_reads` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `notification_id` int(11) NOT NULL,
      `user_id` int(11) NOT NULL,
      `read_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_read` (`notification_id`, `user_id`),
      KEY `idx_user_id` (`user_id`),
      KEY `idx_read_at` (`read_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($koneksi->query($create_reads)) {
        echo "<p>✅ notification_reads table created successfully</p>";
    } else {
        echo "<p>❌ Error creating notification_reads: " . $koneksi->error . "</p>";
    }
} else {
    echo "<p>✅ notification_reads table exists</p>";
}

// Test inserting a sample notification
echo "<h3>Testing Sample Notification Creation</h3>";

$test_query = "INSERT INTO global_notifications (title, description, icon, priority, target_roles, created_by) 
               VALUES ('Test Notification', 'This is a test notification for the system', 'info', 'medium', NULL, 1)";

if ($koneksi->query($test_query)) {
    echo "<p>✅ Sample notification created successfully (ID: " . $koneksi->insert_id . ")</p>";
} else {
    echo "<p>❌ Error creating sample notification: " . $koneksi->error . "</p>";
}

// Check current notifications count
$count_query = "SELECT COUNT(*) as total FROM global_notifications WHERE is_active = TRUE";
$count_result = $koneksi->query($count_query);
$count = $count_result->fetch_assoc()['total'];

echo "<p>📊 Total active notifications: <strong>{$count}</strong></p>";

echo "<p><a href='src/front/admin-settings.php'>🔗 Go to Admin Settings</a></p>";
echo "<p><em>Database setup completed. You can now access the admin settings page.</em></p>";

$koneksi->close();
?>