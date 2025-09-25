<!-- cek sekarang ada di halaman apa -->
<?php
session_start();

// Prevent caching to ensure fresh data
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");

$currentPage = 'ujian';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'siswa') {
    header('Location: ../../login.php');
    exit();
}

require_once '../logic/ujian-logic.php';
require_once '../logic/soal-logic.php';
require_once '../logic/time-helper.php';
$ujianLogic = new UjianLogic();
$soalLogic = new SoalLogic();
$siswa_id = $_SESSION['user']['id'];

// Mulai ujian handler (POST sederhana)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'mulai' && isset($_POST['ujian_id'])) {
    $mulai = $ujianLogic->mulaiUjian((int) $_POST['ujian_id'], $siswa_id);
    if ($mulai['success']) {
        header('Location: kerjakan-ujian.php?us_id=' . $mulai['ujian_siswa_id']);
        exit();
    } else {
        $errorMulai = $mulai['message'];
    }
}

// Check for error parameters from redirects
$showModalError = false;
$showSuccessMessage = false;
$showReviewNotAllowed = false;
if (isset($_GET['error']) && $_GET['error'] === 'ujian_sudah_selesai') {
    $showModalError = true;
}
if (isset($_GET['error']) && $_GET['error'] === 'review_not_allowed') {
    $showReviewNotAllowed = true;
}
if (isset($_GET['finished']) && $_GET['finished'] === '1') {
    $showSuccessMessage = true;
    // Force refresh data untuk memastikan status ter-update
    $forceRefresh = true;
} else {
    $forceRefresh = false;
}

$ujianList = $ujianLogic->getUjianBySiswa($siswa_id, $forceRefresh);

// Debug helper: show session and query results when ?debug=1
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    error_log('DEBUG ujian-user SESSION: ' . json_encode($_SESSION['user'] ?? []));
    error_log('DEBUG ujianList (siswa): ' . json_encode($ujianList));
    echo '<div style="padding:12px;background:#fff;border:1px solid #eee;margin:12px;">';
    echo '<h4>DEBUG: Session User</h4>';
    echo '<pre>' . htmlspecialchars(print_r($_SESSION['user'] ?? [], true)) . '</pre>';
    echo '<h4>DEBUG: ujianList</h4>';
    echo '<pre>' . htmlspecialchars(print_r($ujianList, true)) . '</pre>';

    // Additionally show kelas_siswa rows for classes referenced in ujianList
    $kelasIds = array_unique(array_filter(array_map(function($u){ return $u['kelas_id'] ?? null; }, $ujianList)));
    if (!empty($kelasIds)) {
        $ids = implode(',', array_map('intval', $kelasIds));
        $conn = getConnection();
        $res = $conn->query("SELECT * FROM kelas_siswa WHERE kelas_id IN ($ids) LIMIT 100");
        $rows = [];
        if ($res) {
            while($r = $res->fetch_assoc()) $rows[] = $r;
        }
        echo '<h4>DEBUG: kelas_siswa rows for related kelas</h4>';
        echo '<pre>' . htmlspecialchars(print_r($rows, true)) . '</pre>';
        error_log('DEBUG kelas_siswa rows: ' . json_encode($rows));
    }
    echo '</div>';
}

// Debug: Tampilkan status untuk debugging (hapus ini setelah fix)
if (isset($_GET['debug'])) {
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
    echo "<h3>DEBUG INFO:</h3>";
    foreach ($ujianList as $u) {
        echo "<p>Ujian: " . htmlspecialchars($u['namaUjian']) . " | Status DB: " . htmlspecialchars($u['statusPengerjaan'] ?? 'NULL') . " | Status Calculated: " . htmlspecialchars($u['status_ujian']) . " | US_ID: " . htmlspecialchars($u['ujian_siswa_id'] ?? 'NULL') . "</p>";
    }
    echo "</div>";
}

