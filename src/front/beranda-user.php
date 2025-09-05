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

// Get recent posts from all classes
$recentPosts = $dashboardLogic->getPostinganTerbaruSiswa($siswa_id, 5);

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
    <meta name="user-id" content="<?php echo $_SESSION['user']['id']; ?>">
    <?php require '../../assets/head.php'; ?>
    <link rel="stylesheet" href="../css/kelas-posting.css?v=<?php echo time(); ?>">
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

    <!-- Main Content -->
    <div data-main-content class="md:ml-64 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">
        <!-- Header -->
        <header class="bg-white p-2 md:p-6 header-compact border-b border-gray-200">
            <style>
            @media (max-width: 768px) {
                .header-compact { padding: .5rem .75rem; }
                .header-compact .mobile-logo-wrap img { height: 28px; width: 28px; }
                .header-compact .mobile-logo-text { font-size: 1.35rem; line-height: 1.45rem; }
                .header-compact .action-buttons { gap: .25rem; }
                .header-compact .action-buttons button { padding: .4rem; }
                .header-compact .action-buttons i { font-size: 1.05rem; }
            }
            </style>
            <div class="flex items-center justify-between">
            <div class="hidden md:block">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">Beranda</h1>
                <p class="text-gray-600">Selamat datang, <?php echo htmlspecialchars($_SESSION['user']['namaLengkap']); ?>!</p>
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
            <div class="mb-6 md:mb-8">
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

            <!-- Recent Posts Section -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg md:text-xl font-bold text-gray-800">Postingan Terbaru</h2>
                </div>

                <?php if (!empty($recentPosts)): ?>
                    <div class="space-y-4">
                        <?php foreach ($recentPosts as $post): ?>
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6" data-user-id="<?php echo $post['user_id']; ?>">
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
                                                                                                $formattedContent = htmlspecialchars($post['konten']);
                                                                                                // Bold text: **text** -> <strong>text</strong>
                                                                                                $formattedContent = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $formattedContent);
                                                                                                // Italic text: *text* -> <em>text</em> (only single asterisks not already converted)
                                                                                                $formattedContent = preg_replace('/(?<!\*)\*([^*]+?)\*(?!\*)/', '<em>$1</em>', $formattedContent);
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
                                                        <h3 class="text-xl font-bold text-gray-900 flex items-center">
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
                                        <div class="mt-3 grid grid-cols-2 md:grid-cols-3 gap-2">
                                            <?php foreach ($post['gambar'] as $index => $gambar): ?>
                                                <?php
                                                // Handle different image path formats
                                                $imagePath = '';
                                                if (isset($gambar['path_gambar']) && !empty($gambar['path_gambar'])) {
                                                    // New format with full path
                                                    $imagePath = '../../' . $gambar['path_gambar'];
                                                } else {
                                                    // Old format with just filename
                                                    $imagePath = '../../uploads/postingan/' . $gambar['nama_file'];
                                                }
                                                ?>
                                                <div class="relative aspect-square cursor-pointer rounded-lg overflow-hidden border border-gray-200 post-image-container">
                                                    <img src="<?php echo htmlspecialchars($imagePath); ?>"
                                                        alt="Gambar postingan"
                                                        class="w-full h-full object-cover hover:scale-105 transition-transform post-image"
                                                        data-pswp-src="<?php echo htmlspecialchars($imagePath); ?>"
                                                        data-pswp-width="800"
                                                        data-pswp-height="600"
                                                        onerror="this.parentElement.remove();">
                                                    <?php if (count($post['gambar']) > 3 && $index == 2): ?>
                                                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                                            <span class="text-white font-bold text-lg">+<?php echo count($post['gambar']) - 3; ?></span>
                                                        </div>
                                                        <?php break; ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Post Actions -->
                                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                    <div class="flex items-center space-x-4">
                                        <!-- Like Button -->
                                        <button class="like-btn flex items-center space-x-2 text-gray-600 hover:text-red-600 transition-colors"
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
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                        <i class="ti ti-message-off text-4xl text-gray-300 mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada postingan</h3>
                        <p class="text-gray-500">Postingan dari kelas yang Anda ikuti akan muncul di sini</p>
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
    <script src="../script/photoswipe-simple.js"></script>
    <script src="../script/assignment-manager.js"></script>
    <script src="../script/kelas-posting-stable.js?v=<?php echo time(); ?>"></script>
    <script>
        // Initialize like functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize KelasPosting for comments functionality (beranda context)
            window.kelasPosting = new KelasPosting(null, {
                canPost: false,  // No posting in beranda
                canComment: true // Allow commenting
            });

            // Like button functionality
            document.querySelectorAll('.like-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const postId = this.dataset.postId;
                    const isLiked = this.dataset.liked === 'true';

                    try {
                        const response = await fetch('../logic/toggle-like.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                postingan_id: postId
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            const heartIcon = this.querySelector('i');
                            const countSpan = this.querySelector('.like-count');

                            if (result.action === 'liked') {
                                heartIcon.className = 'ti ti-heart-filled text-red-600';
                                this.dataset.liked = 'true';
                                countSpan.textContent = parseInt(countSpan.textContent) + 1;
                            } else {
                                heartIcon.className = 'ti ti-heart';
                                this.dataset.liked = 'false';
                                countSpan.textContent = parseInt(countSpan.textContent) - 1;
                            }
                        }
                    } catch (error) {
                        console.error('Error toggling like:', error);
                    }
                });
            });

            // Comment button functionality - use KelasPosting method
            document.querySelectorAll('.comment-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const postId = this.dataset.postId;
                    if (window.kelasPosting) {
                        window.kelasPosting.openCommentsModal(postId);
                    }
                });
            });

            // Load comments preview for all posts
            setTimeout(() => {
                document.querySelectorAll('[id^="comments-preview-"]').forEach(container => {
                    const postId = container.id.replace('comments-preview-', '');
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