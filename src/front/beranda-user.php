<!-- cek sekarang ada di halaman apa -->
<?php 
session_start();
$currentPage = 'beranda'; 

// Check if user is logged in and is a siswa
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'siswa') {
    header("Location: ../../index.php");
    exit();
}

// Include logic files
require_once '../logic/dashboard-logic.php';
require_once '../logic/kelas-logic.php';

// Get dashboard data
$dashboardLogic = new DashboardLogic();
$kelasLogic = new KelasLogic();
$siswa_id = $_SESSION['user']['id'];
$dashboardData = $dashboardLogic->getDashboardSiswa($siswa_id);

// Ensure default values if data is null
if (!$dashboardData) {
    $dashboardData = [
        'totalKelas' => 0,
        'ujianSelesai' => 0,
        'rataNilai' => 0,
        'kelasTerbaru' => [],
        'ujianMendatang' => []
    ];
}
?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<?php require '../component/modal-join-class.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Beranda</title>
    <style>
        /* Additional responsive styles */
        @media (max-width: 768px) {
            .grid {
                gap: 1rem;
            }
            
            .text-xl, .text-2xl {
                font-size: 1.25rem;
            }
            
            .p-6 {
                padding: 1rem;
            }
        }
        
        /* Smooth transitions */
        .transition-all {
            transition-property: all;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 300ms;
        }
        
        /* Card hover effects */
        .hover\:shadow-md:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Main Content -->
    <div data-main-content class="md:ml-64 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">
        <!-- Header -->
        <header class="bg-white p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Beranda</h1>
                    <p class="text-gray-600">Selamat datang, <?php echo htmlspecialchars($_SESSION['user']['namaLengkap']); ?>!</p>
                </div>
                <div class="flex items-center space-x-2 md:space-x-4">
                    <button command="show-modal" commandfor="join-class-modal" class="p-2 border rounded-full text-gray-400 hover:text-orange-600 transition-colors flex items-center">
                        <i class="ti ti-user-plus text-lg md:text-xl"></i>
                        <span class="hidden md:inline ml-1 text-sm">Gabung Kelas</span>
                    </button>
                    <button class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="ti ti-bell text-lg md:text-xl"></i>
                    </button>
                    <button class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="ti ti-search text-lg md:text-xl"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6 mb-6 md:mb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6">
                    <div class="flex items-center">
                        <div class="p-2 md:p-3 bg-orange-tipis rounded-lg">
                            <i class="ti ti-book text-orange-600 text-lg md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-4">
                            <p class="text-xs md:text-sm text-gray-600">Total Kelas</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-800"><?php echo $dashboardData['totalKelas'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6">
                    <div class="flex items-center">
                        <div class="p-2 md:p-3 bg-orange-tipis rounded-lg">
                            <i class="ti ti-clipboard-check text-orange-600 text-lg md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-4">
                            <p class="text-xs md:text-sm text-gray-600">Ujian Selesai</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-800"><?php echo $dashboardData['ujianSelesai'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6 sm:col-span-2 md:col-span-1">
                    <div class="flex items-center">
                        <div class="p-2 md:p-3 bg-orange-tipis rounded-lg">
                            <i class="ti ti-star text-orange-600 text-lg md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-4">
                            <p class="text-xs md:text-sm text-gray-600">Rata-rata Nilai</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-800"><?php echo $dashboardData['rataNilai'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Classes Section -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg md:text-xl font-bold text-gray-800">Kelas Saya</h2>
                    <a href="kelas-user.php" class="text-orange-600 hover:text-orange-700 text-sm font-medium">Lihat Semua</a>
                </div>
                
                <?php if (isset($dashboardData['kelasTerbaru']) && !empty($dashboardData['kelasTerbaru'])): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                        <?php foreach ($dashboardData['kelasTerbaru'] as $kelas): ?>
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                                <div class="h-32 sm:h-40 md:h-48 bg-gradient-to-br from-orange-400 to-orange-600 relative">
                                    <?php if (!empty($kelas['gambarKover'])): ?>
                                        <img src="<?php echo htmlspecialchars($kelas['gambarKover']); ?>" alt="<?php echo htmlspecialchars($kelas['namaKelas']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                                            <i class="ti ti-book text-white text-4xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="absolute top-2 md:top-4 right-2 md:right-4">
                                        <span class="bg-white bg-opacity-90 text-orange-600 text-xs font-medium px-2 py-1 rounded-full">
                                            <?php echo htmlspecialchars($kelas['mataPelajaran'] ?? $kelas['namaKelas']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="p-4 md:p-6">
                                    <h3 class="font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($kelas['namaKelas']); ?></h3>
                                    <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($kelas['namaGuru'] ?? 'Guru'); ?></p>
                                    <div class="flex items-center justify-between text-xs md:text-sm text-gray-600 mb-3 md:mb-4">
                                        <span class="flex items-center">
                                            <i class="ti ti-users mr-1"></i>
                                            <?php echo $kelas['jumlahSiswa'] ?? 0; ?> siswa
                                        </span>
                                        <span class="flex items-center">
                                            <i class="ti ti-calendar mr-1"></i>
                                            <?php echo date('M Y', strtotime($kelas['dibuat'])); ?>
                                        </span>
                                    </div>
                                    <a href="kelas-user.php?id=<?php echo $kelas['id']; ?>" class="w-full bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors text-sm md:font-medium inline-block text-center">
                                        Masuk Kelas
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="ti ti-book-off text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Belum bergabung dengan kelas</h3>
                        <p class="text-gray-500 mb-4">Mulai dengan bergabung ke kelas pertama Anda</p>
                        <button command="show-modal" commandfor="join-class-modal" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                            <i class="ti ti-user-plus mr-2"></i>
                            Gabung Kelas
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../script/menu-bar-script.js"></script>
    <script src="../script/kelas-management.js"></script>
</body>
</html>