function statusBadge($status)
{
    switch ($status) {
        case 'selesai':
            return 'bg-blue-100 text-blue-700';
        case 'sedang_mengerjakan':
            return 'bg-yellow-100 text-yellow-700';
        case 'dapat_dikerjakan':
            return 'bg-green-100 text-green-700';
        case 'belum_dimulai':
            return 'bg-purple-100 text-purple-700';
        case 'terlambat':
            return 'bg-red-100 text-red-700';
        case 'waktu_habis':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}

function statusText($status)
{
    switch ($status) {
        case 'selesai':
            return 'SELESAI';
        case 'sedang_mengerjakan':
            return 'MENGERJAKAN';
        case 'dapat_dikerjakan':
            return 'TERSEDIA';
        case 'belum_dimulai':
            return 'BELUM MULAI';
        case 'terlambat':
            return 'TERLAMBAT';
        case 'waktu_habis':
            return 'WAKTU HABIS';
        default:
            return strtoupper($status);
    }
}
?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<?php // Profile photo helper for fresh avatar URL 
?>
<?php require_once '../logic/profile-photo-helper.php'; ?>
<?php require '../component/modal-ujian-selesai.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Ujian</title>
    <!-- Search system styles -->
    <link rel="stylesheet" href="../css/search-system.css">
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
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Ujian</h1>
                    <p class="text-gray-600 hidden md:block">Daftar ujian di kelas yang kamu ikuti</p>
                </div>
                <div class="flex items-center space-x-2 md:space-x-4" style="align-items: center;">
                    <div class="search-other-buttons flex items-center space-x-2 md:space-x-4">
                        <button class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="ti ti-bell text-lg md:text-xl"></i>
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

            <!-- Ujian Siswa -->
            <div class="mb-6 relative min-h-[40vh] md:min-h-[50vh]">
                <?php if (isset($errorMulai)): ?>
                    <div class="mb-4 p-3 border border-red-200 bg-red-50 text-sm text-red-600 rounded">
                        <?= htmlspecialchars($errorMulai) ?></div>
                <?php endif; ?>
                <?php if ($showSuccessMessage): ?>
                    <div class="mb-4 p-3 border border-green-200 bg-green-50 text-sm text-green-600 rounded">
                        <i class="ti ti-circle-check mr-2"></i>Ujian berhasil diselesaikan! Status telah diperbarui.
                    </div>
                <?php endif; ?>
                <?php if (empty($ujianList)): ?>
                    <div class="absolute inset-0 flex items-center justify-center text-center opacity-75">
                        <div>
                            <i class="ti ti-pencil-question text-6xl text-gray-400 mb-4"></i>
                            <h3 class="text-xl font-medium text-gray-700 mb-2">Belum ada Ujian</h3>
                            <p class="text-gray-500">Ujian yang Anda ikuti akan muncul di sini</p>
                        </div>
                    </div>

                <?php else: ?>
                    <div id="ujianGrid" class="search-results-container grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                        <?php foreach ($ujianList as $u):
                            $nama = htmlspecialchars($u['namaUjian']);
                            $kelas = htmlspecialchars($u['namaKelas']);
                            $tanggal = htmlspecialchars(date('d M Y', strtotime($u['tanggalUjian'])));
                            
                            // Gunakan waktu dari tabel ujian (jadwal ujian), bukan dari ujian_siswa - Format 24 jam
                            $jam = htmlspecialchars(TimeHelper::formatTimeRange($u['waktuMulai'], $u['waktuSelesai']));
                            
                            $durasi = (int) $u['durasi'];
                            $statusP = $u['status_ujian'];
                            $badge = statusBadge($statusP);
                            
                            // Format nilai: (nilai siswa)/(total nilai soal) jika ada
                            $nilaiSiswa = $u['totalNilai'] ?? null;
                            $totalSoal = $u['totalBobot'] ?? $u['jumlahSoal'] ?? null;
                            $displayNilai = null;
                            if ($nilaiSiswa !== null) {
                                if ($totalSoal !== null) {
                                    $displayNilai = $nilaiSiswa . '/' . $totalSoal;
                                } else {
                                    $displayNilai = $nilaiSiswa;
                                }
                            }
                            
                            $cover = !empty($u['gambar_kelas']) ? '../../' . htmlspecialchars($u['gambar_kelas']) : '';
                            ?>
                            <div id="ujian-<?= $u['id'] ?>"
                                class="search-card bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-all group relative"
                                data-class-id="<?= $u['id'] ?>"
                                data-nama-ujian="<?= htmlspecialchars($u['namaUjian']) ?>"
                                data-nama-kelas="<?= htmlspecialchars($u['namaKelas']) ?>"
                                data-mata-pelajaran="<?= htmlspecialchars($u['mataPelajaran'] ?? '') ?>"
                                data-deskripsi="<?= htmlspecialchars($u['deskripsi'] ?? '') ?>"
                                data-topik="<?= htmlspecialchars($u['topik'] ?? '') ?>">
                                <div class="h-32 sm:h-40 md:h-48 bg-gradient-to-br from-indigo-400 to-indigo-600 relative">
                                    <?php if ($cover): ?>
                                        <img src="<?= $cover ?>" alt="<?= $nama ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                                            <i class="ti ti-clipboard-check text-white text-4xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span
                                        class="absolute top-2 md:top-3 right-2 md:right-3 text-[10px] px-2 py-1 rounded-full <?= $badge ?> font-medium uppercase tracking-wide bg-white/80 shadow-sm">
                                        <?= htmlspecialchars(statusText($statusP)) ?>
                                    </span>
                                </div>
                                <!-- Teacher avatar positioned at left on the cover/card boundary (moved outside cover) -->
                                <?php
                                $ownerId = isset($u['guru_id']) ? $u['guru_id'] : null;
                                $ownerPhoto = $ownerId ? getUserProfilePhotoUrl($ownerId) : null;
                                ?>
                                <div class="relative -mt-8 z-20 ml-4 md:ml-6">
                                    <?php if ($ownerPhoto): ?>
                                        <img src="<?= htmlspecialchars($ownerPhoto) ?>" alt="Foto Guru"
                                            class="w-16 h-16 md:w-20 md:h-20 rounded-full border-4 border-white object-cover shadow-md"
                                            onerror="this.parentElement.innerHTML='<div class=\'w-16 h-16 md:w-20 md:h-20 rounded-full border-4 border-white bg-orange-600 flex items-center justify-center\'><i class=\'ti ti-user text-white text-xl\'></i></div>'">
                                    <?php else: ?>
                                        <div
                                            class="w-16 h-16 md:w-20 md:h-20 rounded-full border-4 border-white bg-orange-600 flex items-center justify-center shadow-md">
                                            <i class="ti ti-user text-white text-xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-4 md:p-5 space-y-3">
                                    <h3
                                        class="font-semibold leading-snug drop-shadow line-clamp-2 pb-0 mb-0 group-hover:line-clamp-none transition-all">
                                        <?= $nama ?></h3>
                                    <p class="text-sm text-gray-600"><?= $kelas ?></p>

                                    <div class="grid grid-cols-2 gap-2 text-[12px] text-gray-600">
                                        <div class="flex items-center"><i class="ti ti-calendar mr-1"></i><?= $tanggal ?></div>
                                        <div class="flex items-center justify-end"><i class="ti ti-clock mr-1"></i><?= $jam ?></div>
                                        <div class="flex items-center"><i class="ti ti-hourglass mr-1"></i><?= $durasi ?> mnt</div>
                                        <div class="flex items-center justify-end"><i class="ti ti-trophy mr-1"></i><?= $displayNilai !== null ? htmlspecialchars($displayNilai) : 'Tidak ada data' ?></div>
                                    </div>
                                    <div class="pt-1">
                                        <?php if ($statusP === 'belum_dikerjakan' || $statusP === 'dapat_dikerjakan'): ?>
                                            <form method="post" class="flex">
                                                <input type="hidden" name="ujian_id" value="<?= (int) $u['id'] ?>">
                                                <input type="hidden" name="aksi" value="mulai">
                                                <button
                                                    class="flex-1 bg-green-600 text-white rounded-lg px-3 py-2 text-xs hover:bg-green-700 transition">
                                                    <?= $statusP === 'dapat_dikerjakan' ? 'Mulai Ujian' : 'Mulai' ?>
                                                </button>
                                            </form>
                                        <?php elseif ($statusP === 'sedang_mengerjakan'): ?>
                                            <div class="flex">
                                                <a href="kerjakan-ujian.php?us_id=<?= (int) $u['ujian_siswa_id'] ?>"
                                                    class="flex-1 bg-yellow-500 text-white rounded-lg px-3 py-3 text-xs text-center hover:bg-yellow-600 transition">Lanjutkan</a>
                                            </div>
                                        <?php elseif ($statusP === 'selesai'): ?>
                                            <div class="flex gap-2">
                                                <button onclick="showModalUjianSelesai()"
                                                    class="flex-1 bg-gray-400 text-white rounded-lg px-3 py-3 text-xs cursor-not-allowed">
                                                    Ujian Selesai
                                                </button>
                                                <a href="review-ujian.php?ujian_id=<?= (int) $u['id'] ?>"
                                                    class="flex-1 bg-orange text-white rounded-lg px-3 py-3 text-xs text-center hover:bg-orange-600 transition">
                                                    Lihat Nilai
                                                </a>
                                            </div>
                                        <?php elseif ($statusP === 'belum_dimulai'): ?>
                                            <div class="flex">
                                                <button disabled
                                                    class="flex-1 bg-gray-400 text-white rounded-lg px-3 py-2 text-xs cursor-not-allowed">
                                                    Belum Dimulai
                                                </button>
                                            </div>
                                        <?php elseif ($statusP === 'terlambat' || $statusP === 'waktu_habis'): ?>
                                            <div class="flex">
                                                <button disabled
                                                    class="flex-1 bg-red-400 text-white rounded-lg px-3 py-2 text-xs cursor-not-allowed">
                                                    Waktu Habis
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 space-y-3 z-[10000]"></div>

    <script src="../script/menu-bar-script.js"></script>
    <script src="../script/notification-highlight.js?v=<?php echo time(); ?>"></script>
    
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
            apiEndpoint: '../logic/search-ujian-siswa-api.php',
            searchFields: ['namaUjian', 'deskripsi', 'mataPelajaran', 'namaKelas', 'topik'],
            debounceDelay: 800,
            minSearchLength: 1
        };
    </script>
    <script src="../script/search-system.js"></script>

    <script>
        // Toast notification system
        function showToast(message, type = 'info', duration = 5000) {
            const colors = {
                success: 'bg-green-600',
                error: 'bg-red-600',
                info: 'bg-blue-600',
                warning: 'bg-yellow-600 text-gray-900'
            };
            const container = document.getElementById('toast-container');
            if (!container) return alert(message);

            const el = document.createElement('div');
            el.className = `toast flex items-start text-sm text-white px-4 py-3 rounded-lg shadow-lg backdrop-blur-md bg-opacity-90 ${colors[type] || colors.info} animate-fade-in`;
            el.innerHTML = `
                <div class="mr-3 mt-0.5">
                    <i class="ti ${type === 'success' ? 'ti-check' : type === 'error' ? 'ti-alert-circle' : type === 'warning' ? 'ti-alert-triangle' : 'ti-info-circle'}"></i>
                </div>
                <div class="flex-1">${message}</div>
                <button class="ml-3 text-white/80 hover:text-white" onclick="this.parentElement.remove()">
                    <i class="ti ti-x"></i>
                </button>
            `;

            container.appendChild(el);

            setTimeout(() => {
                el.classList.add('opacity-0', 'translate-x-2');
                setTimeout(() => el.remove(), 300);
            }, duration);
        }

        // Show specific notifications based on URL parameters
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);

            // Remove error parameter from URL to clean it up
            if (urlParams.has('error')) {
                const url = new URL(window.location);
                url.searchParams.delete('error');
                window.history.replaceState({}, '', url);
            }
        });
    </script>

    <?php if ($showModalError): ?>
        <script>
            // Tampilkan modal error ketika halaman dimuat
            document.addEventListener('DOMContentLoaded', function () {
                showModalUjianSelesai();
            });
        </script>
    <?php endif; ?>

    <?php if ($showReviewNotAllowed): ?>
        <script>
            // Show toast for review not allowed
            document.addEventListener('DOMContentLoaded', function () {
                showToast('Guru tidak mengizinkan siswa melihat hasil ujian ini.', 'warning');
            });
        </script>
    <?php endif; ?>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateX(8px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-fade-in {
            animation: fade-in .25s ease-out;
        }

        #toast-container .toast {
            transition: all .3s ease;
        }
    </style>
    
    <!-- Dynamic Modal Component -->
    <?php require '../component/modal-dynamic.php'; ?>

</body>

</html>