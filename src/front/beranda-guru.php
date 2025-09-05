<?php 
// cek sekarang ada di halaman apa
session_start();
$currentPage = 'beranda'; 

// Check if user is logged in and is a guru
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
        header("Location: ../../index.php");
        exit();
    }
    
    // Include logic files
    require_once '../logic/dashboard-logic.php';
    require_once '../logic/kelas-logic.php';
    
    // Get dashboard data
    $dashboardLogic = new DashboardLogic();
    $kelasLogic = new KelasLogic();
    $guru_id = $_SESSION['user']['id'];
    $dashboardData = $dashboardLogic->getDashboardGuru($guru_id);
    
    // Check if there's a new class to highlight
    $newClassId = isset($_GET['new_class']) ? intval($_GET['new_class']) : null;
    ?>
    <!-- includes -->
    <?php require '../component/sidebar.php'; ?>
    <?php require '../component/menu-bar-mobile.php'; ?>
    <?php require '../component/modal-add-class.php'; ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="user-id" content="<?php echo $_SESSION['user']['id']; ?>">
        <?php require '../../assets/head.php'; ?>
        <title>Beranda</title>
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
                        <button command="show-modal" commandfor="add-class-modal" class="p-2 border rounded-full text-gray-400 hover:text-orange-600 transition-colors flex items-center">
                            <i class="ti ti-plus text-lg md:text-xl"></i>
                            <span class="hidden md:inline ml-1 text-sm">Tambah Kelas</span>
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
                                <i class="ti ti-users text-orange-600 text-lg md:text-xl"></i>
                            </div>
                            <div class="ml-3 md:ml-4">
                                <p class="text-xs md:text-sm text-gray-600">Total Siswa</p>
                                <p class="text-xl md:text-2xl font-bold text-gray-800"><?php echo $dashboardData['totalSiswa'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6 sm:col-span-2 md:col-span-1">
                        <div class="flex items-center">
                            <div class="p-2 md:p-3 bg-orange-tipis rounded-lg">
                                <i class="ti ti-clipboard-check text-orange-600 text-lg md:text-xl"></i>
                            </div>
                            <div class="ml-3 md:ml-4">
                                <p class="text-xs md:text-sm text-gray-600">Ujian Aktif</p>
                                <p class="text-xl md:text-2xl font-bold text-gray-800"><?php echo $dashboardData['ujianAktif'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Classes Section -->
                <div class="mb-6">
                    <h2 class="text-lg md:text-xl font-bold text-gray-800 mb-4">Kelas Tersedia</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                        <?php if ($newClassId): ?>
                            <div class="col-span-full mb-4">
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <i class="ti ti-check-circle text-green-600 text-xl mr-3"></i>
                                        <div>
                                            <h3 class="text-green-800 font-medium">Kelas Berhasil Dibuat!</h3>
                                            <p class="text-green-700 text-sm">Kelas baru Anda sudah siap digunakan. Lihat kelas yang diberi highlight di bawah.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($dashboardData['kelasTerbaru'])): ?>
                            <?php foreach ($dashboardData['kelasTerbaru'] as $kelas): ?>
                                <?php $isNewClass = ($newClassId && $kelas['id'] == $newClassId); ?>
                                <div class="bg-white rounded-lg shadow-sm border <?php echo $isNewClass ? 'border-orange-300 ring-2 ring-orange-200' : 'border-gray-200'; ?> overflow-hidden hover:shadow-md transition-all <?php echo $isNewClass ? 'animate-pulse' : ''; ?>">
                                    <div class="h-32 sm:h-40 md:h-48 bg-gradient-to-br from-orange-400 to-orange-600 relative">
                                        <?php if (!empty($kelas['gambarKover'])): ?>
                                            <img src="../../<?php echo htmlspecialchars($kelas['gambarKover']); ?>" alt="<?php echo htmlspecialchars($kelas['namaKelas']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                                                <i class="ti ti-book text-white text-4xl"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="absolute top-2 md:top-4 right-2 md:right-4">
                                            <span class="bg-white bg-opacity-90 text-orange-600 text-xs font-medium px-2 py-1 rounded-full">
                                                <?php echo htmlspecialchars($kelas['mataPelajaran']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="p-4 md:p-6">
                                        <h3 class="font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($kelas['namaKelas']); ?></h3>
                                        <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($kelas['mataPelajaran']); ?></p>
                                        <div class="flex items-center justify-between text-xs md:text-sm text-gray-600 mb-3 md:mb-4">
                                            <span class="flex items-center">
                                                <i class="ti ti-users mr-1"></i>
                                                <?php echo $kelas['jumlahSiswa'] ?? 0; ?> siswa
                                            </span>
                                            <span class="flex items-center">
                                                <i class="ti ti-clipboard-check mr-1"></i>
                                                <?php echo $kelas['jumlahUjian'] ?? 0; ?> ujian
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between gap-2">
                                            <a href="kelas-guru.php?id=<?php echo $kelas['id']; ?>" class="flex-1 bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors text-sm md:font-medium text-center">
                                                <?php echo $isNewClass ? 'Masuk Kelas Baru' : 'Kelola'; ?>
                                            </a>
                                            <div class="relative">
                                                <button onclick="toggleDropdown('dropdown-<?php echo $kelas['id']; ?>')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                    <i class="ti ti-dots-vertical text-lg"></i>
                                                </button>
                                                <div id="dropdown-<?php echo $kelas['id']; ?>" class="hidden fixed w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                                                    <div class="py-1">
                                                        <a href="kelas-guru.php?id=<?php echo $kelas['id']; ?>" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            <i class="ti ti-eye mr-2"></i>
                                                            Detail
                                                        </a>
                                                        <a href="#" onclick="editKelas(<?php echo $kelas['id']; ?>)" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            <i class="ti ti-edit mr-2"></i>
                                                            Edit
                                                        </a>
                                                        <a href="#" onclick="hapusKelas(<?php echo $kelas['id']; ?>)" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                            <i class="ti ti-trash mr-2"></i>
                                                            Hapus
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-full flex flex-col items-center justify-center py-16 px-4">
                                <div class="w-24 h-24 bg-orange-100 rounded-full flex items-center justify-center mb-6">
                                    <i class="ti ti-book-plus text-orange text-4xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Belum Ada Kelas</h3>
                                <p class="text-gray-500 text-sm text-center mb-8 max-w-sm">
                                    Mulai membuat kelas pertama untuk mengelola siswa dan ujian
                                </p>
                                <button command="show-modal" commandfor="add-class-modal" class="inline-flex items-center px-6 py-3 bg-orange text-white font-medium rounded-lg hover:bg-orange-600 transition-colors">
                                    <i class="ti ti-plus mr-2"></i>
                                    Buat Kelas Pertama
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>

        <script src="../script/menu-bar-script.js"></script>
        <script src="../script/dropdown-beranda-guru.js"></script>
        <script src="../script/kelas-management.js"></script>
        <script src="../script/profile-sync.js"></script>

    <style src></style>
    </body>
    </html>