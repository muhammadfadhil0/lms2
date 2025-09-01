<!-- cek sekarang ada di halaman apa -->
<?php 
session_start();
$currentPage = 'ujian'; 

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'siswa') {
    header('Location: ../../index.php');
    exit();
}

require_once '../logic/ujian-logic.php';
require_once '../logic/soal-logic.php';
$ujianLogic = new UjianLogic();
$soalLogic = new SoalLogic();
$siswa_id = $_SESSION['user']['id'];

// Mulai ujian handler (POST sederhana)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'mulai' && isset($_POST['ujian_id'])) {
    $mulai = $ujianLogic->mulaiUjian((int)$_POST['ujian_id'], $siswa_id);
    if ($mulai['success']) {
        header('Location: kerjakan-ujian.php?us_id=' . $mulai['ujian_siswa_id']);
        exit();
    } else {
        $errorMulai = $mulai['message'];
    }
}

$ujianList = $ujianLogic->getUjianBySiswa($siswa_id);

function statusBadge($status) {
    switch($status) {
        case 'selesai': return 'bg-blue-100 text-blue-700';
        case 'sedang_mengerjakan': return 'bg-yellow-100 text-yellow-700';
        case 'dapat_dikerjakan': return 'bg-green-100 text-green-700';
        case 'belum_dimulai': return 'bg-purple-100 text-purple-700';
        case 'terlambat': return 'bg-red-100 text-red-700';
        case 'waktu_habis': return 'bg-red-100 text-red-700';
        default: return 'bg-gray-100 text-gray-700';
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

    <!-- Main Content -->
    <div data-main-content class="md:ml-64 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">
        <!-- Header -->
        <header class="bg-white p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Ujian</h1>
                </div>
                <div class="flex items-center space-x-2 md:space-x-4">
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

            <!-- Ujian Siswa -->
            <div class="mb-6">
                <?php if (isset($errorMulai)): ?>
                    <div class="mb-4 p-3 border border-red-200 bg-red-50 text-sm text-red-600 rounded"><?= htmlspecialchars($errorMulai) ?></div>
                <?php endif; ?>
                <?php if (empty($ujianList)): ?>
                    <div class="p-6 bg-white border rounded-lg text-center text-gray-500">Belum ada ujian aktif untuk kelas yang kamu ikuti.</div>
                <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                <?php foreach ($ujianList as $u): 
                    $nama = htmlspecialchars($u['namaUjian']);
                    $kelas = htmlspecialchars($u['namaKelas']);
                    $tanggal = htmlspecialchars(date('d M Y', strtotime($u['tanggalUjian'])));
                    $jam = htmlspecialchars(substr($u['waktuMulai'],0,5));
                    $durasi = (int)$u['durasi'];
                    $statusP = $u['status_ujian'];
                    $badge = statusBadge($statusP);
                    $totalNilai = $u['totalNilai'] ?? null;
                    $cover = !empty($u['gambarKover']) ? '../../'.htmlspecialchars($u['gambarKover']) : '';
                ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition group relative">
                        <div class="h-32 sm:h-40 md:h-48 bg-gradient-to-br from-indigo-400 to-indigo-600 relative">
                            <?php if ($cover): ?>
                                <img src="<?= $cover ?>" alt="<?= $nama ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center">
                                    <i class="ti ti-clipboard-check text-white text-4xl"></i>
                                </div>
                            <?php endif; ?>
                            <span class="absolute top-2 right-2 text-[10px] px-2 py-1 rounded <?= $badge ?> font-medium uppercase tracking-wide"><?= htmlspecialchars($statusP) ?></span>
                            <div class="absolute bottom-3 left-4 right-4">
                                <span class="bg-white/90 text-indigo-600 text-[11px] font-medium px-2 py-1 rounded-full inline-block mb-1">Kelas: <?= $kelas ?></span>
                                <h3 class="text-white text-sm font-semibold leading-tight drop-shadow line-clamp-2"><?= $nama ?></h3>
                            </div>
                        </div>
                        <div class="p-4 md:p-5 space-y-3">
                            <div class="flex justify-between text-[11px] text-gray-600">
                                <span class="flex items-center"><i class="ti ti-calendar mr-1"></i><?= $tanggal ?></span>
                                <span class="flex items-center"><i class="ti ti-clock mr-1"></i><?= $jam ?></span>
                            </div>
                            <div class="flex justify-between text-[11px] text-gray-600">
                                <span class="flex items-center"><i class="ti ti-hourglass mr-1"></i><?= $durasi ?> mnt</span>
                                <?php if ($totalNilai !== null): ?>
                                <span class="flex items-center"><i class="ti ti-scoreboard mr-1"></i><?= (int)$totalNilai ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="pt-1">
                                <?php if ($statusP === 'belum_dikerjakan' || $statusP === 'dapat_dikerjakan'): ?>
                                    <form method="post" class="flex gap-2">
                                        <input type="hidden" name="ujian_id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="aksi" value="mulai">
                                        <button class="flex-1 bg-green-600 text-white rounded-lg px-3 py-2 text-xs hover:bg-green-700">
                                            <?= $statusP === 'dapat_dikerjakan' ? 'Mulai Ujian' : 'Mulai' ?>
                                        </button>
                                    </form>
                                <?php elseif ($statusP === 'sedang_mengerjakan'): ?>
                                    <a href="kerjakan-ujian.php?us_id=<?= (int)$u['ujian_siswa_id'] ?>" class="block bg-yellow-500 text-white rounded-lg px-3 py-2 text-xs text-center hover:bg-yellow-600">Lanjutkan</a>
                                <?php elseif ($statusP === 'selesai'): ?>
                                    <a href="review-ujian.php?ujian_id=<?= (int)$u['id'] ?>" class="block bg-blue-600 text-white rounded-lg px-3 py-2 text-xs text-center hover:bg-blue-700">Lihat Nilai</a>
                                <?php elseif ($statusP === 'belum_dimulai'): ?>
                                    <button disabled class="flex-1 bg-gray-400 text-white rounded-lg px-3 py-2 text-xs cursor-not-allowed">
                                        Belum Dimulai
                                    </button>
                                <?php elseif ($statusP === 'terlambat' || $statusP === 'waktu_habis'): ?>
                                    <button disabled class="flex-1 bg-red-400 text-white rounded-lg px-3 py-2 text-xs cursor-not-allowed">
                                        Waktu Habis
                                    </button>
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

<script src="../script/menu-bar-script.js"></script>
</body>
</html>