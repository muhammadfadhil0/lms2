<!-- cek sekarang ada di halaman apa -->
<?php
session_start();
$currentPage = 'kelas';

// Check if user is logged in and is a siswa
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'siswa') {
    header("Location: ../../index.php");
    exit();
}

// Include logic files
require_once '../logic/kelas-logic.php';
require_once '../logic/postingan-logic.php';

// Check if kelas ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: beranda-user.php");
    exit();
}

$kelasLogic = new KelasLogic();
$kelas_id = intval($_GET['id']);
$user_id = $_SESSION['user']['id'];

// Get class details
$detailKelas = $kelasLogic->getDetailKelas($kelas_id);

// Check if class exists
if (!$detailKelas) {
    header("Location: beranda-user.php");
    exit();
}

// Get user's classes to check if enrolled
$userClasses = $kelasLogic->getKelasBySiswa($user_id);
$isEnrolled = false;
foreach ($userClasses as $userClass) {
    if ($userClass['id'] == $kelas_id) {
        $isEnrolled = true;
        break;
    }
}

if (!$isEnrolled) {
    header("Location: beranda-user.php");
    exit();
}

// Get class students
$siswaKelas = $kelasLogic->getSiswaKelas($kelas_id);
$jumlahSiswa = count($siswaKelas);

// Get class posts
$postinganLogic = new PostinganLogic();
$statistikPostingan = $postinganLogic->getStatistikPostingan($kelas_id);

// Get recent assignments for this class
$recentAssignments = [];
try {
    require_once '../logic/koneksi.php';
    $stmt = $pdo->prepare("
        SELECT t.*, 
               pt.status as student_status,
               pt.nilai as student_score,
               CASE 
                   WHEN t.deadline < NOW() THEN 'expired'
                   WHEN pt.status = 'dinilai' THEN 'graded'
                   WHEN pt.status = 'dikumpulkan' THEN 'submitted'
                   ELSE 'pending'
               END as submission_status
        FROM tugas t
        LEFT JOIN pengumpulan_tugas pt ON t.id = pt.assignment_id AND pt.siswa_id = ?
        WHERE t.kelas_id = ?
        ORDER BY t.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id, $kelas_id]);
    $recentAssignments = $stmt->fetchAll();
} catch (Exception $e) {
    $recentAssignments = [];
}

// Check class permissions
$canPost = !isset($detailKelas['restrict_posting']) || !$detailKelas['restrict_posting'];
$canComment = !isset($detailKelas['restrict_comments']) || !$detailKelas['restrict_comments'];
?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<?php 
// Include profile photo helper for fresh data
require_once '../logic/profile-photo-helper.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?php echo $_SESSION['user']['id']; ?>">
    <?php require '../../assets/head.php'; ?>
    <link rel="stylesheet" href="../css/kelas-posting.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/image-upload.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/file-upload.css?v=<?php echo time(); ?>">
    <title><?php echo htmlspecialchars($detailKelas['namaKelas']); ?> - Kelas</title>
</head>

