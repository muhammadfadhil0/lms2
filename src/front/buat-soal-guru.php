<!-- cek sekarang ada di halaman apa -->
<?php
session_start();
$currentPage = 'buat-soal';

// Check if user is logged in and is a guru
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    header("Location: ../../login.php");
    exit();
}
require_once '../logic/ujian-logic.php';
require_once '../logic/soal-logic.php';
require_once '../logic/time-helper.php';
$ujianLogic = new UjianLogic();
$soalLogic = new SoalLogic();
$guru_id = $_SESSION['user']['id'];
$ujian_id = isset($_GET['ujian_id']) ? (int) $_GET['ujian_id'] : 0;
$ujian = null;
if ($ujian_id) {
    $ujian = $ujianLogic->getUjianByIdAndGuru($ujian_id, $guru_id);
}
if (!$ujian) {
    // Jika tidak ada ujian yang valid, redirect kembali ke halaman list ujian
    header('Location: ujian-guru.php');
    exit();
}
// Auto activate if still draft
if ($ujian['status'] === 'draft') {
    $ujianLogic->updateStatusUjian($ujian_id, 'aktif');
    $ujian = $ujianLogic->getUjianByIdAndGuru($ujian_id, $guru_id); // refresh
    $justActivated = true;
} else {
    $justActivated = isset($_GET['created']) && $_GET['created'] == '1';
}
// Ambil daftar soal yang sudah ada
$soalList = $soalLogic->getSoalByUjian($ujian_id);

// Sekarang topik sudah terpisah di database, baca langsung dari field topik
$topik = $ujian['topik'] ?? '';
$deskripsi = $ujian['deskripsi'] ?? '';
?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<?php require '../component/modal-add-class.php'; ?>
<?php require '../component/modal-incomplete-questions.php'; ?>
<?php require '../component/modal-delete-question.php'; ?>
<?php require '../component/modal-add-question-pingo.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <link rel="stylesheet" href="../css/modal-styles.css">
    <title>Buat Soal - LMS</title>
    <style>
        .question-card {
            transition: all 0.3s ease;
        }

        .question-card.active {
            border-color: rgb(255, 99, 71);
            box-shadow: 0 0 0 1px rgb(255, 99, 71);
        }

        .sidebar-tools {
            position: sticky;
            top: 20px;
        }

        .option-input {
            transition: all 0.2s ease;
        }

        .option-input:focus {
            border-color: rgb(255, 99, 71);
            box-shadow: 0 0 0 1px rgb(255, 99, 71);
        }

        .drag-handle {
            cursor: grab;
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        /* Grid navigation scroll after many items */
        #question-nav.overflow-limit {
            max-height: 260px;
            overflow-y: auto;
            padding-right: 4px;
        }

        #question-nav::-webkit-scrollbar {
            width: 6px;
        }

        #question-nav::-webkit-scrollbar-track {
            background: transparent;
        }

        #question-nav::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 4px;
        }

        #question-nav::-webkit-scrollbar-thumb:hover {
            background: #d1d5db;
        }

        /* Toast animations */
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

        /* Pingo AI Modal Styles */
        #pingo-ai-modal .bg-white {
            backdrop-filter: blur(10px);
        }

        /* Radio button custom styles with orange theme */
        input[type="radio"]:checked+div {
            color: inherit;
        }

        /* Difficulty radio button hover effects */
        input[name="ai-difficulty"]:checked+div .font-medium {
            color: rgb(255, 99, 71) !important;
        }

        /* Loading animation for AI button */
        .animate-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Modal animation with orange accents */
        #pingo-ai-modal:not(.hidden) .bg-white {
            animation: modal-slide-in 0.3s ease-out;
        }

        @keyframes modal-slide-in {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-10px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Enhanced focus states for orange theme */
        #pingo-ai-modal select:focus,
        #pingo-ai-modal textarea:focus {
            border-color: rgb(255, 99, 71);
            box-shadow: 0 0 0 1px rgb(255, 71);
        }

        /* Z-index fixes for modal */
        .sidebar-tools {
            z-index: 50 !important;
        }

        [data-sidebar],
        .sidebar,
        nav,
        header {
            z-index: 40 !important;
        }

        /* Ensure PingoAI modal is above everything */
        #add-soal-ai {
            z-index: 9999 !important;
        }

        /* Toast container should be above modal */
        #toast-container {
            z-index: 10100 !important;
        }
    </style>
