<?php
// cek sekarang ada di halaman apa
session_start();
$currentPage = 'beranda';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Include logic files
require_once '../logic/dashboard-logic.php';

// Get dashboard data for admin
$dashboardLogic = new DashboardLogic();
$admin_id = $_SESSION['user']['id'];
$dashboardData = $dashboardLogic->getDashboardAdmin($admin_id);
?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<?php require_once '../logic/profile-photo-helper.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?php echo $_SESSION['user']['id']; ?>">
    <?php require '../../assets/head.php'; ?>
    <link rel="stylesheet" href="../css/search-system.css">
    <title>Admin Dashboard</title>
    <style>
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .appointment-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #f3f4f6;
        }
        .appointment-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-day-off { background: #fef3c7; color: #92400e; }
        .status-available { background: #dcfce7; color: #166534; }
        .status-scheduled { background: #fecaca; color: #991b1b; }
        
        /* New status badges for users */
        .status-gratis { background: #e0f2fe; color: #0277bd; }
        .status-premium { background: #f3e5f5; color: #7b1fa2; }
        .status-guru { background: #e8f5e8; color: #2e7d32; }
        .status-siswa { background: #fff3e0; color: #f57900; }
        
        .user-item {
            transition: background-color 0.2s ease;
        }
        .user-item:hover {
            background-color: #f9fafb;
        }
        .user-item:last-child td {
            border-bottom: none;
        }
        .user-item td {
            padding: 0.75rem 0.5rem;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        .user-item td:first-child {
            padding-left: 0;
        }
        .user-item td:last-child {
            padding-right: 0;
        }
        
        /* Table responsive improvements */
        @media (max-width: 768px) {
            .user-item td {
                padding: 0.5rem 0.25rem;
                font-size: 0.875rem;
            }
            .status-badge {
                padding: 0.125rem 0.5rem;
                font-size: 0.6875rem;
            }
        }
        
        /* Navigation buttons for users pagination */
        #prevUsers, #nextUsers {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #prevUsers:disabled, #nextUsers:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.125rem;
        }
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.75rem;
            font-weight: 500;
            position: relative;
        }
        .calendar-day:hover {
            transform: scale(1.1);
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        .calendar-day.other-month {
            color: #9ca3af;
            background-color: #f9fafb;
        }
        /* Heatmap colors - GitHub style dengan warna orange */
        .heatmap-0 { background-color: #f3f4f6; color: #6b7280; }
        .heatmap-1 { background-color: #fed7aa; color: #9a3412; }
        .heatmap-2 { background-color: #fdba74; color: #9a3412; }
        .heatmap-3 { background-color: #fb923c; color: white; }
        .heatmap-4 { background-color: #f97316; color: white; }
        .heatmap-5 { background-color: #ea580c; color: white; }
        
        /* Tooltip untuk heatmap */
        .heatmap-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 20;
            margin-bottom: 0.25rem;
        }
        .heatmap-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 4px solid transparent;
            border-top-color: #1f2937;
        }
        .calendar-day:hover .heatmap-tooltip {
            opacity: 1;
        }
        
        /* Navigation button styles */
        #prevMonth, #nextMonth {
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        #prevMonth:hover, #nextMonth:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        
        /* Calendar container swipe styles */
        #heatmapContainer {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        /* Month label styles */
        #currentMonthLabel {
            font-weight: 500;
            color: #374151;
            transition: all 0.2s ease;
        }
        
        /* Calendar grid animation */
        .calendar-grid {
            transition: transform 0.3s ease-in-out, opacity 0.2s ease-in-out;
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .calendar-day {
                font-size: 0.65rem;
            }
            
            #prevMonth, #nextMonth {
                width: 20px;
                height: 20px;
            }
            
            #currentMonthLabel {
                font-size: 0.7rem;
                min-width: 70px;
            }
            
            .heatmap-tooltip {
                font-size: 0.65rem;
                padding: 0.375rem;
            }
        }
        
        .progress-circle {
            position: relative;
            width: 120px;
            height: 120px;
        }
        .progress-svg {
            transform: rotate(-90deg);
        }
        .progress-bg {
            fill: none;
            stroke: #f3f4f6;
            stroke-width: 8;
        }
        .progress-bar {
            fill: none;
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dasharray 0.3s ease;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Main Content -->
    <div data-main-content class="md:ml-64 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">
        <!-- Header -->
        <header class="bg-white p-2 md:p-6 header-compact border-b border-gray-200">
            <style>
                @media (max-width: 768px) {
                    .header-compact {
                        padding: .5rem .75rem;
                    }
                    .header-compact .mobile-logo-wrap img {
                        height: 28px;
                        width: 28px;
                    }
                    .header-compact .mobile-logo-text {
                        font-size: 1.35rem;
                        line-height: 1.45rem;
                    }
                    .header-compact .action-buttons {
                        gap: .25rem;
                    }
                    .header-compact .action-buttons button {
                        padding: .4rem;
                    }
                    .header-compact .action-buttons i {
                        font-size: 1.05rem;
                    }
                }
            </style>

            <div class="flex items-center justify-between">
                <div class="hidden md:block">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Admin Dashboard</h1>
                    <p class="text-gray-600">Selamat datang, <?php echo htmlspecialchars($_SESSION['user']['namaLengkap']); ?>!</p>
                </div>
                <div class="flex md:hidden items-center gap-2 mobile-logo-wrap">
                    <img src="../../assets/img/logo.png" alt="Logo" class="h-7 w-7 flex-shrink-0">
                    <div id="logoTextContainer" class="transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap">
                        <h1 id="logoText" class="mobile-logo-text font-bold text-gray-800">Point</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-2 md:space-x-4" style="align-items: center;">
                    <div class="search-other-buttons flex items-center space-x-2 md:space-x-4">
                        <button class="relative p-2 text-gray-400 hover:text-gray-600 transition-colors"
                            data-notification-trigger="true">
                            <i class="ti ti-bell text-lg md:text-xl"></i>
                            <span id="notification-badge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                <span id="notification-count">0</span>
                            </span>
                        </button>
                    </div>
                    <button class="search-btn p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="ti ti-search text-lg md:text-xl"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
            <!-- Stats Cards Row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
                <!-- Pengguna Gratis Card -->
                <div class="stats-card">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-2xl font-bold text-gray-800"><?php echo $dashboardData['penggunaGratis'] ?? '0'; ?></div>
                        <div class="w-1 h-12 bg-green-500 rounded-full"></div>
                    </div>
                    <div class="text-sm text-gray-600 mb-1">Pengguna Gratis</div>
                    <div class="text-xs text-blue-600">
                        <i class="ti ti-users mr-1"></i>
                        Aktif
                    </div>
                </div>

                <!-- Pengguna Premium Card -->
                <div class="stats-card">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-2xl font-bold text-gray-800"><?php echo $dashboardData['penggunaPremium'] ?? '0'; ?></div>
                        <div class="w-1 h-12 bg-purple-500 rounded-full"></div>
                    </div>
                    <div class="text-sm text-gray-600 mb-1">Pengguna Premium</div>
                    <div class="text-xs text-purple-600">
                        <i class="ti ti-crown mr-1"></i>
                        Berlangganan
                    </div>
                </div>

                <!-- Total Kelas Aktif Card -->
                <div class="stats-card">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-2xl font-bold text-gray-800"><?php echo $dashboardData['totalKelasAktif'] ?? '0'; ?></div>
                        <div class="w-1 h-12 bg-blue-500 rounded-full"></div>
                    </div>
                    <div class="text-sm text-gray-600 mb-1">Kelas Aktif</div>
                    <div class="text-xs text-green-600">
                        <i class="ti ti-school mr-1"></i>
                        Berjalan
                    </div>
                </div>

                <!-- Total Ujian Aktif Card -->
                <div class="stats-card">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-2xl font-bold text-gray-800"><?php echo $dashboardData['totalUjianAktif'] ?? '0'; ?></div>
                        <div class="w-1 h-12 bg-orange-500 rounded-full"></div>
                    </div>
                    <div class="text-sm text-gray-600 mb-1">Ujian Aktif</div>
                    <div class="text-xs text-orange-600">
                        <i class="ti ti-clipboard-check mr-1"></i>
                        Tersedia
                    </div>
                </div>
            </div>

            <!-- Charts and Data Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Registration Statistics Chart -->
                <div class="chart-container">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Statistik Pendaftaran</h3>
                        <select id="periodSelect" class="text-sm border border-gray-300 rounded-md px-2 py-1">
                            <option value="day">By Day (30 hari)</option>
                            <option value="week">By Week (12 minggu)</option>
                            <option value="month" selected>By Month (12 bulan)</option>
                            <option value="year">By Year (5 tahun)</option>
                            <option value="2year">By 2 Years (24 bulan)</option>
                        </select>
                    </div>
                    <div class="relative h-64">
                        <canvas id="registrationChart" width="400" height="200"></canvas>
                        <div class="absolute top-4 right-4 bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm shadow-md">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                <span class="text-xs text-gray-600">Guru</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                                <span class="text-xs text-gray-600">Siswa</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Pendaftar -->
                <div class="chart-container">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Total Pendaftar</h3>
                        <div class="text-sm text-gray-500">Keseluruhan</div>
                    </div>
                    <div class="flex items-center justify-center">
                        <div class="relative w-48 h-48 md:w-56 md:h-56 lg:w-64 lg:h-64">
                            <canvas id="totalUsersChart" width="256" height="256"></canvas>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <div class="text-2xl md:text-3xl lg:text-4xl font-extrabold text-gray-800" id="totalUsersCount"><?php echo ($dashboardData['totalGuru'] ?? 0) + ($dashboardData['totalSiswa'] ?? 0); ?></div>
                                    <div class="text-sm md:text-base text-gray-500">Total</div>
                                </div>
                            </div>
                        </div>
                        <div class="ml-8">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                <span class="text-sm text-gray-600">Guru</span>
                                <span class="text-sm font-semibold ml-auto" id="totalGuruCount"><?php echo $dashboardData['totalGuru'] ?? 0; ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                                <span class="text-sm text-gray-600">Siswa</span>
                                <span class="text-sm font-semibold ml-auto" id="totalSiswaCount"><?php echo $dashboardData['totalSiswa'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row - Appointments and Calendar -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Pendaftar Terbaru List -->
                <div class="lg:col-span-2 chart-container">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Pendaftar Terbaru</h3>
                        <div class="flex items-center gap-2">
                            <button id="prevUsers" class="p-1 text-gray-400 hover:text-gray-600 transition-colors border border-gray-300 rounded-md">
                                <i class="ti ti-chevron-left text-sm"></i>
                            </button>
                            <span id="userPageInfo" class="text-xs text-gray-500 min-w-[60px] text-center">1 / 1</span>
                            <button id="nextUsers" class="p-1 text-gray-400 hover:text-gray-600 transition-colors border border-gray-300 rounded-md">
                                <i class="ti ti-chevron-right text-sm"></i>
                            </button>
                        </div>
                    </div>
                    <div class="overflow-hidden">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 uppercase tracking-wider">
                                    <th class="pb-3 w-12">No</th>
                                    <th class="pb-3 min-w-0">Nama & Email</th>
                                    <th class="pb-3 w-20">Role</th>
                                    <th class="pb-3 w-28">Waktu Daftar</th>
                                    <th class="pb-3 w-24">Status</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody" class="text-sm">
                                <!-- Data akan diisi oleh JavaScript -->
                                <tr class="user-item">
                                    <td colspan="5" class="text-center py-4 text-gray-500">
                                        <div class="flex items-center justify-center">
                                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500 mr-2"></div>
                                            Memuat data...
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Registration Heatmap Calendar -->
                <div class="chart-container">
                    <div class="items-center justify-between text-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Aktivitas Pendaftaran</h3>
                        <div class="flex justify-center items-center gap-2">
                            <button id="prevMonth" class="p-1 text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="ti ti-chevron-left text-sm"></i>
                            </button>
                            <div id="currentMonthLabel" class="text-xs text-gray-500 min-w-[80px] text-center">
                                <!-- Month label -->
                            </div>
                            <button id="nextMonth" class="p-1 text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="ti ti-chevron-right text-sm"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Calendar Header -->
                    <div class="calendar-grid mb-1">
                        <div class="text-xs text-gray-500 font-medium p-1 text-center">Mon</div>
                        <div class="text-xs text-gray-500 font-medium p-1 text-center">Tue</div>
                        <div class="text-xs text-gray-500 font-medium p-1 text-center">Wed</div>
                        <div class="text-xs text-gray-500 font-medium p-1 text-center">Thu</div>
                        <div class="text-xs text-gray-500 font-medium p-1 text-center">Fri</div>
                        <div class="text-xs text-gray-500 font-medium p-1 text-center">Sat</div>
                        <div class="text-xs text-gray-500 font-medium p-1 text-center">Sun</div>
                    </div>
                    
                    <!-- Calendar Days Container with swipe support -->
                    <div id="heatmapContainer" class="relative overflow-hidden mb-4" style="touch-action: pan-y;">
                        <div id="heatmapCalendar" class="calendar-grid transition-transform duration-300 ease-in-out">
                            <!-- Days akan diisi oleh JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Legend -->
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>Kurang</span>
                        <div class="flex items-center gap-1">
                            <div class="w-3 h-3 rounded-sm heatmap-0"></div>
                            <div class="w-3 h-3 rounded-sm heatmap-1"></div>
                            <div class="w-3 h-3 rounded-sm heatmap-2"></div>
                            <div class="w-3 h-3 rounded-sm heatmap-3"></div>
                            <div class="w-3 h-3 rounded-sm heatmap-4"></div>
                            <div class="w-3 h-3 rounded-sm heatmap-5"></div>
                        </div>
                        <span>Banyak</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../script/menu-bar-script.js"></script>
    <script src="../script/profile-sync.js"></script>
    
    <!-- Chart.js for the line chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Registration chart data from PHP
        const registrationData = <?php echo json_encode($dashboardData['registrationChart'] ?? []); ?>;
        
        // Initialize the registration statistics chart
        const ctx = document.getElementById('registrationChart').getContext('2d');
        let registrationChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: registrationData.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Guru',
                    data: registrationData.guru || [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }, {
                    label: 'Siswa',
                    data: registrationData.siswa || [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#f97316',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            title: function(context) {
                                return 'Bulan ' + context[0].label;
                            },
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' pendaftar';
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6'
                        },
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 12,
                            maxRotation: 0
                        }
                    }
                }
            }
        });
        
        // Handle period selection change
        document.getElementById('periodSelect').addEventListener('change', function() {
            const selectedPeriod = this.value;
            
            // Show loading state
            const chartTitle = document.querySelector('.chart-container h3');
            const originalTitle = chartTitle.textContent;
            chartTitle.textContent = 'Memuat data...';
            
            // Make AJAX request to get new data
            fetch(`../logic/get-registration-chart-data.php?period=${selectedPeriod}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update chart data
                        registrationChart.data.labels = data.data.labels;
                        registrationChart.data.datasets[0].data = data.data.guru;
                        registrationChart.data.datasets[1].data = data.data.siswa;
                        
                        // Update chart options based on period
                        if (selectedPeriod === 'day' || selectedPeriod === 'week') {
                            registrationChart.options.scales.x.ticks = {
                                maxTicksLimit: 15,
                                maxRotation: 45
                            };
                        } else if (selectedPeriod === '2year') {
                            registrationChart.options.scales.x.ticks = {
                                maxTicksLimit: 12,
                                maxRotation: 45
                            };
                        } else {
                            registrationChart.options.scales.x.ticks = {
                                maxTicksLimit: 12,
                                maxRotation: 0
                            };
                        }
                        
                        registrationChart.update('active');
                        chartTitle.textContent = originalTitle;
                    } else {
                        console.error('Error:', data.message);
                        chartTitle.textContent = originalTitle;
                    }
                })
                .catch(error => {
                    console.error('Error fetching chart data:', error);
                    chartTitle.textContent = originalTitle;
                });
        });

        // Total Users Donut Chart
        const totalUsersData = {
            guru: <?php echo $dashboardData['totalGuru'] ?? 0; ?>,
            siswa: <?php echo $dashboardData['totalSiswa'] ?? 0; ?>
        };
        
        const totalUsersCtx = document.getElementById('totalUsersChart').getContext('2d');
        const totalUsersChart = new Chart(totalUsersCtx, {
            type: 'doughnut',
            data: {
                labels: ['Guru', 'Siswa'],
                datasets: [{
                    data: [totalUsersData.guru, totalUsersData.siswa],
                    backgroundColor: ['#3b82f6', '#f97316'],
                    borderWidth: 0,
                    cutout: '60%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                elements: {
                    arc: {
                        borderWidth: 0
                    }
                }
            }
        });

        // Heatmap Calendar functionality
        const registrationHeatmap = <?php echo json_encode($dashboardData['registrationHeatmap'] ?? []); ?>;
        let currentDisplayMonth = new Date(); // Start with current month
        
        // Month names in Indonesian
        const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                           'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        function generateHeatmapCalendar(targetMonth = currentDisplayMonth) {
            const calendarContainer = document.getElementById('heatmapCalendar');
            const monthLabel = document.getElementById('currentMonthLabel');
            
            // Update month label
            monthLabel.textContent = `${monthNames[targetMonth.getMonth()]} ${targetMonth.getFullYear()}`;
            
            // Clear existing content
            calendarContainer.innerHTML = '';
            
            // Get first and last day of the target month
            const firstDayOfMonth = new Date(targetMonth.getFullYear(), targetMonth.getMonth(), 1);
            const lastDayOfMonth = new Date(targetMonth.getFullYear(), targetMonth.getMonth() + 1, 0);
            
            // Calculate the starting Monday (beginning of calendar grid)
            const startMonday = new Date(firstDayOfMonth);
            const dayOfWeek = firstDayOfMonth.getDay();
            startMonday.setDate(firstDayOfMonth.getDate() - (dayOfWeek === 0 ? 6 : dayOfWeek - 1));
            
            // Generate weeks for the month view (6 weeks to cover all possible month layouts)
            const weeks = [];
            let weekStart = new Date(startMonday);
            
            for (let week = 0; week < 6; week++) {
                const weekDays = [];
                for (let day = 0; day < 7; day++) {
                    const currentDay = new Date(weekStart);
                    currentDay.setDate(weekStart.getDate() + day);
                    weekDays.push(new Date(currentDay));
                }
                weeks.push(weekDays);
                weekStart.setDate(weekStart.getDate() + 7);
            }
            
            // Flatten weeks to get all days
            const allDays = weeks.flat();
            
            // Find max registrations for scaling (from all available data)
            const maxRegistrations = Math.max(1, ...Object.values(registrationHeatmap).map(d => d.total));
            
            // Create calendar days
            allDays.forEach(date => {
                const dayElement = document.createElement('div');
                const dateString = formatDateForHeatmap(date);
                const isCurrentMonth = date.getMonth() === targetMonth.getMonth() && date.getFullYear() === targetMonth.getFullYear();
                const isToday = isSameDate(date, new Date());
                
                // Get registration data for this date
                const dayData = registrationHeatmap[dateString] || { total: 0, guru: 0, siswa: 0 };
                
                // Calculate heatmap intensity (0-5)
                const intensity = dayData.total === 0 ? 0 : Math.min(5, Math.ceil((dayData.total / maxRegistrations) * 5));
                
                dayElement.className = `calendar-day heatmap-${intensity} ${!isCurrentMonth ? 'other-month' : ''}`;
                dayElement.textContent = date.getDate();
                
                // Add tooltip
                const tooltip = document.createElement('div');
                tooltip.className = 'heatmap-tooltip';
                tooltip.innerHTML = `
                    <div>${formatDateIndonesian(date)}</div>
                    <div>${dayData.total} pendaftar</div>
                    ${dayData.total > 0 ? `<div>${dayData.guru} guru, ${dayData.siswa} siswa</div>` : ''}
                `;
                dayElement.appendChild(tooltip);
                
                // Add special styling for today
                if (isToday) {
                    dayElement.style.border = '2px solid #3b82f6';
                }
                
                calendarContainer.appendChild(dayElement);
            });
        }
        
        // Navigation functions
        function navigateMonth(direction) {
            const newMonth = new Date(currentDisplayMonth);
            newMonth.setMonth(currentDisplayMonth.getMonth() + direction);
            currentDisplayMonth = newMonth;
            
            // Add slide animation
            const container = document.getElementById('heatmapCalendar');
            container.style.transform = direction > 0 ? 'translateX(10px)' : 'translateX(-10px)';
            container.style.opacity = '0.7';
            
            setTimeout(() => {
                generateHeatmapCalendar(currentDisplayMonth);
                container.style.transform = 'translateX(0)';
                container.style.opacity = '1';
            }, 150);
        }
        
        function formatDateForHeatmap(date) {
            return date.getFullYear() + '-' + 
                   String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(date.getDate()).padStart(2, '0');
        }
        
        function formatDateIndonesian(date) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 
                           'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
        }
        
        function isSameDate(date1, date2) {
            return date1.getDate() === date2.getDate() &&
                   date1.getMonth() === date2.getMonth() &&
                   date1.getFullYear() === date2.getFullYear();
        }
        
        // Initialize heatmap calendar with current month
        generateHeatmapCalendar(currentDisplayMonth);
        
        // Add navigation event listeners
        document.getElementById('prevMonth').addEventListener('click', () => navigateMonth(-1));
        document.getElementById('nextMonth').addEventListener('click', () => navigateMonth(1));
        
        // Add swipe gesture support
        let startX = 0;
        let startY = 0;
        let isSwipeActive = false;
        
        const heatmapContainer = document.getElementById('heatmapContainer');
        
        // Touch events for mobile
        heatmapContainer.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            isSwipeActive = true;
        }, { passive: true });
        
        heatmapContainer.addEventListener('touchmove', (e) => {
            if (!isSwipeActive) return;
            
            // Prevent default only if this is a horizontal swipe
            const deltaX = Math.abs(e.touches[0].clientX - startX);
            const deltaY = Math.abs(e.touches[0].clientY - startY);
            
            if (deltaX > deltaY && deltaX > 10) {
                e.preventDefault();
            }
        }, { passive: false });
        
        heatmapContainer.addEventListener('touchend', (e) => {
            if (!isSwipeActive) return;
            
            const endX = e.changedTouches[0].clientX;
            const endY = e.changedTouches[0].clientY;
            const deltaX = startX - endX;
            const deltaY = Math.abs(startY - endY);
            
            // Check if it's a horizontal swipe (not vertical scroll)
            if (Math.abs(deltaX) > 50 && deltaY < 100) {
                if (deltaX > 0) {
                    // Swipe left - next month
                    navigateMonth(1);
                } else {
                    // Swipe right - previous month
                    navigateMonth(-1);
                }
            }
            
            isSwipeActive = false;
        }, { passive: true });
        
        // Mouse events for desktop (optional)
        let isMouseDown = false;
        let mouseStartX = 0;
        
        heatmapContainer.addEventListener('mousedown', (e) => {
            mouseStartX = e.clientX;
            isMouseDown = true;
            heatmapContainer.style.cursor = 'grabbing';
        });
        
        heatmapContainer.addEventListener('mousemove', (e) => {
            if (!isMouseDown) return;
            
            const deltaX = e.clientX - mouseStartX;
            if (Math.abs(deltaX) > 5) {
                e.preventDefault();
            }
        });
        
        heatmapContainer.addEventListener('mouseup', (e) => {
            if (!isMouseDown) return;
            
            const deltaX = mouseStartX - e.clientX;
            
            if (Math.abs(deltaX) > 50) {
                if (deltaX > 0) {
                    navigateMonth(1);
                } else {
                    navigateMonth(-1);
                }
            }
            
            isMouseDown = false;
            heatmapContainer.style.cursor = 'default';
        });
        
        heatmapContainer.addEventListener('mouseleave', () => {
            isMouseDown = false;
            heatmapContainer.style.cursor = 'default';
        });
        
        // Keyboard navigation (optional)
        document.addEventListener('keydown', (e) => {
            // Only if heatmap container is in viewport or focused
            const rect = heatmapContainer.getBoundingClientRect();
            const isInView = rect.top >= 0 && rect.bottom <= window.innerHeight;
            
            if (isInView) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    navigateMonth(-1);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    navigateMonth(1);
                }
            }
        });
    </script>

    <!-- Notification Badge Script -->
    <script>
        // Load notification count on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadNotificationBadge();
            
            // Add click event for notification bell
            const notificationTrigger = document.querySelector('[data-notification-trigger="true"]');
            if (notificationTrigger) {
                notificationTrigger.addEventListener('click', function() {
                    openNotificationsModal();
                });
            }
        });

        // Function to load notification badge count
        async function loadNotificationBadge() {
            try {
                const response = await fetch('../logic/get-notifications.php?unread_only=1', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                if (data.success && data.notifications) {
                    const unreadCount = data.notifications.length;
                    updateNotificationBadge(unreadCount);
                } else {
                    updateNotificationBadge(0);
                }
            } catch (error) {
                console.error('Error loading notification count:', error);
                updateNotificationBadge(0);
            }
        }

        // Function to update notification badge
        function updateNotificationBadge(count) {
            const badge = document.getElementById('notification-badge');
            const countEl = document.getElementById('notification-count');
            
            if (badge && countEl) {
                countEl.textContent = count;
                
                if (count > 0) {
                    badge.classList.remove('hidden');
                    // Add small animation
                    badge.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        badge.style.transform = 'scale(1)';
                    }, 100);
                } else {
                    badge.classList.add('hidden');
                }
            }
        }

        // Function called from modal-notifications.php to refresh badge
        function updateBerandaNotifications() {
            loadNotificationBadge();
        }
    </script>

    <!-- Recent Users Pagination Script -->
    <script>
        // Recent Users functionality
        let currentUserPage = 1;
        let isLoadingUsers = false;
        
        // Initialize recent users on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentUsers(1);
            
            // Add navigation event listeners
            document.getElementById('prevUsers').addEventListener('click', () => {
                if (currentUserPage > 1) {
                    loadRecentUsers(currentUserPage - 1);
                }
            });
            
            document.getElementById('nextUsers').addEventListener('click', () => {
                loadRecentUsers(currentUserPage + 1);
            });
        });
        
        // Function to load recent users with pagination
        async function loadRecentUsers(page) {
            if (isLoadingUsers) return;
            
            isLoadingUsers = true;
            
            try {
                // Show loading state
                const tbody = document.getElementById('usersTableBody');
                tbody.innerHTML = `
                    <tr class="user-item">
                        <td colspan="5" class="text-center py-4 text-gray-500">
                            <div class="flex items-center justify-center">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500 mr-2"></div>
                                Memuat data...
                            </div>
                        </td>
                    </tr>
                `;
                
                // Fetch data from server
                const response = await fetch(`../logic/get-recent-users.php?page=${page}`);
                const data = await response.json();
                
                if (data.success) {
                    currentUserPage = data.data.pagination.currentPage;
                    renderRecentUsers(data.data.users);
                    updateUsersPagination(data.data.pagination);
                } else {
                    throw new Error(data.message || 'Failed to load users');
                }
                
            } catch (error) {
                console.error('Error loading recent users:', error);
                
                // Show error state
                const tbody = document.getElementById('usersTableBody');
                tbody.innerHTML = `
                    <tr class="user-item">
                        <td colspan="5" class="text-center py-4 text-red-500">
                            <i class="ti ti-alert-circle mr-2"></i>
                            Gagal memuat data pendaftar
                        </td>
                    </tr>
                `;
                
                // Reset pagination
                updateUsersPagination({
                    currentPage: 1,
                    totalPages: 0,
                    hasNext: false,
                    hasPrev: false
                });
            } finally {
                isLoadingUsers = false;
            }
        }
        
        // Function to render users in the table
        function renderRecentUsers(users) {
            const tbody = document.getElementById('usersTableBody');
            
            if (users.length === 0) {
                tbody.innerHTML = `
                    <tr class="user-item">
                        <td colspan="5" class="text-center py-4 text-gray-500">
                            <i class="ti ti-users mr-2"></i>
                            Tidak ada pendaftar ditemukan
                        </td>
                    </tr>
                `;
                return;
            }
            
            const rows = users.map((user, index) => {
                const number = ((currentUserPage - 1) * 5) + index + 1;
                const roleClass = user.role === 'guru' ? 'status-guru' : 'status-siswa';
                const statusClass = user.subscription_status === 'Premium' ? 'status-premium' : 'status-gratis';
                
                return `
                    <tr class="user-item">
                        <td class="font-medium text-gray-600">${number.toString().padStart(2, '0')}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r ${user.role === 'guru' ? 'from-blue-400 to-blue-600' : 'from-orange-400 to-orange-600'} flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                                    ${user.namaLengkap.charAt(0).toUpperCase()}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-gray-900 truncate">${escapeHtml(user.namaLengkap)}</div>
                                    <div class="text-xs text-gray-500 truncate">${escapeHtml(user.email)}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge ${roleClass}">
                                ${user.role === 'guru' ? 'Guru' : 'Siswa'}
                            </span>
                        </td>
                        <td class="text-gray-600 text-sm">${user.timeAgo}</td>
                        <td>
                            <span class="status-badge ${statusClass}">
                                <i class="ti ${user.subscription_status === 'Premium' ? 'ti-crown' : 'ti-user'} mr-1"></i>
                                ${user.subscription_status}
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');
            
            tbody.innerHTML = rows;
        }
        
        // Function to update pagination controls
        function updateUsersPagination(pagination) {
            const prevBtn = document.getElementById('prevUsers');
            const nextBtn = document.getElementById('nextUsers');
            const pageInfo = document.getElementById('userPageInfo');
            
            // Update buttons state
            prevBtn.disabled = !pagination.hasPrev;
            nextBtn.disabled = !pagination.hasNext;
            
            // Update page info
            pageInfo.textContent = `${pagination.currentPage} / ${Math.max(1, pagination.totalPages)}`;
            
            // Update button styles
            prevBtn.className = `p-1 transition-colors border border-gray-300 rounded-md ${
                pagination.hasPrev 
                    ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50' 
                    : 'text-gray-300 cursor-not-allowed'
            }`;
            
            nextBtn.className = `p-1 transition-colors border border-gray-300 rounded-md ${
                pagination.hasNext 
                    ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50' 
                    : 'text-gray-300 cursor-not-allowed'
            }`;
        }
        
        // Helper function to escape HTML
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>