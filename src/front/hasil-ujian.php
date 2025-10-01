<?php
session_start();

// Redirect jika belum login atau bukan guru
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    header('Location: ../../login.php');
    exit();
}

require_once '../logic/ujian-logic.php';
require_once '../logic/soal-logic.php';

$ujianLogic = new UjianLogic();
$soalLogic = new SoalLogic();

$ujian_id = (int)($_GET['ujian_id'] ?? 0);
$guru_id = $_SESSION['user']['id'];

// Validasi ujian milik guru
$ujian = $ujianLogic->getUjianByIdAndGuru($ujian_id, $guru_id);
if (!$ujian) {
    header('Location: ujian-guru.php');
    exit();
}

// Ambil soal ujian
$soalList = $soalLogic->getSoalByUjian($ujian_id);

// Ambil hasil ujian siswa
$hasilUjian = $ujianLogic->getHasilUjian($ujian_id);

// Debug: uncomment untuk melihat struktur data
// echo '<pre>'; var_dump($hasilUjian); echo '</pre>'; exit;

// Mode koreksi yang dipilih
$mode_koreksi = $_GET['mode'] ?? 'tabel';
$koreksi_id = (int)($_GET['koreksi_id'] ?? 0);

$autoScore = (int)($ujian['autoScore'] ?? 0);
?>

