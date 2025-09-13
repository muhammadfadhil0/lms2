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
require_once '../logic/notification-logic.php';

// Get dashboard data
$dashboardLogic = new DashboardLogic();
$kelasLogic = new KelasLogic();
$notificationLogic = new NotificationLogic();
$siswa_id = $_SESSION['user']['id'];
$dashboardData = $dashboardLogic->getDashboardSiswa($siswa_id);

// Get recent posts from all classes
$recentPosts = $dashboardLogic->getPostinganTerbaruSiswa($siswa_id, 5); // Reduced from 15 to 5

// Get recent notifications (2 latest)
$recentNotifications = $notificationLogic->getUserNotifications($siswa_id, 2);
$unreadNotificationsCount = $notificationLogic->getUnreadCount($siswa_id);

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
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    </style>
</head>

<body class="bg-gray-50">

    <!-- Header (fixed, shifted right on desktop and below left sidebar) -->
    <header class="site-header md:hidden bg-white px-3 py-1 header-compact border-b border-gray-200 fixed top-0 z-30" style="height:3.5rem; left:0; right:0;">
        <style>
            /* Header transition and collapsed state support */
            .site-header {
                transition: left 220ms cubic-bezier(0.4,0,0.2,1), width 220ms cubic-bezier(0.4,0,0.2,1);
            }

            /* When sidebar is collapsed, shift header left to align next to collapsed sidebar */
            .sidebar-collapsed .site-header {
                left: 4rem; /* collapsed sidebar width approximation (w-16 = 4rem) */
            }
            /* Shift header to the right on md+ so it sits beside the left sidebar
               and keep header z-index lower than the sidebar (sidebar z-40) */
            @media (min-width: 768px) {
                .site-header {
                    left: 16rem; /* equal to sidebar width (w-64) */
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
                <p class="text-gray-600 m-0">Selamat datang, <?php echo htmlspecialchars($_SESSION['user']['namaLengkap']); ?>!</p>
            </div>
            <div class="flex md:hidden items-center gap-2 mobile-logo-wrap">
                <img src="../../assets/img/logo.png" alt="Logo" class="h-7 w-7 flex-shrink-0">
                <div id="logoTextContainer" class="transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap">
                    <h1 id="logoText" class="mobile-logo-text font-bold text-gray-800">Point</h1>
                </div>
            </div>
            <div class="flex items-center action-buttons gap-1 md:space-x-4">
                <button command="show-modal" commandfor="join-class-modal" class="p-1 md:p-2 border rounded-full text-gray-400 hover:text-orange-600 transition-colors flex items-center">
                    <i class="ti ti-user-plus text-base md:text-xl"></i>
                    <span class="inline md:hidden ml-1 text-sm">Gabung</span>
                    <span class="hidden md:inline ml-1 text-sm">Gabung Kelas</span>
                </button>
                <button class="p-1 md:p-2 text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="ti ti-bell text-base md:text-xl"></i>
                </button>
                <button class="p-1 md:p-2 text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="ti ti-search text-base md:text-xl"></i>
                </button>
            </div>
        </div>
    </header>

    <div data-main-content class="pt-[3.5rem] md:pt-0 md:ml-64 md:mr-96 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">

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
                    <div class="min-w-[150px] md:min-w-0 flex-1 bg-white rounded-lg shadow-sm border border-gray-200 p-3 md:p-6 flex items-center">
                        <div class="p-2 md:p-3 md:me-4 bg-orange-tipis rounded-lg flex-shrink-0 md:mb-3">
                            <i class="ti ti-book text-orange-600 text-base md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-0">
                            <p class="text-[11px] md:text-sm text-gray-600 tracking-wide">Total Kelas</p>
                            <p class="text-lg md:text-2xl font-bold text-gray-800 leading-tight"><?php echo $dashboardData['totalKelas'] ?? 0; ?></p>
                        </div>
                    </div>
                    <!-- Card: Ujian Selesai -->
                    <div class="min-w-[150px] md:min-w-0 flex-1 bg-white rounded-lg shadow-sm border border-gray-200 p-3 md:p-6 flex items-center">
                        <div class="p-2 md:p-3 md:me-4 bg-orange-tipis rounded-lg flex-shrink-0 md:mb-3">
                            <i class="ti ti-clipboard-check text-orange-600 text-base md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-0">
                            <p class="text-[11px] md:text-sm text-gray-600 tracking-wide">Ujian Selesai</p>
                            <p class="text-lg md:text-2xl font-bold text-gray-800 leading-tight"><?php echo $dashboardData['ujianSelesai'] ?? 0; ?></p>
                        </div>
                    </div>
                    <!-- Card: Rata-rata Nilai -->
                    <div class="hidden md:flex min-w-[150px] md:min-w-0 flex-1 bg-white rounded-lg shadow-sm border border-gray-200 p-3 md:p-6 flex items-center">
                        <div class="p-2 md:p-3 md:me-4 bg-orange-tipis rounded-lg flex-shrink-0 md:mb-3">
                            <i class="ti ti-star text-orange-600 text-base md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-0">
                            <p class="text-[11px] md:text-sm text-gray-600 tracking-wide">Rata-rata Nilai</p>
                            <p class="text-lg md:text-2xl font-bold text-gray-800 leading-tight"><?php echo $dashboardData['rataNilai'] ?? 0; ?></p>
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
                        right: 1.5rem; /* align with padding */
                        top: 0; /* pin to top because header hidden on desktop */
                        height: 100vh;
                        overflow: hidden;
                        z-index: 40;
                    }

                    .beranda-right-sidebar .sidebar-scroll {
                        height: 100%;
                        overflow-y: auto;
                        -ms-overflow-style: none;
                        scrollbar-width: thin;
                        overscroll-behavior: contain; /* prevent scroll chaining to page */
                    }

                    .beranda-right-sidebar .sidebar-scroll::-webkit-scrollbar {
                        width: 8px;
                    }

                    .beranda-right-sidebar .sidebar-scroll::-webkit-scrollbar-thumb {
                        background: rgba(0,0,0,0.08);
                        border-radius: 999px;
                    }
                }
            </style>

            <aside class="hidden md:block beranda-right-sidebar">
                <div class="bg-transparent h-full rounded-lg shadow-none">
                    <div class="sidebar-scroll bg-transparent p-2 mt-4 ps-5 overflow-y-auto">
                        <!-- Card: Rekomendasi (image above text) -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="text-base font-semibold text-gray-800">Rekomendasi</h3>
                                <button class="text-sm text-gray-500 hover:text-gray-700">Lihat Semua</button>
                            </div>
                            <div class="p-3 space-y-4">
                                <!-- Recommended card item with image above -->
                                <div class="rounded-lg overflow-hidden bg-gray-50 border border-gray-100 hover:shadow-md transition-shadow">
                                    <div class="w-full h-40 bg-cover bg-center" style="background-image: url('../../assets/img/rekomendasi-sample-1.jpg');"></div>
                                    <div class="p-3">
                                        <div class="text-sm font-semibold text-gray-900">Kelas: Matematika Dasar</div>
                                        <div class="text-xs text-gray-500 mt-1">Ringkasan & latihan soal tersedia</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Minimal Info Card (Desktop-only moved from top stats) -->
                        <div class="hidden md:block bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                            <h3 class="text-sm font-semibold text-gray-800 mb-3">Info</h3>
                            <div class="space-y-3 text-sm text-gray-700">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Total Kelas</span>
                                    <span class="font-semibold text-gray-800"><?php echo $dashboardData['totalKelas'] ?? 0; ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Ujian Selesai</span>
                                    <span class="font-semibold text-gray-800"><?php echo $dashboardData['ujianSelesai'] ?? 0; ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Rata-rata Nilai</span>
                                    <span class="font-semibold text-gray-800"><?php echo $dashboardData['rataNilai'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Card: Pemberitahuan -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Pemberitahuan
                                    <?php if ($unreadNotificationsCount > 0): ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            <?php echo $unreadNotificationsCount; ?>
                                        </span>
                                    <?php endif; ?>
                                </h3>
                                <button onclick="openNotificationsModal()" class="text-xs text-gray-500 hover:text-gray-700">
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
                                                    <i class="ti <?php echo $notificationLogic->getNotificationIcon($notification['type']); ?> <?php echo $notificationLogic->getNotificationColor($notification['type']); ?>"></i>
                                                    <?php if (!$notification['is_read']): ?>
                                                        <div class="w-2 h-2 bg-orange-500 rounded-full -mt-1 -ml-1"></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($notification['title']); ?></div>
                                                    <div class="text-xs text-gray-600 mb-1"><?php echo htmlspecialchars($notification['message']); ?></div>
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

                        <!-- Card: Tugas Terbaru -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-800">Tugas Terbaru</h3>
                                <button class="text-xs text-gray-500 hover:text-gray-700">Lihat Semua</button>
                            </div>
                            <div class="p-2 space-y-2">
                                <div class="px-3 py-2 bg-gray-50 rounded hover:bg-gray-100">
                                    <div class="flex items-center justify-between">
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-gray-900 truncate">Tugas: Laporan Kimia</div>
                                            <div class="text-xs text-gray-500">Deadline: 18 Sep 2025</div>
                                        </div>
                                        <button class="ml-3 px-2 py-1 bg-blue-600 text-white text-xs rounded">Kumpulkan</button>
                                    </div>
                                </div>

                                <div class="px-3 py-2 bg-gray-50 rounded hover:bg-gray-100">
                                    <div class="flex items-center justify-between">
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-gray-900 truncate">Tugas: Soal Matematika</div>
                                            <div class="text-xs text-gray-500">Deadline: 20 Sep 2025</div>
                                        </div>
                                        <button class="ml-3 px-2 py-1 bg-white border border-gray-200 text-sm rounded">Lihat</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card: Teman Sekelas -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-800">Teman Sekelas</h3>
                                <button class="text-xs text-gray-500 hover:text-gray-700">Lihat Semua</button>
                            </div>
                            <div class="p-2 space-y-2">
                                <div class="flex items-center space-x-3 px-2 py-2 hover:bg-gray-50 rounded">
                                    <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-sm text-gray-700">A</div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900">Aisyah Putri</div>
                                        <div class="text-xs text-gray-500">Guru: Matematika</div>
                                    </div>
                                    <button class="ml-2 px-2 py-1 bg-white border border-gray-200 text-sm rounded">Chat</button>
                                </div>

                                <div class="flex items-center space-x-3 px-2 py-2 hover:bg-gray-50 rounded">
                                    <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-sm text-gray-700">R</div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900">Rizky Pratama</div>
                                        <div class="text-xs text-gray-500">Siswa • Kelas A</div>
                                    </div>
                                    <button class="ml-2 px-2 py-1 bg-white border border-gray-200 text-sm rounded">Chat</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
            <script>
                (function() {
                    // Enable sidebar-only scrolling when pointer is over the right sidebar
                    var sidebarScroll = document.querySelector('.beranda-right-sidebar .sidebar-scroll');
                    if (!sidebarScroll) return;

                    // Ensure the element can receive wheel events and prevent page scroll
                    sidebarScroll.addEventListener('wheel', function(e) {
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

                <!-- Posts Container for Dynamic Loading -->
                <div id="berandaPostsContainer" class="space-y-4">
                    <!-- Initial posts loaded via PHP for faster first paint -->
                    <?php if (!empty($recentPosts)): ?>
                        <?php foreach ($recentPosts as $post): ?>
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6" data-user-id="<?php echo $post['user_id']; ?>" data-post-id="<?php echo $post['id']; ?>">
                                <!-- Post Header -->
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full overflow-hidden flex items-center justify-center flex-shrink-0 bg-gray-100">
                                            <?php if (isset($post['fotoProfil']) && !empty($post['fotoProfil'])): ?>
                                                <?php
                                                $fotoProfil = $post['fotoProfil'];
                                                // Check if it already contains the full path
                                                if (strpos($fotoProfil, 'uploads/profile/') === 0) {
                                                    $photoPath = '../../' . $fotoProfil;
                                                } else {
                                                    $photoPath = '../../uploads/profile/' . $fotoProfil;
                                                }
                                                ?>
                                                <img src="<?php echo htmlspecialchars($photoPath); ?>"
                                                    alt="Profile Photo"
                                                    class="w-full h-full object-cover post-profile-photo"
                                                    onerror="this.parentElement.innerHTML='<div class=\'w-full h-full bg-orange-600 rounded-full flex items-center justify-center\'><span class=\'text-white font-medium text-sm\'><?php echo strtoupper(substr($post['namaPenulis'], 0, 1)); ?></span></div>'">
                                            <?php else: ?>
                                                <!-- Fallback with role-based colors -->
                                                <div class="w-full h-full rounded-full flex items-center justify-center <?php
                                                                                                                        switch ($post['rolePenulis']) {
                                                                                                                            case 'admin':
                                                                                                                                echo 'bg-red-100 text-red-600';
                                                                                                                                break;
                                                                                                                            case 'guru':
                                                                                                                                echo 'bg-blue-100 text-blue-600';
                                                                                                                                break;
                                                                                                                            case 'siswa':
                                                                                                                                echo 'bg-green-100 text-green-600';
                                                                                                                                break;
                                                                                                                            default:
                                                                                                                                echo 'bg-orange-600 text-white';
                                                                                                                        }
                                                                                                                        ?>">
                                                    <span class="font-medium text-sm">
                                                        <?php echo strtoupper(substr($post['namaPenulis'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($post['namaPenulis']); ?></h4>
                                                <span class="text-sm text-gray-500">•</span>
                                                <span class="text-sm text-orange-600 font-medium"><?php echo htmlspecialchars($post['namaKelas']); ?></span>
                                            </div>
                                            <p class="text-xs text-gray-500">
                                                <?php echo date('d M Y, H:i', strtotime($post['dibuat'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Post Content -->
                                <div class="mb-4">
                                    <div class="post-content text-gray-800 whitespace-pre-wrap"><?php
                                                                                                // Convert markdown-style formatting to HTML
                                                                                                // First do the markdown replacements before htmlspecialchars
                                                                                                $formattedContent = $post['konten'];
                                                                                                // Bold text: **text** -> <strong>text</strong>
                                                                                                $formattedContent = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $formattedContent);
                                                                                                // Italic text: *text* -> <em>text</em> (only single asterisks not already converted)
                                                                                                $formattedContent = preg_replace('/(?<!\*)\*([^*]+?)\*(?!\*)/', '<em>$1</em>', $formattedContent);
                                                                                                // Now escape HTML but preserve our formatting tags
                                                                                                $formattedContent = htmlspecialchars($formattedContent, ENT_QUOTES, 'UTF-8', false);
                                                                                                // Unescape our formatting tags
                                                                                                $formattedContent = str_replace(['&lt;strong&gt;', '&lt;/strong&gt;', '&lt;em&gt;', '&lt;/em&gt;'], ['<strong>', '</strong>', '<em>', '</em>'], $formattedContent);
                                                                                                echo $formattedContent;
                                                                                                ?></div>

                                    <!-- Assignment Content (if it's an assignment post) -->
                                    <?php if ($post['tipePost'] === 'tugas' || $post['tipe_postingan'] === 'assignment'): ?>
                                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 mt-3" data-assignment-id="<?php echo $post['assignment_id'] ?? 0; ?>">
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
                                                            <?php echo isset($post['assignment_title']) ? htmlspecialchars($post['assignment_title']) : 'Tugas'; ?>
                                                        </h3>
                                                    </div>

                                                    <!-- Assignment Details Grid -->
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                                                        <?php if (isset($post['assignment_deadline']) && $post['assignment_deadline']): ?>
                                                            <?php
                                                            $isExpired = strtotime($post['assignment_deadline']) < time();
                                                            ?>
                                                            <div class="flex items-center space-x-2 p-3 bg-white rounded-lg border <?php echo $isExpired ? 'border-red-200 bg-red-50' : 'border-gray-200'; ?>">
                                                                <div class="flex-shrink-0">
                                                                    <i class="ti ti-calendar-due text-lg <?php echo $isExpired ? 'text-red-500' : 'text-orange-500'; ?>"></i>
                                                                </div>
                                                                <div class="flex-1 min-w-0">
                                                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Deadline</div>
                                                                    <div class="text-sm font-semibold <?php echo $isExpired ? 'text-red-700' : 'text-gray-900'; ?> truncate">
                                                                        <span class="hidden sm:inline"><?php echo date('l, d M Y H:i', strtotime($post['assignment_deadline'])); ?></span>
                                                                        <span class="sm:hidden"><?php echo date('d M, H:i', strtotime($post['assignment_deadline'])); ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if (isset($post['assignment_max_score']) && $post['assignment_max_score']): ?>
                                                            <div class="flex items-center space-x-2 p-3 bg-white rounded-lg border border-gray-200">
                                                                <div class="flex-shrink-0">
                                                                    <i class="ti ti-trophy text-lg text-yellow-500"></i>
                                                                </div>
                                                                <div class="flex-1">
                                                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nilai Maksimal</div>
                                                                    <div class="text-sm font-semibold text-gray-900"><?php echo $post['assignment_max_score']; ?> Poin</div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if (isset($post['assignment_file_path']) && $post['assignment_file_path']): ?>
                                                        <div class="p-3 bg-white rounded-lg border border-gray-200">
                                                            <div class="flex items-center space-x-3">
                                                                <div class="flex-shrink-0">
                                                                    <i class="ti ti-file text-blue-600 text-xl"></i>
                                                                </div>
                                                                <div class="flex-1 min-w-0">
                                                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">File Tugas</div>
                                                                    <div class="text-sm font-semibold text-gray-900 truncate"><?php echo basename($post['assignment_file_path']); ?></div>
                                                                </div>
                                                                <a href="../../<?php echo htmlspecialchars($post['assignment_file_path']); ?>" target="_blank"
                                                                    class="flex items-center space-x-1 bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex-shrink-0">
                                                                    <i class="ti ti-download"></i>
                                                                    <span class="hidden sm:inline">Download</span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Assignment Submission Status & Actions -->
                                            <div class="mt-3 pt-3 border-t border-blue-200">
                                                <?php
                                                // Get submission status for this assignment
                                                $submissionStatus = 'not_submitted';
                                                $studentScore = null;
                                                $isExpired = false;

                                                if (isset($post['assignment_deadline']) && $post['assignment_deadline']) {
                                                    $isExpired = strtotime($post['assignment_deadline']) < time();
                                                }

                                                // Check if we have submission data
                                                if (isset($post['student_status'])) {
                                                    if ($post['student_status'] === 'dinilai') {
                                                        $submissionStatus = 'graded';
                                                        $studentScore = $post['student_score'] ?? null;
                                                    } elseif ($post['student_status'] === 'dikumpulkan') {
                                                        $submissionStatus = 'submitted';
                                                    }
                                                }
                                                ?>

                                                <div class="flex items-center justify-between" id="assignment-status-row-<?php echo $post['assignment_id'] ?? 0; ?>">
                                                    <div class="flex items-center">
                                                        <?php if ($submissionStatus === 'graded'): ?>
                                                            <span id="assignment-status-badge-<?php echo $post['assignment_id']; ?>" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
                                                                <i class="ti ti-check-circle mr-1"></i>
                                                                Sudah dinilai
                                                                <?php if ($studentScore !== null): ?>
                                                                    (<?php echo $studentScore; ?>)
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php elseif ($submissionStatus === 'submitted'): ?>
                                                            <span id="assignment-status-badge-<?php echo $post['assignment_id']; ?>" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                                                                <i class="ti ti-upload mr-1"></i>
                                                                Sudah dikumpulkan
                                                            </span>
                                                        <?php elseif ($isExpired): ?>
                                                            <span id="assignment-status-badge-<?php echo $post['assignment_id']; ?>" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-red-100 text-red-800">
                                                                <i class="ti ti-clock-x mr-1"></i>
                                                                Terlambat
                                                            </span>
                                                        <?php else: ?>
                                                            <span id="assignment-status-badge-<?php echo $post['assignment_id']; ?>" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-yellow-100 text-yellow-800">
                                                                <i class="ti ti-clock mr-1"></i>
                                                                Belum dikumpulkan
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if ($submissionStatus !== 'graded' && !$isExpired && isset($post['assignment_id'])): ?>
                                                        <button id="open-inline-form-btn-<?php echo $post['assignment_id']; ?>" onclick="showSubmissionForm(<?php echo $post['assignment_id']; ?>)"
                                                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors">
                                                            <?php echo $submissionStatus === 'submitted' ? 'Update Tugas' : 'Kumpulkan Tugas'; ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (isset($post['assignment_id'])): ?>
                                                    <!-- Progress Bar States -->
                                                    <?php
                                                    $progressWidth = 0;
                                                    $leftState = 'text-gray-400';
                                                    $rightState = 'text-gray-400';
                                                    $barColor = 'bg-green-500';
                                                    if ($submissionStatus === 'submitted') {
                                                        $progressWidth = 50;
                                                        $leftState = 'text-green-600';
                                                    } elseif ($submissionStatus === 'graded') {
                                                        $progressWidth = 100;
                                                        $leftState = 'text-green-600';
                                                        $rightState = 'text-green-600';
                                                    }
                                                    ?>
                                                    <div class="mt-3" id="assignment-progress-wrapper-<?php echo $post['assignment_id']; ?>">
                                                        <div class="flex justify-between text-[10px] md:text-xs font-medium mb-1 text-gray-500">
                                                            <span class="flex items-center gap-1 <?php echo $leftState; ?>">
                                                                <i class="ti ti-check"></i>
                                                                Terkumpul
                                                            </span>
                                                            <span class="flex items-center gap-1 <?php echo $rightState; ?>">
                                                                <i class="ti ti-star"></i>
                                                                Dinilai
                                                            </span>
                                                        </div>
                                                        <div class="relative h-2 bg-gray-200 rounded-full" id="assignment-progress-bar-<?php echo $post['assignment_id']; ?>">
                                                            <div class="absolute inset-y-0 left-0 <?php echo $barColor; ?> rounded-full transition-all duration-500" id="assignment-progress-fill-<?php echo $post['assignment_id']; ?>" style="width: <?php echo $progressWidth; ?>%"></div>
                                                            <div class="absolute -top-1 w-4 h-4 rounded-full border-2 border-white shadow left-0 translate-x-[-2px] flex items-center justify-center <?php echo ($progressWidth > 0) ? 'bg-green-500' : 'bg-gray-300'; ?>">
                                                                <i class="ti ti-check text-white text-[10px]"></i>
                                                            </div>
                                                            <div class="absolute -top-1 w-4 h-4 rounded-full border-2 border-white shadow right-0 translate-x-[2px] flex items-center justify-center <?php echo ($progressWidth == 100) ? 'bg-green-500' : 'bg-gray-300'; ?>">
                                                                <i class="ti ti-star text-white text-[10px]"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($submissionStatus !== 'graded' && !$isExpired && isset($post['assignment_id'])): ?>
                                                    <div id="submission-form-<?php echo $post['assignment_id']; ?>" class="hidden mt-4">
                                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                                            <div class="flex items-start justify-between mb-4">
                                                                <div>
                                                                    <h4 class="font-semibold text-gray-900 mb-1">Kumpulkan Tugas: <?php echo htmlspecialchars($post['assignment_title'] ?? 'Tugas'); ?></h4>
                                                                    <?php if (!empty($post['assignment_deadline'])): ?>
                                                                        <p class="text-xs text-gray-600 flex items-center"><i class="ti ti-clock mr-1"></i>Deadline: <?php echo date('d M Y, H:i', strtotime($post['assignment_deadline'])); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <button onclick="hideSubmissionForm(<?php echo $post['assignment_id']; ?>)" type="button" class="text-gray-500 hover:text-gray-700"><i class="ti ti-x"></i></button>
                                                            </div>
                                                            <div class="mb-4">
                                                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File</label>
                                                                <div class="flex items-center flex-wrap gap-3">
                                                                    <input type="file" id="submission-file-<?php echo $post['assignment_id']; ?>" class="hidden" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif" onchange="handleSubmissionFileSelect(<?php echo $post['assignment_id']; ?>, this)">
                                                                    <button type="button" onclick="document.getElementById('submission-file-<?php echo $post['assignment_id']; ?>').click()" class="bg-white border border-gray-300 hover:bg-gray-100 px-4 py-2 rounded-lg text-sm font-medium">
                                                                        <i class="ti ti-paperclip mr-2"></i>Pilih File
                                                                    </button>
                                                                    <span class="text-xs text-gray-500">PDF, DOC, PPT, Gambar (Maks 10MB)</span>
                                                                </div>
                                                            </div>
                                                            <div id="submission-preview-<?php echo $post['assignment_id']; ?>" class="hidden mb-4">
                                                                <div class="bg-white border border-gray-200 rounded-lg p-3">
                                                                    <div class="flex items-center justify-between">
                                                                        <div class="flex items-center space-x-3">
                                                                            <div id="file-icon-<?php echo $post['assignment_id']; ?>" class="text-blue-600 text-lg"><i class="ti ti-file"></i></div>
                                                                            <div>
                                                                                <div id="file-name-<?php echo $post['assignment_id']; ?>" class="text-sm font-medium text-gray-900"></div>
                                                                                <div id="file-size-<?php echo $post['assignment_id']; ?>" class="text-xs text-gray-500"></div>
                                                                            </div>
                                                                        </div>
                                                                        <button type="button" onclick="removeSubmissionFile(<?php echo $post['assignment_id']; ?>)" class="text-red-600 hover:text-red-800 p-1 rounded"><i class="ti ti-x"></i></button>
                                                                    </div>
                                                                    <div id="image-preview-<?php echo $post['assignment_id']; ?>" class="hidden mt-3">
                                                                        <img class="max-w-full h-48 object-cover rounded-lg" />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="mb-4">
                                                                <label class="block text-sm font-medium text-gray-700 mb-2" for="submission-notes-<?php echo $post['assignment_id']; ?>">Catatan (Opsional)</label>
                                                                <textarea id="submission-notes-<?php echo $post['assignment_id']; ?>" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Tambahkan catatan untuk guru..."></textarea>
                                                            </div>
                                                            <div class="flex justify-end items-center gap-3">
                                                                <button type="button" onclick="hideSubmissionForm(<?php echo $post['assignment_id']; ?>)" class="text-gray-600 hover:text-gray-800 text-sm font-medium">Batal</button>
                                                                <button type="button" id="submit-btn-<?php echo $post['assignment_id']; ?>" onclick="submitAssignment(<?php echo $post['assignment_id']; ?>)" disabled class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                                                    <i class="ti ti-send mr-2"></i>Kumpulkan
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Post Images -->
                                    <?php if (!empty($post['gambar'])): ?>

                                        
                                        <div class="mt-3 post-media-container">
                                            <div class="post-media-grid grid-<?php echo count($post['gambar']); ?>">
                                                <?php foreach ($post['gambar'] as $index => $media): ?>
                                                    <?php
                                                    // Handle different media path formats
                                                    $mediaPath = '';
                                                    if (isset($media['path_gambar']) && !empty($media['path_gambar'])) {
                                                        // New format with full path
                                                        $mediaPath = '../../' . $media['path_gambar'];
                                                    } else {
                                                        // Old format with just filename
                                                        $mediaPath = '../../uploads/postingan/' . $media['nama_file'];
                                                    }
                                                    
                                                    // Check if this is a video
                                                    $isVideo = (isset($media['media_type']) && $media['media_type'] === 'video') ||
                                                               (isset($media['tipe_file']) && strpos($media['tipe_file'], 'video/') === 0);
                                                    $mediaClass = count($post['gambar']) === 1 ? 'single' : 'multiple';
                                                    ?>
                                                    
                                                    <?php if ($isVideo): ?>
                                                        <!-- Video Media -->
                                                        <div class="post-media-item <?php echo $mediaClass; ?>">
                                                            <video controls 
                                                                   class="post-media" 
                                                                   data-media-index="<?php echo $index; ?>"
                                                                   preload="metadata">
                                                                <source src="<?php echo htmlspecialchars($mediaPath); ?>" 
                                                                        type="<?php echo htmlspecialchars($media['tipe_file'] ?? 'video/mp4'); ?>">
                                                                Your browser does not support the video tag.
                                                            </video>
                                                            <div class="post-media-type-badge video">
                                                                <i class="ti ti-video"></i> Video
                                                            </div>
                                                            <button class="media-download-btn" 
                                                                    onclick="downloadMedia('<?php echo htmlspecialchars($mediaPath); ?>', '<?php echo htmlspecialchars($media['nama_file']); ?>')" 
                                                                    title="Download Video">
                                                                <i class="ti ti-download"></i>
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <!-- Image Media -->
                                                        <div class="post-media-item <?php echo $mediaClass; ?>">
                                                            <img src="<?php echo htmlspecialchars($mediaPath); ?>"
                                                                alt="<?php echo htmlspecialchars($media['nama_file'] ?? 'Media postingan'); ?>"
                                                                class="post-media"
                                                                data-media-index="<?php echo $index; ?>"
                                                                data-pswp-src="<?php echo htmlspecialchars($mediaPath); ?>"
                                                                data-pswp-width="800"
                                                                data-pswp-height="600"
                                                                style="cursor: pointer;"
                                                                onerror="this.style.display='none';">
                                                            <div class="post-media-type-badge image">
                                                                <i class="ti ti-photo"></i> Gambar
                                                            </div>
                                                            <button class="media-download-btn" 
                                                                    onclick="downloadMedia('<?php echo htmlspecialchars($mediaPath); ?>', '<?php echo htmlspecialchars($media['nama_file']); ?>')" 
                                                                    title="Download Gambar">
                                                                <i class="ti ti-download"></i>
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php
                                                    // If there are more than 4 images, after rendering the 4th (index 3)
                                                    // show an overlay on the 4th image indicating how many more images exist.
                                                    $totalGambar = count($post['gambar']);
                                                    if ($totalGambar > 4 && $index == 3): ?>
                                                        <!-- Show overflow indicator on the 4th image only when >4 images -->
                                                        <div class="post-media-item multiple relative">
                                                            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center rounded-lg">
                                                                <span class="text-white font-bold text-lg">+<?php echo $totalGambar - 4; ?></span>
                                                            </div>
                                                        </div>
                                                        <?php break; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Post Files -->
                                    <?php if (!empty($post['files'])): ?>
                                        <?php
                                        // Helper function for file size formatting
                                        if (!function_exists('formatFileSize')) {
                                            function formatFileSize($bytes)
                                            {
                                                if ($bytes == 0) return '0 Bytes';
                                                $k = 1024;
                                                $sizes = ['Bytes', 'KB', 'MB', 'GB'];
                                                $i = floor(log($bytes) / log($k));
                                                return round(($bytes / pow($k, $i)), 2) . ' ' . $sizes[$i];
                                            }
                                        }
                                        ?>
                                        <div class="mt-3 space-y-2">
                                            <?php foreach ($post['files'] as $file): ?>
                                                <?php
                                                // Get file extension for icon
                                                $extension = strtolower($file['ekstensi_file']);

                                                // Icon mapping
                                                $iconMap = [
                                                    'pdf' => ['icon' => 'ti-file-type-pdf', 'class' => 'pdf', 'bg' => 'bg-red-500'],
                                                    'doc' => ['icon' => 'ti-file-type-doc', 'class' => 'word', 'bg' => 'bg-blue-500'],
                                                    'docx' => ['icon' => 'ti-file-type-doc', 'class' => 'word', 'bg' => 'bg-blue-500'],
                                                    'xls' => ['icon' => 'ti-file-type-xls', 'class' => 'excel', 'bg' => 'bg-green-500'],
                                                    'xlsx' => ['icon' => 'ti-file-type-xls', 'class' => 'excel', 'bg' => 'bg-green-500'],
                                                    'ppt' => ['icon' => 'ti-presentation', 'class' => 'powerpoint', 'bg' => 'bg-orange-500'],
                                                    'pptx' => ['icon' => 'ti-presentation', 'class' => 'powerpoint', 'bg' => 'bg-orange-500'],
                                                    'txt' => ['icon' => 'ti-file-text', 'class' => 'text', 'bg' => 'bg-gray-500'],
                                                    'zip' => ['icon' => 'ti-file-zip', 'class' => 'archive', 'bg' => 'bg-yellow-600'],
                                                    'rar' => ['icon' => 'ti-file-zip', 'class' => 'archive', 'bg' => 'bg-yellow-600'],
                                                    '7z' => ['icon' => 'ti-file-zip', 'class' => 'archive', 'bg' => 'bg-yellow-600']
                                                ];

                                                $iconInfo = $iconMap[$extension] ?? ['icon' => 'ti-file', 'class' => 'default', 'bg' => 'bg-gray-500'];
                                                ?>
                                                <a href="../../<?php echo htmlspecialchars($file['path_file']); ?>"
                                                    class="flex items-center p-3 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 hover:border-gray-300 transition-all hover:shadow-sm"
                                                    target="_blank" rel="noopener noreferrer">
                                                    <div class="w-12 h-12 <?php echo $iconInfo['bg']; ?> text-white rounded-lg flex items-center justify-center mr-3 flex-shrink-0 shadow-sm">
                                                        <i class="ti <?php echo $iconInfo['icon']; ?> text-lg"></i>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-medium text-gray-900 truncate text-sm" title="<?php echo htmlspecialchars($file['nama_file']); ?>">
                                                            <?php echo htmlspecialchars($file['nama_file']); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            <?php echo formatFileSize($file['ukuran_file']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="ml-3 text-gray-400 hover:text-gray-600">
                                                        <i class="ti ti-download text-lg"></i>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Post Actions -->
                                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                    <div class="flex items-center space-x-4">
                                        <!-- Like Button -->
                                        <button class="like-btn flex items-center space-x-2 <?php echo $post['userLiked'] ? 'text-red-600' : 'text-gray-600'; ?> hover:text-red-600 transition-colors"
                                            data-post-id="<?php echo $post['id']; ?>"
                                            data-liked="<?php echo $post['userLiked'] ? 'true' : 'false'; ?>">
                                            <i class="ti ti-heart<?php echo $post['userLiked'] ? '-filled text-red-600' : ''; ?>"></i>
                                            <span class="like-count text-sm"><?php echo $post['jumlahLike']; ?></span>
                                        </button>

                                        <!-- Comment Button -->
                                        <?php if (!$post['restrict_comments']): ?>
                                            <button class="comment-btn flex items-center space-x-2 text-gray-600 hover:text-blue-600 transition-colors"
                                                data-post-id="<?php echo $post['id']; ?>">
                                                <i class="ti ti-message-circle"></i>
                                                <span class="comment-count text-sm"><?php echo $post['jumlahKomentar']; ?></span>
                                            </button>
                                        <?php else: ?>
                                            <span class="flex items-center space-x-2 text-gray-400">
                                                <i class="ti ti-message-circle-off"></i>
                                                <span class="text-sm">Komentar dinonaktifkan</span>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Class Link -->
                                    <a href="kelas-user.php?id=<?php echo $post['kelas_id']; ?>"
                                        class="text-orange-600 hover:text-orange-700 text-sm font-medium">
                                        Lihat Kelas
                                    </a>
                                </div>

                                <!-- Comments Section for KelasPosting compatibility -->
                                <?php if (!$post['restrict_comments']): ?>
                                    <!-- View All Comments Button -->
                                    <button class="view-all-comments text-orange text-sm hover:text-orange-600 transition-colors" data-post-id="<?php echo $post['id']; ?>" style="display: none;">
                                        Lihat komentar lainnya
                                    </button>

                                    <!-- Comments Preview - Always visible if there are comments -->
                                    <div id="comments-preview-<?php echo $post['id']; ?>" class="mt-4 pt-4 border-t border-gray-100" style="display: none;">
                                        <!-- Preview comments (max 3) will be loaded here -->
                                    </div>

                                    <!-- Quick Comment Input -->
                                    <div id="quick-comment-<?php echo $post['id']; ?>" class="hidden mt-4 pt-4 border-t border-gray-100">
                                        <form class="flex space-x-3" onsubmit="addQuickComment(event, <?php echo $post['id']; ?>)">
                                            <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center flex-shrink-0">
                                                <i class="ti ti-user text-white text-sm"></i>
                                            </div>
                                            <div class="flex-1">
                                                <textarea placeholder="Tulis komentar... (tekan Enter untuk mengirim)"
                                                    rows="2"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm"
                                                    onkeydown="handleCommentKeydown(event, <?php echo $post['id']; ?>)"
                                                    required></textarea>
                                                <div class="flex justify-end mt-2">
                                                    <button type="button" class="text-gray-500 text-sm mr-3" onclick="hideQuickComment(<?php echo $post['id']; ?>)">Batal</button>
                                                    <button type="submit" class="bg-orange-600 text-white px-4 py-1.5 rounded-lg hover:bg-orange-700 text-sm">Kirim</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                            <i class="ti ti-message-off text-4xl text-gray-300 mb-3"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada postingan</h3>
                            <p class="text-gray-500 mb-4">Postingan dari kelas yang Anda ikuti akan muncul di sini</p>
                            <div class="text-sm text-gray-400">
                                <p>Tips:</p>
                                <ul class="list-disc list-inside mt-2 space-y-1">
                                    <li>Pastikan Anda sudah bergabung dengan kelas</li>
                                    <li>Minta guru untuk membuat postingan di kelas</li>
                                    <li>Periksa koneksi internet Anda</li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Load More Button for Beranda -->
                <?php if (!empty($recentPosts) && count($recentPosts) >= 5): ?>
                    <div class="text-center mt-6">
                        <button id="loadMoreBerandaPosts" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                            <i class="ti ti-plus mr-2"></i>
                            Muat Postingan Lainnya
                        </button>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- Include Modal Components -->
    <?php require '../component/modal-comments.php'; ?>
    <?php /* Modal submit assignment dihilangkan pada beranda karena diganti inline form */ ?>

    <script src="../script/menu-bar-script.js"></script>
    <script src="../script/kelas-management.js"></script>
    <script src="../script/media-upload-manager.js"></script>
    <script src="../script/photoswipe-simple.js"></script>
    <script src="../script/assignment-manager.js"></script>
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
        window.markNotificationAsRead = async function(notificationId, element) {
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
        window.handleNotificationClick = async function(notificationId, redirectUrl, hasValidRedirect, element) {
            console.log('🔔 Notification clicked:', {notificationId, redirectUrl, hasValidRedirect});
            
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
        
        window.loadBerandaNotifications = async function() {
            try {
                const response = await fetch('../logic/get-notifications.php?limit=2');
                const data = await response.json();
                
                if (data.success) {
                    updateBerandaNotificationsUI(data.notifications);
                    updateUnreadBadge();
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
            
            let html = '<ul class="divide-y divide-gray-100">';
            notifications.forEach(notification => {
                const isRead = notification.is_read == '1';
                const iconClass = getNotificationIcon(notification.type);
                const colorClass = getNotificationColor(notification.type);
                
                html += `
                    <li class="px-3 py-2 flex items-start space-x-3 hover:bg-gray-50 cursor-pointer ${isRead ? 'opacity-75' : ''}" 
                        onclick="markNotificationAsRead(${notification.id}, this)">
                        <div class="flex-shrink-0 mt-1 relative">
                            <i class="ti ${iconClass} ${colorClass}"></i>
                            ${!isRead ? '<div class="w-2 h-2 bg-orange-500 rounded-full absolute -mt-1 -ml-1"></div>' : ''}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-gray-900 font-medium">${escapeHtml(notification.title)}</div>
                            <div class="text-xs text-gray-600 mb-1">${escapeHtml(notification.message)}</div>
                            <div class="text-xs text-gray-500">
                                ${notification.nama_kelas ? 'Kelas: ' + escapeHtml(notification.nama_kelas) + ' • ' : ''}
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
        window.downloadMedia = function(url, filename) {
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

        // Initialize like functionality
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Initialize KelasPosting for comments functionality (beranda context)
            window.kelasPosting = new KelasPosting(null, {
                canPost: false, // No posting in beranda
                canComment: true // Allow commenting
            });

            // Prevent KelasPosting from loading posts since we already have them in PHP
            if (window.kelasPosting) {
                window.kelasPosting.initialized = true; // Mark as initialized to prevent auto-loading
                window.kelasPosting.hasMorePosts = false; // Prevent infinite scroll
            }

            // Initialize beranda lazy loading
            initializeBerandaLazyLoading();

            // Debug: Check if we have posts
            console.log('Recent posts loaded:', <?php echo json_encode(count($recentPosts)); ?>);
            console.log('Has posts data:', <?php echo !empty($recentPosts) ? 'true' : 'false'; ?>);
            <?php if (!empty($recentPosts)): ?>
                console.log('First post:', <?php echo json_encode($recentPosts[0] ?? []); ?>);
                console.log('Posts container children:', document.getElementById('berandaPostsContainer').children.length);
            <?php endif; ?>

            // Comment button functionality - use KelasPosting method
            document.querySelectorAll('.comment-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const postId = this.dataset.postId;
                    if (window.kelasPosting) {
                        window.kelasPosting.openCommentsModal(postId);
                    }
                });
            });

            // Like button functionality
            document.querySelectorAll('.like-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const postId = this.dataset.postId;
                    if (window.kelasPosting) {
                        window.kelasPosting.toggleLike(postId);
                    }
                });
            });

            // Load comments preview for all posts
            setTimeout(() => {
                console.log('Loading comments preview for existing posts...');
                document.querySelectorAll('[id^="comments-preview-"]').forEach(container => {
                    const postId = container.id.replace('comments-preview-', '');
                    console.log('Loading comments for post:', postId);
                    if (window.kelasPosting) {
                        console.log('Loading comments preview for post', postId);
                        window.kelasPosting.loadCommentsPreview(postId);
                    }
                });
            }, 500); // Give time for KelasPosting to fully initialize
        });

        // Initialize assignment manager for beranda
        if (typeof AssignmentManager !== 'undefined') {
            window.assignmentManager = new AssignmentManager(null, 'siswa'); // null for beranda context
        }

        // Make assignment functions globally available
        window.showSubmissionModal = function(assignmentId) {
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
        window.handleSubmissionFileSelect = function(assignmentId, input) {
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
                reader.onload = function(e) {
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

        // Beranda lazy loading functionality
        let berandaOffset = 5; // We already loaded 5 posts
        let berandaHasMore = true;
        let berandaLoading = false;

        function initializeBerandaLazyLoading() {
            const loadMoreBtn = document.getElementById('loadMoreBerandaPosts');
            const container = document.getElementById('berandaPostsContainer');

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
            const loadMoreBtn = document.getElementById('loadMoreBerandaPosts');
            const container = document.getElementById('berandaPostsContainer');

            // Show loading state
            if (loadMoreBtn) {
                loadMoreBtn.innerHTML = '<i class="ti ti-loader animate-spin mr-2"></i>Memuat...';
                loadMoreBtn.disabled = true;
            }

            try {
                const response = await fetch(`../logic/get-beranda-posts.php?offset=${berandaOffset}&limit=5&_=${Date.now()}`);
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
                console.error('Error loading more posts:', error);
                if (loadMoreBtn) {
                    loadMoreBtn.innerHTML = '<i class="ti ti-alert-circle mr-2"></i>Gagal memuat';
                }
            } finally {
                berandaLoading = false;
                if (loadMoreBtn && berandaHasMore) {
                    loadMoreBtn.innerHTML = '<i class="ti ti-plus mr-2"></i>Muat Postingan Lainnya';
                    loadMoreBtn.disabled = false;
                }
            }
        }

        function createBerandaPostElement(post) {
            // Create post element (similar to PHP template but in JS)
            const postDiv = document.createElement('div');
            postDiv.className = 'bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6';
            postDiv.setAttribute('data-user-id', post.user_id);
            postDiv.setAttribute('data-post-id', post.id);

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
                    ${post.konten ? `<div class="post-content text-gray-800 whitespace-pre-wrap">${formatPostContent(post.konten)}</div>` : ''}
                </div>
                
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
                    </div>
                    
                    <!-- Class Link -->
                    <a href="kelas-user.php?id=${post.kelas_id}" class="text-orange-600 hover:text-orange-700 text-sm font-medium">
                        Lihat Kelas
                    </a>
                </div>
                
                <!-- Comments Section for KelasPosting compatibility -->
                ${!post.restrict_comments ? `
                <button class="view-all-comments text-orange text-sm hover:text-orange-600 transition-colors" data-post-id="${post.id}" style="display: none;">
                    Lihat komentar lainnya
                </button>
                <div id="comments-preview-${post.id}" class="mt-4 pt-4 border-t border-gray-100" style="display: none;">
                    <!-- Preview comments will be loaded here -->
                </div>
                ` : ''}
                ${buildMediaHtml(post)}
            `;

            return postDiv;
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
            formatted = formatted.replace(/(?:^|[^*])\*([^*]+?)\*(?![*])/g, function(match, p1, offset, string) {
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
            // Like button
            const likeBtn = postElement.querySelector('.like-btn');
            if (likeBtn && window.kelasPosting) {
                likeBtn.addEventListener('click', function() {
                    window.kelasPosting.toggleLike(postId);
                });
            }

            // Comment button
            const commentBtn = postElement.querySelector('.comment-btn');
            if (commentBtn && window.kelasPosting) {
                commentBtn.addEventListener('click', function() {
                    window.kelasPosting.openCommentsModal(postId);
                });
            }

            // Load comments preview for new post
            if (window.kelasPosting) {
                setTimeout(() => {
                    window.kelasPosting.loadCommentsPreview(postId);
                }, 300);
            }
        }

        // Inline assignment submission handlers (beranda)
        window.showSubmissionForm = function(assignmentId) {
            const formWrapper = document.getElementById(`submission-form-${assignmentId}`);
            const openBtn = document.getElementById(`open-inline-form-btn-${assignmentId}`);
            if (formWrapper && openBtn) {
                formWrapper.classList.remove('hidden');
                openBtn.classList.add('hidden');
            }
        };
        window.hideSubmissionForm = function(assignmentId) {
            const formWrapper = document.getElementById(`submission-form-${assignmentId}`);
            const openBtn = document.getElementById(`open-inline-form-btn-${assignmentId}`);
            if (formWrapper && openBtn) {
                formWrapper.classList.add('hidden');
                openBtn.classList.remove('hidden');
            }
        };
    </script>
    <script src="../script/profile-sync.js"></script>
</body>

</html>