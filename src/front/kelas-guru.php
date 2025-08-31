<!-- cek sekarang ada di halaman apa -->
<?php
session_start();
$currentPage = 'kelas';

// Check if user is logged in and is a guru
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    header("Location: ../../index.php");
    exit();
}

// Include logic files
require_once '../logic/kelas-logic.php';
require_once '../logic/postingan-logic.php';

// Check if kelas ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: beranda-guru.php");
    exit();
}

$kelasLogic = new KelasLogic();
$kelas_id = intval($_GET['id']);
$guru_id = $_SESSION['user']['id'];

// Get class details
$detailKelas = $kelasLogic->getDetailKelas($kelas_id);

// Check if class exists and belongs to this guru
if (!$detailKelas || $detailKelas['guru_id'] != $guru_id) {
    header("Location: beranda-guru.php");
    exit();
}

// Get class students
$siswaKelas = $kelasLogic->getSiswaKelas($kelas_id);
$jumlahSiswa = count($siswaKelas);

// Get class posts
$postinganLogic = new PostinganLogic();
$statistikPostingan = $postinganLogic->getStatistikPostingan($kelas_id);
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
    <link rel="stylesheet" href="../css/kelas-posting.css">
    <link rel="stylesheet" href="../css/class-settings.css">
    <link rel="stylesheet" href="../css/image-upload.css">
    <title><?php echo htmlspecialchars($detailKelas['namaKelas']); ?> - Kelola Kelas</title>
    <style>
        /* Hide scrollbar for horizontal quick actions on mobile */
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Main Content -->
    <div class="md:ml-64 min-h-screen transition-all duration-300 ease-in-out" data-main-content>
        <!-- Breadcrumb -->
        <div class="bg-white border-b border-gray-200 p-4">
            <div class="flex items-center space-x-2 text-sm">
                <a href="beranda-guru.php" class="text-orange-600 hover:text-orange-800 flex items-center">
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
                <div class="flex items-center space-x-3 lg:space-x-4 mb-3">
                    <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-full bg-orange-600 flex items-center justify-center">
                        <i class="ti ti-user text-white text-lg lg:text-xl"></i>
                    </div>
                    <div>
                        <p class="text-base lg:text-lg font-medium"><?php echo htmlspecialchars($detailKelas['namaGuru']); ?></p>
                        <p class="text-xs lg:text-sm opacity-90">Guru Pengampu</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4 text-sm lg:text-base">
                    <span class="bg-orange-600 bg-opacity-80 px-3 py-1 rounded-full">
                        <?php echo htmlspecialchars($detailKelas['mataPelajaran']); ?>
                    </span>
                    <span class="flex items-center">
                        <i class="ti ti-users mr-1"></i>
                        <?php echo $jumlahSiswa; ?> Siswa
                    </span>
                    <span class="flex items-center">
                        <i class="ti ti-key mr-1"></i>
                        <?php echo htmlspecialchars($detailKelas['kodeKelas']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="max-w-7xl mx-auto p-4 lg:p-6">
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Left Column - Posts -->
                <div class="flex-1 lg:w-2/3">
                    <!-- Mobile Quick Actions Separate Container -->
                    <div class="md:hidden bg-white rounded-lg p-3 shadow-sm mb-4">
                        <div class="grid grid-cols-4 gap-2">
                            <button type="button" onclick="openClassSettings()" class="group flex flex-col items-center justify-center rounded-lg bg-white border border-gray-200 text-[10px] font-medium text-gray-600 shadow-sm active:scale-95 transition hover:border-orange-300 hover:bg-orange-50 h-20">
                                <i class="ti ti-settings text-orange text-xl mb-1"></i>
                                <span class="leading-tight">Setting</span>
                            </button>
                            <button type="button" onclick="openCreateAssignmentModal()" class="group flex flex-col items-center justify-center rounded-lg bg-white border border-gray-200 text-[10px] font-medium text-gray-600 shadow-sm active:scale-95 transition hover:border-orange-300 hover:bg-orange-50 h-20">
                                <i class="ti ti-file-plus text-orange text-xl mb-1"></i>
                                <span class="leading-tight">Tugas</span>
                            </button>
                            <button type="button" onclick="openScheduleModal()" class="group flex flex-col items-center justify-center rounded-lg bg-white border border-gray-200 text-[10px] font-medium text-gray-600 shadow-sm active:scale-95 transition hover:border-blue-300 hover:bg-blue-50 h-20">
                                <i class="ti ti-calendar-plus text-blue-600 text-xl mb-1"></i>
                                <span class="leading-tight">Jadwal</span>
                            </button>
                            <button type="button" onclick="openMaterialModal()" class="group flex flex-col items-center justify-center rounded-lg bg-white border border-gray-200 text-[10px] font-medium text-gray-600 shadow-sm active:scale-95 transition hover:border-green-300 hover:bg-green-50 h-20">
                                <i class="ti ti-upload text-green-600 text-xl mb-1"></i>
                                <span class="leading-tight">Materi</span>
                            </button>
                        </div>
                    </div>
                    <!-- Create Post -->
                    <div class="bg-white rounded-lg p-4 lg:p-6 shadow-sm mb-6">
                        <form id="postForm" enctype="multipart/form-data">
                            <div class="flex items-start space-x-3 lg:space-x-4">
                                <div class="w-8 h-8 lg:w-10 lg:h-10 rounded-full bg-orange-500 flex items-center justify-center">
                                    <i class="ti ti-user text-white"></i>
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
                                    
                                    <div class="flex items-center justify-between mt-4">
                                        <div class="flex space-x-2 lg:space-x-4">
                                            <div class="image-upload-container">
                                                <input type="file" id="imageInput" name="images[]" multiple accept="image/*" class="image-upload-input">
                                                <label for="imageInput" class="image-upload-label flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base cursor-pointer">
                                                    <i class="ti ti-photo mr-1 lg:mr-2"></i>
                                                    <span class="hidden sm:inline">Foto</span>
                                                </label>
                                            </div>
                                            <button type="button" onclick="openCreateAssignmentModal()" class="flex items-center text-gray-600 hover:text-blue-600 transition-colors text-sm lg:text-base">
                                                <i class="ti ti-clipboard-list mr-1 lg:mr-2"></i>
                                                <span class="hidden sm:inline">Tugas</span>
                                            </button>
                                            <button type="button" class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                                <i class="ti ti-file mr-1 lg:mr-2"></i>
                                                <span class="hidden sm:inline">File</span>
                                            </button>
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
                <div class="lg:w-1/3">
                    <div class="sticky top-6">


                        <!-- Quick Actions -->
                        <div class="bg-white rounded-lg p-4 lg:p-6 shadow-sm mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h3>
                            <div class="space-y-2">
                                <button onclick="openClassSettings()" class="w-full flex items-center p-3 text-left hover:bg-orange-50 rounded-lg transition-colors border border-transparent hover:border-orange-200">
                                    <i class="ti ti-settings mr-3 text-orange"></i>
                                    <span class="text-sm text-gray-700 font-medium">Pengaturan Kelas</span>
                                </button>
                                <button onclick="openAssignmentReports()" class="w-full flex items-center p-3 text-left hover:bg-blue-50 rounded-lg transition-colors border border-transparent hover:border-blue-200">
                                    <i class="ti ti-clipboard-check mr-3 text-blue-600"></i>
                                    <span class="text-sm text-gray-700 font-medium">Laporan Tugas Siswa</span>
                                </button>
                                <button class="w-full flex items-center p-3 text-left hover:bg-orange-50 rounded-lg transition-colors border border-transparent hover:border-orange-200">
                                    <i class="ti ti-file-plus mr-3 text-orange"></i>
                                    <span class="text-sm text-gray-700 font-medium">Buat Tugas</span>
                                </button>
                                <button onclick="openScheduleModal()" class="w-full flex items-center p-3 text-left hover:bg-blue-50 rounded-lg transition-colors border border-transparent hover:border-blue-200">
                                    <i class="ti ti-calendar-plus mr-3 text-blue-600"></i>
                                    <span class="text-sm text-gray-700 font-medium">Upload Jadwal Kelas</span>
                                </button>
                                <button onclick="openMaterialModal()" class="w-full flex items-center p-3 text-left hover:bg-green-50 rounded-lg transition-colors border border-transparent hover:border-green-200">
                                    <i class="ti ti-upload mr-3 text-green-600"></i>
                                    <span class="text-sm text-gray-700 font-medium">Upload Materi Pelajaran</span>
                                </button>
                            </div>
                        </div>


                        <!-- Class Stats -->
                        <div class="bg-white rounded-lg p-4 lg:p-6 shadow-sm mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Detail Kelas</h3>
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-orange-tipis rounded-lg flex items-center justify-center mr-3">
                                        <i class="ti ti-users text-orange"></i>
                                    </div>
                                    <div>
                                        <p class="text-xl font-bold text-gray-900"><?php echo $jumlahSiswa; ?></p>
                                        <p class="text-sm text-gray-600">Mahasiswa</p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-orange-tipis rounded-lg flex items-center justify-center mr-3">
                                        <i class="ti ti-book text-orange"></i>
                                    </div>
                                    <div>
                                        <p class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($detailKelas['mataPelajaran']); ?></p>
                                        <p class="text-sm text-gray-600">Mata Pelajaran</p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-orange-tipis rounded-lg flex items-center justify-center mr-3">
                                        <i class="ti ti-key text-orange"></i>
                                    </div>
                                    <div>
                                        <p class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($detailKelas['kodeKelas']); ?></p>
                                        <p class="text-sm text-gray-600">Kode Kelas</p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-orange-tipis rounded-lg flex items-center justify-center mr-3">
                                        <i class="ti ti-message-circle text-orange"></i>
                                    </div>
                                    <div>
                                        <p class="text-xl font-bold text-gray-900"><?php echo $statistikPostingan['totalPostingan'] ?? 0; ?></p>
                                        <p class="text-sm text-gray-600">Postingan</p>
                                    </div>
                                </div>
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
    <?php require '../component/modal-edit-post.php'; ?>
    <?php require '../component/modal-class-settings.php'; ?>
    <?php require '../component/modal-class-background.php'; ?>
    <?php require '../component/modal-edit-class.php'; ?>
    <?php require '../component/modal-manage-students.php'; ?>
    <?php require '../component/modal-class-permissions.php'; ?>
    <?php require '../component/modal-create-assignment.php'; ?>
    <?php require '../component/modal-upload-schedule.php'; ?>
    <?php require '../component/modal-upload-material.php'; ?>
    <?php require '../component/modal-schedule-list.php'; ?>
    <?php require '../component/modal-material-list.php'; ?>
    <?php require '../component/modal-classmates-list.php'; ?>

    <script src="../script/menu-bar-script.js"></script>
    <script src="../script/class-settings-manager.js"></script>
    <script src="../script/image-upload-manager.js"></script>
    <script src="../script/photoswipe-simple.js"></script>
    <script src="../script/edit-post-modal.js"></script>
    <script src="../script/assignment-manager.js"></script>
    <script src="../script/kelas-files-manager.js"></script>
    <script src="../script/list-modals-manager.js?v=<?php echo time(); ?>"></script>
    <script src="../script/kelas-posting-stable.js?v=<?php echo time(); ?>"></script>
    <script>
        // Initialize global variables
        window.currentUserId = <?php echo $_SESSION['user']['id']; ?>;
        window.currentUserRole = '<?php echo $_SESSION['user']['role']; ?>';
        
        // Initialize posting system when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const kelasId = <?php echo $kelas_id; ?>;
            const permissions = {
                canPost: true,
                canComment: true
            };
            window.kelasPosting = new KelasPosting(kelasId, permissions);
            window.classSettings = new ClassSettingsManager(kelasId);
            window.assignmentManager = new AssignmentManager(kelasId, '<?php echo $_SESSION['user']['role']; ?>');
            window.kelasFilesManager = new KelasFilesManager(kelasId, '<?php echo $_SESSION['user']['role']; ?>');

            // Initialize list modals manager
            window.listModalsManager = new ListModalsManager(kelasId);

            // Global helpers using manager
            window.openScheduleListModal = function() { window.listModalsManager.open('schedule'); };
            window.openMaterialListModal = function() { window.listModalsManager.open('material'); };
            window.openClassmatesListModal = function() { window.listModalsManager.open('classmates'); };
        });
    </script>
</body>

</html>