<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Hasil Ujian - <?= htmlspecialchars($ujian['namaUjian']) ?></title>
    <style>
        /* Orange color class */
        .text-orange {
            color: #f97316;
        }

        .bg-orange {
            background-color: #f97316;
        }

        .border-orange {
            border-color: #f97316;
        }

        .hover\:bg-orange-600:hover {
            background-color: #ea580c;
        }

        /* Modern Button Styles */
        .btn-orange {
            background: #f97316;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-orange:hover {
            background: #ea580c;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(249, 115, 22, 0.3);
        }

        /* Table Styles */
        .results-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .results-table th {
            background: #f8fafc;
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            border-bottom: 1px solid #e5e7eb;
        }

        .results-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        .results-table tbody tr:hover {
            background: #f9fafb;
        }

        /* Score Badge */
        .score-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .score-excellent {
            background: #dcfce7;
            color: #166534;
        }

        .score-good {
            background: #dbeafe;
            color: #1e40af;
        }

        .score-average {
            background: #fef3c7;
            color: #92400e;
        }

        .score-poor {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-selesai {
            background: #dcfce7;
            color: #166534;
        }

        .status-sedang {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-belum {
            background: #fef3c7;
            color: #92400e;
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-dialog {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-dialog > div {
            transform: translateY(-20px) scale(0.95);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-dialog > div {
            transform: translateY(0) scale(1);
        }

        .modal-content {
            background: white;
            margin: 2% auto;
            padding: 24px;
            width: 90%;
            max-width: 800px;
            border-radius: 12px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        /* Dialog Styles */
        dialog {
            margin: auto;
            padding: 0;
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        dialog::backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .close {
            float: right;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: #6b7280;
            transition: color 0.2s ease;
        }

        .close:hover {
            color: #374151;
        }

        /* Question Card */
        .question-item {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            background: white;
            transition: all 0.2s ease;
        }

        .question-item:hover {
            border-color: #d1d5db;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* Progress Bar */
        .progress-container {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #f97316, #ea580c);
            border-radius: 8px;
            transition: width 0.3s ease;
        }

        /* Form Controls */
        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        /* Toast */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 16px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        }

        .toast-success {
            background: #10b981;
        }

        .toast-error {
            background: #ef4444;
        }

        /* Mode Buttons */
        .mode-btn-active {
            background-color: #f97316 !important;
            color: white !important;
            border-color: #f97316 !important;
        }

        .mode-btn-active:hover {
            background-color: #ea580c !important;
            border-color: #ea580c !important;
        }

        /* Dropdown */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 280px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            z-index: 1;
            top: 100%;
            left: 0;
            margin-top: 4px;
        }

        .dropdown-content.show {
            display: block;
        }

        .dropdown-item {
            color: #374151;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background-color: #f9fafb;
        }

        .dropdown-item .item-title {
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }

        .dropdown-item .item-desc {
            font-size: 12px;
            color: #6b7280;
        }

        /* Student Navigation */
        .student-nav {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        /* Form Cards */
        .question-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .question-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .question-number {
            display: inline-block;
            background: #f97316;
            color: white;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .question-text {
            font-size: 16px;
            font-weight: 500;
            color: #111827;
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .answer-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .student-answer {
            font-size: 14px;
            color: #374151;
            white-space: pre-wrap;
            min-height: 40px;
        }

        .scoring-section {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }

        .scoring-section.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .point-input {
            width: 80px;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            text-align: center;
        }

        /* Sidebar Updates */
        .sidebar-section {
            background: white;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 12px 16px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            font-size: 14px;
            color: #374151;
        }

        .student-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .student-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .student-item:hover {
            background: #f9fafb;
        }

        .student-item.active {
            background: #fef3e2;
            border-left: 3px solid #f97316;
        }

        .student-item:last-child {
            border-bottom: none;
        }

        .student-name {
            font-size: 14px;
            color: #111827;
        }

        .student-score {
            font-size: 12px;
            color: #6b7280;
            margin-left: auto;
        }

        .scroll-controls {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 8px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }

        .scroll-btn {
            padding: 4px 8px;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }

        .scroll-btn:hover {
            background: #f3f4f6;
        }

        /* Back Button */
        .mode-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .back-btn {
            padding: 8px 16px;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 6px;
            color: #374151;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .back-btn:hover {
            background: #f9fafb;
            color: #111827;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .modal-content {
                margin: 5% auto;
                width: 95%;
                padding: 16px;
            }

            .results-table th,
            .results-table td {
                padding: 12px 8px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body class="bg-gray-50">

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 space-y-3 z-[10000]"></div>

    <div data-main-content class="md:ml-64 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">
        <!-- Header -->
        <header class="bg-white p-4 md:p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="detail-ujian-guru.php?id=<?= $ujian_id ?>" class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-gray-100">
                        <i class="ti ti-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Hasil Ujian</h1>
                        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($ujian['namaUjian']) ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <?php if ($autoScore): ?>
                        <span class="hidden sm:inline-flex items-center text-xs px-3 py-1 rounded-full bg-amber-100 text-amber-700 font-medium">Auto Score</span>
                    <?php endif; ?>
                    <span class="text-xs px-3 py-1 rounded-full font-medium uppercase tracking-wide bg-blue-100 text-blue-700 ring-1 ring-blue-200">Koreksi</span>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Main Content (col-span-3) -->
                    <div class="lg:col-span-3 space-y-6">

                        <!-- Panel Kontrol -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="ti ti-settings text-orange mr-2"></i>
                                Panel Kontrol
                            </h2>
                            <div class="flex flex-wrap gap-3">
                                <button onclick="periksaOtomatisPG()" class="btn-orange">
                                    <i class="ti ti-check-text"></i>
                                    <span>Periksa Otomatis PG</span>
                                </button>

                                <!-- Dropdown Koreksi Ujian -->
                                <div class="dropdown">
                                    <button onclick="toggleDropdown()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                                        <i class="ti ti-edit"></i>
                                        Koreksi Ujian
                                        <i class="ti ti-chevron-down ml-1"></i>
                                    </button>
                                    <div id="dropdown-content" class="dropdown-content">
                                        <div class="dropdown-item" onclick="switchToSwipeMode()">
                                            <div class="item-title">
                                                <i class="ti ti-swipe mr-2"></i>Mode Swipe
                                            </div>
                                            <div class="item-desc">Koreksi satu per satu dengan navigasi swipe, cocok untuk soal essay</div>
                                        </div>
                                        <div class="dropdown-item" onclick="switchToFormulirMode()">
                                            <div class="item-title">
                                                <i class="ti ti-forms mr-2"></i>Mode Formulir
                                            </div>
                                            <div class="item-desc">Koreksi menggunakan formulir lengkap dengan detail jawaban</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($mode_koreksi === 'swipe'): ?>
                            <!-- Mode Swipe untuk Koreksi -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <div class="mode-header">
                                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                        <i class="ti ti-swipe text-orange mr-2"></i>
                                        Mode Koreksi Swipe
                                    </h2>
                                    <a href="?ujian_id=<?= $ujian_id ?>" class="back-btn">
                                        <i class="ti ti-arrow-left"></i>
                                        Kembali ke Tabel
                                    </a>
                                </div>
                                
                                <!-- Student Navigation -->
                                <div class="student-nav">
                                    <button onclick="prevStudent()" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors" id="btn-prev-student">
                                        <i class="ti ti-chevron-left"></i>
                                        Sebelumnya
                                    </button>
                                    <div class="flex-1 text-center">
                                        <span class="font-medium text-gray-800" id="current-student-name">Loading...</span>
                                        <div class="text-sm text-gray-500" id="student-progress">0 / 0</div>
                                    </div>
                                    <button onclick="nextStudent()" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors" id="btn-next-student">
                                        Selanjutnya
                                        <i class="ti ti-chevron-right"></i>
                                    </button>
                                </div>
                                
                                <div class="progress-container">
                                    <div class="progress-bar" id="progress-bar" style="width: 0%"></div>
                                </div>
                                
                                <!-- Swipe Container with Arrow Navigation -->
                                <div class="flex items-center gap-4">
                                    <!-- Left Arrow -->
                                    <button onclick="prevSwipeCard()" class="p-3 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm" id="btn-prev-swipe">
                                        <i class="ti ti-chevron-left text-xl text-gray-600"></i>
                                    </button>
                                    
                                    <!-- Main Card Container -->
                                    <div class="flex-1 relative bg-gray-50 rounded-lg p-6 min-h-[400px]" id="swipe-container">
                                        <!-- Cards will be populated by JavaScript -->
                                        <div class="text-center text-gray-500">Memuat data koreksi...</div>
                                    </div>
                                    
                                    <!-- Right Arrow -->
                                    <button onclick="nextSwipeCard()" class="p-3 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm" id="btn-next-swipe">
                                        <i class="ti ti-chevron-right text-xl text-gray-600"></i>
                                    </button>
                                </div>
                                <div class="flex justify-center space-x-4 mt-4">
                                    <button onclick="swipeAnswer(false)" class="px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors font-medium">
                                        <i class="ti ti-x"></i> Salah
                                    </button>
                                    <button onclick="swipeAnswer(true)" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium">
                                        <i class="ti ti-check"></i> Benar
                                    </button>
                                    <button onclick="showScoreInput()" class="px-6 py-3 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors font-medium">
                                        <i class="ti ti-edit"></i> Input Nilai
                                    </button>
                                </div>
                                
                                <!-- Keyboard Shortcuts Info -->
                                <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="text-xs text-gray-600 text-center">
                                        <span class="font-semibold">Keyboard Shortcuts:</span>
                                        <div class="flex justify-center gap-6 mt-1 flex-wrap">
                                            <span><kbd class="px-1 py-0.5 bg-white border rounded text-xs">↑/↓</kbd> Navigasi</span>
                                            <span><kbd class="px-1 py-0.5 bg-white border rounded text-xs">←</kbd> Salah</span>
                                            <span><kbd class="px-1 py-0.5 bg-white border rounded text-xs">→</kbd> Benar</span>
                                            <span><kbd class="px-1 py-0.5 bg-white border rounded text-xs">Alt+↓</kbd> Input Nilai</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($mode_koreksi === 'formulir'): ?>
                            <!-- Mode Formulir untuk Koreksi -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <div class="mode-header">
                                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                        <i class="ti ti-forms text-orange mr-2"></i>
                                        Mode Koreksi Formulir
                                    </h2>
                                    <a href="?ujian_id=<?= $ujian_id ?>" class="back-btn">
                                        <i class="ti ti-arrow-left"></i>
                                        Kembali ke Tabel
                                    </a>
                                </div>
                                
                                <!-- Student Navigation -->
                                <div class="student-nav">
                                    <button onclick="prevFormStudent()" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors" id="btn-prev-form-student">
                                        <i class="ti ti-chevron-left"></i>
                                        Sebelumnya
                                    </button>
                                    <div class="flex-1 text-center">
                                        <span class="font-medium text-gray-800" id="current-form-student-name">Loading...</span>
                                        <div class="text-sm text-gray-500" id="form-student-progress">0 / 0</div>
                                    </div>
                                    <button onclick="nextFormStudent()" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors" id="btn-next-form-student">
                                        Selanjutnya
                                        <i class="ti ti-chevron-right"></i>
                                    </button>
                                </div>
                                
                                <div id="form-koreksi">
                                    <div class="text-center text-gray-500 py-8">Memuat formulir koreksi...</div>
                                </div>
                                
                                <!-- Save Button -->
                                <div class="mt-6 text-right">
                                    <button onclick="saveFormulirScores()" class="btn-orange px-8 py-3">
                                        <i class="ti ti-device-floppy"></i>
                                        <span>Simpan Nilai</span>
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Tabel Hasil Ujian (Default) -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="ti ti-table text-orange mr-2"></i>
                                    Hasil Ujian Siswa
                                    <span class="ml-2 text-xs font-medium text-gray-500">(<?= count($hasilUjian) ?> Siswa)</span>
                                </h2>

                                <!-- Info Ujian -->
                                <div class="mb-6 p-4 bg-gray-100 border border-gray-200 rounded-lg">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                        <div class="flex items-start gap-3">
                                            <i class="ti ti-file-text w-6 h-6 text-blue-700 flex-shrink-0"></i>
                                            <div>
                                                <div class="text-xs text-blue-700 font-semibold">Nama Ujian</div>
                                                <div class="text-sm text-blue-900 font-medium"><?= htmlspecialchars($ujian['namaUjian']) ?></div>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-3">
                                            <i class="ti ti-list w-6 h-6 text-blue-700 flex-shrink-0"></i>
                                            <div>
                                                <div class="text-xs text-blue-700 font-semibold">Total Soal</div>
                                                <div class="text-sm text-blue-900 font-medium"><?= count($soalList) ?> soal</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (empty($hasilUjian)): ?>
                                    <div class="p-8 border border-dashed rounded-lg text-center text-gray-500 bg-gray-50">
                                        <i class="ti ti-clipboard-off text-4xl mb-4 text-gray-400"></i>
                                        <p class="text-lg font-medium mb-2">Belum Ada Hasil Ujian</p>
                                        <p class="text-sm mb-4">Belum ada siswa yang mengerjakan ujian ini.</p>
                                        <div class="text-xs text-gray-400 space-y-1">
                                            <p>• Data akan muncul setelah siswa mulai mengerjakan ujian</p>
                                            <p>• Pastikan ujian sudah dalam status "aktif"</p>
                                            <p>• Siswa harus terdaftar di kelas yang sama dengan ujian</p>
                                        </div>
                                        <div class="mt-4">
                                            <a href="../../debug-hasil-ujian.php?ujian_id=<?= $ujian_id ?>"
                                                class="text-xs text-blue-600 hover:text-blue-800 underline"
                                                target="_blank">
                                                🔍 Debug Data (Development Mode)
                                            </a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Desktop Table View (hidden on mobile) -->
                                    <div class="hidden md:block overflow-x-auto border border-gray-200 rounded-lg">
                                        <table class="results-table">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Nama Siswa</th>
                                                    <th>Benar</th>
                                                    <th>Salah / Kosong</th>
                                                    <th>Status</th>
                                                    <th>Nilai</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $no = 1;
                                                foreach ($hasilUjian as $hasil): ?>
                                                    <tr>
                                                        <td><?= $no++ ?></td>
                                                        <td>
                                                            <div class="flex items-center">
                                                                <div class="w-8 h-8 rounded-full overflow-hidden mr-3 flex-shrink-0">
                                                                    <?php
                                                                    $nama = $hasil['namaLengkap'] ?? 'Unknown User';
                                                                    if (isset($hasil['fotoProfil']) && !empty($hasil['fotoProfil'])): 
                                                                        $photoPath = '';
                                                                        if (strpos($hasil['fotoProfil'], 'uploads/profile/') === 0) {
                                                                            $photoPath = '../../' . $hasil['fotoProfil'];
                                                                        } else {
                                                                            $photoPath = '../../uploads/profile/' . $hasil['fotoProfil'];
                                                                        }
                                                                    ?>
                                                                        <img src="<?= htmlspecialchars($photoPath) ?>" 
                                                                             alt="Profile Photo" 
                                                                             class="w-full h-full object-cover"
                                                                             onerror="this.parentElement.innerHTML='<div class=\'w-full h-full bg-orange text-white rounded-full flex items-center justify-center text-sm font-medium\'><?= strtoupper(substr($nama, 0, 1)) ?></div>'">
                                                                    <?php else: ?>
                                                                        <div class="w-full h-full bg-orange text-white rounded-full flex items-center justify-center text-sm font-medium">
                                                                            <?= strtoupper(substr($nama, 0, 1)) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div>
                                                                    <span class="font-medium text-gray-800"><?= htmlspecialchars($nama) ?></span>
                                                                    <?php if (isset($hasil['siswa_id'])): ?>
                                                                        <div class="text-xs text-gray-500">ID: <?= $hasil['siswa_id'] ?></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php $jumlahBenar = (int)($hasil['jumlahBenar'] ?? 0); ?>
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                <?= $jumlahBenar ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="flex gap-2 items-center justify-center">
                                                                <?php
                                                                $jumlahSalah = (int)($hasil['jumlahSalah'] ?? 0);
                                                                $totalSoal = count($soalList);
                                                                $tidakDijawab = $totalSoal - $jumlahBenar - $jumlahSalah;
                                                                ?>
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                    <i class="text-xs mr-1"></i>
                                                                    <?= $jumlahSalah ?>
                                                                </span>
                                                                <span>/</span>
                                                                <?php if ($tidakDijawab > 0): ?>
                                                                    <span class="inline-flex items-center mt-1 px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                                        <i class="text-xs mr-1"></i>
                                                                        <?= $tidakDijawab ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $status = $hasil['status'] ?? 'belum';
                                                            $statusClass = $status === 'selesai' ? 'status-selesai' : ($status === 'sedang_mengerjakan' ? 'status-sedang' : 'status-belum');
                                                            ?>
                                                            <span class="status-badge <?= $statusClass ?>">
                                                                <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $nilai = (float)($hasil['totalNilai'] ?? 0);
                                                            $scoreClass = $nilai >= 85 ? 'score-excellent' : ($nilai >= 70 ? 'score-good' : ($nilai >= 60 ? 'score-average' : 'score-poor'));
                                                            ?>
                                                            <span class="score-badge <?= $scoreClass ?>">
                                                                <?= number_format($nilai, 1) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="detail-jawaban-guru.php?ujian_id=<?= $ujian_id ?>&ujian_siswa_id=<?= (int)($hasil['id'] ?? 0) ?>"
                                                                class="inline-flex items-center px-3 py-1 text-xs bg-orange text-white rounded hover:bg-orange-600 transition-colors">
                                                                <i class="ti ti-eye mr-1"></i> Detail
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php $no++; endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Mobile Card View (hidden on desktop) -->
                                    <div class="md:hidden space-y-4">
                                        <?php $no = 1;
                                        foreach ($hasilUjian as $hasil): ?>
                                            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                                <!-- Header dengan nama siswa -->
                                                <div class="flex items-center justify-between mb-3">
                                                    <div class="flex items-center">
                                                        <div class="w-10 h-10 rounded-full overflow-hidden mr-3 flex-shrink-0">
                                                            <?php
                                                            $nama = $hasil['namaLengkap'] ?? 'Unknown User';
                                                            if (isset($hasil['fotoProfil']) && !empty($hasil['fotoProfil'])): 
                                                                $photoPath = '';
                                                                if (strpos($hasil['fotoProfil'], 'uploads/profile/') === 0) {
                                                                    $photoPath = '../../' . $hasil['fotoProfil'];
                                                                } else {
                                                                    $photoPath = '../../uploads/profile/' . $hasil['fotoProfil'];
                                                                }
                                                            ?>
                                                                <img src="<?= htmlspecialchars($photoPath) ?>" 
                                                                     alt="Profile Photo" 
                                                                     class="w-full h-full object-cover"
                                                                     onerror="this.parentElement.innerHTML='<div class=\'w-full h-full bg-orange text-white rounded-full flex items-center justify-center text-sm font-bold\'><?= strtoupper(substr($nama, 0, 1)) ?></div>'">
                                                            <?php else: ?>
                                                                <div class="w-full h-full bg-orange text-white rounded-full flex items-center justify-center text-sm font-bold">
                                                                    <?= strtoupper(substr($nama, 0, 1)) ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <div class="font-medium text-gray-900 text-sm"><?= htmlspecialchars($nama) ?></div>
                                                            <?php if (isset($hasil['siswa_id'])): ?>
                                                                <div class="text-xs text-gray-500">ID: <?= $hasil['siswa_id'] ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="text-xs font-medium text-gray-500">#<?= $no++ ?></div>
                                                </div>

                                                <!-- Status dan Nilai -->
                                                <div class="flex items-center gap-2 justify-end mb-3">
                                                    <div class="flex items-center space-x-2">
                                                        <?php
                                                        $status = $hasil['status'] ?? 'belum';
                                                        $statusClass = $status === 'selesai' ? 'status-selesai' : ($status === 'sedang_mengerjakan' ? 'status-sedang' : 'status-belum');
                                                        ?>
                                                        <span class="status-badge <?= $statusClass ?>">
                                                            <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                                        </span>
                                                    </div>
                                                    <div class="text-right">
                                                        <?php
                                                        $nilai = (float)($hasil['totalNilai'] ?? 0);
                                                        $scoreClass = $nilai >= 85 ? 'score-excellent' : ($nilai >= 70 ? 'score-good' : ($nilai >= 60 ? 'score-average' : 'score-poor'));
                                                        ?>
                                                        <span class="score-badge <?= $scoreClass ?> text-lg font-bold">
                                                            <?= number_format($nilai, 1) ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Statistik Jawaban -->
                                                <div class="grid grid-cols-3 gap-3 mb-4">
                                                    <div class="text-center p-2 bg-green-50 rounded-lg border border-green-200">
                                                        <?php $jumlahBenar = (int)($hasil['jumlahBenar'] ?? 0); ?>
                                                        <div class="text-green-700 font-bold text-lg"><?= $jumlahBenar ?></div>
                                                        <div class="text-xs text-green-600 flex items-center justify-center">
                                                            Benar
                                                        </div>
                                                    </div>
                                                    <div class="text-center p-2 bg-red-50 rounded-lg border border-red-200">
                                                        <?php
                                                        $jumlahSalah = (int)($hasil['jumlahSalah'] ?? 0);
                                                        ?>
                                                        <div class="text-red-700 font-bold text-lg"><?= $jumlahSalah ?></div>
                                                        <div class="text-xs text-red-600 flex items-center justify-center">
                                                            Salah
                                                        </div>
                                                    </div>
                                                    <div class="text-center p-2 bg-gray-50 rounded-lg border border-gray-200">
                                                        <?php
                                                        $totalSoal = count($soalList);
                                                        $tidakDijawab = $totalSoal - $jumlahBenar - $jumlahSalah;
                                                        ?>
                                                        <div class="text-gray-700 font-bold text-lg"><?= $tidakDijawab ?></div>
                                                        <div class="text-xs text-gray-600 flex items-center justify-center">
                                                            Kosong
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Tombol Detail -->
                                                <div class="pt-2 border-t border-gray-100">
                                                    <a href="detail-jawaban-guru.php?ujian_id=<?= $ujian_id ?>&ujian_siswa_id=<?= (int)($hasil['id'] ?? 0) ?>"
                                                        class="w-full px-4 py-2 text-sm bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors font-medium flex items-center justify-center">
                                                        <i class="ti ti-eye mr-2"></i>
                                                        Lihat Detail
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar (col-span-1) -->
                    <div class="lg:col-span-1">
                        <?php if ($mode_koreksi === 'swipe' || $mode_koreksi === 'formulir'): ?>
                            <!-- Sidebar untuk Mode Koreksi -->
                            <div class="sidebar-section">
                                <div class="sidebar-header">
                                    <i class="ti ti-chart-bar mr-2"></i>
                                    Ringkasan Hasil Ujian
                                </div>
                                <div class="p-4">
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Total Siswa</span>
                                            <span class="font-medium" id="total-siswa"><?= count($hasilUjian) ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Selesai Dinilai</span>
                                            <span class="font-medium text-green-600" id="selesai-dinilai">0</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Belum Dinilai</span>
                                            <span class="font-medium text-red-600" id="belum-dinilai"><?= count($hasilUjian) ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Total Soal</span>
                                            <span class="font-medium"><?= count($soalList) ?></span>
                                        </div>
                                        <?php if ($autoScore): ?>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Auto Score</span>
                                                <span class="font-medium text-amber-600">Aktif</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- List Siswa untuk Koreksi -->
                            <div class="sidebar-section">
                                <div class="sidebar-header">
                                    <i class="ti ti-users mr-2"></i>
                                    Daftar Siswa
                                </div>
                                <div class="student-list" id="student-list">
                                    <div class="text-center text-gray-500 py-4 text-sm">
                                        Memuat daftar siswa...
                                    </div>
                                </div>
                                <div class="scroll-controls">
                                    <button class="scroll-btn" onclick="scrollStudentList('up')">
                                        <i class="ti ti-chevron-up"></i>
                                    </button>
                                    <button class="scroll-btn" onclick="scrollStudentList('down')">
                                        <i class="ti ti-chevron-down"></i>
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Sidebar Default untuk Mode Tabel -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                                <h3 class="text-lg font-medium text-gray-800 mb-4">Ringkasan Hasil Ujian</h3>

                                <!-- Statistik -->
                                <div class="">
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Total Siswa</span>
                                            <span class="font-medium"><?= count($hasilUjian) ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Selesai</span>
                                            <span class="font-medium text-green-600">
                                                <?= count(array_filter($hasilUjian, function ($h) {
                                                    return ($h['status'] ?? '') === 'selesai';
                                                })) ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Sedang Mengerjakan</span>
                                            <span class="font-medium text-blue-600">
                                                <?= count(array_filter($hasilUjian, function ($h) {
                                                    return ($h['status'] ?? '') === 'sedang_mengerjakan';
                                                })) ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Rata-rata Nilai</span>
                                            <span class="font-medium">
                                                <?php
                                                $nilaiSelesai = array_filter($hasilUjian, function ($h) {
                                                    return ($h['status'] ?? '') === 'selesai';
                                                });
                                                $rataRata = empty($nilaiSelesai) ? 0 : array_sum(array_column($nilaiSelesai, 'totalNilai')) / count($nilaiSelesai);
                                                echo number_format($rataRata, 1);
                                                ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Total Soal</span>
                                            <span class="font-medium"><?= count($soalList) ?></span>
                                        </div>
                                        <?php if ($autoScore): ?>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Auto Score</span>
                                                <span class="font-medium text-amber-600">Aktif</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Detail Jawaban -->
    <div id="modal-detail" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 class="text-xl font-bold mb-4">Detail Jawaban Siswa</h2>
            <div id="detail-content">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal Input Nilai -->
    <div id="modal-score" class="modal" style="display: none;">
        <div class="modal-backdrop" onclick="closeScoreModal()"></div>
        <div class="modal-dialog">
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                <div class="bg-white px-5 pt-6 pb-5 sm:p-7 sm:pb-5">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-14 bg-orange-100 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:size-12">
                            <span class="ti ti-edit text-xl text-orange-600"></span>
                        </div>
                        <div class="mt-4 text-center sm:mt-0 sm:ml-5 sm:text-left">
                            <h3 id="score-dialog-title" class="text-lg font-semibold text-gray-900">Input Nilai</h3>
                            <div class="">
                                <p class="text-base text-gray-500">Masukkan nilai untuk jawaban siswa</p>
                                <p id="score-soal-info" class="text-sm text-gray-700 mt-1 font-medium"></p>
                            </div>
                        </div>
                    </div>

                    <form id="score-form" class="mt-6" onsubmit="saveManualScore(event)">
                        <div class="space-y-5">
                            <div>
                                <label for="score-input" class="block text-base font-medium text-gray-700 mb-2" id="score-label">
                                    Nilai (0-100)
                                </label>
                                <input type="number" id="score-input" name="score" 
                                    min="0" max="100" step="0.1" required
                                    class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-base"
                                    placeholder="Masukkan nilai">
                                <div class="text-left mt-1 text-xs text-amber-700">
                                    <i class="ti ti-info-circle mr-1"></i>
                                    <span id="score-info-text">Nilai maksimal untuk soal ini adalah 100 poin</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="bg-gray-50 px-5 py-5 sm:px-6 flex gap-3">
                    <button type="button" onclick="closeScoreModal()"
                        class="flex-1 inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        Batal
                    </button>
                    <button type="submit" form="score-form"
                        class="flex-1 inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-3 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        <i class="ti ti-check mr-2"></i>
                        Simpan Nilai
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentSwipeData = [];
        let currentSwipeIndex = 0;
        let currentKoreksiData = null;
        const API_URL = '../logic/hasil-ujian-api.php';
        const ujianId = <?= $ujian_id ?>;

        // Helper function to format numbers (remove unnecessary decimal places)
        function formatNumber(value) {
            if (value === null || value === undefined || value === '') {
                return 0;
            }
            const num = parseFloat(value);
            if (isNaN(num)) return 0;
            
            // If it's a whole number, return without decimals
            if (num % 1 === 0) {
                return num.toString();
            }
            // Otherwise, keep up to 2 decimal places but remove trailing zeros
            return num.toFixed(2).replace(/\.?0+$/, '');
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            document.getElementById('toast-container').appendChild(toast);

            setTimeout(() => toast.remove(), 3000);
        }

        // API call helper
        async function apiCall(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('ujian_id', ujianId);

            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                return {
                    success: false,
                    message: 'Network error'
                };
            }
        }

        // Periksa otomatis pilihan ganda
        async function periksaOtomatisPG() {
            if (confirm('Yakin ingin memproses semua jawaban pilihan ganda secara otomatis?')) {
                const result = await apiCall('periksa_otomatis_pg');

                if (result.success) {
                    showToast(result.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            }
        }

        // Global variables for navigation
        let currentStudentIndex = 0;
        let studentList = [];
        let currentFormData = {};
        
        // Switch to table mode
        function switchToTableMode() {
            window.location.href = `?ujian_id=${ujianId}&mode=tabel`;
        }

        // Switch to swipe mode
        function switchToSwipeMode() {
            window.location.href = `?ujian_id=${ujianId}&mode=swipe`;
        }

        // Switch to formulir mode
        function switchToFormulirMode() {
            window.location.href = `?ujian_id=${ujianId}&mode=formulir`;
        }

        // Toggle dropdown
        function toggleDropdown() {
            const dropdown = document.getElementById("dropdown-content");
            dropdown.classList.toggle("show");
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.dropdown button')) {
                const dropdowns = document.getElementsByClassName("dropdown-content");
                for (let i = 0; i < dropdowns.length; i++) {
                    const openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
            
            // Close modal detail when clicking outside
            const modalDetail = document.getElementById('modal-detail');
            if (event.target === modalDetail) {
                closeModal();
            }
            
            // Close score modal when clicking backdrop
            const modalScore = document.getElementById('modal-score');
            const modalBackdrop = event.target.classList.contains('modal-backdrop');
            if (event.target === modalScore || modalBackdrop) {
                closeScoreModal();
            }
        }

        // Load formulir koreksi
        async function loadFormulirKoreksi() {
            const result = await apiCall('get_formulir_data');
            
            if (result.success) {
                studentList = result.data;
                currentStudentIndex = 0;
                updateStudentList();
                await loadFormulirForStudent(currentStudentIndex);
            } else {
                document.getElementById('form-koreksi').innerHTML = `
                    <div class="text-center text-red-500 py-8">
                        <i class="ti ti-alert-circle text-2xl mb-2"></i>
                        <p>Gagal memuat data formulir: ${result.message || 'Unknown error'}</p>
                    </div>
                `;
            }
        }

        // Load formulir for specific student
        async function loadFormulirForStudent(studentIndex) {
            if (!studentList[studentIndex]) return;
            
            const student = studentList[studentIndex];
            updateFormStudentNavigation();
            
            // Get questions and answers for this student
            const result = await apiCall('get_detail_jawaban', {
                ujian_siswa_id: student.ujian_siswa_id
            });
            
            if (result.success) {
                console.log('Detail jawaban data:', result.data); // Debug log
                const container = document.getElementById('form-koreksi');
                let content = `
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-900 mb-2">${student.nama}</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm text-blue-700">
                            <div>Nilai Saat Ini: <span class="font-medium">${formatNumber(student.nilai)}</span></div>
                            <div>Status: <span class="font-medium">${student.status || 'belum_dinilai'}</span></div>
                        </div>
                    </div>
                `;
                
                result.data.forEach((jawaban, index) => {
                    const isAutoScore = <?= $autoScore ? 'true' : 'false' ?>;
                    const isDisabled = isAutoScore && jawaban.tipeSoal === 'pilihan_ganda';
                    const disabledClass = isDisabled ? 'disabled' : '';
                    
                    // Safe parsing for pilihanJawaban
                    let pilihanOptions = '';
                    if (jawaban.tipeSoal === 'pilihan_ganda' && jawaban.pilihanJawaban) {
                        try {
                            const pilihan = JSON.parse(jawaban.pilihanJawaban);
                            if (Array.isArray(pilihan)) {
                                pilihanOptions = pilihan.map((p, i) => 
                                    `<div class="flex items-center">
                                        <span class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs mr-2">${String.fromCharCode(65 + i)}</span>
                                        ${p}
                                    </div>`
                                ).join('');
                            } else {
                                pilihanOptions = 'Format pilihan tidak valid';
                            }
                        } catch (e) {
                            // If not JSON, treat as plain text
                            pilihanOptions = `<div class="text-gray-600">${jawaban.pilihanJawaban}</div>`;
                        }
                    }
                    
                    content += `
                        <div class="question-card">
                            <div class="question-number">Soal ${jawaban.nomorSoal}</div>
                            <div class="question-text">${jawaban.pertanyaan}</div>
                            
                            ${jawaban.tipeSoal === 'pilihan_ganda' ? `
                                <div class="mb-4">
                                    <div class="text-sm text-gray-600 mb-2">Jawaban yang Benar:</div>
                                    <div class="px-4 py-2 bg-green-50 border-l-4 border-green-400 rounded-r-lg">
                                        <span class="font-semibold text-green-800">${jawaban.kunciJawaban || 'Tidak ada kunci jawaban'}</span>
                                    </div>
                                </div>
                            ` : ''}
                            
                            <div class="answer-section">
                                <div class="text-sm font-medium text-gray-700 mb-2">Jawaban Siswa:</div>
                                <div class="student-answer">
                                    ${jawaban.tipeSoal === 'pilihan_ganda' ? 
                                        (jawaban.pilihanJawaban || 'Tidak dijawab') : 
                                        (jawaban.jawaban || 'Tidak dijawab')
                                    }
                                </div>
                            </div>
                            
                            ${jawaban.tipeSoal === 'jawaban_panjang' || jawaban.tipeSoal === 'jawaban_singkat' ? `
                                <div class="answer-section">
                                    <div class="text-sm font-medium text-gray-700 mb-2">Kunci Jawaban:</div>
                                    <div class="student-answer">
                                        ${jawaban.kunciJawaban || 'Tidak ada kunci jawaban'}
                                    </div>
                                </div>
                            ` : ''}
                            
                            <div class="scoring-section ${disabledClass}">
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center">
                                        <input type="radio" name="benar_${jawaban.soal_id}" value="1" 
                                               ${jawaban.benar == 1 ? 'checked' : ''} 
                                               onchange="updateQuestionScore(${jawaban.soal_id}, this.value, ${jawaban.poin || 0})"
                                               ${isDisabled ? 'disabled' : ''}>
                                        <span class="ml-2 text-sm text-green-600 font-medium">Benar</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="benar_${jawaban.soal_id}" value="0" 
                                               ${jawaban.benar == 0 ? 'checked' : ''} 
                                               onchange="updateQuestionScore(${jawaban.soal_id}, this.value, 0)"
                                               ${isDisabled ? 'disabled' : ''}>
                                        <span class="ml-2 text-sm text-red-600 font-medium">Salah</span>
                                    </label>
                                    <div class="flex items-center gap-2 ml-auto">
                                        <label class="text-sm font-medium text-gray-700">Poin:</label>
                                        <input type="number" min="0" max="${jawaban.poin_soal || 100}" 
                                               value="${formatNumber(jawaban.poin)}" 
                                               class="point-input" 
                                               onchange="updateQuestionScore(${jawaban.soal_id}, document.querySelector('input[name=benar_${jawaban.soal_id}]:checked')?.value || 0, this.value)"
                                               ${isDisabled ? 'disabled' : ''}>
                                        <span class="text-xs text-gray-500">/ ${formatNumber(jawaban.poin_soal)}</span>
                                    </div>
                                </div>
                                ${isDisabled ? `
                                    <div class="mt-2 text-xs text-amber-600">
                                        <i class="ti ti-info-circle mr-1"></i>
                                        Auto Score aktif
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
                
                container.innerHTML = content;
                currentFormData = {
                    ujian_siswa_id: student.ujian_siswa_id,
                    scores: {}
                };
            } else {
                document.getElementById('form-koreksi').innerHTML = `
                    <div class="text-center text-red-500 py-8">
                        <i class="ti ti-alert-circle text-2xl mb-2"></i>
                        <p>Gagal memuat detail jawaban: ${result.message || 'Unknown error'}</p>
                    </div>
                `;
            }
        }

        // Update question score in memory
        function updateQuestionScore(soalId, benar, poin) {
            currentFormData.scores[soalId] = {
                benar: parseInt(benar),
                poin: parseFloat(poin)
            };
        }

        // Save formulir scores
        async function saveFormulirScores() {
            if (!currentFormData.ujian_siswa_id || Object.keys(currentFormData.scores).length === 0) {
                showToast('Tidak ada data untuk disimpan', 'error');
                return;
            }
            
            const scores = Object.entries(currentFormData.scores).map(([soalId, score]) => ({
                ujian_siswa_id: currentFormData.ujian_siswa_id,
                soal_id: parseInt(soalId),
                benar: score.benar,
                poin: score.poin
            }));
            
            const result = await apiCall('batch_save_scores', { scores: JSON.stringify(scores) });
            
            if (result.success) {
                showToast(result.message);
                // Update student status in list
                if (studentList[currentStudentIndex]) {
                    studentList[currentStudentIndex].status = 'sudah_dinilai';
                    updateStudentList();
                }
            } else {
                showToast(result.message, 'error');
            }
        }

        // Student navigation functions
        function prevFormStudent() {
            if (currentStudentIndex > 0) {
                currentStudentIndex--;
                loadFormulirForStudent(currentStudentIndex);
                updateStudentList();
            }
        }

        function nextFormStudent() {
            if (currentStudentIndex < studentList.length - 1) {
                currentStudentIndex++;
                loadFormulirForStudent(currentStudentIndex);
                updateStudentList();
            }
        }

        function updateFormStudentNavigation() {
            const prevBtn = document.getElementById('btn-prev-form-student');
            const nextBtn = document.getElementById('btn-next-form-student');
            const nameSpan = document.getElementById('current-form-student-name');
            const progressSpan = document.getElementById('form-student-progress');
            
            if (prevBtn) prevBtn.disabled = currentStudentIndex === 0;
            if (nextBtn) nextBtn.disabled = currentStudentIndex === studentList.length - 1;
            if (nameSpan && studentList[currentStudentIndex]) {
                nameSpan.textContent = studentList[currentStudentIndex].nama;
            }
            if (progressSpan) {
                progressSpan.textContent = `${currentStudentIndex + 1} / ${studentList.length}`;
            }
        }

        // Update student list in sidebar
        function updateStudentList() {
            const container = document.getElementById('student-list');
            if (!container || !studentList.length) return;
            
            let content = '';
            studentList.forEach((student, index) => {
                const isActive = index === currentStudentIndex;
                const activeClass = isActive ? 'active' : '';
                
                content += `
                    <div class="student-item ${activeClass}" onclick="selectStudent(${index})">
                        <div class="student-name">${student.nama}</div>
                        <div class="student-score">${formatNumber(student.nilai)}</div>
                    </div>
                `;
            });
            
            container.innerHTML = content;
        }

        // Select student from list
        function selectStudent(index) {
            currentStudentIndex = index;
            const mode = new URLSearchParams(window.location.search).get('mode');
            
            if (mode === 'formulir') {
                loadFormulirForStudent(index);
            } else if (mode === 'swipe') {
                // For swipe mode, find first question of selected student
                const studentData = studentList[currentStudentIndex];
                if (studentData && currentSwipeData.length > 0) {
                    const firstQuestionIndex = currentSwipeData.findIndex(item => 
                        item.ujian_siswa_id === studentData.ujian_siswa_id
                    );
                    if (firstQuestionIndex !== -1) {
                        currentSwipeIndex = firstQuestionIndex;
                        updateSwipeCard();
                        updateSwipeNavigation();
                    }
                }
                updateSwipeStudentNavigation();
            }
            updateStudentList();
        }

        // Scroll student list
        function scrollStudentList(direction) {
            const container = document.getElementById('student-list');
            if (!container) return;
            
            const scrollAmount = 100;
            if (direction === 'up') {
                container.scrollTop -= scrollAmount;
            } else {
                container.scrollTop += scrollAmount;
            }
        }

        // Update nilai siswa
        async function updateNilai(ujianSiswaId, nilai) {
            const result = await apiCall('update_nilai', {
                ujian_siswa_id: ujianSiswaId,
                nilai: nilai
            });
            
            if (result.success) {
                showToast('Nilai berhasil diupdate');
            } else {
                showToast('Gagal mengupdate nilai', 'error');
            }
        }

        // Update status siswa
        async function updateStatus(ujianSiswaId, status) {
            const result = await apiCall('update_status', {
                ujian_siswa_id: ujianSiswaId,
                status: status
            });
            
            if (result.success) {
                showToast('Status berhasil diupdate');
            } else {
                showToast('Gagal mengupdate status', 'error');
            }
        }

        // Load swipe data
        async function loadSwipeData() {
            const result = await apiCall('get_swipe_data');

            if (result.success) {
                // Filter data based on auto score setting
                let filteredData = result.data.filter(item =>
                    !<?= $autoScore ?> || item.tipeSoal !== 'pilihan_ganda'
                );
                
                // Sort data: prioritize ungraded questions (benar is null or empty)
                currentSwipeData = filteredData.sort((a, b) => {
                    // Prioritize ungraded questions
                    const aUngraded = (a.benar === null || a.benar === '' || a.poin_jawaban === null || a.poin_jawaban === 0);
                    const bUngraded = (b.benar === null || b.benar === '' || b.poin_jawaban === null || b.poin_jawaban === 0);
                    
                    if (aUngraded && !bUngraded) return -1;
                    if (!aUngraded && bUngraded) return 1;
                    
                    // If both have same grading status, sort by student name then question number
                    if (a.siswa_nama !== b.siswa_nama) {
                        return a.siswa_nama.localeCompare(b.siswa_nama);
                    }
                    return a.nomorSoal - b.nomorSoal;
                });
                
                currentSwipeIndex = 0;
                
                // Load student list for sidebar
                const studentsResult = await apiCall('get_formulir_data');
                if (studentsResult.success) {
                    studentList = studentsResult.data;
                    updateStudentList();
                    
                    // Set current student based on first card
                    if (currentSwipeData.length > 0) {
                        const firstCard = currentSwipeData[0];
                        const studentIndex = studentList.findIndex(s => s.ujian_siswa_id === firstCard.ujian_siswa_id);
                        if (studentIndex !== -1) {
                            currentStudentIndex = studentIndex;
                        }
                    }
                    
                    // Update student navigation
                    updateSwipeStudentNavigation();
                }
                
                updateSwipeCard();
                updateSwipeNavigation();
            } else {
                showToast('Gagal memuat data', 'error');
            }
        }

        // Student navigation for swipe mode
        function prevStudent() {
            if (currentStudentIndex > 0) {
                currentStudentIndex--;
                // Find first question of this student
                const studentData = studentList[currentStudentIndex];
                if (studentData) {
                    const firstQuestionIndex = currentSwipeData.findIndex(item => 
                        item.ujian_siswa_id === studentData.ujian_siswa_id
                    );
                    if (firstQuestionIndex !== -1) {
                        currentSwipeIndex = firstQuestionIndex;
                        updateSwipeCard();
                        updateStudentList();
                        updateSwipeNavigation();
                    }
                }
                updateSwipeStudentNavigation();
            }
        }

        function nextStudent() {
            if (currentStudentIndex < studentList.length - 1) {
                currentStudentIndex++;
                // Find first question of this student
                const studentData = studentList[currentStudentIndex];
                if (studentData) {
                    const firstQuestionIndex = currentSwipeData.findIndex(item => 
                        item.ujian_siswa_id === studentData.ujian_siswa_id
                    );
                    if (firstQuestionIndex !== -1) {
                        currentSwipeIndex = firstQuestionIndex;
                        updateSwipeCard();
                        updateStudentList();
                        updateSwipeNavigation();
                    }
                }
                updateSwipeStudentNavigation();
            }
        }

        function updateSwipeStudentNavigation() {
            const prevBtn = document.getElementById('btn-prev-student');
            const nextBtn = document.getElementById('btn-next-student');
            const nameSpan = document.getElementById('current-student-name');
            const progressSpan = document.getElementById('student-progress');
            
            if (prevBtn) prevBtn.disabled = currentStudentIndex === 0;
            if (nextBtn) nextBtn.disabled = currentStudentIndex === studentList.length - 1;
            
            if (nameSpan && studentList[currentStudentIndex]) {
                nameSpan.textContent = studentList[currentStudentIndex].nama;
            }
            if (progressSpan) {
                progressSpan.textContent = `${currentStudentIndex + 1} / ${studentList.length}`;
            }
        }

        // Arrow navigation functions for swipe mode
        function prevSwipeCard() {
            if (currentSwipeIndex > 0) {
                currentSwipeIndex--;
                updateSwipeCard();
                updateSwipeNavigation();
            }
        }

        function nextSwipeCard() {
            if (currentSwipeIndex < currentSwipeData.length - 1) {
                currentSwipeIndex++;
                updateSwipeCard();
                updateSwipeNavigation();
            }
        }

        // Update arrow button states
        function updateSwipeNavigation() {
            const prevBtn = document.getElementById('btn-prev-swipe');
            const nextBtn = document.getElementById('btn-next-swipe');
            
            if (prevBtn) {
                prevBtn.disabled = currentSwipeIndex === 0;
                prevBtn.style.opacity = currentSwipeIndex === 0 ? '0.5' : '1';
            }
            if (nextBtn) {
                nextBtn.disabled = currentSwipeIndex >= currentSwipeData.length - 1;
                nextBtn.style.opacity = currentSwipeIndex >= currentSwipeData.length - 1 ? '0.5' : '1';
            }
        }

        // Update swipe card
        function updateSwipeCard() {
            if (currentSwipeIndex >= currentSwipeData.length) {
                document.getElementById('swipe-container').innerHTML = `
                    <div class="text-center">
                        <i class="ti ti-circle-check text-4xl text-green-500 mb-4"></i>
                        <h3 class="text-lg font-medium text-green-600 mb-2">Semua soal telah selesai diperiksa!</h3>
                        <p class="text-gray-600 mb-4">Anda telah menyelesaikan koreksi semua jawaban.</p>
                        <div class="flex justify-center gap-3">
                            <button onclick="window.location.reload()" class="btn-orange">
                                <i class="ti ti-refresh"></i>
                                <span>Refresh Halaman</span>
                            </button>
                            <button onclick="switchToTableMode()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                                <i class="ti ti-table"></i>
                                <span>Kembali ke Tabel</span>
                            </button>
                        </div>
                    </div>
                `;
                updateProgress();
                return;
            }

            const data = currentSwipeData[currentSwipeIndex];
            
            // Update current student index based on current card
            if (studentList.length > 0) {
                const studentIndex = studentList.findIndex(s => s.ujian_siswa_id === data.ujian_siswa_id);
                if (studentIndex !== -1) {
                    currentStudentIndex = studentIndex;
                    updateSwipeStudentNavigation();
                    updateStudentList();
                }
            }
            
            const container = document.getElementById('swipe-container');

            let cardContent = `
                <div class="bg-white rounded-lg p-6 border border-gray-200">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 rounded-full overflow-hidden mx-auto mb-2">
                            ${data.fotoProfil && data.fotoProfil.trim() !== '' ? 
                                `<img src="${data.fotoProfil.startsWith('uploads/profile/') ? '../../' + data.fotoProfil : '../../uploads/profile/' + data.fotoProfil}" 
                                      alt="Profile Photo" 
                                      class="w-full h-full object-cover"
                                      onerror="this.parentElement.innerHTML='<div class=\\'w-full h-full bg-orange text-white rounded-full flex items-center justify-center text-xl font-bold\\'>${data.siswa_nama.charAt(0).toUpperCase()}</div>'">` :
                                `<div class="w-full h-full bg-orange text-white rounded-full flex items-center justify-center text-xl font-bold">${data.siswa_nama.charAt(0).toUpperCase()}</div>`}
                        </div>
                        <h4 class="text-lg font-bold text-gray-800">${data.siswa_nama}</h4>
                        <p class="text-gray-600">Soal ${data.nomorSoal} - ${data.tipeSoal.replace('_', ' ')}</p>
                    </div>
                    
                    <div class="question-item border-l-4 border-blue-500 bg-blue-50">
                        <h5 class="font-semibold text-blue-800 mb-2">
                            <i class="ti ti-help-circle mr-1"></i>
                            Pertanyaan:
                        </h5>
                        <p class="text-gray-800">${data.pertanyaan}</p>
                    </div>`;

            if (data.tipeSoal === 'jawaban_panjang' || data.tipeSoal === 'jawaban_singkat') {
                cardContent += `
                    <div class="question-item border-l-4 border-yellow-500 bg-yellow-50">
                        <h5 class="font-semibold text-yellow-800 mb-2">
                            <i class="ti ti-edit mr-1"></i>
                            Jawaban Siswa:
                        </h5>
                        <p class="text-gray-800">${data.jawaban || 'Tidak ada jawaban'}</p>
                    </div>
                    
                    <div class="question-item border-l-4 border-green-500 bg-green-50">
                        <h5 class="font-semibold text-green-800 mb-2">
                            <i class="ti ti-key mr-1"></i>
                            Kunci Jawaban:
                        </h5>
                        <p class="text-gray-800">${data.kunciJawaban || 'Tidak ada kunci jawaban'}</p>
                    </div>
                    
                    <div class="question-item border-l-4 border-purple-500 bg-purple-50">
                        <h5 class="font-semibold text-purple-800 mb-2">
                            <i class="ti ti-star mr-1"></i>
                            Poin Maksimal:
                        </h5>
                        <p class="text-gray-800 font-medium">${formatNumber(data.poin)} poin</p>
                    </div>`;
            } else {
                cardContent += `
                    <div class="question-item border-l-4 border-gray-500 bg-gray-50">
                        <h5 class="font-semibold text-gray-800 mb-2">
                            <i class="ti ti-list mr-1"></i>
                            Jawaban Pilihan Ganda:
                        </h5>
                        <p class="text-gray-800 mb-1"><strong>Jawaban:</strong> ${data.pilihanJawaban || 'Tidak dijawab'}</p>
                        <p class="text-gray-800"><strong>Kunci:</strong> ${data.kunciJawaban}</p>
                    </div>`;
            }

            cardContent += `
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg text-center">
                        <p class="text-sm text-gray-600 mb-1">
                            Status: ${data.benar === 1 ? '<span class="text-green-600 font-semibold">✓ Benar</span>' : 
                                    data.benar === 0 ? '<span class="text-red-600 font-semibold">✗ Salah</span>' : 
                                    '<span class="text-gray-600">? Belum dinilai</span>'}
                        </p>
                        <p class="text-sm text-gray-600">Poin: <span class="font-medium">${formatNumber(data.poin_jawaban)}</span></p>
                    </div>
                </div>`;

            container.innerHTML = cardContent;
            updateProgress();
            updateSwipeNavigation();
        }

        // Update progress bar
        function updateProgress() {
            const progress = currentSwipeData.length > 0 ?
                (currentSwipeIndex / currentSwipeData.length) * 100 : 100;
            document.getElementById('progress-bar').style.width = progress + '%';
        }

        // Handle swipe answer
        async function swipeAnswer(benar) {
            if (currentSwipeIndex >= currentSwipeData.length) return;

            const data = currentSwipeData[currentSwipeIndex];
            const poin = benar ? data.poin : 0;

            await saveScore(data.ujian_siswa_id, data.soal_id, benar ? 1 : 0, poin);

            currentSwipeIndex++;
            updateSwipeCard();
        }

        // Show score input modal
        function showScoreInput() {
            if (currentSwipeIndex >= currentSwipeData.length) return;

            const data = currentSwipeData[currentSwipeIndex];
            const maxScore = parseFloat(data.poin) || 100; // Gunakan poin per soal, bukan total ujian
            
            // Update informasi soal
            document.getElementById('score-soal-info').textContent = `Soal ${data.nomorSoal} - ${data.tipeSoal.replace('_', ' ')}`;
            
            // Update label dan max value berdasarkan poin soal
            document.getElementById('score-label').textContent = `Nilai (0-${formatNumber(maxScore)})`;
            document.getElementById('score-input').max = maxScore;
            document.getElementById('score-input').value = formatNumber(data.poin_jawaban || 0);
            document.getElementById('score-info-text').textContent = `Nilai maksimal untuk soal ini adalah ${formatNumber(maxScore)} poin`;
            
            // Show modal with fade effect
            const modal = document.getElementById('modal-score');
            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }

        // Close score modal with fade effect
        function closeScoreModal() {
            const modal = document.getElementById('modal-score');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Handle keyboard events for modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('modal-score');
                if (modal.classList.contains('show')) {
                    closeScoreModal();
                }
            }
        });

        // Save manual score - updated to handle form submission
        async function saveManualScore(event) {
            if (event) {
                event.preventDefault();
            }
            
            const score = parseFloat(document.getElementById('score-input').value);
            const data = currentSwipeData[currentSwipeIndex];
            const maxScore = parseFloat(data.poin) || 100; // Gunakan poin per soal
            
            if (isNaN(score)) {
                showToast('Masukkan nilai yang valid', 'error');
                return;
            }
            
            if (score < 0 || score > maxScore) {
                showToast(`Nilai harus antara 0 dan ${formatNumber(maxScore)}`, 'error');
                return;
            }

            const benar = score > 0 ? 1 : 0;

            await saveScore(data.ujian_siswa_id, data.soal_id, benar, score);

            closeScoreModal();
            currentSwipeIndex++;
            updateSwipeCard();
        }

        // Save score to database
        async function saveScore(ujian_siswa_id, soal_id, benar, poin) {
            const result = await apiCall('save_manual_score', {
                ujian_siswa_id: ujian_siswa_id,
                soal_id: soal_id,
                benar: benar,
                poin: poin
            });

            if (!result.success) {
                showToast(result.message || 'Gagal menyimpan nilai', 'error');
            } else {
                showToast('Nilai berhasil disimpan');
            }
        }

        // Show detail modal
        async function showDetailModal(ujianSiswaId) {
            const result = await apiCall('get_detail_jawaban', {
                ujian_siswa_id: ujianSiswaId
            });

            if (result.success) {
                let content = `
                    <div class="mb-6">
                        <h3 class="text-lg font-bold mb-2">Detail Jawaban - ${result.siswa_nama}</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><span class="text-gray-600">Total Soal:</span> <span class="font-medium">${result.data.length}</span></div>
                            <div><span class="text-gray-600">Nilai:</span> <span class="font-medium">${result.nilai || 0}</span></div>
                        </div>
                    </div>
                `;

                result.data.forEach((jawaban, index) => {
                    const isCorrect = jawaban.benar === 1;
                    const statusClass = isCorrect ? 'border-green-200 bg-green-50' :
                        jawaban.benar === 0 ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-gray-50';

                    content += `
                        <div class="question-item border-2 ${statusClass}">
                            <div class="flex justify-between items-start mb-3">
                                <h5 class="font-semibold text-gray-800">Soal ${jawaban.nomorSoal}</h5>
                                <span class="text-xs font-medium px-2 py-1 rounded ${
                                    jawaban.benar === 1 ? 'bg-green-100 text-green-800' : 
                                    jawaban.benar === 0 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'
                                }">
                                    ${jawaban.benar === 1 ? 'Benar' : jawaban.benar === 0 ? 'Salah' : 'Belum dinilai'}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <p class="text-sm text-gray-600 mb-1">Pertanyaan:</p>
                                <p class="text-gray-800">${jawaban.pertanyaan}</p>
                            </div>
                            
                            <div class="mb-3">
                                <p class="text-sm text-gray-600 mb-1">Jawaban Siswa:</p>
                                <p class="text-gray-800">${jawaban.jawaban || 'Tidak dijawab'}</p>
                            </div>
                            
                            ${jawaban.tipeSoal !== 'pilihan_ganda' ? `
                                <div class="mb-3">
                                    <p class="text-sm text-gray-600 mb-1">Kunci Jawaban:</p>
                                    <p class="text-gray-800">${jawaban.kunciJawaban || '-'}</p>
                                </div>
                            ` : ''}
                            
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Poin: <span class="font-medium">${formatNumber(jawaban.poin_jawaban)}/${formatNumber(jawaban.poin_soal)}</span></span>
                                <span class="text-gray-600 capitalize">${jawaban.tipeSoal.replace('_', ' ')}</span>
                            </div>
                        </div>`;
                });

                document.getElementById('detail-content').innerHTML = content;
                
                // Show modal with fade effect
                const modal = document.getElementById('modal-detail');
                modal.style.display = 'block';
                setTimeout(() => {
                    modal.classList.add('show');
                }, 10);
            } else {
                showToast('Gagal memuat detail jawaban', 'error');
            }
        }

        // Close modal with fade effect
        function closeModal() {
            const modal = document.getElementById('modal-detail');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Export to Excel
        function exportToExcel() {
            window.open(`../logic/export-hasil-ujian.php?ujian_id=${ujianId}`, '_blank');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            const mode = new URLSearchParams(window.location.search).get('mode');

            if (mode === 'swipe') {
                loadSwipeData();
            } else if (mode === 'formulir') {
                loadFormulirKoreksi();
            }
        });

        // Keyboard shortcuts for swipe mode
        document.addEventListener('keydown', function(event) {
            const mode = new URLSearchParams(window.location.search).get('mode');
            if (mode !== 'swipe') return;

            switch (event.key) {
                case 'ArrowLeft':
                    // If Ctrl is pressed, navigate to previous card, otherwise mark as incorrect
                    if (event.ctrlKey) {
                        prevSwipeCard();
                    } else {
                        swipeAnswer(false);
                    }
                    break;
                case 'ArrowRight':
                    // If Ctrl is pressed, navigate to next card, otherwise mark as correct
                    if (event.ctrlKey) {
                        nextSwipeCard();
                    } else {
                        swipeAnswer(true);
                    }
                    break;
                case 'ArrowUp':
                    // Navigate to previous card
                    prevSwipeCard();
                    break;
                case 'ArrowDown':
                    // Navigate to next card or show score input if Alt is pressed
                    if (event.altKey) {
                        showScoreInput();
                    } else {
                        nextSwipeCard();
                    }
                    break;
                case 'Escape':
                    closeScoreModal();
                    closeModal();
                    break;
            }
        });
    </script>
    <script src="../script/menu-bar-script.js"></script>
</body>

</html>