<!-- cek sekarang ada di halaman apa -->
<?php
session_start();
$currentPage = 'ujian';

// Check auth & role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    header('Location: ../../index.php');
    exit();
}

require_once '../logic/ujian-logic.php';
$ujianLogic = new UjianLogic();
$guru_id = $_SESSION['user']['id'];
$ujianList = $ujianLogic->getUjianByGuru($guru_id);

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
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Ujian</title>
</head>

<body class="bg-gray-50">

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 space-y-3 z-[10000]"></div>

    <!-- Main Content -->
    <div data-main-content class="md:ml-64 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">
        <!-- Header -->
        <header class="bg-white p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Ujian</h1>
                    <p class="text-gray-600 text-sm hidden sm:block">Daftar ujian yang Anda buat & kelola</p>
                </div>
                <div class="flex items-center space-x-2 md:space-x-4">
                    <a href="buat-ujian-guru.php" class="p-2 border rounded-full text-gray-400 hover:text-orange-600 transition-colors flex items-center">
                        <i class="ti ti-plus text-lg md:text-xl"></i>
                        <span class="hidden md:inline ml-1 text-sm">Tambah Ujian</span>
                    </a>
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

            <!-- Ujian List -->
            <div class="mb-6">
                <?php if (empty($ujianList)): ?>
                    <div class="p-6 bg-white border rounded-lg text-center text-gray-500">Belum ada ujian. Klik "Tambah Ujian" untuk membuat.</div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                        <?php foreach ($ujianList as $u):
                            $tanggal = htmlspecialchars(date('d M Y', strtotime($u['tanggalUjian'])));
                            $waktu = htmlspecialchars(substr($u['waktuMulai'], 0, 5) . ' - ' . substr($u['waktuSelesai'], 0, 5));
                            $status = htmlspecialchars($u['status']);
                            $badge = badgeColor($status);
                            $nama = htmlspecialchars($u['namaUjian']);
                            $kelas = htmlspecialchars($u['namaKelas'] ?? '-');
                            $soal = (int)($u['jumlahSoal'] ?? $u['totalSoal'] ?? 0);
                            $peserta = (int)($u['jumlahPeserta'] ?? 0);
                            $cover = !empty($u['gambarKover']) ? '../../' . htmlspecialchars($u['gambarKover']) : '';
                        ?>
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all group relative">
                                <div class="h-32 sm:h-40 md:h-48 bg-gradient-to-br from-orange-400 to-orange-600 relative overflow-hidden rounded-t-lg">
                                    <?php if ($cover): ?>
                                        <img src="<?= $cover ?>" alt="<?= $nama ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                                            <i class="ti ti-clipboard-check text-white text-4xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="absolute top-2 md:top-3 right-2 md:right-3">
                                        <span class="bg-white/90 text-[10px] font-medium px-2 py-1 rounded-full <?= $badge ?> uppercase tracking-wide shadow-sm inline-block">
                                            <?= $status ?>
                                        </span>
                                    </div>
                                    <div class="absolute bottom-3 left-4 right-4">
                                        <span class="bg-white/90 text-orange-600 text-[11px] font-medium px-2 py-1 rounded-full inline-block mb-1 shadow-sm">Kelas: <?= $kelas ?></span>
                                    </div>
                                </div>
                                <div class="p-4 md:p-5 space-y-3">
                                    <h3 class="font-semibold leading-snug drop-shadow line-clamp-2 group-hover:line-clamp-none transition-all"><?= $nama ?></h3>
                                    <div class="grid grid-cols-2 gap-2 text-[11px] text-gray-600">
                                        <div class="flex items-center"><i class="ti ti-calendar mr-1"></i><?= $tanggal ?></div>
                                        <div class="flex items-center justify-end"><i class="ti ti-clock mr-1"></i><?= $waktu ?></div>
                                        <div class="flex items-center"><i class="ti ti-help mr-1"></i><?= $soal ?> Soal</div>
                                        <div class="flex items-center justify-end"><i class="ti ti-users mr-1"></i><?= $peserta ?> Peserta</div>
                                    </div>
                                    <div class="flex items-stretch gap-2 pt-1">
                                        <a href="route-ujian.php?id=<?= (int)$u['id'] ?>" class="flex-1 text-xs px-3 py-2 rounded-lg bg-orange text-white hover:bg-orange-600 transition text-center font-medium">Buka</a>
                                        <div class="relative">
                                            <button onclick="toggleExamDropdown('exam-dd-<?= (int)$u['id'] ?>')" class="h-full px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600 text-xs flex items-center justify-center focus:ring-2 focus:ring-orange-300">
                                                <i class="ti ti-dots-vertical text-base"></i>
                                            </button>
                                            <div id="exam-dd-<?= (int)$u['id'] ?>" class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-50 py-1 text-xs">
                                                <a href="duplikat-ujian.php?id=<?= (int)$u['id'] ?>" class="flex items-center px-3 py-2 hover:bg-gray-50">
                                                    <i class="ti ti-copy mr-2"></i> Duplikasi
                                                </a>
                                                <button onclick="hapusUjian(<?= (int)$u['id'] ?>)" class="w-full text-left flex items-center px-3 py-2 hover:bg-red-50 text-red-600">
                                                    <i class="ti ti-trash mr-2"></i> Hapus
                                                </button>
                                            </div>
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

    <script src="../script/menu-bar-script.js"></script>
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
            el.className = `toast flex items-start text-sm text-white px-4 py-3 rounded-lg shadow-lg backdrop-blur-md bg-opacity-90 ${colors[type]||colors.info} animate-fade-in`;
            el.innerHTML = `<div class="mr-3 mt-0.5">
                <i class="ti ${type==='success'?'ti-check':type==='error'?'ti-alert-circle':type==='warning'?'ti-alert-triangle':'ti-info-circle'}"></i>
            </div><div class="flex-1">${message}</div>
            <button class="ml-3 text-white/80 hover:text-white" onclick="this.parentElement.remove()"><i class="ti ti-x"></i></button>`;
            container.appendChild(el);
            setTimeout(() => {
                el.classList.add('opacity-0', 'translate-x-2');
                setTimeout(() => el.remove(), 300);
            }, 4000);
        }

        // Read query params for status messages (duplication, errors)
        (function() {
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

        function toggleExamDropdown(id) {
            document.querySelectorAll('[id^="exam-dd-"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            const dd = document.getElementById(id);
            if (dd) dd.classList.toggle('hidden');
        }
        document.addEventListener('click', e => {
            if (!e.target.closest('[id^="exam-dd-"]') && !e.target.closest('button[onclick^="toggleExamDropdown"]')) {
                document.querySelectorAll('[id^="exam-dd-"]').forEach(el => el.classList.add('hidden'));
            }
        });

        function hapusUjian(id) {
            if (confirm('Hapus ujian ini? Semua data terkait soal akan ikut terhapus.')) {
                fetch('../logic/delete-ujian.php', {
                        method: 'POST',
                        body: new URLSearchParams({
                            ujian_id: id
                        })
                    })
                    .then(r => r.json()).then(j => {
                        if (j.success) {
                            // reload with flag
                            const url = new URL(location.href);
                            url.searchParams.set('deleted', '1');
                            location.href = url;
                        } else {
                            showToast(j.message || 'Gagal menghapus ujian', 'error');
                        }
                    })
                    .catch(() => showToast('Gagal menghapus ujian (network error).', 'error'));
            }
        }
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
    </style>
</body>

</html>