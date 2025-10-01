<!-- cek sekarang ada di halaman apa -->
<?php
session_start();
$currentPage = 'beranda';

// Check if user is logged in and is a siswa
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'siswa') {
    header("Location: ../../login.php");
    exit();
}

// Include logic files
require_once '../logic/dashboard-logic.php';
require_once '../logic/kelas-logic.php';
require_once '../logic/notification-logic.php';
require_once '../logic/advertisement-logic.php';

// Get dashboard data
$dashboardLogic = new DashboardLogic();
$kelasLogic = new KelasLogic();
$notificationLogic = new NotificationLogic();
$advertisementLogic = new AdvertisementLogic();
$siswa_id = $_SESSION['user']['id'];
$dashboardData = $dashboardLogic->getDashboardSiswa($siswa_id);

// Get active advertisements
$advertisements = $advertisementLogic->getActiveAdvertisements();

// Get recent posts from all classes
$recentPosts = $dashboardLogic->getPostinganTerbaruSiswa($siswa_id, 5); // Reduced from 15 to 5

// Get recent notifications (2 latest) - will be loaded via AJAX for better integration
$recentNotifications = [];
$unreadNotificationsCount = 0;

// Get latest 2 assignments from classes the student follows
$recentAssignments = [];
try {
    require_once '../logic/koneksi.php';
    $sql = "SELECT t.id, t.judul as title, t.deadline, t.kelas_id, k.namaKelas, t.created_at
            FROM tugas t
            JOIN kelas k ON t.kelas_id = k.id
            JOIN kelas_siswa ks ON k.id = ks.kelas_id AND ks.siswa_id = ? AND ks.status = 'aktif'
            WHERE k.status = 'aktif'
            ORDER BY t.created_at DESC
            LIMIT 2";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$siswa_id]);
    $recentAssignments = $stmt->fetchAll();
} catch (Exception $e) {
    $recentAssignments = [];
}

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
<?php require '../component/modal-notifications.php'; ?>
<?php require '../component/modal-assignment-list.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CRITICAL: Prevent FOUC for font-size only -->
    <script>
        (function () {
            const savedFontSize = localStorage.getItem('userFontSize') || '100';
            try {
                const fontSizePercentage = savedFontSize / 100;
                document.documentElement.style.fontSize = `${fontSizePercentage}rem`;
            } catch (e) {
                document.documentElement.style.fontSize = '1rem';
            }
        })();
    </script>

    <meta name="user-id" content="<?php echo $_SESSION['user']['id']; ?>">
    <?php require '../../assets/head.php'; ?>
    <link rel="stylesheet" href="../css/kelas-posting.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/media-upload.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/file-upload.css?v=<?php echo time(); ?>">
    <title>Beranda</title>
    <style>
        /* Additional responsive styles */
        @media (max-width: 768px) {
            .grid {
                gap: 1rem;
            }

            .text-xl,
            .text-2xl {
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

        /* Post content styling */
        .post-content strong {
            font-weight: 600;
            color: #1f2937;
        }

        .post-content em {
            font-style: italic;
            color: #374151;
        }

        /* Image error handling */
        .post-image-error {
            display: none !important;
        }

        /* Advertisement Slider Styles */
        .advertisement-slider {
            position: relative;
            min-height: 200px;
        }

        .advertisement-item {
            display: none;
            animation: fadeIn 0.5s ease-in-out;
        }

        .advertisement-item.active {
            display: block;
        }

        .ad-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            border: none;
            background-color: #d1d5db;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .ad-indicator.active {
            background-color: #f97316;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        /* Post Layout Fixes */
        .bg-white.rounded-lg.shadow-sm.border.border-gray-200 {
            overflow: hidden;
            /* Prevent content from breaking out */
        }

        /* Media Container Fixes */
        .post-media-container {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }

        .post-media-grid {
            display: grid;
            gap: 0.5rem;
            width: 100%;
            max-width: 100%;
        }

        .post-media-grid.grid-1 {
            grid-template-columns: 1fr;
        }

        .post-media-grid.grid-2 {
            grid-template-columns: 1fr 1fr;
        }

        .post-media-grid.grid-3 {
            grid-template-columns: 2fr 1fr;
            grid-template-rows: 1fr 1fr;
        }

        .post-media-grid.grid-3 .post-media-item:first-child {
            grid-row: span 2;
        }

        .post-media-grid.grid-4 {
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
        }

        .post-media-item {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
            border-radius: 0.5rem;
        }

        .post-media-item.single {
            aspect-ratio: 16/9;
            max-height: 400px;
        }

        .post-media {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Comments section fixes */
        div[id^="comments-preview-"] {
            max-width: 100%;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Assignment content fixes */
        .bg-gradient-to-r.from-blue-50.to-indigo-50 {
            max-width: 100%;
            overflow: hidden;
        }

        /* Prevent content overflow */
        .post-content {
            max-width: 100%;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Ensure all containers stay within bounds */
        .bg-white.rounded-lg .space-y-2,
        .bg-white.rounded-lg .space-y-4 {
            max-width: 100%;
        }

        /* Fix for assignment form containers */
        div[id^="submission-form-"] {
            max-width: 100%;
            box-sizing: border-box;
        }

        /* AI button positioning fix */
        .ai-explain-btn {
            white-space: nowrap;
        }

        /* Responsive fixes for mobile */
        @media (max-width: 768px) {

            .post-media-grid.grid-3,
            .post-media-grid.grid-4 {
                grid-template-columns: 1fr 1fr;
            }

            .post-media-grid.grid-3 .post-media-item:first-child {
                grid-row: span 1;
            }

            .post-media-item {
                aspect-ratio: 1;
            }

            .post-media-item.single {
                aspect-ratio: 16/9;
                max-height: 250px;
            }
        }
    </style>
</head>

<body class="bg-gray-50">

    <!-- Header (fixed, shifted right on desktop and below left sidebar) -->
    <header class="site-header md:hidden bg-white px-3 py-1 header-compact border-b border-gray-200 fixed top-0 z-30"
        style="height:3.5rem; left:0; right:0;">
        <style>
            /* Header transition and collapsed state support */
            .site-header {
                transition: left 220ms cubic-bezier(0.4, 0, 0.2, 1), width 220ms cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* When sidebar is collapsed, shift header left to align next to collapsed sidebar */
            .sidebar-collapsed .site-header {
                left: 4rem;
                /* collapsed sidebar width approximation (w-16 = 4rem) */
            }

            /* Shift header to the right on md+ so it sits beside the left sidebar
               and keep header z-index lower than the sidebar (sidebar z-40) */
            @media (min-width: 768px) {
                .site-header {
                    left: 16rem;
                    /* equal to sidebar width (w-64) */
                    right: 0;
                }
            }

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
                <h1 class="text-xl md:text-2xl font-bold text-gray-800 m-0">Beranda</h1>
                <p class="text-gray-600 m-0">Selamat datang,
                    <?php echo htmlspecialchars($_SESSION['user']['namaLengkap']); ?>!
                </p>
            </div>
            <div class="flex md:hidden items-center gap-2 mobile-logo-wrap">
                <img src="../../assets/img/logo.png" alt="Logo" class="h-7 w-7 flex-shrink-0">
                <div id="logoTextContainer"
                    class="transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap">
                    <h1 id="logoText" class="mobile-logo-text font-bold text-gray-800">Point</h1>
                </div>
            </div>
            <div class="flex items-center action-buttons gap-1 md:space-x-4">
                <button command="show-modal" commandfor="join-class-modal"
                    class="p-1 md:p-2 border rounded-full text-gray-400 hover:text-orange-600 transition-colors flex items-center">
                    <i class="ti ti-user-plus text-base md:text-xl"></i>
                    <span class="inline md:hidden ml-1 text-sm">Gabung</span>
                    <span class="hidden md:inline ml-1 text-sm">Gabung Kelas</span>
                </button>
                <!-- Dark mode toggle removed -->
                <button class="p-1 md:p-2 text-gray-400 hover:text-gray-600 transition-colors"
                    data-notification-trigger="true">
                    <i class="ti ti-bell text-base md:text-xl"></i>
                </button>
                <button class="p-1 md:p-2 text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="ti ti-search text-base md:text-xl"></i>
                </button>
            </div>
        </div>
    </header>

    <div data-main-content
        class="pt-[3.5rem] md:pt-0 md:ml-64 md:mr-96 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
            <!-- Stats Cards (Horizontal scroll on mobile, grid on md+) -->
            <style>
                /* Hanya gaya kecil khusus komponen ini */
                @media (max-width: 767px) {
                    .stats-scroll {
                        -ms-overflow-style: none;
                        scrollbar-width: none;
                    }

                    .stats-scroll::-webkit-scrollbar {
                        display: none;
                    }
                }
            </style>
            <div class="mb-6 md:mb-8 md:hidden">
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
                                <?php echo $dashboardData['totalKelas'] ?? 0; ?>
                            </p>
                        </div>
                    </div>
                    <!-- Card: Ujian Selesai -->
                    <div
                        class="min-w-[150px] md:min-w-0 flex-1 bg-white rounded-lg shadow-sm border border-gray-200 p-3 md:p-6 flex items-center">
                        <div class="p-2 md:p-3 md:me-4 bg-orange-tipis rounded-lg flex-shrink-0 md:mb-3">
                            <i class="ti ti-clipboard-check text-orange-600 text-base md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-0">
                            <p class="text-[11px] md:text-sm text-gray-600 tracking-wide">Ujian Selesai</p>
                            <p class="text-lg md:text-2xl font-bold text-gray-800 leading-tight">
                                <?php echo $dashboardData['ujianSelesai'] ?? 0; ?>
                            </p>
                        </div>
                    </div>
                    <!-- Card: Rata-rata Nilai -->
                    <div
                        class="hidden md:flex min-w-[150px] md:min-w-0 flex-1 bg-white rounded-lg shadow-sm border border-gray-200 p-3 md:p-6 flex items-center">
                        <div class="p-2 md:p-3 md:me-4 bg-orange-tipis rounded-lg flex-shrink-0 md:mb-3">
                            <i class="ti ti-star text-orange-600 text-base md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-0">
                            <p class="text-[11px] md:text-sm text-gray-600 tracking-wide">Rata-rata Nilai</p>
                            <p class="text-lg md:text-2xl font-bold text-gray-800 leading-tight">
                                <?php echo $dashboardData['rataNilai'] ?? 0; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar (Desktop-only, fixed) -->
            <style>
                /* Small custom styles for the right sidebar */
                @media (min-width: 768px) {
                    .beranda-right-sidebar {
                        width: 360px;
                        position: fixed;
                        right: 1.5rem;
                        /* align with padding */
                        top: 0;
                        /* pin to top because header hidden on desktop */
                        height: 100vh;
                        overflow: hidden;
                        z-index: 40;
                    }

                    .beranda-right-sidebar .sidebar-scroll {
                        height: 100%;
                        overflow-y: auto;
                        -ms-overflow-style: none;
                        scrollbar-width: thin;
                        overscroll-behavior: contain;
                        /* prevent scroll chaining to page */
                    }

                    .beranda-right-sidebar .sidebar-scroll::-webkit-scrollbar {
                        width: 8px;
                    }

                    .beranda-right-sidebar .sidebar-scroll::-webkit-scrollbar-thumb {
                        background: rgba(0, 0, 0, 0.08);
                        border-radius: 999px;
                    }
                }
            </style>

            <aside class="hidden md:block beranda-right-sidebar">
                <div class="bg-transparent h-full rounded-lg shadow-none">
                    <div class="sidebar-scroll bg-transparent p-2 mt-4 pb-4 ps-5 overflow-y-auto">
                        <!-- Card: Papan Iklan -->
                        <?php if (!empty($advertisements)): ?>
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 overflow-hidden">
                                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                    <h3 class="text-base font-semibold text-gray-800">Rekomendasi</h3>
                                    <?php if (count($advertisements) > 1): ?>
                                        <div class="flex items-center space-x-2">
                                            <button id="prev-ad-btn"
                                                class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                                                <i class="ti ti-chevron-left"></i>
                                            </button>
                                            <button id="next-ad-btn"
                                                class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                                                <i class="ti ti-chevron-right"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3 relative">
                                    <!-- Advertisement slider -->
                                    <div class="advertisement-slider relative">
                                        <?php foreach ($advertisements as $index => $ad): ?>
                                            <div class="advertisement-item <?php echo $index === 0 ? 'active' : ''; ?>"
                                                data-ad-id="<?php echo $ad['id']; ?>">
                                                <div
                                                    class="rounded-lg overflow-hidden bg-gray-50 border border-gray-100 hover:shadow-md transition-shadow">
                                                    <?php if ($ad['link_url'] && $ad['link_url'] !== '#'): ?>
                                                        <a href="<?php echo htmlspecialchars($ad['link_url']); ?>" target="_blank"
                                                            class="block">
                                                        <?php endif; ?>
                                                        <?php if ($ad['image_path']): ?>
                                                            <div class="w-full h-40 bg-cover bg-center bg-gray-200 <?php echo ($ad['link_url'] && $ad['link_url'] !== '#') ? 'cursor-pointer' : ''; ?>"
                                                                style="background-image: url('<?php echo htmlspecialchars($ad['image_path']); ?>');"
                                                                onerror="this.style.backgroundImage='url(../../assets/img/placeholder-ad.jpg)';">
                                                            </div>
                                                        <?php else: ?>
                                                            <div
                                                                class="w-full h-40 bg-gradient-to-br from-orange-100 to-orange-200 flex items-center justify-center <?php echo ($ad['link_url'] && $ad['link_url'] !== '#') ? 'cursor-pointer' : ''; ?>">
                                                                <i class="ti ti-photo text-4xl text-orange-400"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($ad['link_url'] && $ad['link_url'] !== '#'): ?>
                                                        </a>
                                                    <?php endif; ?>
                                                    <div class="p-3">
                                                        <div class="text-sm font-semibold text-gray-900">
                                                            <?php echo htmlspecialchars($ad['title']); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500 mt-1 line-clamp-2">
                                                            <?php echo htmlspecialchars($ad['description']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if (count($advertisements) > 1): ?>
                                        <!-- Advertisement indicators -->
                                        <div class="flex justify-center mt-3 space-x-2">
                                            <?php foreach ($advertisements as $index => $ad): ?>
                                                <button class="ad-indicator <?php echo $index === 0 ? 'active' : ''; ?>"
                                                    data-index="<?php echo $index; ?>"></button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Minimal Info Card (Desktop-only moved from top stats) -->
                        <div class="hidden md:block bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                            <h3 class="text-sm font-semibold text-gray-800 mb-3">Info</h3>
                            <div class="space-y-3 text-sm text-gray-700">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Total Kelas</span>
                                    <span
                                        class="font-semibold text-gray-800"><?php echo $dashboardData['totalKelas'] ?? 0; ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Ujian Selesai</span>
                                    <span
                                        class="font-semibold text-gray-800"><?php echo $dashboardData['ujianSelesai'] ?? 0; ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Rata-rata Nilai</span>
                                    <span
                                        class="font-semibold text-gray-800"><?php echo $dashboardData['rataNilai'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Card: Pemberitahuan -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Pemberitahuan
                                    <?php if ($unreadNotificationsCount > 0): ?>
                                        <span
                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            <?php echo $unreadNotificationsCount; ?>
                                        </span>
                                    <?php endif; ?>
                                </h3>
                                <button onclick="openNotificationsModal()"
                                    class="text-xs text-gray-500 hover:text-gray-700">
                                    Lihat Semua
                                </button>
                            </div>
                            <div class="p-2" id="beranda-notifications-container">
                                <?php if (!empty($recentNotifications)): ?>
                                    <ul class="divide-y divide-gray-100">
                                        <?php foreach ($recentNotifications as $notification): ?>
                                            <?php
                                            $redirectUrl = $notificationLogic->getNotificationRedirectUrl($notification);
                                            $hasValidRedirect = $notificationLogic->hasValidRedirect($notification);
                                            ?>
                                            <li class="px-3 py-2 flex items-start space-x-3 hover:bg-gray-50 cursor-pointer <?php echo $notification['is_read'] ? 'opacity-75' : ''; ?>"
                                                onclick="handleNotificationClick(<?php echo $notification['id']; ?>, '<?php echo $redirectUrl; ?>', <?php echo $hasValidRedirect ? 'true' : 'false'; ?>, this)">
                                                <div class="flex-shrink-0 mt-1">
                                                    <i
                                                        class="ti <?php echo $notificationLogic->getNotificationIcon($notification['type']); ?> <?php echo $notificationLogic->getNotificationColor($notification['type']); ?>"></i>
                                                    <?php if (!$notification['is_read']): ?>
                                                        <div class="w-2 h-2 bg-orange-500 rounded-full -mt-1 -ml-1"></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm text-gray-900 font-medium">
                                                        <?php echo htmlspecialchars($notification['title']); ?>
                                                    </div>
                                                    <div class="text-xs text-gray-600 mb-1">
                                                        <?php echo htmlspecialchars($notification['message']); ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        <?php if ($notification['nama_kelas']): ?>
                                                            Kelas: <?php echo htmlspecialchars($notification['nama_kelas']); ?> •
                                                        <?php endif; ?>
                                                        <?php echo $notificationLogic->getTimeAgo($notification['created_at']); ?>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="px-3 py-4 text-center text-gray-500 text-sm">
                                        <i class="ti ti-bell-off text-2xl mb-2 block"></i>
                                        Tidak ada pemberitahuan
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Card: Tugas Terbaru (dynamic) -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-800">Tugas Terbaru</h3>
                                <button onclick="openAssignmentsModal()"
                                    class="text-xs text-gray-500 hover:text-gray-700">Lihat Semua</button>
                            </div>
                            <div class="p-2 space-y-2" id="beranda-recent-assignments">
                                <?php if (!empty($recentAssignments)): ?>
                                    <?php foreach ($recentAssignments as $assign): ?>
                                        <?php
                                        $deadlineText = $assign['deadline'] ? date('d M Y, H:i', strtotime($assign['deadline'])) : 'Tidak ada deadline';
                                        ?>
                                        <div class="px-3 py-2 bg-gray-50 rounded hover:bg-gray-100">
                                            <div class="flex items-center justify-between">
                                                <div class="min-w-0">
                                                    <div class="text-sm font-medium text-gray-900 truncate">
                                                        <?php echo htmlspecialchars($assign['title']); ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500">Kelas:
                                                        <?php echo htmlspecialchars($assign['namaKelas']); ?> • Deadline:
                                                        <?php echo $deadlineText; ?>
                                                    </div>
                                                </div>
                                                <a href="../front/kelas-user.php?id=<?php echo (int) $assign['kelas_id']; ?>#post-assignment-<?php echo (int) $assign['id']; ?>"
                                                    class="ml-3 px-2 py-1 bg-white border border-gray-200 text-sm rounded">Lihat</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="px-3 py-4 text-center text-gray-500 text-sm">
                                        <i class="ti ti-clipboard-off text-2xl mb-2 block"></i>
                                        Tidak ada tugas terbaru
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </aside>
            <script>
                (function () {
                    // Enable sidebar-only scrolling when pointer is over the right sidebar
                    var sidebarScroll = document.querySelector('.beranda-right-sidebar .sidebar-scroll');
                    if (!sidebarScroll) return;

                    // Ensure the element can receive wheel events and prevent page scroll
                    sidebarScroll.addEventListener('wheel', function (e) {
                        // Only intercept vertical scrolling
                        if (Math.abs(e.deltaY) < 1) return;
                        // Scroll the sidebar container
                        sidebarScroll.scrollTop += e.deltaY;
                        // Prevent the page from scrolling
                        e.preventDefault();
                    }, { passive: false });
                })();
            </script>

            <!-- Recent Posts Section -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg md:text-xl font-bold text-gray-800">Beranda</h2>
                </div>

                <!-- Posts Feed -->
                <div id="postsContainer" class="space-y-6">
                    <!-- Initial loading state -->
                    <div class="text-center py-12 text-gray-500">
                        <i class="ti ti-loader animate-spin text-4xl mb-4"></i>
                        <p class="text-lg font-medium">Memuat postingan...</p>
                        <p class="text-sm text-gray-400 mt-1">Mohon tunggu sebentar</p>
                    </div>
                </div>
                                <!-- Load More Button for Beranda (if needed) -->
                <div class="text-center mt-6 hidden" id="loadMoreContainer">
                    <button id="loadMorePosts"
                        class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                        <i class="ti ti-plus mr-2"></i>
                        Muat Postingan Lainnya
                    </button>
                </div>
            </div>

        </main>
        </div>

    </main>
    </div>

    <!-- Include Modal Components -->
    <?php require '../component/modal-comments.php'; ?>
    <?php require '../component/modal-ai-explanation.php'; ?>
    <?php /* Modal submit assignment dihilangkan pada beranda karena diganti inline form */ ?>

    <script src="../script/menu-bar-script.js"></script>
    <script src="../script/kelas-management.js"></script>
    <script src="../script/media-upload-manager.js"></script>
    <script src="../script/photoswipe-simple.js"></script>
    <script src="../script/assignment-manager.js"></script>
    <script src="../script/assignment-file-manager.js?v=<?php echo time(); ?>"></script>
    <script src="../script/ai-explanation-manager.js?v=<?php echo time(); ?>"></script>
    <script src="../script/kelas-posting-stable.js?v=<?php echo time(); ?>"></script>
    <script>
        // BERANDA DEBUG & MEDIA FUNCTIONS
        console.log('🏠 BERANDA-USER.PHP DEBUG INITIALIZED');
        console.log('📱 Current User ID:', <?php echo $_SESSION['user']['id']; ?>);
        console.log('👤 Current User Role:', '<?php echo $_SESSION['user']['role']; ?>');
        console.log('📊 Recent Posts Count:', <?php echo count($recentPosts); ?>);
        console.log('🔔 Notifications Count:', <?php echo count($recentNotifications); ?>);
        console.log('🔴 Unread Notifications:', <?php echo $unreadNotificationsCount; ?>);

        // Notification functions
        window.markNotificationAsRead = async function (notificationId, element) {
            if (element.classList.contains('opacity-75')) {
                // Already read, just open modal
                openNotificationsModal();
                return;
            }

            try {
                const response = await fetch('../logic/mark-notification-read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ notification_id: notificationId })
                });

                const data = await response.json();

                if (data.success) {
                    // Update UI
                    element.classList.add('opacity-75');
                    const unreadDot = element.querySelector('.bg-orange-500');
                    if (unreadDot) unreadDot.remove();

                    // Update unread count badge
                    updateUnreadBadge();

                    // Open full notifications modal
                    openNotificationsModal();
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
                // Still open modal even if mark as read fails
                openNotificationsModal();
            }
        };

        // Handle notification click with redirect
        window.handleNotificationClick = async function (notificationId, redirectUrl, hasValidRedirect, element) {
            console.log('🔔 Notification clicked:', { notificationId, redirectUrl, hasValidRedirect });

            // First, mark as read if not already read
            if (!element.classList.contains('opacity-75')) {
                try {
                    const response = await fetch('../logic/mark-notification-read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ notification_id: notificationId })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Update UI
                        element.classList.add('opacity-75');
                        const unreadDot = element.querySelector('.bg-orange-500');
                        if (unreadDot) unreadDot.remove();

                        // Update unread count badge
                        updateUnreadBadge();
                    }
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                }
            }

            // Then redirect if valid target exists
            if (hasValidRedirect && redirectUrl && redirectUrl !== 'beranda-user.php') {
                console.log('🔗 Redirecting to:', redirectUrl);
                // Add a small delay to ensure UI updates are visible
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 300);
            } else {
                console.log('📝 No valid redirect, staying on beranda');
                // If no valid redirect, just show a message or do nothing
                showToast('Notifikasi ditandai sebagai dibaca');
            }
        };

        window.loadBerandaNotifications = async function () {
            try {
                const response = await fetch('../logic/get-notifications.php?limit=2');
                const data = await response.json();

                if (data.success) {
                    // Cache notifications for modal fallback
                    window.berandaNotificationsCache = data.notifications;

                    updateBerandaNotificationsUI(data.notifications);
                    updateUnreadBadge();
                } else {
                    console.error('Beranda notifications API error:', data.message);
                }
            } catch (error) {
                console.error('Error loading beranda notifications:', error);
            }
        };

        function updateBerandaNotificationsUI(notifications) {
            const container = document.getElementById('beranda-notifications-container');
            if (!container) return;

            if (notifications.length === 0) {
                container.innerHTML = `
                    <div class="px-3 py-4 text-center text-gray-500 text-sm">
                        <i class="ti ti-bell-off text-2xl mb-2 block"></i>
                        Tidak ada pemberitahuan
                    </div>
                `;
                return;
            }

            // Cache notifications for modal use
            window.berandaNotificationsCache = notifications;

            let html = '<ul class="divide-y divide-gray-100">';
            notifications.forEach(notification => {
                console.log('🔍 Beranda processing notification:', notification);
                const isRead = notification.is_read == '1';

                // Handle both global and personal notifications - use same logic as modal
                let iconClass, colorClass;
                if (notification.source === 'global') {
                    console.log('🌍 Global notification icon data:', notification.icon);
                    // For global notifications, use the icon from database with proper formatting
                    if (notification.icon) {
                        // Ensure icon doesn't have ti- prefix already to avoid duplication
                        const cleanIcon = notification.icon.replace(/^ti-/, '');
                        iconClass = `ti-${cleanIcon}`;
                    } else {
                        iconClass = 'ti-info-circle';
                    }
                    // Use priority-based colors if available
                    if (notification.priority) {
                        colorClass = getPriorityColor(notification.priority);
                    } else {
                        colorClass = 'text-blue-500';
                    }
                    console.log('🎨 Global notification styling:', { iconClass, colorClass });
                } else {
                    // For personal notifications, use type-based icons
                    iconClass = getNotificationIcon(notification.type);
                    colorClass = getNotificationColor(notification.type);
                }

                // Safely quote the notification ID for onclick
                const notificationIdQuoted = typeof notification.id === 'string' ? `'${notification.id}'` : notification.id;

                html += `
                    <li class="px-3 py-2 flex items-start space-x-3 hover:bg-gray-50 cursor-pointer ${isRead ? 'opacity-75' : ''}" 
                        onclick="markNotificationAsRead(${notificationIdQuoted}, this)">
                        <div class="flex-shrink-0 mt-1 relative">
                            <i class="ti ${iconClass} ${colorClass}"></i>
                            ${!isRead ? '<div class="w-2 h-2 bg-orange-500 rounded-full absolute -mt-1 -ml-1"></div>' : ''}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-gray-900 font-medium">${escapeHtml(notification.title)}</div>
                            <div class="text-xs text-gray-600 mb-1">${escapeHtml(notification.message || notification.description || '')}</div>
                            <div class="text-xs text-gray-500">
                                ${notification.nama_kelas ? 'Kelas: ' + escapeHtml(notification.nama_kelas) + ' • ' : ''}
                                ${notification.source === 'global' ? 'Pemberitahuan Global • ' : ''}
                                ${notification.time_ago || formatTimeAgo(notification.created_at)}
                            </div>
                        </div>
                    </li>
                `;
            });
            html += '</ul>';

            container.innerHTML = html;
        }

        async function updateUnreadBadge() {
            try {
                const response = await fetch('../logic/get-notifications.php?unread_only=1');
                const data = await response.json();

                if (data.success) {
                    const unreadCount = data.notifications.length;
                    const badge = document.querySelector('.bg-orange-100.text-orange-800');
                    const headerText = document.querySelector('#beranda-notifications-container').closest('.bg-white').querySelector('h3');

                    if (unreadCount > 0) {
                        if (!badge) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800';
                            newBadge.textContent = unreadCount;
                            headerText.appendChild(newBadge);
                        } else {
                            badge.textContent = unreadCount;
                        }
                    } else {
                        if (badge) badge.remove();
                    }
                }
            } catch (error) {
                console.error('Error updating unread badge:', error);
            }
        }

        // Global download function for media
        window.downloadMedia = function (url, filename) {
            console.log('📥 Downloading media:', filename, 'from:', url);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename || 'media-file';
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };

        // Initialize global variables
        window.currentUserId = <?php echo $_SESSION['user']['id']; ?>;
        window.currentUserRole = '<?php echo $_SESSION['user']['role']; ?>';

        // Emergency fallback to clear any stuck loading states
        window.addEventListener('load', function() {
            setTimeout(() => {
                const postsContainer = document.getElementById('postsContainer');
                if (postsContainer && postsContainer.innerHTML.includes('Memuat postingan')) {
                    console.warn('⚠️ Emergency fallback: Clearing stuck loading state');
                    loadInitialBerandaPosts();
                }
            }, 20000); // 20 second fallback after page load
        });

        // Initialize like functionality
        document.addEventListener('DOMContentLoaded', function () {
            console.log('🚀 DOM Content Loaded - Initializing Beranda...');

            // Media debugging - check if we have media elements
            const mediaContainers = document.querySelectorAll('.post-media-container');
            const videoElements = document.querySelectorAll('video.post-media');
            const imageElements = document.querySelectorAll('img.post-media');

            console.log('🎬 Media Debug:');
            console.log('  - Media containers found:', mediaContainers.length);
            console.log('  - Video elements found:', videoElements.length);
            console.log('  - Image elements found:', imageElements.length);

            if (videoElements.length > 0) {
                console.log('🎥 Video elements details:');
                videoElements.forEach((video, index) => {
                    console.log(`  Video ${index + 1}:`, {
                        src: video.querySelector('source')?.src,
                        type: video.querySelector('source')?.type,
                        controls: video.hasAttribute('controls'),
                        preload: video.preload
                    });
                });
            }

            if (imageElements.length > 0) {
                console.log('🖼️ Image elements details:');
                imageElements.forEach((img, index) => {
                    console.log(`  Image ${index + 1}:`, {
                        src: img.src,
                        alt: img.alt,
                        loaded: img.complete
                    });
                });
            }

        // Note: We don't use KelasPosting for beranda since it's designed for specific kelas
        // Instead, we use custom beranda post loading with get-beranda-posts.php API
        
        // Load initial posts for beranda
        loadInitialBerandaPosts();
        
        // Debug: Check posts loading
        console.log('KelasPosting initialized for beranda');
        console.log('Posts container found:', document.getElementById('postsContainer') !== null);

            // Comment button functionality - use direct modal opening for beranda
            document.querySelectorAll('.comment-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const postId = this.dataset.postId;
                    openCommentsModal(postId); // Use direct function call
                });
            });

            // Like button functionality - Standalone implementation for beranda
            document.querySelectorAll('.like-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const postId = this.dataset.postId;
                    console.log('🔴 BERANDA Like button clicked:', { postId });
                    handleBerandaLike(postId, this);
                });
            });

            // ⭐ Initialize AI Explanation Manager
            if (typeof AiExplanationManager !== 'undefined') {
                console.log('🧠 Initializing AI Explanation Manager for Beranda...');
                window.aiExplanationManager = new AiExplanationManager();
            } else {
                console.warn('⚠️ AiExplanationManager not loaded');
            }

            // Load beranda notifications
            console.log('📬 Loading beranda notifications...');
            loadBerandaNotifications();

            // Load comments preview for all posts
            setTimeout(() => {
                console.log('Loading comments preview for existing posts...');
                document.querySelectorAll('[id^="comments-preview-"]').forEach(container => {
                    const postId = container.id.replace('comments-preview-', '');
                    console.log('Loading comments for post:', postId);
                    loadCommentsPreview(postId); // Use direct function call for beranda
                });
            }, 500);
        });

        // Initialize AssignmentManager for beranda (same as kelas-guru.php)
        if (typeof AssignmentManager !== 'undefined') {
            window.assignmentManager = new AssignmentManager(null, '<?php echo $_SESSION['user']['role']; ?>'); // null for beranda context
        }

        // Advertisement Slider Functionality
        let currentAdIndex = 0;
        let adInterval = null;
        const advertisementItems = document.querySelectorAll('.advertisement-item');
        const adIndicators = document.querySelectorAll('.ad-indicator');

        function showAdvertisement(index) {
            // Hide all advertisements
            advertisementItems.forEach(item => item.classList.remove('active'));
            adIndicators.forEach(indicator => indicator.classList.remove('active'));

            // Show selected advertisement
            if (advertisementItems[index]) {
                advertisementItems[index].classList.add('active');
            }
            if (adIndicators[index]) {
                adIndicators[index].classList.add('active');
            }

            currentAdIndex = index;
        }

        function nextAdvertisement() {
            const nextIndex = (currentAdIndex + 1) % advertisementItems.length;
            showAdvertisement(nextIndex);
        }

        function previousAdvertisement() {
            const prevIndex = (currentAdIndex - 1 + advertisementItems.length) % advertisementItems.length;
            showAdvertisement(prevIndex);
        }

        // Initialize advertisement slider
        if (advertisementItems.length > 1) {
            // Auto-rotate advertisements every 5 seconds
            adInterval = setInterval(nextAdvertisement, 5000);

            // Add click handlers for indicators
            adIndicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => {
                    clearInterval(adInterval);
                    showAdvertisement(index);
                    // Restart auto-rotation
                    adInterval = setInterval(nextAdvertisement, 5000);
                });
            });

            // Add click handler for next button
            const nextBtn = document.getElementById('next-ad-btn');
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    clearInterval(adInterval);
                    nextAdvertisement();
                    // Restart auto-rotation
                    adInterval = setInterval(nextAdvertisement, 5000);
                });
            }

            // Add click handler for previous button
            const prevBtn = document.getElementById('prev-ad-btn');
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    clearInterval(adInterval);
                    previousAdvertisement();
                    // Restart auto-rotation
                    adInterval = setInterval(nextAdvertisement, 5000);
                });
            }
        }

        // Standalone like handler for beranda
        async function handleBerandaLike(postId, buttonElement) {
            try {
                console.log('🔴 BERANDA handleBerandaLike called:', { postId });

                const formData = new FormData();
                formData.append('post_id', postId);
                formData.append('action', 'toggle_like');

                const response = await fetch('../logic/handle-like.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('🔴 BERANDA Like response:', result);

                if (result.success) {
                    // Update like count in UI
                    const likeCount = buttonElement.querySelector('.like-count');
                    const heartIcon = buttonElement.querySelector('i');

                    if (result.action === 'liked') {
                        // User melakukan like
                        if (likeCount) {
                            likeCount.textContent = result.like_count || ((parseInt(likeCount.textContent) || 0) + 1);
                        }
                        buttonElement.classList.remove('text-gray-600');
                        buttonElement.classList.add('text-red-600');
                        buttonElement.setAttribute('data-liked', 'true');
                        if (heartIcon) {
                            heartIcon.className = 'ti ti-heart-filled text-red-600';
                        }
                        console.log('✅ BERANDA Post liked successfully');
                    } else if (result.action === 'unliked') {
                        // User melakukan unlike
                        if (likeCount) {
                            likeCount.textContent = result.like_count || Math.max(0, (parseInt(likeCount.textContent) || 0) - 1);
                        }
                        buttonElement.classList.remove('text-red-600');
                        buttonElement.classList.add('text-gray-600');
                        buttonElement.setAttribute('data-liked', 'false');
                        if (heartIcon) {
                            heartIcon.className = 'ti ti-heart';
                        }
                        console.log('✅ BERANDA Post unliked successfully');
                    }
                } else {
                    console.error('🔴 BERANDA Like error:', result.message);
                }
            } catch (error) {
                console.error('🔴 BERANDA Like exception:', error);
            }
        }

        // Global function to force clear loading states
        window.clearStuckLoaders = function() {
            console.log('🧹 Force clearing any stuck loaders...');
            const postsContainer = document.getElementById('postsContainer');
            if (postsContainer) {
                // Check if there are any stuck loading indicators
                const loadingElements = postsContainer.querySelectorAll('.ti-loader, .animate-spin');
                const hasLoadingText = postsContainer.innerHTML.includes('Memuat postingan');
                
                if (loadingElements.length > 0 || hasLoadingText) {
                    console.log('🔄 Found stuck loader, attempting to reload...');
                    loadInitialBerandaPosts();
                } else {
                    console.log('✅ No stuck loaders found');
                }
            }
        };

        // Make assignment functions globally available
        window.showSubmissionModal = function (assignmentId) {
            console.log('🎯 showSubmissionModal called with ID:', assignmentId);

            // Get assignment details from the post
            const assignmentPost = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
            let assignmentTitle = 'Tugas';

            if (assignmentPost) {
                const titleElement = assignmentPost.querySelector('.assignment-title');
                if (titleElement) {
                    assignmentTitle = titleElement.textContent;
                }
            }

            // Open modal using assignment manager
            if (window.assignmentManager && typeof window.assignmentManager.openSubmitAssignmentModal === 'function') {
                // Coba ambil data tugas ringan (opsional). Jika tidak ada, hanya buka modal dan biarkan status di-load oleh checkSubmissionStatus
                const assignmentData = {
                    judul: assignmentTitle,
                    deskripsi: '',
                    deadline: null,
                    nilai_maksimal: ''
                };
                // Jika di DOM ada elemen dengan data-assignment-detail
                const detailEl = document.querySelector(`[data-assignment-detail="${assignmentId}"]`);
                if (detailEl) {
                    assignmentData.deskripsi = detailEl.getAttribute('data-description') || '';
                    assignmentData.deadline = detailEl.getAttribute('data-deadline') || null;
                    assignmentData.nilai_maksimal = detailEl.getAttribute('data-maxscore') || '';
                }
                window.assignmentManager.openSubmitAssignmentModal(assignmentId, assignmentData);
            } else {
                console.error('AssignmentManager not available');
                alert('Fitur pengumpulan tugas sedang tidak tersedia (AssignmentManager tidak ter-load)');
            }
        };
        // showSubmissionModal defined

        // File handling functions (same as kelas-posting-stable.js)
        window.handleSubmissionFileSelect = function (assignmentId, input) {
            const file = input.files[0];
            if (!file) return;

            // Validate file size (10MB)
            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                alert('Ukuran file terlalu besar. Maksimal 10MB.');
                input.value = '';
                return;
            }

            // Show preview
            const preview = document.getElementById(`submission-preview-${assignmentId}`);
            const fileIcon = document.getElementById(`file-icon-${assignmentId}`);
            const fileName = document.getElementById(`file-name-${assignmentId}`);
            const fileSize = document.getElementById(`file-size-${assignmentId}`);
            const imagePreview = document.getElementById(`image-preview-${assignmentId}`);

            if (fileName) fileName.textContent = file.name;
            if (fileSize) fileSize.textContent = formatFileSize(file.size);

            // Set appropriate icon
            const ext = file.name.toLowerCase().split('.').pop();
            if (fileIcon) fileIcon.innerHTML = getFileIconHtml(ext);

            // Show image preview if it's an image
            if (file.type.startsWith('image/') && imagePreview) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else if (imagePreview) {
                imagePreview.classList.add('hidden');
            }

            if (preview) preview.classList.remove('hidden');

            // Enable submit button
            const submitBtn = document.getElementById(`submit-btn-${assignmentId}`);
            if (submitBtn) submitBtn.disabled = false;
        }

        function removeSubmissionFile(assignmentId) {
            const input = document.getElementById(`submission-file-${assignmentId}`);
            const preview = document.getElementById(`submission-preview-${assignmentId}`);
            const submitBtn = document.getElementById(`submit-btn-${assignmentId}`);
            const imagePreview = document.getElementById(`image-preview-${assignmentId}`);

            if (input) input.value = '';
            if (preview) preview.classList.add('hidden');
            if (imagePreview) imagePreview.classList.add('hidden');
            if (submitBtn) submitBtn.disabled = true;
        }

        async function submitAssignment(assignmentId) {
            const fileInput = document.getElementById(`submission-file-${assignmentId}`);
            const notesInput = document.getElementById(`submission-notes-${assignmentId}`);
            const submitBtn = document.getElementById(`submit-btn-${assignmentId}`);

            if (!fileInput || !fileInput.files[0]) {
                alert('Silakan pilih file untuk dikumpulkan');
                return;
            }

            // Show loading
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="ti ti-loader animate-spin mr-2"></i>Mengirim...';
            }

            try {
                const formData = new FormData();
                formData.append('assignment_id', assignmentId);
                formData.append('submission_file', fileInput.files[0]);
                if (notesInput) {
                    formData.append('notes', notesInput.value);
                }

                const response = await fetch('../logic/submit-assignment.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Realtime UI update tanpa reload
                    const badge = document.getElementById(`assignment-status-badge-${assignmentId}`);
                    if (badge) {
                        badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800';
                        badge.innerHTML = '<i class="ti ti-upload mr-1"></i>Sudah dikumpulkan';
                    }
                    const progressFill = document.getElementById(`assignment-progress-fill-${assignmentId}`);
                    if (progressFill) {
                        progressFill.style.width = '50%';
                    }
                    const openBtn = document.getElementById(`open-inline-form-btn-${assignmentId}`);
                    if (openBtn) {
                        openBtn.textContent = 'Update Tugas';
                    }
                    const formWrapper = document.getElementById(`submission-form-${assignmentId}`);
                    if (formWrapper) {
                        formWrapper.classList.add('hidden');
                        if (openBtn) openBtn.classList.remove('hidden');
                    }
                    // Toast sederhana
                    showToast('Tugas berhasil dikumpulkan');
                } else {
                    alert('Gagal mengumpulkan tugas: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error submitting assignment:', error);
                alert('Terjadi kesalahan saat mengumpulkan tugas');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Kumpulkan';
                }
            }
        }

        function showToast(message) {
            const el = document.createElement('div');
            el.className = 'fixed top-4 right-4 bg-green-600 text-white text-sm px-4 py-2 rounded shadow z-50 animate-fade-in';
            el.textContent = message;
            document.body.appendChild(el);
            setTimeout(() => {
                el.style.opacity = '0';
                el.style.transition = 'opacity .4s';
            }, 2500);
            setTimeout(() => {
                el.remove();
            }, 3000);
        }

        // Helper functions
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function getFileIconHtml(extension) {
            const iconMap = {
                'pdf': '<i class="ti ti-file-type-pdf text-red-600"></i>',
                'doc': '<i class="ti ti-file-type-doc text-blue-600"></i>',
                'docx': '<i class="ti ti-file-type-doc text-blue-600"></i>',
                'xls': '<i class="ti ti-file-type-xls text-green-600"></i>',
                'xlsx': '<i class="ti ti-file-type-xls text-green-600"></i>',
                'ppt': '<i class="ti ti-file-type-ppt text-orange-600"></i>',
                'pptx': '<i class="ti ti-file-type-ppt text-orange-600"></i>',
                'jpg': '<i class="ti ti-photo text-purple-600"></i>',
                'jpeg': '<i class="ti ti-photo text-purple-600"></i>',
                'png': '<i class="ti ti-photo text-purple-600"></i>',
                'gif': '<i class="ti ti-photo text-purple-600"></i>',
                'txt': '<i class="ti ti-file-text text-gray-600"></i>'
            };

            return iconMap[extension] || '<i class="ti ti-file text-gray-600"></i>';
        }

        // Get notification icon based on type (same as modal)
        function getNotificationIcon(type) {
            switch (type) {
                case 'tugas_baru': return 'ti-clipboard-plus';
                case 'postingan_baru': return 'ti-message-circle';
                case 'ujian_baru': return 'ti-file-text';
                case 'pengingat_ujian': return 'ti-bell';
                case 'like_postingan': return 'ti-heart';
                case 'komentar_postingan': return 'ti-message-2';
                default: return 'ti-info-circle';
            }
        }

        // Get notification color based on type (same as modal)
        function getNotificationColor(type) {
            switch (type) {
                case 'tugas_baru': return 'text-blue-500';
                case 'postingan_baru': return 'text-green-500';
                case 'ujian_baru': return 'text-purple-500';
                case 'pengingat_ujian': return 'text-orange-500';
                case 'like_postingan': return 'text-red-500';
                case 'komentar_postingan': return 'text-indigo-500';
                default: return 'text-gray-500';
            }
        }

        // Get priority color (same as modal)
        function getPriorityColor(priority) {
            switch (priority) {
                case 'urgent': return 'text-red-600';
                case 'high': return 'text-orange-500';
                case 'medium': return 'text-blue-500';
                case 'low': return 'text-gray-500';
                default: return 'text-blue-500';
            }
        }

        // Load initial posts for beranda (using API like kelas-guru.php)
        async function loadInitialBerandaPosts() {
            const postsContainer = document.getElementById('postsContainer');
            if (!postsContainer) return;

            console.log('🔄 Loading initial beranda posts...');

            // Create AbortController for timeout handling
            const controller = new AbortController();
            const timeoutId = setTimeout(() => {
                controller.abort();
                console.warn('⏰ Beranda posts request timeout after 15 seconds');
            }, 15000); // 15 second timeout

            try {
                const response = await fetch('../logic/get-beranda-posts.php?offset=0&limit=5&_=' + Date.now(), {
                    signal: controller.signal,
                    headers: {
                        'Cache-Control': 'no-cache'
                    }
                });

                // Clear timeout since request completed
                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                console.log('📥 Initial posts API response:', result);

                // Clear loading state
                postsContainer.innerHTML = '';

                if (result.success && result.posts && result.posts.length > 0) {
                    // Create posts using our existing function
                    result.posts.forEach(post => {
                        const postElement = createBerandaPostElement(post);
                        postsContainer.appendChild(postElement);
                        
                        // Add event listeners for new post
                        addPostEventListeners(postElement, post.id);
                    });

                    berandaOffset = result.posts.length;

                    // Show load more button if there might be more posts
                    if (result.posts.length >= 5) {
                        const loadMoreContainer = document.getElementById('loadMoreContainer');
                        if (loadMoreContainer) {
                            loadMoreContainer.classList.remove('hidden');
                        }
                    }

                    console.log('✅ Initial posts loaded successfully');
                } else {
                    // Show empty state
                    postsContainer.innerHTML = `
                        <div class="text-center py-12 text-gray-500 space-y-3">
                            <i class="ti ti-message-off text-6xl text-gray-400 mb-2"></i>
                            <h3 class="text-xl font-medium text-gray-700">Belum ada postingan</h3>
                            <p class="text-gray-500">Postingan dari kelas yang Anda ikuti akan muncul di sini</p>
                            <button command="show-modal" commandfor="join-class-modal"
                                class="inline-flex items-center mx-auto p-2 border border-transparent rounded-full bg-orange-600 text-white hover:bg-orange-700 transition-colors">
                                <i class="ti ti-user-plus text-lg md:text-xl"></i>
                                <span class="inline md:hidden ml-1 text-sm">Gabung</span>
                                <span class="hidden md:inline ml-1 text-sm">Gabung Kelas</span>
                            </button>
                        </div>
                    `;
                    console.log('📭 No posts found');
                }
            } catch (error) {
                // Clear timeout in case of error
                clearTimeout(timeoutId);
                
                console.error('❌ Error loading initial posts:', error);
                
                let errorMessage = 'Gagal memuat postingan';
                let errorDetail = 'Silakan muat ulang halaman';
                
                if (error.name === 'AbortError') {
                    errorMessage = 'Koneksi timeout';
                    errorDetail = 'Periksa koneksi internet Anda';
                } else if (error.message.includes('HTTP error')) {
                    errorMessage = 'Server error';
                    errorDetail = 'Coba lagi dalam beberapa saat';
                }
                
                postsContainer.innerHTML = `
                    <div class="text-center py-12 text-gray-500">
                        <i class="ti ti-alert-circle text-4xl mb-4"></i>
                        <p class="text-lg font-medium">${errorMessage}</p>
                        <p class="text-sm text-gray-400 mt-1">${errorDetail}</p>
                        <button onclick="loadInitialBerandaPosts()" 
                                class="mt-4 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                            <i class="ti ti-refresh mr-2"></i>
                            Coba Lagi
                        </button>
                    </div>
                `;
            }
        }

        // Beranda lazy loading functionality
        let berandaOffset = 0; // Start from 0 since loadInitialBerandaPosts handles first load
        let berandaHasMore = true;
        let berandaLoading = false;

        function initializeBerandaLazyLoading() {
            const loadMoreBtn = document.getElementById('loadMorePosts'); // Consistent ID
            const container = document.getElementById('postsContainer');

            console.log('Initialize lazy loading:');
            console.log('- Load more button:', loadMoreBtn);
            console.log('- Container:', container);
            console.log('- Container children:', container ? container.children.length : 'N/A');

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', loadMoreBerandaPosts);
            }

            // Optional: Add scroll-based loading
            window.addEventListener('scroll', () => {
                if (berandaHasMore && !berandaLoading) {
                    const scrollPosition = window.innerHeight + window.scrollY;
                    const threshold = document.body.offsetHeight - 800;

                    if (scrollPosition >= threshold) {
                        loadMoreBerandaPosts();
                    }
                }
            });
        }

        async function loadMoreBerandaPosts() {
            if (berandaLoading || !berandaHasMore) return;

            berandaLoading = true;
            const loadMoreBtn = document.getElementById('loadMorePosts'); // Consistent with container ID
            const container = document.getElementById('postsContainer');

            // Show loading state
            if (loadMoreBtn) {
                loadMoreBtn.innerHTML = '<i class="ti ti-loader animate-spin mr-2"></i>Memuat...';
                loadMoreBtn.disabled = true;
            }

            // Create AbortController for timeout handling
            const controller = new AbortController();
            const timeoutId = setTimeout(() => {
                controller.abort();
                console.warn('⏰ Beranda load more request timeout after 15 seconds');
            }, 15000); // 15 second timeout

            try {
                const response = await fetch(`../logic/get-beranda-posts.php?offset=${berandaOffset}&limit=5&_=${Date.now()}`, {
                    signal: controller.signal,
                    headers: {
                        'Cache-Control': 'no-cache'
                    }
                });

                // Clear timeout since request completed
                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                console.log('Beranda API Response:', result); // Debug

                if (result.success && result.posts && result.posts.length > 0) {
                    // Add new posts to container
                    result.posts.forEach(post => {
                        const postElement = createBerandaPostElement(post);
                        container.appendChild(postElement);

                        // Add event listeners for new post
                        addPostEventListeners(postElement, post.id);
                    });

                    berandaOffset += result.posts.length;

                    // Check if there are more posts
                    if (result.posts.length < 5) {
                        berandaHasMore = false;
                        if (loadMoreBtn) {
                            loadMoreBtn.style.display = 'none';
                        }
                    }
                } else {
                    berandaHasMore = false;
                    if (loadMoreBtn) {
                        loadMoreBtn.style.display = 'none';
                    }
                }
            } catch (error) {
                // Clear timeout in case of error
                clearTimeout(timeoutId);
                
                console.error('Error loading more posts:', error);
                
                let errorMessage = 'Gagal memuat';
                if (error.name === 'AbortError') {
                    errorMessage = 'Timeout - Coba lagi';
                } else if (error.message.includes('HTTP error')) {
                    errorMessage = 'Server error';
                }
                
                if (loadMoreBtn) {
                    loadMoreBtn.innerHTML = `<i class="ti ti-alert-circle mr-2"></i>${errorMessage}`;
                }
            } finally {
                berandaLoading = false;
                if (loadMoreBtn && berandaHasMore) {
                    loadMoreBtn.innerHTML = '<i class="ti ti-plus mr-2"></i>Muat Postingan Lainnya';
                    loadMoreBtn.disabled = false;
                }
            }
        }

        // Helper function to create assignment content
        function createAssignmentContent(post) {
            if (!post.assignment_id || !post.assignment_title) return '';

            const isExpired = post.assignment_deadline && new Date(post.assignment_deadline) < new Date();
            const deadlineClass = isExpired ? 'border-red-200 bg-red-50' : 'border-gray-200';
            const deadlineIconClass = isExpired ? 'text-red-500' : 'text-orange-500';
            const deadlineTextClass = isExpired ? 'text-red-700' : 'text-gray-900';

            // Format deadline
            const formatDeadline = (deadline) => {
                if (!deadline) return '';
                const date = new Date(deadline);
                return date.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            };

            const formatDeadlineMobile = (deadline) => {
                if (!deadline) return '';
                const date = new Date(deadline);
                return date.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            };

            return `
                <div id="post-assignment-${post.assignment_id}" class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 mt-3" data-assignment-id="${post.assignment_id}">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <i class="ti ti-clipboard-text text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between mb-3">
                                <h3 class="text-xl font-bold text-gray-900 flex items-center assignment-title">
                                    <i class="ti ti-assignment text-blue-600 mr-2"></i>
                                    ${escapeHtml(post.assignment_title)}
                                </h3>
                            </div>
                            
                            <!-- Assignment Description -->
                            ${post.konten ? `
                                <div class="mb-4 p-3 bg-white rounded-lg border border-gray-200">
                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Deskripsi Tugas</div>
                                    <div class="text-gray-900 text-sm leading-relaxed" style="word-break: break-word; overflow-wrap: break-word;">${formatPostContent(post.konten)}</div>
                                </div>
                            ` : ''}
                            
                            <!-- Assignment Details Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                                ${post.assignment_deadline ? `
                                    <div class="flex items-center space-x-2 p-3 bg-white rounded-lg border ${deadlineClass}">
                                        <div class="flex-shrink-0">
                                            <i class="ti ti-calendar-due text-lg ${deadlineIconClass}"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Deadline</div>
                                            <div class="text-sm font-semibold ${deadlineTextClass} truncate">
                                                <span class="hidden sm:inline">${formatDeadline(post.assignment_deadline)}</span>
                                                <span class="sm:hidden">${formatDeadlineMobile(post.assignment_deadline)}</span>
                                            </div>
                                        </div>
                                    </div>
                                ` : ''}
                                
                                ${post.assignment_max_score ? `
                                    <div class="flex items-center space-x-2 p-3 bg-white rounded-lg border border-gray-200">
                                        <div class="flex-shrink-0">
                                            <i class="ti ti-trophy text-lg text-yellow-500"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nilai Maksimal</div>
                                            <div class="text-sm font-semibold text-gray-900">${post.assignment_max_score} Poin</div>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                            
                            <!-- Assignment Files -->
                            ${post.assignment_files && post.assignment_files.length > 0 ? createAssignmentFilesHtml(post.assignment_files) : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        // Helper function to create assignment files HTML
        function createAssignmentFilesHtml(files) {
            if (!files || files.length === 0) return '';

            const getFileIcon = (fileName) => {
                const ext = fileName.toLowerCase().split('.').pop();
                const iconMap = {
                    'pdf': 'ti ti-file-type-pdf',
                    'doc': 'ti ti-file-type-doc',
                    'docx': 'ti ti-file-type-docx',
                    'ppt': 'ti ti-presentation',
                    'pptx': 'ti ti-presentation',
                    'xls': 'ti ti-file-type-xls',
                    'xlsx': 'ti ti-file-type-xlsx',
                    'txt': 'ti ti-file-text',
                    'zip': 'ti ti-file-zip',
                    'rar': 'ti ti-file-zip',
                    'jpg': 'ti ti-photo',
                    'jpeg': 'ti ti-photo',
                    'png': 'ti ti-photo',
                    'gif': 'ti ti-photo',
                    'mp4': 'ti ti-video',
                    'mp3': 'ti ti-music',
                    'avi': 'ti ti-video',
                    'mov': 'ti ti-video'
                };
                return iconMap[ext] || 'ti ti-file';
            };

            const formatFileSize = (bytes) => {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            };

            if (files.length === 1) {
                const file = files[0];
                const fileIcon = getFileIcon(file.nama_file);
                return `
                    <div class="mt-4">
                        <div class="flex items-center p-3 bg-white rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors cursor-pointer"
                             onclick="window.open('/lms/${file.path_file}', '_blank')">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                <i class="${fileIcon} text-blue-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">File Tugas</div>
                                <div class="text-sm font-semibold text-gray-900 truncate">${escapeHtml(file.nama_file)}</div>
                                <div class="text-xs text-gray-500 mt-0.5">${formatFileSize(file.ukuran_file)} • Klik untuk mengunduh</div>
                            </div>
                            <div class="flex items-center space-x-1 bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex-shrink-0">
                                <i class="ti ti-download"></i>
                                <span class="hidden sm:inline">Unduh</span>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                let html = `<div class="mt-4"><div class="grid gap-2">`;
                files.forEach(file => {
                    const fileIcon = getFileIcon(file.nama_file);
                    html += `
                        <div class="flex items-center p-2 bg-white rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors cursor-pointer"
                             onclick="window.open('/lms/${file.path_file}', '_blank')">
                            <div class="w-8 h-8 bg-blue-100 rounded flex items-center justify-center mr-2 flex-shrink-0">
                                <i class="${fileIcon} text-blue-600 text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium text-gray-900 truncate">${escapeHtml(file.nama_file)}</div>
                                <div class="text-xs text-gray-500">${formatFileSize(file.ukuran_file)}</div>
                            </div>
                            <button class="text-blue-600 hover:text-blue-800 p-1">
                                <i class="ti ti-download text-sm"></i>
                            </button>
                        </div>
                    `;
                });
                html += `</div></div>`;
                return html;
            }
        }

        function createBerandaPostElement(post) {
            try {
                // Create post element (similar to PHP template but in JS)
                const postDiv = document.createElement('div');
                postDiv.className = 'bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6';
                postDiv.setAttribute('data-user-id', post.user_id || '');
                postDiv.setAttribute('data-post-id', post.id || '');

                // Create photo path for profile image
                let photoHtml = '';
                if (post.fotoProfil) {
                    const photoPath = post.fotoProfil.startsWith('uploads/profile/') ?
                        '../../' + post.fotoProfil :
                        '../../uploads/profile/' + post.fotoProfil;
                    photoHtml = `<img src="${photoPath}" alt="Profile Photo" class="w-full h-full object-cover post-profile-photo" 
                    onerror="this.parentElement.innerHTML='<div class=\\'w-full h-full bg-orange-600 rounded-full flex items-center justify-center\\'><span class=\\'text-white font-medium text-sm\\'>${escapeHtml(post.namaPenulis).charAt(0).toUpperCase()}</span></div>'">`;
                } else {
                    // Role-based colors
                    let bgColor = 'bg-orange-600 text-white';
                    switch (post.rolePenulis) {
                        case 'admin':
                            bgColor = 'bg-red-100 text-red-600';
                            break;
                        case 'guru':
                            bgColor = 'bg-blue-100 text-blue-600';
                            break;
                        case 'siswa':
                            bgColor = 'bg-green-100 text-green-600';
                            break;
                    }
                    photoHtml = `<div class="w-full h-full rounded-full flex items-center justify-center ${bgColor}">
                    <span class="font-medium text-sm">${escapeHtml(post.namaPenulis).charAt(0).toUpperCase()}</span>
                </div>`;
                }

                // Create full post template
                // Build images HTML separately to support up to 4 visible images and overlay for >4
                function buildMediaHtml(post) {
                    if (!post.gambar || post.gambar.length === 0) return '';
                    const total = post.gambar.length;
                    const maxShow = 4;
                    const images = post.gambar.slice(0, maxShow);
                    let html = '<div class="mt-3 post-media-container"><div class="post-media-grid grid-' + images.length + '">';

                    images.forEach((media, idx) => {
                        const mediaPath = (media.path_gambar && media.path_gambar.startsWith('uploads')) ? ('../../' + media.path_gambar) : ('../../uploads/postingan/' + (media.nama_file || ''));
                        const isVideo = (media.media_type && media.media_type === 'video') || (media.tipe_file && media.tipe_file.indexOf('video/') === 0);
                        const mediaClass = images.length === 1 ? 'single' : 'multiple';

                        if (isVideo) {
                            html += `<div class="post-media-item ${mediaClass}"><video controls class="post-media" data-media-index="${idx}" preload="metadata"><source src="${mediaPath}" type="${media.tipe_file || 'video/mp4'}">Your browser does not support the video tag.</video><div class="post-media-type-badge video"><i class="ti ti-video"></i> Video</div><button class="media-download-btn" onclick="downloadMedia('${mediaPath}', '${media.nama_file || ''}')" title="Download Video"><i class="ti ti-download"></i></button></div>`;
                        } else {
                            // For the 4th image when total > 4, we'll add overlay later
                            html += `<div class="post-media-item ${mediaClass}"><img src="${mediaPath}" alt="${media.nama_file || 'Media postingan'}" class="post-media" data-media-index="${idx}" data-pswp-src="${mediaPath}" data-pswp-width="800" data-pswp-height="600" style="cursor: pointer;" onerror="this.style.display='none';"><div class="post-media-type-badge image"><i class="ti ti-photo"></i> Gambar</div><button class="media-download-btn" onclick="downloadMedia('${mediaPath}', '${media.nama_file || ''}')" title="Download Gambar"><i class="ti ti-download"></i></button></div>`;
                        }
                    });

                    // If there are more than maxShow, add overlay on the last shown image
                    if (total > maxShow) {
                        const moreCount = total - maxShow;
                        html += `<div class="post-media-item multiple relative"><div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center rounded-lg"><span class="text-white font-bold text-lg">+${moreCount}</span></div></div>`;
                    }

                    html += '</div></div>';
                    return html;
                }

                postDiv.innerHTML = `
                <!-- Post Header -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full overflow-hidden flex items-center justify-center flex-shrink-0 bg-gray-100">
                            ${photoHtml}
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h4 class="font-medium text-gray-900">${escapeHtml(post.namaPenulis)}</h4>
                                <span class="text-sm text-gray-500">•</span>
                                <span class="text-sm text-orange-600 font-medium">${escapeHtml(post.namaKelas)}</span>
                            </div>
                            <p class="text-xs text-gray-500">
                                ${formatTimeAgo(post.dibuat)}
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Post Content -->
                <div class="mb-4">
                    ${post.konten && post.tipe_postingan !== 'assignment' ? `<div class="post-content text-gray-800 whitespace-pre-wrap">${formatPostContent(post.konten)}</div>` : ''}
                </div>
                
                <!-- Assignment Content (if it's an assignment post) -->
                ${post.tipe_postingan === 'assignment' ? createAssignmentContent(post) : ''}
                
                <!-- Post Media -->
                ${buildMediaHtml(post)}
                
                <!-- Post Actions -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="flex items-center space-x-4">
                        <!-- Like Button -->
                        <button class="like-btn flex items-center space-x-2 ${post.userLiked ? 'text-red-600' : 'text-gray-600'} hover:text-red-600 transition-colors" 
                                data-post-id="${post.id}" data-liked="${post.userLiked ? 'true' : 'false'}">
                            <i class="ti ti-heart${post.userLiked ? '-filled text-red-600' : ''}"></i>
                            <span class="like-count text-sm">${post.jumlahLike || 0}</span>
                        </button>
                        
                        <!-- Comment Button -->
                        ${!post.restrict_comments ? `
                        <button class="comment-btn flex items-center space-x-2 text-gray-600 hover:text-blue-600 transition-colors" data-post-id="${post.id}">
                            <i class="ti ti-message-circle"></i>
                            <span class="comment-count text-sm">${post.jumlahKomentar || 0}</span>
                        </button>
                        ` : `
                        <span class="flex items-center space-x-2 text-gray-400">
                            <i class="ti ti-message-circle-off"></i>
                            <span class="text-sm">Komentar dinonaktifkan</span>
                        </span>
                        `}
                        
                        <!-- AI Explanation Button -->
                        <button class="ai-explain-btn flex items-center space-x-2 text-gray-600 hover:text-purple-600 transition-colors" 
                                data-post-id="${post.id}" title="Analisis AI">
                            <i class="ti ti-sparkles"></i>
                        </button>
                    </div>
                    
                    <!-- Class Link -->
                    <a href="kelas-user.php?id=${post.kelas_id}" class="text-orange-600 hover:text-orange-700 text-sm font-medium">
                        Lihat Kelas
                    </a>
                </div>
                
                <!-- Comments Section for KelasPosting compatibility -->
                ${!post.restrict_comments ? `
                <button class="view-all-comments text-orange text-sm hover:text-orange-600 transition-colors mt-3" data-post-id="${post.id}" style="display: none;">
                    Lihat komentar lainnya
                </button>
                <div id="comments-preview-${post.id}" class="mt-4 pt-4 border-t border-gray-100" style="display: none;">
                    <!-- Preview comments will be loaded here -->
                </div>
                ` : ''}
            `;

                return postDiv;
            } catch (error) {
                console.error('Error creating post element:', error);
                // Return a simple error element instead of breaking the page
                const errorDiv = document.createElement('div');
                errorDiv.className = 'bg-red-50 border border-red-200 rounded-lg p-4 text-red-800';
                errorDiv.innerHTML = '<p>Error loading post content</p>';
                return errorDiv;
            }
        }

        function formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);

            if (diffInSeconds < 60) return 'Baru saja';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' menit lalu';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' jam lalu';
            return Math.floor(diffInSeconds / 86400) + ' hari lalu';
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Format post content with markdown-like syntax
        function formatPostContent(content) {
            if (!content) return '';

            // First do the markdown replacements
            let formatted = content;
            // Bold text: **text** -> <strong>text</strong>
            formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // Italic text: *text* -> <em>text</em> (only single asterisks not preceded by *)
            // Use a different approach to avoid lookbehind
            formatted = formatted.replace(/(?:^|[^*])\*([^*]+?)\*(?![*])/g, function (match, p1, offset, string) {
                // Keep the character before * if it exists
                const beforeChar = match.charAt(0) !== '*' ? match.charAt(0) : '';
                return beforeChar + '<em>' + p1 + '</em>';
            });

            // Then escape HTML but preserve our formatting tags
            formatted = escapeHtml(formatted);
            // Unescape our formatting tags
            formatted = formatted.replace(/&lt;strong&gt;/g, '<strong>')
                .replace(/&lt;\/strong&gt;/g, '</strong>')
                .replace(/&lt;em&gt;/g, '<em>')
                .replace(/&lt;\/em&gt;/g, '</em>');

            return formatted;
        }

        // Add event listeners to dynamically loaded posts
        function addPostEventListeners(postElement, postId) {
            // Like button - use standalone beranda handler
            const likeBtn = postElement.querySelector('.like-btn');
            if (likeBtn) {
                likeBtn.addEventListener('click', function () {
                    console.log('🔴 BERANDA Dynamic Like button clicked:', { postId });
                    handleBerandaLike(postId, this);
                });
            }

            // Comment button
            const commentBtn = postElement.querySelector('.comment-btn');
            if (commentBtn) {
                commentBtn.addEventListener('click', function () {
                    openCommentsModal(postId); // Direct call for beranda
                });
            }

            // Load comments preview for new post
            setTimeout(() => {
                loadCommentsPreview(postId); // Direct call for beranda
            }, 300);
        }

        // Inline assignment submission handlers (beranda)
        window.showSubmissionForm = function (assignmentId) {
            const formWrapper = document.getElementById(`submission-form-${assignmentId}`);
            const openBtn = document.getElementById(`open-inline-form-btn-${assignmentId}`);
            if (formWrapper && openBtn) {
                formWrapper.classList.remove('hidden');
                openBtn.classList.add('hidden');
            }
        };
        window.hideSubmissionForm = function (assignmentId) {
            const formWrapper = document.getElementById(`submission-form-${assignmentId}`);
            const openBtn = document.getElementById(`open-inline-form-btn-${assignmentId}`);
            if (formWrapper && openBtn) {
                formWrapper.classList.add('hidden');
                openBtn.classList.remove('hidden');
            }
        };

        // Global functions for beranda comments (since we don't use KelasPosting)
        window.openCommentsModal = function(postId) {
            // Implementation for opening comments modal in beranda context
            console.log('Opening comments modal for post:', postId);
            
            // Use the same modal system as other pages
            const modal = document.getElementById('commentsModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                
                // Set post ID for modal
                modal.setAttribute('data-post-id', postId);
                
                // Load comments for this post
                loadCommentsForModal(postId);
            }
        };

        window.loadCommentsPreview = function(postId) {
            // Implementation for loading comments preview in beranda context
            console.log('Loading comments preview for post:', postId);
            // This can be implemented later if needed for preview functionality
        };

        window.loadCommentsForModal = async function(postId) {
            try {
                // You can implement actual comments loading here
                console.log('Loading comments for modal, post:', postId);
            } catch (error) {
                console.error('Error loading comments:', error);
            }
        };
    </script>
    <script src="../script/profile-sync.js"></script>

    <!-- Dynamic Modal Component -->
    <?php require '../component/modal-dynamic.php'; ?>
</body>

</html>