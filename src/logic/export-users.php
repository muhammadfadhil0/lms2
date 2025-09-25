<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

require_once 'user-logic.php';

$userLogic = new UserLogic();

// Get filters from URL parameters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Get users data for export
$users = $userLogic->exportUsers($search, $role_filter, $status_filter);

// Set headers for Excel download
$filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

// Create file handle
$output = fopen('php://output', 'w');

// Add BOM for proper UTF-8 encoding in Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
$headers = [
    'ID',
    'Nama Lengkap',
    'Email',
    'Username',
    'Role',
    'Status',
    'Tanggal Registrasi'
];

fputcsv($output, $headers);

// Add user data
foreach ($users as $user) {
    $row = [
        $user['id'],
        $user['nama'],
        $user['email'],
        $user['username'] ?? '',
        ucfirst($user['role']),
        ucfirst($user['status']),
        date('d/m/Y H:i:s', strtotime($user['tanggal_registrasi']))
    ];
    
    fputcsv($output, $row);
}

fclose($output);
exit();
?>