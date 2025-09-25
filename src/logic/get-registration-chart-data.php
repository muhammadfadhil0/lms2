<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

require_once 'dashboard-logic.php';

try {
    $period = isset($_GET['period']) ? $_GET['period'] : 'month';
    $value = isset($_GET['value']) ? intval($_GET['value']) : null;
    
    // Validate period
    $allowedPeriods = ['day', 'week', 'month', 'year', '2year'];
    if (!in_array($period, $allowedPeriods)) {
        $period = 'month';
    }
    
    $dashboardLogic = new DashboardLogic();
    $chartData = $dashboardLogic->getRegistrationChartData($period, $value);
    
    echo json_encode([
        'success' => true,
        'data' => $chartData,
        'period' => $period
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching chart data: ' . $e->getMessage()
    ]);
}
?>