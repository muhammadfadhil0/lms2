<?php
// cek sekarang ada di halaman apa
session_start();
$currentPage = 'beranda';

// Check if user is logged in and is a guru
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    header("Location: ../../login.php");
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

// Check class limit for current guru
$classLimitInfo = $kelasLogic->canCreateClass($guru_id);
$canCreateClass = $classLimitInfo['success'] && $classLimitInfo['can_create'];
$isProUser = isset($classLimitInfo['role']) && $classLimitInfo['role'] === 'pro';

// Check if there's a new class to highlight
$newClassId = isset($_GET['new_class']) ? intval($_GET['new_class']) : null;
?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<?php require '../component/modal-add-class.php'; ?>
<?php require '../component/modal-delete-class.php'; ?>
<?php require '../component/modal-upgrade-to-pro.php'; ?>
<?php // Profile photo helper for fresh avatar URL 
?>
<?php require_once '../logic/profile-photo-helper.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Dark mode removed by request -->


    <meta name="user-id" content="<?php echo $_SESSION['user']['id']; ?>">
    <?php require '../../assets/head.php'; ?>
    <link rel="stylesheet" href="../css/search-system.css">
    <title>Beranda</title>
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
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Beranda</h1>
                    <p class="text-gray-600">Selamat datang,
                        <?php echo htmlspecialchars($_SESSION['user']['namaLengkap']); ?>!</p>
                </div>
                <div class="flex md:hidden items-center gap-2 mobile-logo-wrap">
                    <img src="../../assets/img/logo.png" alt="Logo" class="h-7 w-7 flex-shrink-0">
                    <div id="logoTextContainer"
                        class="transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap">
                        <h1 id="logoText" class="mobile-logo-text font-bold text-gray-800">Point</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-2 md:space-x-4" style="align-items: center;">
                    <div class="search-other-buttons flex items-center space-x-2 md:space-x-4">
                        <?php if ($canCreateClass): ?>
                        <button command="show-modal" commandfor="add-class-modal"
                            class="p-2 border rounded-full text-gray-400 hover:text-orange-600 transition-colors flex items-center">
                            <i class="ti ti-plus text-lg md:text-xl"></i>
                            <span class="hidden md:inline ml-1 text-sm">Tambah Kelas</span>
                        </button>
                        <?php else: ?>
                        <button onclick="showUpgradeToProModal()"
                            class="p-2 border border-orange-300 rounded-full text-orange-500 hover:text-orange-600 transition-colors flex items-center">
                            <i class="ti ti-crown text-lg md:text-xl"></i>
                            <span class="hidden md:inline ml-1 text-sm">Upgrade Pro</span>
                        </button>
                        <?php endif; ?>
                        <!-- Dark mode toggle removed -->
                        <button class="relative p-2 text-gray-400 hover:text-gray-600 transition-colors"
                            data-notification-trigger="true">
                            <i class="ti ti-bell text-lg md:text-xl"></i>
                            <!-- Notification Badge -->
                            <span id="notification-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium hidden min-w-[20px]">
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
            <!-- Stats Cards -->
            <div class="mb-6 md:mb-8">
                <div class="stats-scroll flex overflow-x-auto gap-3 -mx-1 px-1 
                            md:grid md:overflow-visible md:gap-6 md:mx-0 md:px-0 md:grid-cols-3">
                    <!-- Card: Total Kelas -->
                    <div
                        class="min-w-[150px] md:min-w-0 flex-1 bg-white rounded-lg shadow-sm border border-gray-200 p-3 md:p-6 flex items-center">
                        <div class="p-2 md:p-3 md:me-4 bg-orange-tipis rounded-lg flex-shrink-0 md:mb-3">
                            <i class="ti ti-book text-orange-600 text-base md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-0">
                            <p class="text-[11px] md:text-sm text-gray-600 tracking-wide">Total Kelas</p>
                            <p class="text-lg md:text-2xl font-bold text-gray-800 leading-tight">
                                <?php echo $dashboardData['totalKelas'] ?? 0; ?></p>
                        </div>
                    </div>
                    <!-- Card: Ujian Selesai -->
                    <div
                        class="min-w-[150px] md:min-w-0 flex-1 bg-white rounded-lg shadow-sm border border-gray-200 p-3 md:p-6 flex items-center">
                        <div class="p-2 md:p-3 md:me-4 bg-orange-tipis rounded-lg flex-shrink-0 md:mb-3">
                            <i class="ti ti-clipboard-check text-orange-600 text-base md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-0">
                            <p class="text-[11px] md:text-sm text-gray-600 tracking-wide">Total Siswa</p>
                            <p class="text-lg md:text-2xl font-bold text-gray-800 leading-tight">
                                <?php echo $dashboardData['totalSiswa'] ?? 0; ?></p>
                        </div>
                    </div>
                    <!-- Card: Rata-rata Nilai -->
                    <div
                        class="hidden md:flex min-w-[150px] md:min-w-0 flex-1 bg-white rounded-lg shadow-sm border border-gray-200 p-3 md:p-6 flex items-center">
                        <div class="p-2 md:p-3 md:me-4 bg-orange-tipis rounded-lg flex-shrink-0 md:mb-3">
                            <i class="ti ti-star text-orange-600 text-base md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-0">
                            <p class="text-[11px] md:text-sm text-gray-600 tracking-wide">Ujian Aktif</p>
                            <p class="text-lg md:text-2xl font-bold text-gray-800 leading-tight">
                                <?php echo $dashboardData['ujianAktif'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Classes Section -->
            <div class="mb-6">
                <h2 class="text-lg md:text-xl font-bold text-gray-800 mb-4">Kelas Tersedia</h2>
                <div class="search-results-container grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <?php if ($newClassId): ?>
                        <div class="col-span-full mb-4">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <i class="ti ti-check-circle text-green-600 text-xl mr-3"></i>
                                    <div>
                                        <h3 class="text-green-800 font-medium">Kelas Berhasil Dibuat!</h3>
                                        <p class="text-green-700 text-sm">Kelas baru Anda sudah siap digunakan. Lihat kelas
                                            yang diberi highlight di bawah.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($dashboardData['kelasTerbaru'])): ?>
                        <?php foreach ($dashboardData['kelasTerbaru'] as $kelas): ?>
                            <?php $isNewClass = ($newClassId && $kelas['id'] == $newClassId); ?>
                            <div class="search-card relative bg-white rounded-lg shadow-sm border <?php echo $isNewClass ? 'border-orange-300 ring-2 ring-orange-200' : 'border-gray-200'; ?> overflow-hidden hover:shadow-md transition-all <?php echo $isNewClass ? 'animate-pulse' : ''; ?>"
                                data-class-id="<?php echo $kelas['id']; ?>">
                                <div class="h-32 sm:h-40 md:h-48 bg-gradient-to-br from-orange-400 to-orange-600 relative">
                                    <?php if (!empty($kelas['gambar_kelas'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($kelas['gambar_kelas']); ?>"
                                            alt="<?php echo htmlspecialchars($kelas['namaKelas']); ?>"
                                            class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div
                                            class="w-full h-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                                            <i class="ti ti-book text-white text-4xl"></i>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Teacher avatar positioned at left on the cover/card boundary -->
                                    <div class="absolute left-4 md:left-6 bottom-0 transform translate-y-1/2">
                                        <?php
                                        // Use guru id from kelas if available, otherwise current guru
                                        $ownerId = isset($kelas['guru_id']) ? $kelas['guru_id'] : $guru_id;
                                        $ownerPhoto = getUserProfilePhotoUrl($ownerId);
                                        ?>
                                        <?php if ($ownerPhoto): ?>
                                            <img src="<?php echo htmlspecialchars($ownerPhoto); ?>" alt="Foto Guru"
                                                class="w-16 h-16 md:w-20 md:h-20 rounded-full border-4 border-white object-cover shadow-md"
                                                onerror="this.parentElement.innerHTML='<div class=\'w-16 h-16 md:w-20 md:h-20 rounded-full border-4 border-white bg-orange-600 flex items-center justify-center\'><i class=\'ti ti-user text-white text-xl\'></i></div>'">
                                        <?php else: ?>
                                            <div
                                                class="w-16 h-16 md:w-20 md:h-20 rounded-full border-4 border-white bg-orange-600 flex items-center justify-center shadow-md">
                                                <i class="ti ti-user text-white text-xl"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="pt-10 p-4 md:pt-12 md:p-6">
                                    <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($kelas['namaKelas']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-600 mb-3">
                                        <?php echo htmlspecialchars($kelas['deskripsi'] ?? 'Kelas pembelajaran'); ?></p>
                                    <div
                                        class="flex items-center justify-between text-xs md:text-sm text-gray-600 mb-3 md:mb-4">
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
                                        <a href="kelas-guru.php?id=<?php echo $kelas['id']; ?>"
                                            class="flex-1 bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors text-sm md:font-medium text-center">
                                            <?php echo $isNewClass ? 'Masuk Kelas Baru' : 'Kelola'; ?>
                                        </a>
                                        <div class="relative">
                                            <button onclick="toggleClassDropdown('class-dd-<?php echo $kelas['id']; ?>', this)"
                                                class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                <i class="ti ti-dots-vertical text-lg"></i>
                                            </button>
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
                            <?php if ($canCreateClass): ?>
                            <button command="show-modal" commandfor="add-class-modal"
                                class="inline-flex items-center px-6 py-3 bg-orange text-white font-medium rounded-lg hover:bg-orange-600 transition-colors">
                                <i class="ti ti-plus mr-2"></i>
                                Buat Kelas Pertama
                            </button>
                            <?php else: ?>
                            <button onclick="showUpgradeToProModal()"
                                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-orange-600 to-amber-600 text-white font-medium rounded-lg hover:from-orange-700 hover:to-amber-700 transition-colors">
                                <i class="ti ti-crown mr-2"></i>
                                Upgrade ke Pro
                            </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Floating Dropdown Container - Outside of all other elements -->
    <?php if (!empty($dashboardData['kelasTerbaru'])): ?>
    <div id="floating-dropdowns" class="fixed top-0 left-0 w-full h-full pointer-events-none z-[10000]">
        <?php foreach ($dashboardData['kelasTerbaru'] as $kelas): ?>
            <div id="class-dd-<?php echo $kelas['id']; ?>"
                class="hidden absolute w-48 bg-white border border-gray-200 rounded-lg shadow-xl py-1 text-sm pointer-events-auto"
                style="box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                <a href="#"
                    onclick="showDeleteClassModal(<?php echo $kelas['id']; ?>, '<?php echo htmlspecialchars($kelas['namaKelas'], ENT_QUOTES); ?>')"
                    class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                    <i class="ti ti-trash mr-2"></i>
                    Hapus
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <script src="../script/menu-bar-script.js"></script>
    <script src="../script/dropdown-beranda-guru.js"></script>
    <script src="../script/kelas-management.js"></script>
    <script src="../script/delete-class-modal.js"></script>
    <script src="../script/upgrade-to-pro-modal.js"></script>
    <script src="../script/profile-sync.js"></script>
    
    <!-- Floating Dropdown Script -->
    <script>
        function toggleClassDropdown(id, buttonElement) {
            // Close all other dropdowns
            document.querySelectorAll('[id^="class-dd-"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            
            const dropdown = document.getElementById(id);
            if (!dropdown) return;
            
            if (dropdown.classList.contains('hidden')) {
                // Show dropdown - calculate position
                const buttonRect = buttonElement.getBoundingClientRect();
                const dropdownWidth = 192; // w-48 = 12rem = 192px
                const dropdownHeight = dropdown.offsetHeight || 60; // estimate
                
                let left = buttonRect.right - dropdownWidth;
                let top = buttonRect.bottom + 8;
                
                // Adjust if dropdown goes off-screen
                if (left < 8) left = 8;
                if (left + dropdownWidth > window.innerWidth - 8) {
                    left = window.innerWidth - dropdownWidth - 8;
                }
                if (top + dropdownHeight > window.innerHeight - 8) {
                    top = buttonRect.top - dropdownHeight - 8;
                }
                
                dropdown.style.left = left + 'px';
                dropdown.style.top = top + 'px';
                dropdown.classList.remove('hidden');
            } else {
                // Hide dropdown
                dropdown.classList.add('hidden');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', e => {
            if (!e.target.closest('[id^="class-dd-"]') && !e.target.closest('button[onclick*="toggleClassDropdown"]')) {
                document.querySelectorAll('[id^="class-dd-"]').forEach(el => el.classList.add('hidden'));
            }
        });
        
        // Close dropdown on scroll or resize
        window.addEventListener('scroll', () => {
            document.querySelectorAll('[id^="class-dd-"]').forEach(el => el.classList.add('hidden'));
        });
        
        window.addEventListener('resize', () => {
            document.querySelectorAll('[id^="class-dd-"]').forEach(el => el.classList.add('hidden'));
        });
    </script>
    
    <!-- Search System -->
    <style>
        /* Ensure search system doesn't break header layout */
        .search-container {
            display: inline-flex !important;
            vertical-align: middle !important;
            align-items: center !important;
        }
        
        .search-container:not(.searching) {
            width: auto !important;
            height: auto !important;
        }
        
        /* Maintain header button alignment */
        .flex.items-center.space-x-2,
        .flex.items-center.space-x-4 {
            align-items: center !important;
        }
        
        .search-other-buttons {
            display: flex !important;
            align-items: center !important;
        }
    </style>
    <script>
        // Configure search system for this page
        window.searchSystemConfig = {
            searchButtonSelector: '.search-btn',
            otherButtonsSelector: '.search-other-buttons',
            resultsContainerSelector: '.search-results-container',
            cardSelector: '.search-card',
            apiEndpoint: '../logic/search-kelas-api.php',
            searchFields: ['namaKelas', 'deskripsi'],
            debounceDelay: 800,
            minSearchLength: 1
        };
    </script>
    <script src="../script/search-system.js"></script>

    <!-- Dynamic Modal Component -->
    <?php require '../component/modal-dynamic.php'; ?>

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

    <style>
        /* Simple Modal Upgrade Pro Styles */
        #upgradeToProModal {
            z-index: 9999;
        }
        
        /* Backdrop click to close */
        #upgradeToProModal .el-dialog-backdrop {
            cursor: pointer;
        }
        
        /* Prevent modal content clicks from closing */
        #upgradeToProModal .el-dialog-panel {
            cursor: default;
        }

        
        /* Modal panel with smooth slide and scale */
        #upgradeToProModal .el-dialog-panel,
        #upgradeToProModal [class*="dialog-panel"] {
            z-index: 99999 !important;
            position: relative !important;
            background-color: white !important;
            transform: translateY(30px) scale(0.9);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 
                        0 10px 20px -5px rgba(0, 0, 0, 0.1) !important;
        }
        
        #upgradeToProModal.show .el-dialog-panel,
        #upgradeToProModal.show [class*="dialog-panel"] {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        
        /* Gradient border glow effect */
        #upgradeToProModal .el-dialog-panel::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: linear-gradient(45deg, #f97316, #f59e0b, #f97316);
            border-radius: 12px;
            z-index: -1;
            opacity: 0.3;
            filter: blur(8px);
            animation: gradientShift 3s ease-in-out infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { 
                background: linear-gradient(45deg, #f97316, #f59e0b, #f97316);
            }
            50% { 
                background: linear-gradient(45deg, #f59e0b, #f97316, #f59e0b);
            }
        }
        
        /* Enhanced button animations */
        #upgradeToProModal button {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateY(0);
        }
        
        #upgradeToProModal button:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
        }
        
        #upgradeToProModal button:active {
            transform: translateY(0);
        }
        
        /* Smooth list item animations */
        #upgradeToProModal ul li {
            opacity: 0;
            transform: translateX(-15px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        #upgradeToProModal.show ul li:nth-child(1) { 
            transition-delay: 0.3s;
            opacity: 1;
            transform: translateX(0);
        }
        #upgradeToProModal.show ul li:nth-child(2) { 
            transition-delay: 0.4s;
            opacity: 1;
            transform: translateX(0);
        }
        #upgradeToProModal.show ul li:nth-child(3) { 
            transition-delay: 0.5s;
            opacity: 1;
            transform: translateX(0);
        }
        #upgradeToProModal.show ul li:nth-child(4) { 
            transition-delay: 0.6s;
            opacity: 1;
            transform: translateX(0);
        }
        
        /* Crown icon animation */
        #upgradeToProModal .icon-tabler-crown {
            animation: crownFloat 2s ease-in-out infinite;
        }
        
        @keyframes crownFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-3px) rotate(2deg); }
        }
        
        /* Price tag pulse effect */
        #upgradeToProModal .bg-orange-50 {
            animation: pricePulse 2s ease-in-out infinite;
        }
        
        @keyframes pricePulse {
            0%, 100% { 
                box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.3);
            }
            50% { 
                box-shadow: 0 0 0 8px rgba(249, 115, 22, 0.1);
            }
        }

        /* Floating dropdown container - Lower z-index when upgrade modal is open */
        #floating-dropdowns {
            z-index: 10000;
            pointer-events: none;
        }
        
        /* When upgrade modal is open, lower other high z-index elements */
        body:has(#upgradeToProModal:not(.hidden)) #floating-dropdowns,
        body:has(#upgradeToProModal:not(.hidden)) .search-results-container,
        body:has(#upgradeToProModal:not(.hidden)) .fixed {
            z-index: 1000 !important;
        }
        
        /* Ensure cards don't interfere */
        .search-card {
            z-index: 1;
            position: relative;
        }

        /* Floating dropdown styling */
        #floating-dropdowns [id^="class-dd-"] {
            pointer-events: auto;
            backdrop-filter: blur(8px);
            background-color: rgba(255, 255, 255, 0.95);
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease-out;
        }

        #floating-dropdowns [id^="class-dd-"]:not(.hidden) {
            animation: dropdownFadeIn 0.2s ease-out;
        }

        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-8px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Modal backdrop and content styling */
        #upgradeToProModal:not(.hidden) {
            display: block !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
        }
        
        /* Ensure modal content is always visible */
        #upgradeToProModal .bg-white {
            background-color: white !important;
            position: relative !important;
            z-index: 99999 !important;
        }

        /* Mobile responsive adjustments */
        @media (max-width: 640px) {
            /* Ensure dropdown works well on mobile */
            [id^="class-dd-"] {
                min-width: 180px;
                right: 0;
            }
            
            /* Mobile modal optimizations */
            #upgradeToProModal {
                padding: 0.75rem !important;
            }
            
            #upgradeToProModal .el-dialog-panel,
            #upgradeToProModal [class*="dialog-panel"] {
                margin: 1rem !important;
                max-width: calc(100vw - 2rem) !important;
                transform: translateY(40px) scale(0.9);
            }
            
            #upgradeToProModal.show .el-dialog-panel,
            #upgradeToProModal.show [class*="dialog-panel"] {
                transform: translateY(0) scale(1);
            }
            
            /* Mobile animations are faster */
            #upgradeToProModal,
            #upgradeToProModal .el-dialog-backdrop,
            #upgradeToProModal [class*="backdrop"],
            #upgradeToProModal .el-dialog-panel,
            #upgradeToProModal [class*="dialog-panel"] {
                transition-duration: 0.25s !important;
            }
            
            /* Reduce blur on mobile for performance */
            #upgradeToProModal:not(.hidden) {
                backdrop-filter: blur(2px);
            }
        }
        
        /* Reduced motion preferences */
        @media (prefers-reduced-motion: reduce) {
            #upgradeToProModal,
            #upgradeToProModal *,
            #upgradeToProModal ul li {
                animation: none !important;
                transition-duration: 0.1s !important;
            }
            
            #upgradeToProModal:not(.hidden) {
                backdrop-filter: none;
            }
        }
    </style>
</body>

</html>