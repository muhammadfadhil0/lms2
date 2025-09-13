<!-- cek sekarang ada di halaman apa -->
<?php 
session_start();
$currentPage = 'dashboard'; 

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Include logic files
require_once '../logic/dashboard-logic.php';
require_once '../logic/user-logic.php';

// Get dashboard data
$dashboardLogic = new DashboardLogic();
$userLogic = new UserLogic();
$admin_id = $_SESSION['user']['id'];

// Simple admin dashboard stats
$totalUsers = 0; // You can implement this in dashboard-logic.php
$totalKelas = 0;
$totalUjian = 0;
?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Admin Dashboard - Point LMS</title>
</head>
<body class="bg-gray-50">
    <!-- Main Content -->
    <div class="md:ml-64 min-h-screen transition-all duration-300 ease-in-out" data-main-content>
        <!-- Header -->
        <div class="bg-white shadow-sm border-b border-gray-200 p-4 md:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Dashboard Administrator</h1>
                    <p class="text-gray-600 mt-1">Kelola sistem pembelajaran Point LMS</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        <i class="ti ti-shield-check mr-2"></i>
                        Administrator
                    </span>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
                <!-- Total Users -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-blue-100">
                            <i class="ti ti-users text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Users</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $totalUsers; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Kelas -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-green-100">
                            <i class="ti ti-school text-2xl text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Kelas</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $totalKelas; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Ujian -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-purple-100">
                            <i class="ti ti-clipboard-check text-2xl text-purple-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Ujian</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $totalUjian; ?></p>
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-orange-100">
                            <i class="ti ti-server text-2xl text-orange-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">System Status</p>
                            <p class="text-sm font-bold text-green-600">Online</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Quick Actions Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="../front/admin-users.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="ti ti-user-plus text-blue-600 mr-3"></i>
                            <span class="text-gray-700">Kelola Pengguna</span>
                        </a>
                        <a href="../front/admin-kelas.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="ti ti-school text-green-600 mr-3"></i>
                            <span class="text-gray-700">Kelola Kelas</span>
                        </a>
                        <a href="../front/admin-reports.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="ti ti-chart-bar text-purple-600 mr-3"></i>
                            <span class="text-gray-700">Lihat Laporan</span>
                        </a>
                        <a href="../front/admin-settings.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="ti ti-settings text-orange-600 mr-3"></i>
                            <span class="text-gray-700">Pengaturan Sistem</span>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h3>
                    <div class="space-y-3">
                        <div class="flex items-center p-3 rounded-lg bg-gray-50">
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="ti ti-user-plus text-green-600 text-sm"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-800">New user registered</p>
                                <p class="text-xs text-gray-500">2 hours ago</p>
                            </div>
                        </div>
                        <div class="flex items-center p-3 rounded-lg bg-gray-50">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="ti ti-school text-blue-600 text-sm"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-800">New class created</p>
                                <p class="text-xs text-gray-500">5 hours ago</p>
                            </div>
                        </div>
                        <div class="flex items-center p-3 rounded-lg bg-gray-50">
                            <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                                <i class="ti ti-clipboard-check text-purple-600 text-sm"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-800">Exam completed</p>
                                <p class="text-xs text-gray-500">1 day ago</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

<script src="../script/menu-bar-script.js"></script>
</body>
</html>
