<?php
session_start();
$currentPage = 'ujian';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'siswa') {
    header('Location: ../../login.php');
    exit();
}

require_once '../logic/ujian-logic.php';
require_once '../logic/time-helper.php';

$ujianLogic = new UjianLogic();
$siswa_id = $_SESSION['user']['id'];
$ujian_id = isset($_GET['ujian_id']) ? (int)$_GET['ujian_id'] : 0;

if ($ujian_id <= 0) {
    header('Location: ujian-user.php');
    exit();
}

// Get review data
$reviewData = $ujianLogic->getReviewUjianSiswa($ujian_id, $siswa_id);

if (!$reviewData) {
    header('Location: ujian-user.php?error=ujian_not_found');
    exit();
}

if (isset($reviewData['error'])) {
    if ($reviewData['error'] === 'not_allowed') {
        // Redirect back with error parameter instead of alert
        header('Location: ujian-user.php?error=review_not_allowed');
        exit();
    }
}

$ujian = $reviewData['ujian'];
$soalList = $reviewData['soal_list'];

function statusBadgeClass($status) {
    switch ($status) {
        case 'selesai':
            return 'bg-green-100 text-green-700 ring-1 ring-green-200';
        default:
            return 'bg-gray-100 text-gray-700 ring-1 ring-gray-200';
    }
}

function getJawabanStatus($soal, $is_answered) {
    if (!$is_answered) {
        return ['class' => 'bg-gray-100 text-gray-700', 'text' => 'Tidak Dijawab'];
    }
    
    if ($soal['benar'] === null) {
        return ['class' => 'bg-blue-100 text-blue-700', 'text' => 'Belum Dikoreksi'];
    }
    
    if ($soal['benar'] == 1) {
        return ['class' => 'bg-green-100 text-green-700', 'text' => 'Benar'];
    }
    
    return ['class' => 'bg-red-100 text-red-700', 'text' => 'Salah'];
}
?>

<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Review Hasil Ujian - <?= htmlspecialchars($ujian['namaUjian']) ?></title>
</head>

