<?php
// Simple API connectivity test
session_start();
header('Content-Type: application/json');

// Simulate test user
$_SESSION['user'] = [
    'id' => 999,
    'namaLengkap' => 'Test User',
    'email' => 'test@edupoint.com',
    'role' => 'siswa'
];

echo json_encode([
    'success' => true, 
    'message' => 'API connection OK',
    'session' => $_SESSION['user'] ?? null,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>