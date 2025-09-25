<!-- cek sekarang ada di halaman apa -->
<?php
session_start();
$currentPage = 'ujian';

// Check auth & role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    header('Location: ../../login.php');
    exit();
}

require_once '../logic/ujian-logic.php';
require_once '../logic/time-helper.php';
$ujianLogic = new UjianLogic();
$guru_id = $_SESSION['user']['id'];
$ujianList = $ujianLogic->getUjianByGuru($guru_id);

// Debug helper: show session and query results when ?debug=1
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    // Log server-side
    error_log('DEBUG ujian-guru SESSION: ' . json_encode($_SESSION['user'] ?? []));
    error_log('DEBUG ujianList: ' . json_encode($ujianList));
    // Print to browser for convenience
    echo '<div style="padding:12px;background:#fff;border:1px solid #eee;margin:12px;">';
    echo '<h4>DEBUG: Session User</h4>';
    echo '<pre>' . htmlspecialchars(print_r($_SESSION['user'] ?? [], true)) . '</pre>';
    echo '<h4>DEBUG: ujianList</h4>';
    echo '<pre>' . htmlspecialchars(print_r($ujianList, true)) . '</pre>';
    echo '</div>';
}

// Helper badge style
function badgeColor($status)
{
    switch ($status) {
        case 'aktif':
            return 'bg-green-100 text-green-700';
        case 'selesai':
            return 'bg-blue-100 text-blue-700';
        default:
            return 'bg-gray-100 text-gray-700'; // draft
    }
}

?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<?php // Profile photo helper for fresh avatar URL ?>
<?php require_once '../logic/profile-photo-helper.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <!-- Dark mode removed by request -->



    <?php require '../../assets/head.php'; ?>
    <link rel="stylesheet" href="../css/search-system.css">
    <title>Ujian</title>
</head>



