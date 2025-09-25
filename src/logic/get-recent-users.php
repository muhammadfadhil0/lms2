<?php
session_start();



// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include logic files
require_once 'dashboard-logic.php';

try {
    // Get page parameter from request
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 5; // Fixed limit of 5 users per page
    
    // Validate page number
    if ($page < 1) {
        $page = 1;
    }
    
    // Get recent users data
    $dashboardLogic = new DashboardLogic();
    $usersData = $dashboardLogic->getRecentUsers($page, $limit);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $usersData
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-recent-users.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>