</head>

<body class="bg-gray-50">

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 space-y-3 z-[10000]"></div>

    <!-- Main Content -->
    <div data-main-content class="md:ml-64 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">
        <!-- Header -->
        <header class="bg-white p-4 md:p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button onclick="history.back()"
                        class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-gray-100">
                        <i class="ti ti-arrow-left text-xl"></i>
                    </button>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Buat Soal Ujian</h1>
                        <p class="text-sm text-gray-600 mt-1">Tambahkan soal untuk ujian</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 md:space-x-4">
                    <div id="save-status" class="flex items-center space-x-2">
                        <i class="ti ti-device-floppy text-lg text-gray-500" aria-hidden="true"></i>
                        <span id="last-saved" class="text-sm text-gray-500">Belum disimpan</span>
                    </div>
                    <div id="save-dot" class="w-2 h-2 bg-amber-300 rounded-full"></div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Progress Indicator -->
                <div class="mb-8">
                    <?php if (!empty($justActivated)): ?>
                        <div
                            class="mb-4 p-4 border border-green-200 bg-green-50 text-green-700 rounded text-sm flex items-start">
                            <i class="ti ti-check mr-2 mt-0.5"></i>
                            <div>
                                <strong>Ujian Aktif.</strong> Ujian telah dibuat dan otomatis diaktifkan. Tambahkan soal
                                sekarang.
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="flex items-center justify-between mb-4">
                        <a href="buat-ujian-guru.php?ujian_id=<?= (int) $ujian['id'] ?>"
                            class="group flex items-center space-x-2 hover:opacity-90"
                            title="Kembali untuk mengubah identitas ujian">
                            <div
                                class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-medium group-hover:ring-2 group-hover:ring-green-300">
                                <i class="ti ti-check text-sm"></i>
                            </div>
                            <span
                                class="text-sm text-green-600 underline decoration-dotted group-hover:text-green-700">Identitas
                                Ujian</span>
                        </a>
                        <div class="flex-1 h-px bg-orange mx-4"></div>
                        <div class="flex items-center space-x-2">
                            <div
                                class="w-8 h-8 bg-orange text-white rounded-full flex items-center justify-center text-sm font-medium">
                                2
                            </div>
                            <span class="text-sm font-medium text-orange">Buat Soal</span>
                        </div>
                    </div>
                </div>

                <!-- Hidden exam id for JS operations -->
                <?php $autoScoreFlag = (isset($ujian['autoScore']) && $ujian['autoScore']) || (isset($_GET['autoscore']) && $_GET['autoscore'] == '1'); ?>
                <input type="hidden" id="ujian_id" value="<?= (int) $ujian['id'] ?>"
                    data-autoscore="<?= $autoScoreFlag ? '1' : '0' ?>">
                <?php if ($autoScoreFlag): ?>
                    <div
                        class="mb-6 p-4 rounded-lg border border-amber-300 bg-amber-50 text-amber-800 text-sm flex items-start">
                        <i class="ti ti-alert-triangle mr-2 mt-0.5"></i>
                        <div>
                            <strong>Penilaian Otomatis Aktif.</strong><br>Hanya soal pilihan ganda yang akan diujikan &
                            dinilai. Penilaian akan kami genapkan menjadi 100.
                        </div>
                    </div>
                <?php endif; ?>
                <!-- Layout Container -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Main Content (3/4 width on lg screens) -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Exam Identity Summary -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="ti ti-info-circle text-orange mr-2"></i>
                                Identitas Ujian
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600">Nama Ujian:</p>
                                    <p class="font-medium text-gray-800" id="exam-name-display">
                                        <?= htmlspecialchars($ujian['namaUjian']) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Kelas:</p>
                                    <p class="font-medium text-gray-800" id="exam-class-display">
                                        <?= htmlspecialchars($ujian['namaKelas'] ?? '-') ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Materi:</p>
                                    <p class="font-medium text-gray-800" id="exam-topic-display">
                                        <?= !empty($topik) ? htmlspecialchars($topik) : '-' ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Tanggal:</p>
                                    <p class="font-medium text-gray-800" id="exam-date-display">
                                        <?= htmlspecialchars(date('d M Y', strtotime($ujian['tanggalUjian']))) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Waktu:</p>
                                    <p class="font-medium text-gray-800" id="exam-time-display">
                                        <?= htmlspecialchars(TimeHelper::formatTimeRange($ujian['waktuMulai'], $ujian['waktuSelesai'])) ?>
                                        <span class="text-xs text-gray-500">(24 jam)</span>
                                        (<?= (int) $ujian['durasi'] ?> menit)</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-gray-600 text-sm">Deskripsi:</p>
                                <p class="text-gray-800 text-sm" id="exam-description-display">
                                    <?= !empty($deskripsi) ? nl2br(htmlspecialchars($deskripsi)) : '-' ?></p>
                            </div>
                        </div>

                        <!-- Questions Container -->
                        <div id="questions-container" class="space-y-4">
                            <?php if (!empty($soalList)): ?>
                                <?php $idx = 0;
                                foreach ($soalList as $s):
                                    $idx++;
                                    $tipeRaw = $s['tipeSoal'];
                                    $tipeValue = $tipeRaw === 'pilihan_ganda' ? 'multiple_choice' : ($tipeRaw === 'isian_singkat' ? 'short_answer' : ($tipeRaw === 'essay' ? 'long_answer' : 'multiple_choice'));
                                    $isMC = $tipeValue === 'multiple_choice';
                                    $pilihan = $s['pilihan_array'] ?? [];
                                    $kunci = $s['kunciJawaban'] ?? '';
                                    $poin = (int) ($s['poin'] ?? 10);
                                    $autoGrading = (isset($s['autoGrading']) && $s['autoGrading']) || $isMC ? true : false;
                                    ?>
                                    <div class="question-card bg-white rounded-lg shadow-sm border border-gray-200 p-6 <?php echo $idx === 1 ? 'active' : ''; ?> <?= ($autoScoreFlag && !$isMC) ? 'opacity-60 pointer-events-none relative' : ''; ?>"
                                        data-question-id="<?= $idx ?>" data-soal-id="<?= (int) $s['id'] ?>"
                                        data-original-type="<?= htmlspecialchars($tipeValue) ?>"
                                        data-active="<?= (!$autoScoreFlag || $isMC) ? '1' : '0' ?>">
                                        <!-- Question Header -->
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-center space-x-3">
                                                <div class="drag-handle p-1 text-gray-400 hover:text-gray-600">
                                                    <i class="ti ti-grip-vertical"></i>
                                                </div>
                                                <h3 class="text-lg font-medium text-gray-800">Soal <?= $idx ?></h3>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <button
                                                    class="duplicate-question p-2 text-gray-400 hover:text-orange transition-colors rounded-lg hover:bg-gray-50"
                                                    title="Duplikat Soal">
                                                    <i class="ti ti-copy"></i>
                                                </button>
                                                <button
                                                    class="delete-question p-2 text-gray-400 hover:text-red-500 transition-colors rounded-lg hover:bg-gray-50"
                                                    title="Hapus Soal" data-delete-existing="1">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <!-- Question Type Selector -->
                                        <div class="mb-4">
                                            <select
                                                class="question-type-select w-full md:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white">
                                                <option value="multiple_choice" <?= $tipeValue === 'multiple_choice' ? 'selected' : ''; ?>>Pilihan Ganda</option>
                                                <option value="short_answer" <?= $tipeValue === 'short_answer' ? 'selected' : ''; ?>>Jawaban Singkat</option>
                                                <option value="long_answer" <?= $tipeValue === 'long_answer' ? 'selected' : ''; ?>>
                                                    Jawaban Panjang</option>
                                            </select>
                                        </div>
                                        <!-- Question Input -->
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Pertanyaan</label>
                                            <textarea
                                                class="question-text w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none"
                                                rows="3"
                                                placeholder="Masukkan pertanyaan..."><?= htmlspecialchars($s['pertanyaan']) ?></textarea>
                                        </div>
                                        <!-- Question Image (placeholder - belum ada fitur gambar tersimpan) -->
                                        <div class="mb-4">
                                            <button
                                                class="add-image-btn flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                                <i class="ti ti-photo"></i>
                                                <span>Tambah Gambar</span>
                                            </button>
                                            <input type="file" class="image-input hidden" accept="image/*">
                                            <div class="image-preview mt-2 hidden">
                                                <img class="max-w-full h-auto rounded-lg border border-gray-200" alt="Preview">
                                                <button class="remove-image mt-2 text-red-500 hover:text-red-700 text-sm">
                                                    <i class="ti ti-x"></i> Hapus Gambar
                                                </button>
                                            </div>
                                        </div>
                                        <!-- Answer Options (Multiple Choice) -->
                                        <div class="answer-options mb-4 <?= !$isMC ? 'hidden' : ''; ?>">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan Jawaban</label>
                                            <div class="space-y-2">
                                                <?php if ($isMC):
                                                    $letters = array_keys($pilihan);
                                                    foreach ($pilihan as $opsi => $det): ?>
                                                        <div class="flex items-center space-x-3">
                                                            <input type="radio" name="correct_answer_<?= $idx ?>"
                                                                value="<?= htmlspecialchars($opsi) ?>"
                                                                class="text-orange-500 focus:ring-orange-500" <?= $det['benar'] ? 'checked' : ''; ?>>
                                                            <span
                                                                class="w-6 text-sm font-medium text-gray-600"><?= htmlspecialchars($opsi) ?>.</span>
                                                            <input type="text"
                                                                class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                                                placeholder="Pilihan <?= htmlspecialchars($opsi) ?>"
                                                                value="<?= htmlspecialchars($det['teks']) ?>">
                                                            <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                                                <i class="ti ti-x"></i>
                                                            </button>
                                                        </div>
                                                    <?php endforeach;
                                                endif; ?>
                                            </div>
                                            <button
                                                class="add-option mt-2 text-orange hover:text-orange-600 text-sm font-medium">
                                                <i class="ti ti-plus"></i> Tambah Pilihan
                                            </button>
                                        </div>
                                        <!-- Short/Long Answer -->
                                        <div class="answer-key <?= $isMC ? 'hidden' : ''; ?> mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Kunci Jawaban</label>
                                            <textarea
                                                class="answer-key-text w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none"
                                                rows="2"
                                                placeholder="Masukkan kunci jawaban..."><?= !$isMC ? htmlspecialchars($kunci) : ''; ?></textarea>
                                        </div>
                                        <?php if ($autoScoreFlag && !$isMC): ?>
                                            <div class="absolute inset-0 flex items-center justify-center text-center p-4 z-10">
                                                <div
                                                    class="bg-white/90 backdrop-blur-sm rounded-md p-3 text-sm font-medium text-amber-800 border border-amber-400 shadow-md">
                                                    <i class="ti ti-alert-triangle mb-2 text-lg"></i><br>
                                                    <strong>Penilaian Otomatis Aktif</strong><br>
                                                    <span class="text-xs">Soal essay tidak dinilai dalam mode ini</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Points Section -->
                                        <div
                                            class="points-section mt-4 pt-4 border-t border-gray-200 <?= $autoScoreFlag ? 'hidden' : '' ?>">
                                            <div class="flex items-center space-x-2">
                                                <span class="text-sm text-gray-700">Poin:</span>
                                                <input type="number"
                                                    class="question-points w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                                    value="<?= $poin ?>" min="1" max="100">
                                                <span class="text-xs text-gray-500">poin</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Fallback: belum ada soal, tampilkan 1 kosong -->
                                <div class="question-card bg-white rounded-lg shadow-sm border border-gray-200 p-6 active"
                                    data-question-id="1">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="drag-handle p-1 text-gray-400 hover:text-gray-600">
                                                <i class="ti ti-grip-vertical"></i>
                                            </div>
                                            <h3 class="text-lg font-medium text-gray-800">Soal 1</h3>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button
                                                class="duplicate-question p-2 text-gray-400 hover:text-orange transition-colors rounded-lg hover:bg-gray-50"
                                                title="Duplikat Soal">
                                                <i class="ti ti-copy"></i>
                                            </button>
                                            <button
                                                class="delete-question p-2 text-gray-400 hover:text-red-500 transition-colors rounded-lg hover:bg-gray-50"
                                                title="Hapus Soal">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <select
                                            class="question-type-select w-full md:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white">
                                            <option value="multiple_choice">Pilihan Ganda</option>
                                            <option value="short_answer">Jawaban Singkat</option>
                                            <option value="long_answer">Jawaban Panjang</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pertanyaan</label>
                                        <textarea
                                            class="question-text w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none"
                                            rows="3" placeholder="Masukkan pertanyaan..."></textarea>
                                    </div>
                                    <div class="mb-4">
                                        <button
                                            class="add-image-btn flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                            <i class="ti ti-photo"></i>
                                            <span>Tambah Gambar</span>
                                        </button>
                                        <input type="file" class="image-input hidden" accept="image/*">
                                        <div class="image-preview mt-2 hidden">
                                            <img class="max-w-full h-auto rounded-lg border border-gray-200" alt="Preview">
                                            <button class="remove-image mt-2 text-red-500 hover:text-red-700 text-sm">
                                                <i class="ti ti-x"></i> Hapus Gambar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="answer-options mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan Jawaban</label>
                                        <div class="space-y-2">
                                            <div class="flex items-center space-x-3">
                                                <input type="radio" name="correct_answer_1" value="A"
                                                    class="text-orange-500 focus:ring-orange-500">
                                                <span class="w-6 text-sm font-medium text-gray-600">A.</span>
                                                <input type="text"
                                                    class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                                    placeholder="Pilihan A">
                                                <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                                    <i class="ti ti-x"></i>
                                                </button>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <input type="radio" name="correct_answer_1" value="B"
                                                    class="text-orange-500 focus:ring-orange-500">
                                                <span class="w-6 text-sm font-medium text-gray-600">B.</span>
                                                <input type="text"
                                                    class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                                    placeholder="Pilihan B">
                                                <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                                    <i class="ti ti-x"></i>
                                                </button>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <input type="radio" name="correct_answer_1" value="C"
                                                    class="text-orange-500 focus:ring-orange-500">
                                                <span class="w-6 text-sm font-medium text-gray-600">C.</span>
                                                <input type="text"
                                                    class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                                    placeholder="Pilihan C">
                                                <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                                    <i class="ti ti-x"></i>
                                                </button>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <input type="radio" name="correct_answer_1" value="D"
                                                    class="text-orange-500 focus:ring-orange-500">
                                                <span class="w-6 text-sm font-medium text-gray-600">D.</span>
                                                <input type="text"
                                                    class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                                    placeholder="Pilihan D">
                                                <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                                    <i class="ti ti-x"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <button
                                            class="add-option mt-2 text-orange hover:text-orange-600 text-sm font-medium">
                                            <i class="ti ti-plus"></i> Tambah Pilihan
                                        </button>
                                    </div>
                                    <div class="answer-key hidden mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Kunci Jawaban</label>
                                        <textarea
                                            class="answer-key-text w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none"
                                            rows="2" placeholder="Masukkan kunci jawaban..."></textarea>
                                    </div>

                                    <!-- Points Section -->
                                    <div class="points-section mt-4 pt-4 border-t border-gray-200">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-700">Poin:</span>
                                            <input type="number"
                                                class="question-points w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                                value="10" min="1" max="100">
                                            <span class="text-xs text-gray-500">poin</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sidebar Tools (1/4 width on lg screens) -->
                    <div class="lg:col-span-1 space-y-4">
                        <div class="sidebar-tools bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Tools</h3>

                            <!-- Add Question -->
                            <button id="add-question-btn"
                                class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors mb-3">
                                <i class="ti ti-plus"></i>
                                <span>Tambah Soal</span>
                            </button>

                            <!-- Pingo AI Helper -->
                            <button id="pingo-ai-btn" command="show-modal" commandfor="add-soal-ai"
                                class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-orange to-orange-600 text-white rounded-lg hover:from-orange-600 hover:to-red-500 mb-3">
                                <i class="ti ti-sparkles"></i>
                                <span>Bantu dengan Pingo AI</span>
                            </button>

                            <!-- import dari word -->
                            <button id="import-word-btn" onclick="openImportModal()"
                                class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-orange to-orange-600 text-white rounded-lg hover:from-orange-600 hover:to-red-500 mb-3">
                                <i class="ti ti-file-import"></i>
                                <span>Import dari Word</span>
                            </button>

                            <!-- Add Description -->
                            <button id="add-description-btn"
                                class="w-full flex items-center justify-center space-x-2 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors mb-3">
                                <i class="ti ti-text"></i>
                                <span>Tambah Deskripsi</span>
                            </button>

                            <!-- Question Navigation -->
                            <div class="mt-6">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Navigasi Soal</h4>
                                <div id="question-nav" class="grid grid-cols-5 gap-2">
                                    <?php if (!empty($soalList)):
                                        foreach ($soalList as $i => $s):
                                            $n = $i + 1;
                                            $isActive = !$autoScoreFlag || $s['tipeSoal'] === 'pilihan_ganda';
                                            ?>
                                            <button
                                                class="question-nav-item flex items-center justify-center aspect-square text-sm rounded-lg border <?= $n === 1 ? 'border-orange bg-orange-50 text-orange font-semibold' : ($isActive ? 'border-gray-200 text-gray-700 hover:bg-gray-50' : 'border-gray-300 bg-gray-100 text-gray-400'); ?>"
                                                data-question="<?= $n ?>"
                                                title="Soal <?= $n ?><?= !$isActive ? ' (Non-aktif)' : '' ?>">
                                                <?= $n ?>
                                                <?php if (!$isActive): ?>
                                                    <span class="absolute -top-1 -right-1 w-2 h-2 bg-amber-400 rounded-full"></span>
                                                <?php endif; ?>
                                            </button>
                                        <?php endforeach;
                                    else: ?>
                                        <button
                                            class="question-nav-item flex items-center justify-center aspect-square text-sm rounded-lg border border-orange bg-orange-50 text-orange font-semibold"
                                            data-question="1" title="Soal 1">1</button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Statistik</h4>
                                <div class="space-y-2 text-sm">
                                    <?php if ($autoScoreFlag): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Soal Aktif:</span>
                                            <span id="total-questions"
                                                class="font-medium"><?= count(array_filter($soalList, fn($s) => $s['tipeSoal'] === 'pilihan_ganda')) ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Total Soal:</span>
                                            <span class="font-medium text-gray-500"><?= count($soalList) ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Poin:</span>
                                            <span id="total-points" class="font-medium">100</span>
                                        </div>
                                        <div class="text-xs text-amber-600 mt-2">
                                            <i class="ti ti-info-circle mr-1"></i>
                                            Hanya soal pilihan ganda yang dinilai
                                        </div>
                                    <?php else: ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Total Soal:</span>
                                            <span id="total-questions" class="font-medium"><?= count($soalList) ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Total Poin:</span>
                                            <span id="total-points"
                                                class="font-medium"><?= array_sum(array_column($soalList, 'poin')) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="buat-ujian-guru.php?ujian_id=<?= (int) $ujian['id'] ?>"
                        class="px-6 py-3 border border-gray-200 text-gray-600 rounded-lg hover:bg-amber-50 transition-colors font-medium text-center">
                        Edit Identitas Ujian
                    </a>
                    <button id="preview-exam-btn"
                        class="px-6 py-3 border border-gray-200 text-gray-600 rounded-lg hover:bg-blue-50 transition-colors font-medium">
                        Preview Ujian
                    </button>
                    <button id="save-draft-btn"
                        class="px-6 py-3 border bg-orange text-white rounded-lg hover:bg-orange-50 transition-colors font-medium">
                        Simpan Ujian
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="../script/menu-bar-script.js"></script>
    <script src="../script/modal-delete-question.js"></script>
    <script src="../script/buat-soal.js"></script>
    <script src="../pingo/pingo-modal.js"></script>
    <script>
        // Show modal when Pingo button is clicked
        document.addEventListener('DOMContentLoaded', function () {
            const pingoBtn = document.getElementById('pingo-ai-btn');

            if (pingoBtn) {
                pingoBtn.addEventListener('click', function (e) {
                    e.preventDefault();

                    // Wait for pingo-modal.js to load and expose the function
                    if (typeof window.openPingoModal === 'function') {
                        window.openPingoModal();
                    } else {
                        // Fallback if function not available yet
                        setTimeout(() => {
                            if (typeof window.openPingoModal === 'function') {
                                window.openPingoModal();
                            } else {
                                console.error('PingoAI modal function not available');
                            }
                        }, 100);
                    }
                });
            }
        });

        function showToast(message, type = 'info') {
            const colors = {
                success: 'bg-green-600',
                error: 'bg-red-600',
                info: 'bg-blue-600',
                warning: 'bg-yellow-600 text-gray-900'
            };
            let c = document.getElementById('toast-container');
            if (!c) {
                c = document.createElement('div');
                c.id = 'toast-container';
                c.className = 'fixed top-4 right-4 space-y-3 z-[10100]';
                document.body.appendChild(c);
            }
            const el = document.createElement('div');
            el.className = `toast flex items-start text-sm text-white px-4 py-3 rounded-lg shadow-lg backdrop-blur-md bg-opacity-90 ${colors[type] || colors.info} animate-fade-in`;
            el.innerHTML = `<div class='mr-3 mt-0.5'><i class="ti ${type === 'success' ? 'ti-check' : type === 'error' ? 'ti-alert-circle' : type === 'warning' ? 'ti-alert-triangle' : 'ti-info-circle'}"></i></div><div class='flex-1'>${message}</div><button class='ml-3 text-white/80 hover:text-white' onclick='this.parentElement.remove()'><i class="ti ti-x"></i></button>`;
            c.appendChild(el);
            setTimeout(() => {
                el.classList.add('opacity-0', 'translate-x-2');
                setTimeout(() => el.remove(), 300);
            }, 4000);
        }
        (function () {
            const p = new URLSearchParams(location.search);
            if (p.get('created') === '1') showToast('Identitas ujian dibuat. Tambahkan soal sekarang.', 'success');
            if (p.get('updated') === '1') showToast('Identitas ujian diperbarui.', 'success');
            if (p.get('created') || p.get('updated')) {
                const url = new URL(location.href);
                ['created', 'updated'].forEach(k => url.searchParams.delete(k));
                history.replaceState({}, '', url);
            }
        })();
    </script>

    <!-- Import Word Modal -->
    <?php require '../component/modal-import-word.php'; ?>

    <!-- Dynamic Modal Component -->
    <?php require '../component/modal-dynamic.php'; ?>
</body>

</html>