<body class="bg-gray-50">

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 space-y-3 z-[10000]"></div>

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
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Ujian</h1>
                    <p class="text-gray-600">Daftar ujian yang Anda buat & kelola</p>
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
                        <a href="buat-ujian-guru.php"
                            class="p-2 border rounded-full text-gray-400 hover:text-orange-600 transition-colors flex items-center">
                            <i class="ti ti-plus text-lg md:text-xl"></i>
                            <span class="hidden md:inline ml-1 text-sm">Tambah Ujian</span>
                        </a>
                        <button id="archiveBtn" class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
                            title="Arsip Ujian">
                            <i class="ti ti-archive text-lg md:text-xl"></i>
                        </button>
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

            <!-- Ujian List -->
            <div class="mb-6">
                <?php if (empty($ujianList)): ?>
                    <div class="flex flex-col items-center justify-center py-16 px-4">
                        <div class="w-24 h-24 bg-orange-100 rounded-full flex items-center justify-center mb-6">
                            <i class="ti ti-clipboard-plus text-orange text-4xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Belum Ada Ujian</h3>
                        <p class="text-gray-500 text-sm text-center mb-8 max-w-sm">
                            Mulai membuat ujian pertama Anda untuk siswa
                        </p>
                        <a href="buat-ujian-guru.php"
                            class="inline-flex items-center px-6 py-3 bg-orange text-white font-medium rounded-lg hover:bg-orange-600 transition-colors">
                            <i class="ti ti-plus mr-2"></i>
                            Buat Ujian Pertama
                        </a>
                    </div>
                <?php else: ?>
                    <div class="search-results-container grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                        <?php foreach ($ujianList as $u):
                            $tanggal = htmlspecialchars(date('d M Y', strtotime($u['tanggalUjian'])));
                            $waktu = htmlspecialchars(TimeHelper::formatTimeRange($u['waktuMulai'], $u['waktuSelesai']));
                            $status = htmlspecialchars($u['status']);
                            $badge = badgeColor($status);
                            $nama = htmlspecialchars($u['namaUjian']);
                            $kelas = htmlspecialchars($u['namaKelas'] ?? '-');
                            $soal = (int) ($u['jumlahSoal'] ?? $u['totalSoal'] ?? 0);
                            $peserta = (int) ($u['jumlahPeserta'] ?? 0);
                            $cover = !empty($u['gambar_kelas']) ? '../../' . htmlspecialchars($u['gambar_kelas']) : '';
                            ?>
                            <div
                                class="search-card bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all group relative"
                                data-class-id="<?= (int) $u['id'] ?>">
                                <div
                                    class="h-32 sm:h-40 md:h-48 bg-gradient-to-br from-orange-400 to-orange-600 relative overflow-hidden rounded-t-lg">
                                    <?php if ($cover): ?>
                                        <img src="<?= $cover ?>" alt="<?= $nama ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div
                                            class="w-full h-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                                            <i class="ti ti-clipboard-check text-white text-4xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="absolute top-2 md:top-3 right-2 md:right-3">
                                        <span
                                            class="bg-white/90 text-[10px] font-medium px-2 py-1 rounded-full <?= $badge ?> uppercase tracking-wide shadow-sm inline-block">
                                            <?= $status ?>
                                        </span>
                                    </div>
                                </div>
                                <!-- Teacher avatar positioned at left on the cover/card boundary (moved outside cover to avoid clipping) -->
                                <?php
                                $ownerId = isset($u['guru_id']) ? $u['guru_id'] : $guru_id;
                                $ownerPhoto = getUserProfilePhotoUrl($ownerId);
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
                                <div class="p-4 md:pt-5 md:p-5 space-y-3">
                                    <h3
                                        class="font-semibold leading-snug drop-shadow line-clamp-2 pb-0 mb-0 group-hover:line-clamp-none transition-all">
                                        <?= $nama ?></h3>
                                    <p class="text-sm text-gray-600"><?= $kelas ?></p>
                                    <div class="grid grid-cols-2 gap-2 text-[11px] text-gray-600">
                                        <div class="flex items-center"><i class="ti ti-calendar mr-1"></i><?= $tanggal ?></div>
                                        <div class="flex items-center justify-end"><i class="ti ti-clock mr-1"></i><?= $waktu ?>
                                        </div>
                                        <div class="flex items-center"><i class="ti ti-help mr-1"></i><?= $soal ?> Soal</div>
                                        <div class="flex items-center justify-end"><i
                                                class="ti ti-users mr-1"></i><?= $peserta ?> Peserta</div>
                                    </div>
                                    <div class="flex items-stretch gap-2 pt-1">
                                        <a href="route-ujian.php?id=<?= (int) $u['id'] ?>"
                                            class="flex-1 text-xs px-3 py-2 rounded-lg bg-orange text-white hover:bg-orange-600 transition text-center font-medium">Buka</a>
                                        <div class="relative">
                                            <button onclick="toggleExamDropdown('exam-dd-<?= (int) $u['id'] ?>', this)"
                                                class="h-full px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600 text-xs flex items-center justify-center focus:ring-2 focus:ring-orange-300">
                                                <i class="ti ti-dots-vertical text-base"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Floating Dropdown Container - Outside of all other elements -->
    <?php if (!empty($ujianList)): ?>
    <div id="floating-dropdowns" class="fixed top-0 left-0 w-full h-full pointer-events-none z-[10000]">
        <?php foreach ($ujianList as $u): ?>
            <div id="exam-dd-<?= (int) $u['id'] ?>"
                class="hidden absolute w-40 bg-white border border-gray-200 rounded-lg shadow-xl py-1 text-xs pointer-events-auto"
                style="box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                <a href="duplikat-ujian.php?id=<?= (int) $u['id'] ?>"
                    class="flex items-center px-3 py-2 hover:bg-gray-50 transition-colors">
                    <i class="ti ti-copy mr-2"></i> Duplikasi
                </a>
                <button onclick="hapusUjian(<?= (int) $u['id'] ?>, '<?= addslashes($u['namaUjian']) ?>')"
                    class="w-full text-left flex items-center px-3 py-2 hover:bg-red-50 text-red-600 transition-colors">
                    <i class="ti ti-trash mr-2"></i> Hapus
                </button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Include Archive Sidebar -->
    <?php require '../component/sidebar-archive.php'; ?>

    <!-- Include Delete Ujian Modal -->
    <?php require '../component/modal-delete-ujian.php'; ?>

    <script src="../script/menu-bar-script.js"></script>
    
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
            apiEndpoint: '../logic/search-ujian-api.php',
            searchFields: ['namaUjian', 'deskripsi', 'mataPelajaran', 'namaKelas', 'topik'],
            debounceDelay: 800,
            minSearchLength: 1
        };
    </script>
    <script src="../script/search-system.js"></script>
    
    <script>
        // Simple toast helper (mirrors style pattern used elsewhere)
        function showToast(message, type = 'info') {
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
            el.innerHTML = `<div class="mr-3 mt-0.5">
                <i class="ti ${type === 'success' ? 'ti-check' : type === 'error' ? 'ti-alert-circle' : type === 'warning' ? 'ti-alert-triangle' : 'ti-info-circle'}"></i>
            </div><div class="flex-1">${message}</div>
            <button class="ml-3 text-white/80 hover:text-white" onclick="this.parentElement.remove()"><i class="ti ti-x"></i></button>`;
            container.appendChild(el);
            setTimeout(() => {
                el.classList.add('opacity-0', 'translate-x-2');
                setTimeout(() => el.remove(), 300);
            }, 4000);
        }

        // Read query params for status messages (duplication, errors)
        (function () {
            const p = new URLSearchParams(location.search);
            if (p.get('duplicated') === '1') {
                showToast('Ujian berhasil diduplikasi. Silakan edit sebelum mengaktifkan.', 'success');
            }
            if (p.get('deleted') === '1') {
                showToast('Ujian berhasil dihapus.', 'success');
            }
            if (p.get('created') === '1') {
                showToast('Ujian baru berhasil dibuat.', 'success');
            }
            if (p.get('updated') === '1') {
                showToast('Ujian berhasil diperbarui.', 'success');
            }
            if (p.get('err') === 'dup') {
                showToast('Gagal menduplikasi ujian.', 'error');
            }
            if (p.get('err') === 'notfound') {
                showToast('Ujian tidak ditemukan atau bukan milik Anda.', 'error');
            }
            if (['dup', 'notfound'].some(v => p.get('err') === v) || p.get('duplicated') || p.get('deleted') || p.get('created') || p.get('updated')) {
                // Clean URL after showing
                const url = new URL(location.href);
                ['duplicated', 'deleted', 'err', 'created', 'updated'].forEach(k => url.searchParams.delete(k));
                window.history.replaceState({}, '', url);
            }
        })();

        function toggleExamDropdown(id, buttonElement) {
            // Close all other dropdowns
            document.querySelectorAll('[id^="exam-dd-"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            
            const dropdown = document.getElementById(id);
            if (!dropdown) return;
            
            if (dropdown.classList.contains('hidden')) {
                // Show dropdown - calculate position
                const buttonRect = buttonElement.getBoundingClientRect();
                const dropdownWidth = 160; // w-40 = 10rem = 160px
                const dropdownHeight = dropdown.offsetHeight || 80; // estimate
                
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
        document.addEventListener('click', e => {
            // Close dropdown if clicking outside
            if (!e.target.closest('[id^="exam-dd-"]') && !e.target.closest('button[onclick*="toggleExamDropdown"]')) {
                document.querySelectorAll('[id^="exam-dd-"]').forEach(el => el.classList.add('hidden'));
            }
        });
        
        // Close dropdown on scroll or resize
        window.addEventListener('scroll', () => {
            document.querySelectorAll('[id^="exam-dd-"]').forEach(el => el.classList.add('hidden'));
        });
        
        window.addEventListener('resize', () => {
            document.querySelectorAll('[id^="exam-dd-"]').forEach(el => el.classList.add('hidden'));
        });

        function hapusUjian(id, namaUjian = '') {
            // Set ujian name in modal
            const ujianNameSpan = document.getElementById('ujianName');
            if (ujianNameSpan) {
                ujianNameSpan.textContent = namaUjian || 'ujian ini';
            }

            // Show modal with animation
            const modal = document.getElementById('deleteUjianModal');
            const backdrop = document.getElementById('deleteUjianBackdrop');
            const panel = document.getElementById('deleteUjianPanel');

            if (modal && backdrop && panel) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';

                // Trigger animations
                requestAnimationFrame(() => {
                    backdrop.classList.remove('opacity-0');
                    backdrop.classList.add('opacity-100');

                    panel.classList.remove('opacity-0', 'translate-y-4', 'scale-95');
                    panel.classList.add('opacity-100', 'translate-y-0', 'scale-100');
                });
            }

            // Store ujian ID for deletion
            window.currentUjianToDelete = id;
        }

        // Modal Delete Ujian functionality
        const deleteUjianModal = document.getElementById('deleteUjianModal');
        const confirmDeleteUjianBtn = document.getElementById('confirmDeleteUjianBtn');
        const cancelDeleteUjianBtn = document.getElementById('cancelDeleteUjianBtn');

        function closeDeleteUjianModal() {
            const modal = document.getElementById('deleteUjianModal');
            const backdrop = document.getElementById('deleteUjianBackdrop');
            const panel = document.getElementById('deleteUjianPanel');

            if (modal && backdrop && panel) {
                // Start exit animation
                backdrop.classList.remove('opacity-100');
                backdrop.classList.add('opacity-0');

                panel.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
                panel.classList.add('opacity-0', 'translate-y-4', 'scale-95');

                // Hide modal after animation completes
                setTimeout(() => {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                }, 300);
            }

            window.currentUjianToDelete = null;
        }

        async function performDeleteUjian(id) {
            const deleteBtn = confirmDeleteUjianBtn;
            const loadingIcon = deleteBtn.querySelector('.delete-ujian-btn-loading');
            const btnText = deleteBtn.querySelector('.delete-ujian-btn-text');

            // Show loading state
            deleteBtn.disabled = true;
            loadingIcon.classList.remove('hidden');
            btnText.textContent = 'Menghapus...';

            try {
                const response = await fetch('../logic/delete-ujian.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        ujian_id: id
                    })
                });

                const result = await response.json();

                if (result.success) {
                    closeDeleteUjianModal();
                    // reload with flag
                    const url = new URL(location.href);
                    url.searchParams.set('deleted', '1');
                    location.href = url;
                } else {
                    showToast(result.message || 'Gagal menghapus ujian', 'error');
                }
            } catch (error) {
                showToast('Gagal menghapus ujian (network error).', 'error');
            } finally {
                // Reset loading state
                deleteBtn.disabled = false;
                loadingIcon.classList.add('hidden');
                btnText.textContent = 'Hapus Ujian';
            }
        }

        // Event listeners for delete ujian modal
        if (confirmDeleteUjianBtn) {
            confirmDeleteUjianBtn.addEventListener('click', () => {
                if (window.currentUjianToDelete) {
                    performDeleteUjian(window.currentUjianToDelete);
                }
            });
        }

        if (cancelDeleteUjianBtn) {
            cancelDeleteUjianBtn.addEventListener('click', closeDeleteUjianModal);
        }

        // Close modal when clicking outside
        if (deleteUjianModal) {
            deleteUjianModal.addEventListener('click', (e) => {
                if (e.target === deleteUjianModal || e.target.id === 'deleteUjianBackdrop') {
                    closeDeleteUjianModal();
                }
            });
        }

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && deleteUjianModal && !deleteUjianModal.classList.contains('hidden')) {
                closeDeleteUjianModal();
            }
        });

        // Archive sidebar functionality
        const archiveBtn = document.getElementById('archiveBtn');
        const archiveSidebar = document.getElementById('archiveSidebar');
        const archiveBackdrop = document.getElementById('archiveBackdrop');
        const closeArchiveBtn = document.getElementById('closeArchiveBtn');
        const archiveLoader = document.getElementById('archiveLoader');
        const archiveContent = document.getElementById('archiveContent');
        const archiveEmpty = document.getElementById('archiveEmpty');

        function openArchiveSidebar() {
            archiveBackdrop.classList.remove('hidden');
            archiveSidebar.classList.remove('translate-x-full');
            document.body.style.overflow = 'hidden';
            loadArchivedExams();
        }

        function closeArchiveSidebar() {
            archiveBackdrop.classList.add('hidden');
            archiveSidebar.classList.add('translate-x-full');
            document.body.style.overflow = '';
        }

        async function loadArchivedExams() {
            archiveLoader.classList.remove('hidden');
            archiveContent.classList.add('hidden');
            archiveEmpty.classList.add('hidden');

            try {
                const response = await fetch('../logic/get-archived-exams.php');
                const result = await response.json();

                if (result.success) {
                    if (result.data.length === 0) {
                        archiveEmpty.classList.remove('hidden');
                    } else {
                        displayArchivedExams(result.data);
                        archiveContent.classList.remove('hidden');
                    }
                } else {
                    showToast('Gagal memuat arsip ujian', 'error');
                }
            } catch (error) {
                showToast('Terjadi kesalahan saat memuat arsip', 'error');
            } finally {
                archiveLoader.classList.add('hidden');
            }
        }

        function displayArchivedExams(exams) {
            archiveContent.innerHTML = exams.map(exam => `
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                    <div class="flex items-start justify-between mb-2">
                        <h4 class="font-medium text-gray-800 text-sm line-clamp-2">${escapeHtml(exam.namaUjian)}</h4>
                        <button onclick="restoreExam(${exam.id})" class="text-orange hover:text-orange-600 text-xs flex items-center">
                            <i class="ti ti-restore mr-1"></i>
                            Pulihkan
                        </button>
                    </div>
                    <div class="text-xs text-gray-600 space-y-1">
                        <div class="flex items-center">
                            <i class="ti ti-calendar w-3 h-3 mr-1"></i>
                            ${new Date(exam.tanggalUjian).toLocaleDateString('id-ID')}
                        </div>
                        <div class="flex items-center">
                            <i class="ti ti-users w-3 h-3 mr-1"></i>
                            ${escapeHtml(exam.namaKelas || '-')}
                        </div>
                        <div class="flex items-center">
                            <i class="ti ti-clock w-3 h-3 mr-1"></i>
                            Diarsipkan: ${new Date(exam.updatedAt || exam.dibuat).toLocaleDateString('id-ID')}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        async function restoreExam(examId) {
            if (!confirm('Pulihkan ujian ini dari arsip?')) return;

            try {
                const response = await fetch('../logic/restore-exam.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        ujian_id: examId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Ujian berhasil dipulihkan', 'success');
                    loadArchivedExams(); // Reload archive
                    // Optionally reload main page to show restored exam
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message || 'Gagal memulihkan ujian', 'error');
                }
            } catch (error) {
                showToast('Terjadi kesalahan saat memulihkan ujian', 'error');
            }
        }

        // Event listeners
        if (archiveBtn) {
            archiveBtn.addEventListener('click', openArchiveSidebar);
        }

        if (closeArchiveBtn) {
            closeArchiveBtn.addEventListener('click', closeArchiveSidebar);
        }

        if (archiveBackdrop) {
            archiveBackdrop.addEventListener('click', closeArchiveSidebar);
        }

        // Close archive on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !archiveSidebar.classList.contains('translate-x-full')) {
                closeArchiveSidebar();
            }
        });
    </script>
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

        /* Modal Delete Ujian Styles */
        #deleteUjianModal {
            z-index: 9999;
        }

        #deleteUjianModal.hidden {
            display: none !important;
        }

        #deleteUjianModal:not(.hidden) {
            display: block !important;
        }

        #deleteUjianBackdrop {
            transition: opacity 0.3s ease-out;
        }

        #deleteUjianPanel {
            transition: all 0.3s ease-out;
            min-height: 200px;
            /* Ensure minimum height */
        }

        /* Ensure content is visible */
        #deleteUjianPanel .bg-white {
            background-color: white;
        }

        #deleteUjianPanel h3 {
            color: #111827;
            /* gray-900 */
        }

        #deleteUjianPanel p {
            color: #6b7280;
            /* gray-500 */
        }

        #deleteUjianPanel span {
            color: #374151;
            /* gray-700 */
        }

        .delete-ujian-btn-text {
            color: white !important;
        }

        /* Floating dropdown container */
        #floating-dropdowns {
            z-index: 10000;
            pointer-events: none;
        }

        /* Floating dropdown styling */
        #floating-dropdowns [id^="exam-dd-"] {
            pointer-events: auto;
            backdrop-filter: blur(8px);
            background-color: rgba(255, 255, 255, 0.95);
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease-out;
        }

        #floating-dropdowns [id^="exam-dd-"]:not(.hidden) {
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

        /* Mobile responsive adjustments */
        @media (max-width: 640px) {
            #deleteUjianPanel {
                margin: 0;
                border-radius: 0.75rem 0.75rem 0 0;
                margin-bottom: 0;
            }

            #deleteUjianPanel.translate-y-4 {
                transform: translateY(100%);
            }

            #deleteUjianPanel.translate-y-0 {
                transform: translateY(0);
            }

            /* Ensure dropdown works well on mobile */
            [id^="exam-dd-"] {
                min-width: 150px;
                right: 0;
            }
        }
    </style>
    
    <!-- Dynamic Modal Component -->
    <?php require '../component/modal-dynamic.php'; ?>
</body>

</html>