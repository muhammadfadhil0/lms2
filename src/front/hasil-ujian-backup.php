<?php
session_start();

// Redirect jika belum login atau bukan guru
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    header('Location: ../../index.php');
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); 
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
        .score-excellent { background: #dcfce7; color: #166534; }
        .score-good { background: #dbeafe; color: #1e40af; }
        .score-average { background: #fef3c7; color: #92400e; }
        .score-poor { background: #fee2e2; color: #991b1b; }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-selesai { background: #dcfce7; color: #166534; }
        .status-sedang { background: #dbeafe; color: #1e40af; }
        .status-belum { background: #fef3c7; color: #92400e; }
        
        /* Modal Styles */
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            backdrop-filter: blur(4px);
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
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
        }
        .close { 
            float: right; 
            font-size: 24px; 
            font-weight: bold; 
            cursor: pointer; 
            color: #6b7280;
            transition: color 0.2s ease;
        }
        .close:hover { color: #374151; }
        
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
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
        .toast-success { background: #10b981; }
        .toast-error { background: #ef4444; }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .modal-content { 
                margin: 5% auto; 
                width: 95%; 
                padding: 16px;
            }
            .results-table th, .results-table td { 
                padding: 12px 8px; 
                font-size: 14px; 
            }
        }
        
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { 
            padding: 16px 12px; 
            text-align: left; 
            border-bottom: 1px solid #e5e7eb; 
            vertical-align: middle;
        }
        .table th { 
            background: #f9fafb; 
            font-weight: 600; 
            color: #374151;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        .table tbody tr:hover { background: #f9fafb; }
        
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.6); 
            backdrop-filter: blur(4px);
        }
        
        .modal-content { 
            background: white; 
            margin: 2% auto; 
            padding: 32px; 
            width: 90%; 
            max-width: 900px; 
            border-radius: 16px; 
            max-height: 85vh; 
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        
        .close { 
            float: right; 
            font-size: 32px; 
            font-weight: bold; 
            cursor: pointer; 
            color: #6b7280;
            transition: color 0.2s ease;
        }
        .close:hover { color: #374151; }
        
        .question-card { 
            border: 1px solid #e5e7eb; 
            border-radius: 12px; 
            padding: 20px; 
            margin-bottom: 20px; 
            transition: all 0.2s ease;
        }
        .question-card:hover { border-color: #d1d5db; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        
        .swipe-container { 
            width: 100%; 
            height: 600px; 
            position: relative; 
            overflow: hidden; 
            border-radius: 16px; 
            box-shadow: 0 10px 25px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); 
        }
        
        .swipe-card { 
            position: absolute; 
            width: 100%; 
            height: 100%; 
            background: white; 
            border-radius: 16px; 
            padding: 32px; 
            box-shadow: 0 4px 16px rgba(0,0,0,0.1); 
            transition: transform 0.3s ease, opacity 0.3s ease; 
            overflow-y: auto;
        }
        
        .swipe-actions { 
            position: absolute; 
            bottom: 24px; 
            left: 50%; 
            transform: translateX(-50%); 
            display: flex; 
            gap: 16px; 
            z-index: 10;
        }
        
        .swipe-btn { 
            width: 64px; 
            height: 64px; 
            border-radius: 50%; 
            border: none; 
            cursor: pointer; 
            font-size: 28px; 
            color: white; 
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .swipe-btn:hover { transform: scale(1.1); }
        .swipe-btn:active { transform: scale(0.95); }
        .swipe-btn.salah { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .swipe-btn.benar { background: linear-gradient(135deg, #10b981, #059669); }
        .swipe-btn.nilai { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        
        .progress-bar { 
            width: 100%; 
            height: 8px; 
            background: #e5e7eb; 
            border-radius: 8px; 
            margin-bottom: 24px; 
            overflow: hidden;
        }
        .progress-fill { 
            height: 100%; 
            background: linear-gradient(90deg, #3b82f6, #1d4ed8); 
            border-radius: 8px; 
            transition: width 0.3s ease; 
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-secondary { background: #f3f4f6; color: #374151; }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        
        .fade-in { animation: fadeIn 0.3s ease; }
        .slide-in { animation: slideIn 0.3s ease; }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .modal-content { 
                margin: 5% auto; 
                width: 95%; 
                padding: 20px;
                max-height: 90vh;
            }
            .table-responsive { overflow-x: auto; }
            .swipe-container { height: 500px; }
            .swipe-card { padding: 20px; }
            .swipe-btn { width: 56px; height: 56px; font-size: 24px; }
            .card { padding: 16px; }
        }
        
        @media (max-width: 640px) {
            .swipe-actions { bottom: 16px; gap: 12px; }
            .swipe-btn { width: 48px; height: 48px; font-size: 20px; }
            .btn { padding: 8px 16px; font-size: 14px; }
        }
        
        /* Print styles */
        @media print {
            .swipe-actions, .btn, .modal { display: none !important; }
            .card { box-shadow: none; border: 1px solid #e5e7eb; }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .table th { background: #374151; color: #f9fafb; }
            .question-card { border-color: #4b5563; background: #f9fafb; }
        }
        
        /* Loading state */
        .loading {
            position: relative;
            overflow: hidden;
        }
        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
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
                                <button onclick="toggleModeKoreksi()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium" id="btn-mode-koreksi">
                                    <i class="ti ti-refresh"></i>
                                    <?= $mode_koreksi === 'swipe' ? 'Mode Tabel' : 'Mode Swipe' ?>
                                </button>
                                <a href="?ujian_id=<?= $ujian_id ?>&mode=formulir" class="px-4 py-2 border border-green-300 text-green-700 rounded-lg hover:bg-green-50 transition-colors font-medium">
                                    <i class="ti ti-forms"></i>
                                    Mode Formulir
                                </a>
                            </div>
                        </div>

                        <?php if ($mode_koreksi === 'swipe'): ?>
                            <!-- Mode Swipe untuk Koreksi -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="ti ti-swipe text-orange mr-2"></i>
                                    Mode Koreksi Swipe
                                </h2>
                                <div class="progress-container">
                                    <div class="progress-bar" id="progress-bar" style="width: 0%"></div>
                                </div>
                                <div class="relative bg-gray-50 rounded-lg p-6 min-h-[400px]" id="swipe-container">
                                    <!-- Cards will be populated by JavaScript -->
                                    <div class="text-center text-gray-500">Memuat data koreksi...</div>
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
                            </div>
                        <?php elseif ($mode_koreksi === 'formulir'): ?>
                            <!-- Mode Formulir untuk Koreksi -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="ti ti-forms text-orange mr-2"></i>
                                    Mode Koreksi Formulir
                                </h2>
                                <div id="form-koreksi">
                                    <div class="text-center text-gray-500 py-8">Memuat formulir koreksi...</div>
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
                                
                                <?php if (empty($hasilUjian)): ?>
                                    <div class="p-8 border border-dashed rounded-lg text-center text-gray-500 bg-gray-50">
                                        <i class="ti ti-clipboard-off text-4xl mb-4 text-gray-400"></i>
                                        <p class="text-lg font-medium mb-2">Belum Ada Hasil Ujian</p>
                                        <p class="text-sm">Belum ada siswa yang mengerjakan ujian ini.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="overflow-x-auto">
                                        <table class="results-table">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Nama Siswa</th>
                                                    <th>Waktu Mulai</th>
                                                    <th>Waktu Selesai</th>
                                                    <th>Status</th>
                                                    <th>Nilai</th>
                                                    <th>Progress</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $no = 1; foreach ($hasilUjian as $hasil): ?>
                                                    <tr>
                                                        <td><?= $no++ ?></td>
                                                        <td>
                                                            <div class="flex items-center">
                                                                <div class="w-8 h-8 bg-orange text-white rounded-full flex items-center justify-center text-sm font-medium mr-3">
                                                                    <?= strtoupper(substr($hasil['nama'] ?? 'U', 0, 1)) ?>
                                                                </div>
                                                                <span class="font-medium text-gray-800"><?= htmlspecialchars($hasil['nama'] ?? 'Unknown') ?></span>
                                                            </div>
                                                        </td>
                                                        <td class="text-sm text-gray-600">
                                                            <?= $hasil['waktu_mulai'] ? date('d/m/Y H:i', strtotime($hasil['waktu_mulai'])) : '-' ?>
                                                        </td>
                                                        <td class="text-sm text-gray-600">
                                                            <?= $hasil['waktu_selesai'] ? date('d/m/Y H:i', strtotime($hasil['waktu_selesai'])) : '-' ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $status = $hasil['status'] ?? 'belum';
                                                            $statusClass = $status === 'selesai' ? 'status-selesai' : 
                                                                         ($status === 'sedang_mengerjakan' ? 'status-sedang' : 'status-belum');
                                                            ?>
                                                            <span class="status-badge <?= $statusClass ?>">
                                                                <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $nilai = (float)($hasil['nilai'] ?? 0);
                                                            $scoreClass = $nilai >= 85 ? 'score-excellent' : 
                                                                        ($nilai >= 70 ? 'score-good' : 
                                                                        ($nilai >= 60 ? 'score-average' : 'score-poor'));
                                                            ?>
                                                            <span class="score-badge <?= $scoreClass ?>">
                                                                <?= number_format($nilai, 1) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $progress = (int)($hasil['progress'] ?? 0);
                                                            $progressColor = $progress >= 100 ? 'bg-green-500' : 
                                                                           ($progress >= 50 ? 'bg-orange' : 'bg-gray-400');
                                                            ?>
                                                            <div class="flex items-center">
                                                                <div class="w-16 h-2 bg-gray-200 rounded-full mr-2">
                                                                    <div class="h-2 <?= $progressColor ?> rounded-full" style="width: <?= min($progress, 100) ?>%"></div>
                                                                </div>
                                                                <span class="text-xs text-gray-600"><?= $progress ?>%</span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <button onclick="showDetailModal(<?= (int)($hasil['ujian_siswa_id'] ?? 0) ?>)" 
                                                                    class="px-3 py-1 text-xs bg-orange text-white rounded hover:bg-orange-600 transition-colors">
                                                                <i class="ti ti-eye"></i> Detail
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar (col-span-1) -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Tools Koreksi</h3>
                            <div class="space-y-3">
                                <a href="?ujian_id=<?= $ujian_id ?>&mode=swipe" class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors">
                                    <i class="ti ti-swipe"></i><span>Mode Swipe</span>
                                </a>
                                <a href="?ujian_id=<?= $ujian_id ?>&mode=formulir" class="w-full flex items-center justify-center space-x-2 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                    <i class="ti ti-forms"></i><span>Mode Formulir</span>
                                </a>
                                <a href="?ujian_id=<?= $ujian_id ?>" class="w-full flex items-center justify-center space-x-2 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                    <i class="ti ti-table"></i><span>Mode Tabel</span>
                                </a>
                                
                                <div class="pt-3">
                                    <button onclick="exportToExcel()" class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                        <i class="ti ti-file-export"></i><span>Export Excel</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Statistik -->
                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Statistik Ujian</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Total Siswa</span>
                                        <span class="font-medium"><?= count($hasilUjian) ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Selesai</span>
                                        <span class="font-medium text-green-600">
                                            <?= count(array_filter($hasilUjian, function($h) { return ($h['status'] ?? '') === 'selesai'; })) ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Sedang Mengerjakan</span>
                                        <span class="font-medium text-blue-600">
                                            <?= count(array_filter($hasilUjian, function($h) { return ($h['status'] ?? '') === 'sedang_mengerjakan'; })) ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Rata-rata Nilai</span>
                                        <span class="font-medium">
                                            <?php 
                                            $nilaiSelesai = array_filter($hasilUjian, function($h) { return ($h['status'] ?? '') === 'selesai'; });
                                            $rataRata = empty($nilaiSelesai) ? 0 : array_sum(array_column($nilaiSelesai, 'nilai')) / count($nilaiSelesai);
                                            echo number_format($rataRata, 1);
                                            ?>
                                        </span>
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
                    </div>
                </div>
            </div>
        </main>
    </div>
                    </div>
                    <div class="swipe-actions">
                        <button class="swipe-btn salah" onclick="swipeAnswer(false)">✗</button>
                        <button class="swipe-btn benar" onclick="swipeAnswer(true)">✓</button>
                        <button class="swipe-btn nilai" onclick="showScoreInput()">📝</button>
                    </div>
                </div>
            <?php elseif ($mode_koreksi === 'formulir'): ?>
                <!-- Mode Formulir untuk Koreksi -->
                <div class="card">
                    <h3 class="text-lg font-semibold mb-4">Mode Koreksi Formulir</h3>
                    <div id="form-koreksi">
                        <!-- Form will be populated by JavaScript -->
                    </div>
                </div>
            <?php else: ?>
                <!-- Tabel Hasil Ujian (Default) -->
                <div class="card">
                    <h3 class="text-lg font-semibold mb-4">Hasil Ujian Siswa</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>Benar</th>
                                    <th>Salah</th>
                                    <th>Tidak Dijawab</th>
                                    <th>Nilai Akhir</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($hasilUjian as $hasil): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($hasil['namaLengkap']) ?></td>
                                        <td class="text-green-600 font-semibold"><?= (int)$hasil['jumlahBenar'] ?></td>
                                        <td class="text-red-600 font-semibold"><?= (int)$hasil['jumlahSalah'] ?></td>
                                        <td class="text-gray-600"><?= count($soalList) - (int)$hasil['jumlahBenar'] - (int)$hasil['jumlahSalah'] ?></td>
                                        <td class="font-semibold">
                                            <?php if ($autoScore): ?>
                                                <?= number_format((float)$hasil['totalNilai'], 1) ?>
                                            <?php else: ?>
                                                <span class="text-gray-400">Belum dinilai</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($autoScore): ?>
                                                <button onclick="showDetailModal(<?= $hasil['id'] ?>)" class="btn btn-primary btn-sm">Rincian</button>
                                            <?php else: ?>
                                                <a href="?ujian_id=<?= $ujian_id ?>&mode=koreksi&koreksi_id=<?= $hasil['id'] ?>" class="btn btn-warning btn-sm">Nilai</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Detail Jawaban -->
    <div id="modal-detail" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Detail Jawaban Siswa</h2>
            <div id="detail-content">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal Input Nilai -->
    <div id="modal-score" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeScoreModal()">&times;</span>
            <h2>Input Nilai</h2>
            <div id="score-content">
                <label>Nilai (0-100):</label>
                <input type="number" id="score-input" min="0" max="100" class="form-control">
                <button onclick="saveManualScore()" class="btn btn-primary mt-3">Simpan</button>
            </div>
        </div>
    </div>

    <script>
        let currentSwipeData = [];
        let currentSwipeIndex = 0;
        let currentKoreksiData = null;
        const API_URL = '../logic/hasil-ujian-api.php';
        const ujianId = <?= $ujian_id ?>;

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `px-4 py-2 rounded-lg text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
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
                return { success: false, message: 'Network error' };
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

        // Toggle mode koreksi
        function toggleModeKoreksi() {
            const currentMode = new URLSearchParams(window.location.search).get('mode') || 'tabel';
            const newMode = currentMode === 'swipe' ? 'tabel' : 'swipe';
            window.location.href = `?ujian_id=${ujianId}&mode=${newMode}`;
        }

        // Load swipe data
        async function loadSwipeData() {
            const result = await apiCall('get_swipe_data');
            
            if (result.success) {
                currentSwipeData = result.data.filter(item => 
                    !<?= $autoScore ?> || item.tipeSoal !== 'pilihan_ganda'
                );
                currentSwipeIndex = 0;
                updateSwipeCard();
            } else {
                showToast('Gagal memuat data', 'error');
            }
        }

        // Update swipe card
        function updateSwipeCard() {
            if (currentSwipeIndex >= currentSwipeData.length) {
                document.getElementById('swipe-container').innerHTML = `
                    <div class="swipe-card text-center">
                        <h3 class="text-green-600">🎉 Semua soal telah selesai diperiksa!</h3>
                        <p class="mt-4">Anda telah menyelesaikan koreksi semua jawaban.</p>
                        <button onclick="window.location.reload()" class="btn btn-primary mt-4">Refresh Halaman</button>
                    </div>
                `;
                updateProgress();
                return;
            }
            
            const data = currentSwipeData[currentSwipeIndex];
            const container = document.getElementById('swipe-container');
            
            let cardContent = `
                <div class="swipe-card">
                    <div class="text-center mb-4">
                        <h4 class="text-lg font-bold">${data.siswa_nama}</h4>
                        <p class="text-gray-600">Soal ${data.nomorSoal} - ${data.tipeSoal}</p>
                    </div>
                    
                    <div class="question-card bg-blue-50">
                        <h5 class="font-semibold text-blue-800 mb-2">Pertanyaan:</h5>
                        <p class="text-gray-800">${data.pertanyaan}</p>
                    </div>`;
            
            if (data.tipeSoal === 'jawaban_panjang' || data.tipeSoal === 'jawaban_singkat') {
                cardContent += `
                    <div class="question-card bg-yellow-50">
                        <h5 class="font-semibold text-yellow-800 mb-2">Jawaban Siswa:</h5>
                        <p class="text-gray-800">${data.jawaban || 'Tidak ada jawaban'}</p>
                    </div>
                    
                    <div class="question-card bg-green-50">
                        <h5 class="font-semibold text-green-800 mb-2">Kunci Jawaban:</h5>
                        <p class="text-gray-800">${data.kunciJawaban || 'Tidak ada kunci jawaban'}</p>
                    </div>
                    
                    <div class="question-card bg-purple-50">
                        <h5 class="font-semibold text-purple-800 mb-2">Poin Maksimal:</h5>
                        <p class="text-gray-800">${data.poin} poin</p>
                    </div>`;
            } else {
                cardContent += `
                    <div class="question-card bg-gray-50">
                        <h5 class="font-semibold text-gray-800 mb-2">Jawaban Pilihan Ganda:</h5>
                        <p class="text-gray-800">Jawaban: ${data.pilihanJawaban || 'Tidak dijawab'}</p>
                        <p class="text-gray-800">Kunci: ${data.kunciJawaban}</p>
                    </div>`;
            }
            
            cardContent += `
                    <div class="mt-4 text-center">
                        <p class="text-sm text-gray-600">
                            Status: ${data.benar === 1 ? '<span class="text-green-600 font-semibold">Benar</span>' : 
                                    data.benar === 0 ? '<span class="text-red-600 font-semibold">Salah</span>' : 
                                    '<span class="text-gray-600">Belum dinilai</span>'}
                        </p>
                        <p class="text-sm text-gray-600">Poin: ${data.poin_jawaban || 0}</p>
                    </div>
                </div>`;
            
            container.innerHTML = cardContent;
            updateProgress();
        }

        // Update progress bar
        function updateProgress() {
            const progress = currentSwipeData.length > 0 ? 
                (currentSwipeIndex / currentSwipeData.length) * 100 : 100;
            document.getElementById('progress-fill').style.width = progress + '%';
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
            document.getElementById('score-input').max = data.poin;
            document.getElementById('score-input').value = data.poin_jawaban || 0;
            document.getElementById('modal-score').style.display = 'block';
        }

        // Close score modal
        function closeScoreModal() {
            document.getElementById('modal-score').style.display = 'none';
        }

        // Save manual score
        async function saveManualScore() {
            const score = parseFloat(document.getElementById('score-input').value);
            if (isNaN(score)) {
                showToast('Masukkan nilai yang valid', 'error');
                return;
            }
            
            const data = currentSwipeData[currentSwipeIndex];
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
            }
        }

        // Show detail modal
        async function showDetailModal(ujianSiswaId) {
            const result = await apiCall('get_detail_jawaban', {
                ujian_siswa_id: ujianSiswaId
            });
            
            if (result.success) {
                let content = `<h3 class="font-bold mb-4">Detail Jawaban - ${result.siswa_nama}</h3>`;
                
                result.data.forEach((jawaban, index) => {
                    const isCorrect = jawaban.benar === 1;
                    const statusClass = isCorrect ? 'bg-green-50 border-green-200' : 
                                       jawaban.benar === 0 ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200';
                    
                    content += `
                        <div class="question-card ${statusClass} border-2">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="font-semibold">Soal ${jawaban.nomorSoal}</h5>
                                <span class="text-sm font-medium px-2 py-1 rounded ${
                                    isCorrect ? 'bg-green-100 text-green-800' : 
                                    jawaban.benar === 0 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'
                                }">
                                    ${isCorrect ? 'Benar' : jawaban.benar === 0 ? 'Salah' : 'Belum dinilai'}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <p class="text-sm font-medium text-gray-700 mb-1">Pertanyaan:</p>
                                <p class="text-gray-900">${jawaban.pertanyaan}</p>
                            </div>
                            
                            <div class="mb-3">
                                <p class="text-sm font-medium text-gray-700 mb-1">Jawaban Siswa:</p>
                                <p class="text-gray-900">${jawaban.jawaban || jawaban.pilihanJawaban || 'Tidak dijawab'}</p>
                            </div>
                            
                            ${jawaban.tipeSoal !== 'pilihan_ganda' ? `
                                <div class="mb-3">
                                    <p class="text-sm font-medium text-gray-700 mb-1">Kunci Jawaban:</p>
                                    <p class="text-gray-900">${jawaban.kunciJawaban || 'Tidak ada kunci jawaban'}</p>
                                </div>
                            ` : ''}
                            
                            <div class="flex justify-between text-sm">
                                <span>Poin diperoleh: <strong>${jawaban.poin || 0}</strong></span>
                                <span>Poin maksimal: <strong>${jawaban.poin_soal}</strong></span>
                            </div>
                        </div>`;
                });
                
                document.getElementById('detail-content').innerHTML = content;
                document.getElementById('modal-detail').style.display = 'block';
            } else {
                showToast('Gagal memuat detail jawaban', 'error');
            }
        }

        // Close modal
        function closeModal() {
            document.getElementById('modal-detail').style.display = 'none';
        }

        // Load formulir koreksi
        async function loadFormulirKoreksi() {
            const result = await apiCall('get_swipe_data');
            
            if (result.success) {
                const container = document.getElementById('form-koreksi');
                let content = '';
                
                // Group by siswa
                const groupedData = {};
                result.data.forEach(item => {
                    if (!groupedData[item.siswa_nama]) {
                        groupedData[item.siswa_nama] = [];
                    }
                    groupedData[item.siswa_nama].push(item);
                });
                
                Object.entries(groupedData).forEach(([siswaName, soalList]) => {
                    content += `
                        <div class="card mb-6">
                            <h4 class="text-lg font-bold mb-4">${siswaName}</h4>
                            ${soalList.map(soal => `
                                <div class="question-card">
                                    <div class="flex justify-between items-start mb-2">
                                        <h5 class="font-semibold">Soal ${soal.nomorSoal} (${soal.tipeSoal})</h5>
                                        <span class="text-sm text-gray-600">${soal.poin} poin</span>
                                    </div>
                                    
                                    <p class="text-gray-800 mb-3">${soal.pertanyaan}</p>
                                    
                                    <div class="mb-3">
                                        <p class="text-sm font-medium text-gray-700">Jawaban:</p>
                                        <p class="text-gray-900">${soal.jawaban || soal.pilihanJawaban || 'Tidak dijawab'}</p>
                                    </div>
                                    
                                    <div class="flex items-center gap-4">
                                        <label class="flex items-center">
                                            <input type="radio" name="benar_${soal.ujian_siswa_id}_${soal.soal_id}" 
                                                   value="1" ${soal.benar === 1 ? 'checked' : ''}>
                                            <span class="ml-2 text-green-600">Benar</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="benar_${soal.ujian_siswa_id}_${soal.soal_id}" 
                                                   value="0" ${soal.benar === 0 ? 'checked' : ''}>
                                            <span class="ml-2 text-red-600">Salah</span>
                                        </label>
                                        <label class="flex items-center">
                                            <span class="text-sm">Nilai:</span>
                                            <input type="number" class="ml-2 w-20 px-2 py-1 border rounded" 
                                                   min="0" max="${soal.poin}" step="0.1"
                                                   value="${soal.poin_jawaban || 0}"
                                                   data-ujian-siswa-id="${soal.ujian_siswa_id}"
                                                   data-soal-id="${soal.soal_id}">
                                        </label>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                });
                
                content += `
                    <div class="text-center">
                        <button onclick="saveAllFormScores()" class="btn btn-success">Simpan Semua Nilai</button>
                    </div>
                `;
                
                container.innerHTML = content;
            }
        }

        // Save all form scores
        async function saveAllFormScores() {
            const scores = [];
            
            document.querySelectorAll('input[type="number"][data-ujian-siswa-id]').forEach(input => {
                const ujianSiswaId = input.dataset.ujianSiswaId;
                const soalId = input.dataset.soalId;
                const poin = parseFloat(input.value) || 0;
                const benarRadio = document.querySelector(`input[name="benar_${ujianSiswaId}_${soalId}"]:checked`);
                const benar = benarRadio ? parseInt(benarRadio.value) : (poin > 0 ? 1 : 0);
                
                scores.push({
                    ujian_siswa_id: ujianSiswaId,
                    soal_id: soalId,
                    benar: benar,
                    poin: poin
                });
            });
            
            if (scores.length === 0) {
                showToast('Tidak ada nilai untuk disimpan', 'error');
                return;
            }
            
            const result = await apiCall('batch_save_scores', {
                scores: JSON.stringify(scores)
            });
            
            if (result.success) {
                showToast(result.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message, 'error');
            }
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

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modalDetail = document.getElementById('modal-detail');
            const modalScore = document.getElementById('modal-score');
            
            if (event.target === modalDetail) {
                modalDetail.style.display = 'none';
            }
            if (event.target === modalScore) {
                modalScore.style.display = 'none';
            }
        }

        // Keyboard shortcuts for swipe mode
        document.addEventListener('keydown', function(event) {
            const mode = new URLSearchParams(window.location.search).get('mode');
            if (mode !== 'swipe') return;
            
            switch(event.key) {
                case 'ArrowLeft':
                    swipeAnswer(false);
                    break;
                case 'ArrowRight':
                    swipeAnswer(true);
                    break;
                case 'ArrowDown':
                    showScoreInput();
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