<body class="bg-gray-50">
    <!-- Main Content -->
    <div class="md:ml-64 min-h-screen transition-all duration-300 ease-in-out" data-main-content>
        <!-- Breadcrumb -->
        <div class="bg-white border-b border-gray-200 p-4">
            <div class="flex items-center space-x-2 text-sm">
                <a href="beranda-user.php" class="text-orange-600 hover:text-orange-800 flex items-center">
                    <i class="ti ti-arrow-left mr-1"></i>
                    Kembali ke Beranda
                </a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-600"><?php echo htmlspecialchars($detailKelas['namaKelas']); ?></span>
            </div>
        </div>

        <!-- Jumbotron -->
        <div class="relative h-60 lg:h-80 overflow-hidden" style="background: linear-gradient(45deg, #f97316, #ea580c);">
            <?php if (!empty($detailKelas['gambarKover'])): ?>
                <img src="../../<?php echo htmlspecialchars($detailKelas['gambarKover']); ?>"
                    alt="<?php echo htmlspecialchars($detailKelas['namaKelas']); ?>"
                    class="w-full h-full object-cover absolute inset-0"
                    style="z-index: 1;">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-black/20" style="z-index: 2;"></div>
            <?php endif; ?>
            <div class="absolute bottom-4 lg:bottom-6 left-4 lg:left-6 text-white" style="z-index: 3;">
                <h1 class="text-2xl lg:text-4xl font-bold mb-2"><?php echo htmlspecialchars($detailKelas['namaKelas']); ?></h1>
                <div class="flex items-center space-x-3 lg:space-x-4">
                    <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-full overflow-hidden flex items-center justify-center flex-shrink-0">
                        <?php if (isset($detailKelas['fotoProfilGuru']) && !empty($detailKelas['fotoProfilGuru'])): ?>
                            <?php
                            $fotoProfilGuru = $detailKelas['fotoProfilGuru'];
                            // Check if it already contains the full path
                            if (strpos($fotoProfilGuru, 'uploads/profile/') === 0) {
                                $photoPath = '../../' . $fotoProfilGuru;
                            } else {
                                $photoPath = '../../uploads/profile/' . $fotoProfilGuru;
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($photoPath); ?>" 
                                 alt="Foto Guru" 
                                 class="w-full h-full object-cover"
                                 onerror="this.parentElement.innerHTML='<div class=\'w-full h-full bg-orange-600 flex items-center justify-center\'><i class=\'ti ti-user text-white text-lg lg:text-xl\'></i></div>'">
                        <?php else: ?>
                            <div class="w-full h-full bg-orange-600 flex items-center justify-center">
                                <i class="ti ti-user text-white text-lg lg:text-xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-base lg:text-lg font-medium"><?php echo htmlspecialchars($detailKelas['namaGuru']); ?></p>
                        <p class="text-xs lg:text-sm opacity-90">Dosen Pengampu</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="max-w-7xl mx-auto p-4 lg:p-6">
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Left Column - Posts -->
                <div class="flex-1 lg:w-2/3">
                    <!-- Mobile Quick Actions (UI only, no logic yet) -->
                    <div class="md:hidden bg-white rounded-lg p-3 shadow-sm mb-4">
                        <div class="grid grid-cols-4 gap-2">
                            <button type="button" data-action="assignments" aria-controls="assignment-list-modal" class="group flex flex-col items-center justify-center rounded-lg bg-white border border-gray-200 text-[10px] font-medium text-gray-600 shadow-sm active:scale-95 transition hover:border-blue-300 hover:bg-blue-50 h-20">
                                <i class="ti ti-clipboard-list text-blue-600 text-xl mb-1"></i>
                                <span class="leading-tight">Tugas</span>
                            </button>
                            <button type="button" data-action="schedule" class="group flex flex-col items-center justify-center rounded-lg bg-white border border-gray-200 text-[10px] font-medium text-gray-600 shadow-sm active:scale-95 transition hover:border-indigo-300 hover:bg-indigo-50 h-20">
                                <i class="ti ti-calendar text-indigo-600 text-xl mb-1"></i>
                                <span class="leading-tight">Jadwal</span>
                            </button>
                            <button type="button" data-action="materials" class="group flex flex-col items-center justify-center rounded-lg bg-white border border-gray-200 text-[10px] font-medium text-gray-600 shadow-sm active:scale-95 transition hover:border-green-300 hover:bg-green-50 h-20">
                                <i class="ti ti-book text-green-600 text-xl mb-1"></i>
                                <span class="leading-tight">Materi</span>
                            </button>
                            <button type="button" data-action="classmates" class="group flex flex-col items-center justify-center rounded-lg bg-white border border-gray-200 text-[10px] font-medium text-gray-600 shadow-sm active:scale-95 transition hover:border-orange-300 hover:bg-orange-50 h-20">
                                <i class="ti ti-users text-orange text-xl mb-1"></i>
                                <span class="leading-tight">Teman</span>
                            </button>
                        </div>
                    </div>
                    <!-- Create Post (only show if posting is allowed) -->
                    <?php if ($canPost): ?>
                    <div class="bg-white rounded-lg p-4 lg:p-6 shadow-sm mb-6">
                        <form id="postForm" enctype="multipart/form-data">
                            <div class="flex items-start space-x-3 lg:space-x-4">
                                <div class="w-8 h-8 lg:w-10 lg:h-10 rounded-full overflow-hidden flex items-center justify-center flex-shrink-0">
                                    <?php 
                                    // Get fresh photo from database
                                    $currentUserPhotoUrl = getUserProfilePhotoUrl($_SESSION['user']['id']);
                                    if ($currentUserPhotoUrl): ?>
                                        <img src="<?php echo htmlspecialchars($currentUserPhotoUrl); ?>" 
                                             alt="Foto Profil" 
                                             class="w-full h-full object-cover"
                                             onerror="this.parentElement.innerHTML='<div class=\'w-full h-full bg-orange-500 flex items-center justify-center\'><i class=\'ti ti-user text-white\'></i></div>'">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-orange-500 flex items-center justify-center">
                                            <i class="ti ti-user text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <textarea id="postTextarea" name="konten" placeholder="Bagikan sesuatu dengan kelas..."
                                        class="w-full p-3 rounded-lg resize-none focus:ring-2 focus:ring-orange-500 focus:outline-none bg-gray-50"
                                        rows="3" required></textarea>
                                    
                                    <!-- Image Preview Container (will be populated by JavaScript) -->
                                    <div class="image-preview-container hidden">
                                        <div class="image-preview-grid"></div>
                                        <div class="upload-message-container"></div>
                                    </div>
                                    
                                    <!-- File Attachment Preview Container -->
                                    <div class="file-preview-container hidden mt-3">
                                        <div class="file-preview-list space-y-2"></div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-4">
                                        <div class="flex space-x-2 lg:space-x-4">
                                            <div class="image-upload-container">
                                                <input type="file" id="imageInput" name="images[]" multiple accept="image/*" class="image-upload-input">
                                                <label for="imageInput" class="image-upload-label flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base cursor-pointer">
                                                    <i class="ti ti-photo mr-1 lg:mr-2"></i>
                                                    <span class="hidden sm:inline">Foto</span>
                                                </label>
                                            </div>
                                            <div class="file-upload-container">
                                                <input type="file" id="fileInput" name="files[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar" class="file-upload-input">
                                                <label for="fileInput" class="file-upload-label flex items-center text-gray-600 hover:text-purple-600 transition-colors text-sm lg:text-base cursor-pointer">
                                                    <i class="ti ti-file mr-1 lg:mr-2"></i>
                                                    <span class="hidden sm:inline">File</span>
                                                </label>
                                            </div>
                                            <button type="button" class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                                <i class="ti ti-link mr-1 lg:mr-2"></i>
                                                <span class="hidden sm:inline">Link</span>
                                            </button>
                                        </div>
                                        <button type="submit" class="bg-orange text-white px-4 lg:px-6 py-2 rounded-lg hover:bg-orange-600 transition-colors text-sm lg:text-base">
                                            Posting
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <!-- Message when posting is restricted -->
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 lg:p-6 mb-6">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-orange-200 flex items-center justify-center mr-3">
                                <i class="ti ti-info-circle text-orange-600"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-orange-800">Posting Dibatasi</h4>
                                <p class="text-sm text-orange-700 mt-1">Saat ini hanya dosen yang dapat membuat postingan baru di kelas ini.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Posts Feed -->
                    <div id="postsContainer" class="space-y-6">
                        <!-- Initial loading state -->
                        <div class="text-center py-12 text-gray-500">
                            <i class="ti ti-loader animate-spin text-4xl mb-4"></i>
                            <p class="text-lg font-medium">Memuat postingan...</p>
                            <p class="text-sm text-gray-400 mt-1">Mohon tunggu sebentar</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Class Details -->
                <div class="lg:w-1/3 hidden md:block">
                    <div class="sticky top-6">
                        <!-- Recent Assignments -->
                        <div class="bg-white rounded-lg p-4 lg:p-6 shadow-sm mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tugas Terbaru</h3>
                            <div class="space-y-3">
                                <?php if (empty($recentAssignments)): ?>
                                    <div class="text-center py-8">
                                        <i class="ti ti-clipboard-off text-4xl text-gray-300 mb-2"></i>
                                        <p class="text-sm text-gray-500">Tidak ada tugas terbaru</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentAssignments as $assignment): ?>
                                        <?php
                                        $statusClass = '';
                                        $statusIcon = '';
                                        $statusText = '';
                                        
                                        switch ($assignment['submission_status']) {
                                            case 'expired':
                                                $statusClass = 'bg-red-50 border-red-200';
                                                $statusIcon = 'ti ti-exclamation-circle text-red-600';
                                                $statusText = 'Terlewat';
                                                break;
                                            case 'graded':
                                                $statusClass = 'bg-green-50 border-green-200';
                                                $statusIcon = 'ti ti-check text-green-600';
                                                $statusText = 'Dinilai (' . $assignment['student_score'] . '/' . $assignment['nilai_maksimal'] . ')';
                                                break;
                                            case 'submitted':
                                                $statusClass = 'bg-yellow-50 border-yellow-200';
                                                $statusIcon = 'ti ti-clock text-yellow-600';
                                                $statusText = 'Menunggu Penilaian';
                                                break;
                                            default:
                                                $statusClass = 'bg-orange-50 border-orange-200';
                                                $statusIcon = 'ti ti-exclamation-circle text-orange-600';
                                                $statusText = 'Belum Dikumpulkan';
                                        }
                                        
                                        $deadlineFormatted = date('j M Y', strtotime($assignment['deadline']));
                                        $isDeadlineSoon = strtotime($assignment['deadline']) - time() < (24 * 60 * 60 * 3); // 3 days
                                        ?>
                                        <div class="assignment-card p-3 <?php echo $statusClass; ?> border rounded-lg cursor-pointer hover:bg-opacity-80 transition-all duration-200" data-assignment-id="<?php echo $assignment['id']; ?>">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <h4 class="font-medium text-gray-900 text-sm"><?php echo htmlspecialchars($assignment['judul']); ?></h4>
                                                    <p class="text-xs text-gray-600 mt-1">
                                                        Deadline: <?php echo $deadlineFormatted; ?>
                                                        <?php if ($isDeadlineSoon && $assignment['submission_status'] === 'pending'): ?>
                                                            <span class="text-red-600 font-medium">(Segera!)</span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                                <div class="flex flex-col items-end">
                                                    <i class="<?php echo $statusIcon; ?> text-sm mb-1"></i>
                                                    <span class="text-xs font-medium"><?php echo $statusText; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($recentAssignments) > 0): ?>
                                        <div class="text-center mt-3">
                                            <button id="view-all-assignments" class="text-sm text-orange-600 hover:text-orange-800 font-medium transition-colors">
                                                Lihat Semua Tugas
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Class Schedule (conditional display) -->
                        <div id="schedule-section" class="bg-white rounded-lg p-4 lg:p-6 shadow-sm mb-6" style="display: none;">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Jadwal Kelas</h3>
                            <div id="class-schedules" class="space-y-3">
                                <!-- Will be populated by JavaScript -->
                            </div>
                        </div>

                        <!-- Learning Materials (conditional display) -->
                        <div id="materials-section" class="bg-white rounded-lg p-4 lg:p-6 shadow-sm mb-6" style="display: none;">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Materi Pembelajaran</h3>
                            <div id="learning-materials" class="space-y-3">
                                <!-- Will be populated by JavaScript -->
                            </div>
                        </div>

                        <!-- Quick Actions (Desktop, matched with mobile style) -->
                        <div class="bg-white rounded-lg p-4 lg:p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h3>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" data-desktop-action="assignments" aria-controls="assignment-list-modal" class="group flex flex-col items-center justify-center rounded-lg bg-white border border-gray-200 text-xs font-medium text-gray-600 shadow-sm hover:border-blue-300 hover:bg-blue-50 transition h-24">
                                    <i class="ti ti-clipboard-list text-blue-600 text-2xl mb-1"></i>
                                    <span class="leading-tight">Tugas</span>
                                </button>
                                <button type="button" data-desktop-action="schedule" class="hidden group flex flex-col items-center justify-center rounded-lg bg-white border border-gray-200 text-xs font-medium text-gray-600 shadow-sm hover:border-indigo-300 hover:bg-indigo-50 transition h-24">
                                    <i class="ti ti-calendar text-indigo-600 text-2xl mb-1"></i>
                                    <span class="leading-tight">Jadwal</span>
                                </button>
                                <button type="button" data-desktop-action="materials" class="hidden group flex flex-col items-center justify-center rounded-lg bg-white border border-gray-200 text-xs font-medium text-gray-600 shadow-sm hover:border-green-300 hover:bg-green-50 transition h-24">
                                    <i class="ti ti-book text-green-600 text-2xl mb-1"></i>
                                    <span class="leading-tight">Materi</span>
                                </button>
                                <button type="button" data-desktop-action="classmates" class="group flex flex-col items-center justify-center rounded-lg bg-white border border-gray-200 text-xs font-medium text-gray-600 shadow-sm hover:border-orange-300 hover:bg-orange-50 transition h-24">
                                    <i class="ti ti-users text-orange text-2xl mb-1"></i>
                                    <span class="leading-tight">Teman</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modal Components -->
    <?php require '../component/modal-delete-post.php'; ?>
    <?php require '../component/modal-comments.php'; ?>
    <?php require '../component/modal-submit-assignment.php'; ?>
    <?php require '../component/modal-assignment-list.php'; ?>
    <?php require '../component/modal-schedule-list.php'; ?>
    <?php require '../component/modal-material-list.php'; ?>
    <?php require '../component/modal-classmates-list.php'; ?>
    <script src="../script/menu-bar-script.js"></script>
    <script src="../script/image-upload-manager.js"></script>
    <script src="../script/file-upload-manager.js?v=<?php echo time(); ?>"></script>
    <script src="../script/photoswipe-simple.js"></script>
    <script src="../script/assignment-manager.js"></script>
    <script src="../script/assignment-list-modal.js?v=<?php echo time(); ?>"></script>
    <script src="../script/kelas-files-manager.js"></script>
    <script src="../script/list-modals-manager.js?v=<?php echo time(); ?>"></script>
    <script src="../script/kelas-posting-stable.js?v=<?php echo time(); ?>"></script>
    <script src="../script/profile-sync.js"></script>
    <script>
        // Define user role for JavaScript access
        window.currentUserRole = '<?php echo $_SESSION['user']['role']; ?>';
        
        // Initialize posting system when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const kelasId = <?php echo $kelas_id; ?>;
            const permissions = {
                canPost: <?php echo $canPost ? 'true' : 'false'; ?>,
                canComment: <?php echo $canComment ? 'true' : 'false'; ?>
            };
            window.kelasPosting = new KelasPosting(kelasId, permissions);
            window.assignmentManager = new AssignmentManager(kelasId, 'siswa');
            
            // Initialize file manager for students (read-only)
            window.kelasFilesManager = new KelasFilesManager(kelasId, 'siswa');
            
            // Load schedules and materials
            loadClassSchedules();
            loadLearningMaterials();
            
            // Initialize assignment navigator for sidebar clicks
            window.assignmentNavigator = new AssignmentNavigator();
            
            // Initialize assignment list modal
            window.assignmentListModal = new AssignmentListModal(kelasId);
            
            // Add event listener for "View All Assignments" button
            const viewAllBtn = document.getElementById('view-all-assignments');
            if (viewAllBtn) {
                viewAllBtn.addEventListener('click', () => {
                    window.assignmentListModal.open();
                });
            }

            // Mobile quick action: open assignment list modal
            const mobileAssignmentsBtn = document.querySelector('button[data-action="assignments"]');
            if (mobileAssignmentsBtn) {
                mobileAssignmentsBtn.addEventListener('click', () => {
                    if (window.assignmentListModal && typeof window.assignmentListModal.open === 'function') {
                        window.assignmentListModal.open();
                    } else {
                        console.warn('AssignmentListModal belum siap.');
                    }
                });
            }

            // Init list modals manager
            window.listModalsManager = new ListModalsManager(kelasId);

            // Mobile quick actions using manager
            const mobileScheduleBtn = document.querySelector('button[data-action="schedule"]');
            mobileScheduleBtn?.addEventListener('click', () => window.listModalsManager.open('schedule'));
            const mobileMaterialBtn = document.querySelector('button[data-action="materials"]');
            mobileMaterialBtn?.addEventListener('click', () => window.listModalsManager.open('material'));
            const mobileClassmatesBtn = document.querySelector('button[data-action="classmates"]');
            mobileClassmatesBtn?.addEventListener('click', () => window.listModalsManager.open('classmates'));
            
            // Debug info
            console.log('🎓 LMS Assignment Navigation initialized');
            console.log('📋 Class ID:', kelasId);
            console.log('👤 User role: siswa');
            console.log('🔧 Available global objects:', {
                kelasPosting: !!window.kelasPosting,
                assignmentManager: !!window.assignmentManager,
                assignmentNavigator: !!window.assignmentNavigator,
                assignmentListModal: !!window.assignmentListModal,
                kelasFilesManager: !!window.kelasFilesManager
            });

            // Desktop quick actions (same behavior as mobile)
            document.querySelectorAll('[data-desktop-action]')?.forEach(btn => {
                btn.addEventListener('click', () => {
                    const action = btn.getAttribute('data-desktop-action');
                    switch(action){
                        case 'assignments':
                            window.assignmentListModal?.open();
                            break;
                        case 'schedule':
                            window.listModalsManager?.open('schedule');
                            break;
                        case 'materials':
                            window.listModalsManager?.open('material');
                            break;
                        case 'classmates':
                            window.listModalsManager?.open('classmates');
                            break;
                    }
                });
            });
        });

    // Legacy simple dialog helpers removed (handled by ListModalsManager)

        // Function to load class schedules
        async function loadClassSchedules() {
            try {
                const response = await fetch(`../logic/get-kelas-files.php?kelas_id=${<?php echo $kelas_id; ?>}&file_type=schedule`);
                const data = await response.json();
                
                // Handle both array response and object with files property
                const schedules = Array.isArray(data) ? data : (data.files || []);
                
                if (data.error) {
                    console.error('API Error loading schedules:', data.error);
                }
                
                const container = document.getElementById('class-schedules');
                const section = document.getElementById('schedule-section');
                if (!container || !section) return;
                
                if (schedules.length === 0) {
                    section.style.display = 'none';
                    return;
                }
                
                section.style.display = 'block';
                container.innerHTML = schedules.map(schedule => renderFileItem(schedule, 'blue')).join('');
            } catch (error) {
                console.error('Error loading schedules:', error);
                const section = document.getElementById('schedule-section');
                if (section) section.style.display = 'none';
            }
        }

        // Function to load learning materials
        async function loadLearningMaterials() {
            try {
                const response = await fetch(`../logic/get-kelas-files.php?kelas_id=${<?php echo $kelas_id; ?>}&file_type=material`);
                const data = await response.json();
                
                // Handle both array response and object with files property
                const materials = Array.isArray(data) ? data : (data.files || []);
                
                if (data.error) {
                    console.error('API Error loading materials:', data.error);
                }
                
                const container = document.getElementById('learning-materials');
                const section = document.getElementById('materials-section');
                if (!container || !section) return;
                
                if (materials.length === 0) {
                    section.style.display = 'none';
                    return;
                }
                
                section.style.display = 'block';
                container.innerHTML = materials.map(material => renderFileItem(material, 'green')).join('');
            } catch (error) {
                console.error('Error loading materials:', error);
                const section = document.getElementById('materials-section');
                if (section) section.style.display = 'none';
            }
        }

        // Function to render file item for students
        function renderFileItem(file, colorTheme) {
            const fileSize = formatFileSize(file.file_size);
            const uploadDate = new Date(file.created_at).toLocaleDateString('id-ID');
            const fileIcon = getFileIcon(file.file_extension);
            
            return `
                <div class="flex items-center p-3 bg-${colorTheme}-50 rounded-lg hover:bg-${colorTheme}-100 transition-colors">
                    <div class="w-10 h-10 bg-${colorTheme}-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="${fileIcon} text-${colorTheme}-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h5 class="text-sm font-medium text-gray-900 truncate">${file.title}</h5>
                        <p class="text-xs text-gray-500">${fileSize} • ${uploadDate}</p>
                    </div>
                    <div class="flex items-center ml-3">
                        <button onclick="downloadFile(${file.id})" class="text-${colorTheme}-600 hover:text-${colorTheme}-800 p-2 rounded-lg hover:bg-${colorTheme}-200 transition-colors" title="Download">
                            <i class="ti ti-download text-sm"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        // Helper functions
        function getFileIcon(extension) {
            const iconMap = {
                'pdf': 'ti ti-file-type-pdf',
                'doc': 'ti ti-file-type-doc',
                'docx': 'ti ti-file-type-docx',
                'ppt': 'ti ti-presentation',
                'pptx': 'ti ti-presentation',
                'jpg': 'ti ti-photo',
                'jpeg': 'ti ti-photo',
                'png': 'ti ti-photo'
            };
            return iconMap[extension.toLowerCase()] || 'ti ti-file';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>

</html>