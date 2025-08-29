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

// Check class permissions
$canPost = !isset($detailKelas['restrict_posting']) || !$detailKelas['restrict_posting'];
$canComment = !isset($detailKelas['restrict_comments']) || !$detailKelas['restrict_comments'];
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
    <link rel="stylesheet" href="../css/image-upload.css">
    <link rel="stylesheet" href="../css/photoswipe-custom.css">
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
                    <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-full bg-orange-600 flex items-center justify-center">
                        <i class="ti ti-user text-white text-lg lg:text-xl"></i>
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
                    <!-- Create Post (only show if posting is allowed) -->
                    <?php if ($canPost): ?>
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
                <div class="lg:w-1/3">
                    <div class="sticky top-6">
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
                                        <i class="ti ti-message-circle text-orange"></i>
                                    </div>
                                    <div>
                                        <p class="text-xl font-bold text-gray-900"><?php echo $statistikPostingan['totalPostingan'] ?? 0; ?></p>
                                        <p class="text-sm text-gray-600">Postingan</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Class Stats -->

                        <!-- Recent Assignments -->
                        <div class="bg-white rounded-lg p-4 lg:p-6 shadow-sm mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tugas Terbaru</h3>
                            <div class="space-y-3">
                                <div class="p-3 bg-orange-tipis rounded-lg">
                                    <h4 class="font-medium text-gray-900 text-sm">Tugas UTS</h4>
                                    <p class="text-xs text-gray-600">Deadline: 23 Nov 2024</p>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <h4 class="font-medium text-gray-900 text-sm">Project Website</h4>
                                    <p class="text-xs text-gray-600">Deadline: 30 Nov 2024</p>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <h4 class="font-medium text-gray-900 text-sm">Quiz JavaScript</h4>
                                    <p class="text-xs text-gray-600">Deadline: 5 Des 2024</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-white rounded-lg p-4 lg:p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h3>
                            <div class="space-y-2">
                                <button class="w-full flex items-center p-3 text-left hover:bg-gray-50 rounded-lg transition-colors">
                                    <i class="ti ti-users mr-3 text-gray-600"></i>
                                    <span class="text-sm text-gray-700">Lihat Teman Sekelas</span>
                                </button>
                                <button class="w-full flex items-center p-3 text-left hover:bg-gray-50 rounded-lg transition-colors">
                                    <i class="ti ti-calendar mr-3 text-gray-600"></i>
                                    <span class="text-sm text-gray-700">Jadwal Kelas</span>
                                </button>
                                <button class="w-full flex items-center p-3 text-left hover:bg-gray-50 rounded-lg transition-colors">
                                    <i class="ti ti-book mr-3 text-gray-600"></i>
                                    <span class="text-sm text-gray-700">Materi Pembelajaran</span>
                                </button>
                                <button class="w-full flex items-center p-3 text-left hover:bg-gray-50 rounded-lg transition-colors">
                                    <i class="ti ti-star mr-3 text-gray-600"></i>
                                    <span class="text-sm text-gray-700">Nilai & Rapor</span>
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
    <?php require '../component/modal-image-viewer.php'; ?>
    <?php require '../component/photoswipe-modal.php'; ?>

    <script src="../script/menu-bar-script.js"></script>
    <script src="../script/image-upload-manager.js"></script>
    <script src="../script/photoswipe-viewer.js"></script>
    <script src="../script/kelas-posting-stable.js"></script>
    <script>
        // Initialize posting system when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const kelasId = <?php echo $kelas_id; ?>;
            const permissions = {
                canPost: <?php echo $canPost ? 'true' : 'false'; ?>,
                canComment: <?php echo $canComment ? 'true' : 'false'; ?>
            };
            window.kelasPosting = new KelasPosting(kelasId, permissions);
        });
    </script>
</body>

</html>