<body class="bg-gray-50">

    <div data-main-content class="md:ml-64 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">
        <!-- Header -->
        <header class="bg-white p-4 md:p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="ujian-user.php" class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-gray-100">
                        <i class="ti ti-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Review Hasil Ujian</h1>
                        <p class="text-sm text-gray-600 mt-1">Lihat jawaban dan koreksi dari guru</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-xs px-3 py-1 rounded-full font-medium uppercase tracking-wide <?= statusBadgeClass($ujian['status']) ?>">
                        <?= htmlspecialchars($ujian['status']) ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Main Content (col-span-3) -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Identitas Ujian -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="ti ti-info-circle text-orange mr-2"></i>
                                Identitas Ujian
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600">Nama Ujian:</p>
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars($ujian['namaUjian']) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Kelas:</p>
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars($ujian['namaKelas'] ?? '-') ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Mata Pelajaran:</p>
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars($ujian['mataPelajaran']) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Durasi:</p>
                                    <p class="font-medium text-gray-800"><?= (int)$ujian['durasi'] ?> menit</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Tanggal:</p>
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars(date('d M Y', strtotime($ujian['tanggalUjian']))) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Waktu:</p>
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars(TimeHelper::formatTimeRange($ujian['waktuMulai'], $ujian['waktuSelesai'])) ?> <span class="text-xs text-gray-500">(24 jam)</span></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Total Soal:</p>
                                    <p class="font-medium text-gray-800"><?= count($soalList) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Jumlah Benar:</p>
                                    <p class="font-medium text-green-600"><?= (int)($ujian['jumlahBenar'] ?? 0) ?></p>
                                </div>
                            </div>
                            <?php if (!empty($ujian['deskripsi'])): ?>
                                <div class="mt-4">
                                    <p class="text-gray-600 text-sm mb-1">Deskripsi:</p>
                                    <p class="text-gray-800 text-sm whitespace-pre-line"><?= nl2br(htmlspecialchars($ujian['deskripsi'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Hasil dan Jawaban -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="ti ti-list-details text-orange mr-2"></i>
                                Hasil & Pembahasan
                                <span class="ml-2 text-xs font-medium text-gray-500">(<?= count($soalList) ?> Soal)</span>
                            </h2>
                            
                            <?php if (empty($soalList)): ?>
                                <div class="p-4 border border-dashed rounded-lg text-sm text-gray-500 bg-gray-50">
                                    Tidak ada soal yang ditemukan.
                                </div>
                            <?php else: ?>
                                <div class="space-y-6">
                                    <?php foreach ($soalList as $soal): 
                                        $is_answered = !empty($soal['jawaban']) || !empty($soal['pilihanJawaban']);
                                        $jawaban_status = getJawabanStatus($soal, $is_answered);
                                    ?>
                                        <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm">
                                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                                <div class="flex items-center space-x-2">
                                                    <span class="inline-flex items-center justify-center w-7 h-7 text-sm font-semibold rounded bg-orange text-white">
                                                        <?= (int)$soal['nomorSoal'] ?>
                                                    </span>
                                                    <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-600 capitalize">
                                                        <?= htmlspecialchars(str_replace('_', ' ', $soal['tipeSoal'])) ?>
                                                    </span>
                                                    <span class="text-xs px-2 py-1 rounded <?= $jawaban_status['class'] ?>">
                                                        <?= $jawaban_status['text'] ?>
                                                    </span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <?php if ($soal['poin_jawaban'] !== null): ?>
                                                        <span class="text-xs px-2 py-1 rounded bg-blue-50 border border-blue-200 text-blue-700">
                                                            Nilai: <?= (int)$soal['poin_jawaban'] ?>/<?= (int)$soal['poin_soal'] ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-xs px-2 py-1 rounded bg-gray-50 border border-gray-200 text-gray-700">
                                                            Poin: <?= (int)$soal['poin_soal'] ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Pertanyaan -->
                                            <div class="mb-4">
                                                <h4 class="font-medium text-gray-800 mb-2">Pertanyaan:</h4>
                                                <div class="text-gray-700 leading-relaxed">
                                                    <?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?>
                                                </div>
                                            </div>

                                            <?php if ($soal['tipeSoal'] === 'pilihan_ganda'): ?>
                                                <!-- Pilihan Ganda -->
                                                <div class="mb-4">
                                                    <h4 class="font-medium text-gray-800 mb-2">Pilihan Jawaban:</h4>
                                                    <ul class="space-y-2">
                                                        <?php if (!empty($soal['pilihan_array'])): foreach ($soal['pilihan_array'] as $opsi => $det): 
                                                            $is_student_choice = ($soal['pilihanJawaban'] === $opsi);
                                                            $is_correct = $det['benar'];
                                                            $choice_class = '';
                                                            
                                                            if ($is_correct) {
                                                                $choice_class = 'bg-green-50 border-green-200 text-green-800';
                                                            } elseif ($is_student_choice && !$is_correct) {
                                                                $choice_class = 'bg-red-50 border-red-200 text-red-800';
                                                            } else {
                                                                $choice_class = 'bg-gray-50 border-gray-200 text-gray-700';
                                                            }
                                                        ?>
                                                            <li class="flex items-start p-3 rounded border <?= $choice_class ?>">
                                                                <span class="inline-block w-6 font-medium"><?= htmlspecialchars($opsi) ?>.</span>
                                                                <span class="flex-1"><?= htmlspecialchars($det['teks']) ?></span>
                                                                <div class="flex items-center space-x-2 ml-2">
                                                                    <?php if ($is_correct): ?>
                                                                        <span class="text-[10px] px-2 py-1 rounded bg-green-100 text-green-700 font-medium">
                                                                            <i class="ti ti-check text-[10px] mr-1"></i>Kunci
                                                                        </span>
                                                                    <?php endif; ?>
                                                                    <?php if ($is_student_choice): ?>
                                                                        <span class="text-[10px] px-2 py-1 rounded bg-blue-100 text-blue-700 font-medium">
                                                                            <i class="ti ti-user text-[10px] mr-1"></i>Pilihan Anda
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </li>
                                                        <?php endforeach; endif; ?>
                                                    </ul>
                                                </div>
                                            <?php else: ?>
                                                <!-- Essay/Isian -->
                                                <div class="mb-4">
                                                    <h4 class="font-medium text-gray-800 mb-2">Jawaban Anda:</h4>
                                                    <div class="p-3 bg-gray-50 border border-gray-200 rounded text-sm text-gray-700">
                                                        <?php if ($is_answered): ?>
                                                            <?= nl2br(htmlspecialchars($soal['jawaban'])) ?>
                                                        <?php else: ?>
                                                            <em class="text-gray-500">Tidak dijawab</em>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <?php if (!empty($soal['kunciJawaban'])): ?>
                                                    <div class="mb-4">
                                                        <h4 class="font-medium text-gray-800 mb-2">Kunci Jawaban:</h4>
                                                        <div class="p-3 bg-green-50 border border-green-200 rounded text-sm text-green-800">
                                                            <?= nl2br(htmlspecialchars($soal['kunciJawaban'])) ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <!-- Note: Koreksi Guru feature not available in current schema -->
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sidebar (col-span-1) -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Ringkasan Hasil</h3>
                            
                            <!-- Skor Total -->
                            <div class="mb-6 p-4 bg-gradient-to-r from-orange-50 to-orange-100 border border-orange-200 rounded-lg">
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-orange-600 mb-1">
                                        <?= $ujian['totalNilai'] !== null ? number_format($ujian['totalNilai'], 1) : '-' ?>
                                    </div>
                                    <div class="text-sm text-orange-700">Nilai Total</div>
                                </div>
                            </div>

                            <!-- Statistik -->
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Soal</span>
                                    <span class="font-medium"><?= count($soalList) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Jawaban Benar</span>
                                    <span class="font-medium text-green-600"><?= (int)($ujian['jumlahBenar'] ?? 0) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Jawaban Salah</span>
                                    <span class="font-medium text-red-600"><?= (int)($ujian['jumlahSalah'] ?? 0) ?></span>
                                </div>
                                <?php 
                                $tidak_dijawab = count($soalList) - (int)($ujian['jumlahBenar'] ?? 0) - (int)($ujian['jumlahSalah'] ?? 0);
                                if ($tidak_dijawab > 0): 
                                ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Tidak Dijawab</span>
                                        <span class="font-medium text-gray-500"><?= $tidak_dijawab ?></span>
                                    </div>
                                <?php endif; ?>
                                <hr class="my-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Waktu Pengerjaan</span>
                                    <span class="font-medium">
                                        <?php 
                                        if ($ujian['waktuMulai'] && $ujian['waktuSelesai']) {
                                            $start = new DateTime($ujian['waktuMulai']);
                                            $end = new DateTime($ujian['waktuSelesai']);
                                            $diff = $start->diff($end);
                                            echo $diff->format('%H:%I:%S');
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div class="mt-6">
                                <a href="ujian-user.php" class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors">
                                    <i class="ti ti-arrow-left"></i>
                                    <span>Kembali ke Daftar Ujian</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../script/menu-bar-script.js"></script>
</body>

</html>
