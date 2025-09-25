<?php
session_start();
$currentPage = 'ujian';

// Redirect jika belum login atau bukan admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

require_once '../logic/ujian-logic.php';

$ujianLogic = new UjianLogic();

// Get parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$mapel_filter = $_GET['mapel'] ?? '';
$guru_filter = $_GET['guru'] ?? '';
$sort_by = $_GET['sort'] ?? 'id';
$sort_order = $_GET['order'] ?? 'DESC';
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;

// Get ujian with filters
$ujian = $ujianLogic->getUjian($search, $status_filter, $mapel_filter, $guru_filter, $sort_by, $sort_order, $page, $per_page);
$total_ujian = $ujianLogic->countUjian($search, $status_filter, $mapel_filter, $guru_filter);
$total_pages = ceil($total_ujian / $per_page);

// Get statistics
$stats = $ujianLogic->getUjianStats();

// Get teachers for filter dropdown
$teachers = $ujianLogic->getTeachers();

// Get mata pelajaran list
$mapel_list = $ujianLogic->getMataPelajaran();

// Get kelas list
$kelas_list = $ujianLogic->getKelas();
?>

<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Kelola Ujian - Admin Panel</title>
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

        .btn-secondary {
            background: #f8fafc;
            color: #374151;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid #d1d5db;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: #f1f5f9;
            border-color: #9ca3af;
        }

        /* Table Styles */
        .ujian-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .ujian-table th {
            background: #f8fafc;
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .ujian-table th:hover {
            background: #f1f5f9;
        }

        .ujian-table th.sortable {
            position: relative;
        }

        .ujian-table th.sortable::after {
            content: '↕';
            position: absolute;
            right: 8px;
            color: #9ca3af;
            font-size: 12px;
        }

        .ujian-table th.sorted-asc::after {
            content: '↑';
            color: #f97316;
        }

        .ujian-table th.sorted-desc::after {
            content: '↓';
            color: #f97316;
        }

        .ujian-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        .ujian-table tbody tr:hover {
            background: #f9fafb;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-draft {
            background: #e5e7eb;
            color: #4b5563;
        }

        .status-aktif {
            background: #dcfce7;
            color: #166534;
        }

        .status-selesai {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-arsip {
            background: #fef3c7;
            color: #92400e;
        }

        /* Mata Pelajaran Badge */
        .mapel-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            background: #f0f9ff;
            color: #0369a1;
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
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal.show {
            opacity: 1;
            visibility: visible;
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

        .modal-content {
            background: white;
            padding: 24px;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: translateY(-20px) scale(0.95);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0) scale(1);
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

        /* Form Controls */
        .form-input {
            width: 100%;
            padding: 10px 12px;
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

        .form-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            resize: vertical;
            min-height: 80px;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: border-color 0.2s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        /* Search Bar */
        .search-container {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 10px 40px 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }

        /* Action Buttons */
        .action-btn {
            padding: 6px 8px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .action-btn-edit {
            background: #dbeafe;
            color: #1e40af;
        }

        .action-btn-edit:hover {
            background: #bfdbfe;
        }

        .action-btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-btn-delete:hover {
            background: #fecaca;
        }

        .action-btn-view {
            background: #dcfce7;
            color: #166534;
        }

        .action-btn-view:hover {
            background: #bbf7d0;
        }

        /* Editable Cells */
        .editable-cell {
            cursor: pointer;
            position: relative;
            padding: 12px 8px;
            transition: all 0.2s ease;
        }

        .editable-cell:hover {
            background: #f8fafc;
            border: 2px solid #f97316;
            border-radius: 4px;
        }

        .editable-cell.editing {
            background: #fff7ed;
            border: 2px solid #f97316;
            border-radius: 4px;
        }

        .editable-cell .edit-input {
            width: 100%;
            min-width: 120px;
            font-size: 14px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 6px 8px;
        }

        .editable-cell .edit-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.1);
        }

        .editable-cell .edit-select {
            width: 100%;
            min-width: 120px;
            font-size: 14px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 6px 8px;
            background: white;
        }

        .editable-cell .edit-select:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.1);
        }

        .editable-content {
            position: relative;
        }

        /* Edit Indicator */
        .editable-cell::after {
            content: '✏️';
            position: absolute;
            top: 4px;
            right: 4px;
            opacity: 0;
            font-size: 10px;
            transition: opacity 0.2s ease;
        }

        .editable-cell:hover::after {
            opacity: 0.7;
        }

        .editable-cell.editing::after {
            content: '💾';
            opacity: 1;
            cursor: pointer;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
            font-size: 14px;
            transition: all 0.2s;
        }

        .pagination a:hover {
            background: #f3f4f6;
        }

        .pagination .current {
            background: #f97316;
            color: white;
            border-color: #f97316;
        }

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Filter Badge */
        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            background: #f97316;
            color: white;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
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

        /* Duration Badge */
        .duration-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            background: #fef3c7;
            color: #92400e;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .ujian-table th,
            .ujian-table td {
                padding: 8px 6px;
                font-size: 12px;
            }

            .modal-content {
                margin: 1rem;
                max-width: none;
            }

            .search-container {
                max-width: none;
            }

            .control-buttons {
                flex-direction: column;
                width: 100%;
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
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Kelola Ujian</h1>
                    <p class="text-gray-600 mt-1">Manajemen semua ujian dalam sistem</p>
                </div>
                <button onclick="showAddUjianModal()" class="btn-orange">
                    <i class="ti ti-plus"></i>
                    Tambah Ujian
                </button>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="stat-card">
                        <div class="stat-number text-blue-600"><?= number_format($stats['total_ujian']) ?></div>
                        <div class="stat-label">Total Ujian</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-green-600"><?= number_format($stats['ujian_aktif']) ?></div>
                        <div class="stat-label">Aktif</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-yellow-600"><?= number_format($stats['ujian_selesai']) ?></div>
                        <div class="stat-label">Selesai</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-gray-600"><?= number_format($stats['ujian_draft']) ?></div>
                        <div class="stat-label">Draft</div>
                    </div>
                </div>

                <!-- Search and Filter Bar -->
                <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
                    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                        <div class="search-container">
                            <input 
                                type="text" 
                                id="search-input"
                                class="search-input" 
                                placeholder="Cari judul ujian, deskripsi, atau mata pelajaran..."
                                value="<?= htmlspecialchars($search) ?>"
                            >
                            <i class="ti ti-search search-icon"></i>
                        </div>
                        
                        <div class="control-buttons flex gap-2">
                            <button onclick="showFilterModal()" class="btn-secondary">
                                <i class="ti ti-filter"></i>
                                Filter
                            </button>
                            <button onclick="exportUjian()" class="btn-secondary">
                                <i class="ti ti-download"></i>
                                Export
                            </button>
                        </div>
                    </div>

                    <!-- Active Filters -->
                    <?php if (!empty($search) || !empty($status_filter) || !empty($mapel_filter) || !empty($guru_filter)): ?>
                    <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-200">
                        <span class="text-sm text-gray-600">Filter aktif:</span>
                        
                        <?php if (!empty($search)): ?>
                        <span class="filter-badge">
                            Pencarian: "<?= htmlspecialchars($search) ?>"
                            <button onclick="removeFilter('search')" class="ml-1 text-xs">×</button>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($status_filter)): ?>
                        <span class="filter-badge">
                            Status: <?= ucfirst($status_filter) ?>
                            <button onclick="removeFilter('status')" class="ml-1 text-xs">×</button>
                        </span>
                        <?php endif; ?>

                        <?php if (!empty($mapel_filter)): ?>
                        <span class="filter-badge">
                            Mapel: <?= htmlspecialchars($mapel_filter) ?>
                            <button onclick="removeFilter('mapel')" class="ml-1 text-xs">×</button>
                        </span>
                        <?php endif; ?>

                        <?php if (!empty($guru_filter)): ?>
                        <span class="filter-badge">
                            Guru: 
                            <?php
                            $guru_name = array_filter($teachers, function($t) use ($guru_filter) { 
                                return $t['id'] == $guru_filter; 
                            });
                            echo !empty($guru_name) ? reset($guru_name)['nama'] : 'Unknown';
                            ?>
                            <button onclick="removeFilter('guru')" class="ml-1 text-xs">×</button>
                        </span>
                        <?php endif; ?>
                        
                        <button onclick="clearFilters()" class="text-xs text-orange-600 hover:text-orange-800">
                            Hapus semua filter
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <table class="ujian-table">
                        <thead>
                            <tr>
                                <th onclick="sortTable('id')" class="sortable <?= $sort_by === 'id' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                    ID
                                </th>
                                <th onclick="sortTable('judul')" class="sortable <?= $sort_by === 'judul' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                    Judul Ujian
                                </th>
                                <th onclick="sortTable('mata_pelajaran')" class="sortable <?= $sort_by === 'mata_pelajaran' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                    Mata Pelajaran
                                </th>
                                <th onclick="sortTable('nama_guru')" class="sortable <?= $sort_by === 'nama_guru' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                    Guru Pengajar
                                </th>
                                <th>Durasi</th>
                                <th>Total Soal</th>
                                <th onclick="sortTable('status')" class="sortable <?= $sort_by === 'status' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                    Status
                                </th>
                                <th onclick="sortTable('tanggal_mulai')" class="sortable <?= $sort_by === 'tanggal_mulai' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                    Tanggal Mulai
                                </th>
                                <th onclick="sortTable('created_at')" class="sortable <?= $sort_by === 'created_at' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                    Dibuat
                                </th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ujian)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-8 text-gray-500">
                                    <i class="ti ti-clipboard-off text-3xl mb-3 block"></i>
                                    <?php if (!empty($search) || !empty($status_filter) || !empty($mapel_filter) || !empty($guru_filter)): ?>
                                        Tidak ada ujian yang sesuai dengan filter
                                    <?php else: ?>
                                        Belum ada ujian yang terdaftar
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($ujian as $exam): ?>
                                <tr data-ujian-id="<?= $exam['id'] ?>">
                                    <td>
                                        <span class="font-mono text-sm">#<?= $exam['id'] ?></span>
                                    </td>
                                    <td class="editable-cell" data-field="judul" data-ujian-id="<?= $exam['id'] ?>">
                                        <div class="editable-content">
                                            <span class="display-text font-medium"><?= htmlspecialchars($exam['judul']) ?></span>
                                            <input type="text" class="edit-input" value="<?= htmlspecialchars($exam['judul']) ?>" style="display: none;">
                                        </div>
                                    </td>
                                    <td>
                                        <span class="mapel-badge"><?= htmlspecialchars($exam['mata_pelajaran']) ?></span>
                                    </td>
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="font-medium text-sm"><?= htmlspecialchars($exam['nama_guru'] ?? 'Belum ditentukan') ?></span>
                                            <?php if ($exam['email_guru']): ?>
                                            <span class="text-xs text-gray-500"><?= htmlspecialchars($exam['email_guru']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="editable-cell" data-field="durasi" data-ujian-id="<?= $exam['id'] ?>">
                                        <div class="editable-content">
                                            <span class="display-text">
                                                <span class="duration-badge"><?= $exam['durasi'] ?> menit</span>
                                            </span>
                                            <input type="number" class="edit-input" value="<?= $exam['durasi'] ?>" min="1" style="display: none;">
                                        </div>
                                    </td>
                                    <td>
                                        <span class="font-medium"><?= $exam['total_soal'] ?? 0 ?></span>
                                        <span class="text-xs text-gray-500">soal</span>
                                    </td>
                                    <td class="editable-cell" data-field="status" data-ujian-id="<?= $exam['id'] ?>">
                                        <div class="editable-content">
                                            <span class="display-text">
                                                <span class="status-badge status-<?= $exam['status'] ?>">
                                                    <?= ucfirst($exam['status']) ?>
                                                </span>
                                            </span>
                                            <select class="edit-select" style="display: none;">
                                                <option value="draft" <?= $exam['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                                <option value="aktif" <?= $exam['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                                <option value="selesai" <?= $exam['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                                <option value="arsip" <?= $exam['status'] === 'arsip' ? 'selected' : '' ?>>Arsip</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="editable-cell" data-field="tanggal_mulai" data-ujian-id="<?= $exam['id'] ?>">
                                        <div class="editable-content">
                                            <span class="display-text text-sm">
                                                <?= $exam['tanggal_mulai'] ? date('d/m/Y H:i', strtotime($exam['tanggal_mulai'])) : 'Belum ditentukan' ?>
                                            </span>
                                            <input type="datetime-local" class="edit-input" value="<?= $exam['tanggal_mulai'] ? date('Y-m-d\TH:i', strtotime($exam['tanggal_mulai'])) : '' ?>" style="display: none;">
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-sm text-gray-600">
                                            <?= date('d/m/Y', strtotime($exam['created_at'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex gap-1">
                                            <button onclick="editUjian(<?= $exam['id'] ?>)" class="action-btn action-btn-edit" title="Edit">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            <button onclick="deleteUjian(<?= $exam['id'] ?>)" class="action-btn action-btn-delete" title="Hapus">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                            <button onclick="viewUjianDetails(<?= $exam['id'] ?>)" class="action-btn action-btn-view" title="Detail">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">«</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">‹</a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">›</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">»</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Filter -->
    <div id="modal-filter" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Filter Ujian</h3>
                    <span class="close" onclick="closeFilterModal()">&times;</span>
                </div>
                
                <form onsubmit="applyFilters(event)" id="filter-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="aktif" <?= $status_filter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                <option value="arsip" <?= $status_filter === 'arsip' ? 'selected' : '' ?>>Arsip</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mata Pelajaran</label>
                            <select name="mapel" class="form-select">
                                <option value="">Semua Mata Pelajaran</option>
                                <?php foreach ($mapel_list as $mapel): ?>
                                <option value="<?= htmlspecialchars($mapel) ?>" <?= $mapel_filter === $mapel ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mapel) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Guru Pengajar</label>
                            <select name="guru" class="form-select">
                                <option value="">Semua Guru</option>
                                <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>" <?= $guru_filter == $teacher['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($teacher['nama']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="submit" class="btn-orange flex-1">
                            <i class="ti ti-filter"></i>
                            Terapkan Filter
                        </button>
                        <button type="button" onclick="clearFilters()" class="btn-secondary">
                            <i class="ti ti-x"></i>
                            Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Add/Edit Ujian -->
    <div id="modal-ujian" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="ujian-modal-title" class="text-lg font-semibold">Tambah Ujian Baru</h3>
                    <span class="close" onclick="closeUjianModal()">&times;</span>
                </div>
                
                <form onsubmit="saveUjian(event)" id="ujian-form">
                    <input type="hidden" name="ujian_id" id="ujian-id">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Judul Ujian <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="judul" 
                                id="ujian-judul"
                                class="form-input" 
                                required 
                                placeholder="Contoh: Ujian Tengah Semester Matematika"
                            >
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Mata Pelajaran <span class="text-red-500">*</span>
                                </label>
                                <select name="mata_pelajaran" id="ujian-mapel" class="form-select" required>
                                    <option value="">Pilih Mata Pelajaran</option>
                                    <?php foreach ($mapel_list as $mapel): ?>
                                    <option value="<?= htmlspecialchars($mapel) ?>"><?= htmlspecialchars($mapel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Guru Pengajar <span class="text-red-500">*</span>
                                </label>
                                <select name="guru_id" id="ujian-guru" class="form-select" required>
                                    <option value="">Pilih Guru</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Kelas <span class="text-red-500">*</span>
                            </label>
                            <select name="kelas_id" id="ujian-kelas" class="form-select" required>
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($kelas_list as $kelas): ?>
                                <option value="<?= $kelas['id'] ?>"><?= htmlspecialchars($kelas['namaKelas']) ?> - <?= htmlspecialchars($kelas['mataPelajaran']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                            <textarea 
                                name="deskripsi" 
                                id="ujian-deskripsi"
                                class="form-textarea" 
                                placeholder="Deskripsi ujian (opsional)"
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Durasi (menit) <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    name="durasi" 
                                    id="ujian-durasi"
                                    class="form-input" 
                                    required 
                                    min="1"
                                    placeholder="90"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select name="status" id="ujian-status" class="form-select" required>
                                    <option value="draft">Draft</option>
                                    <option value="aktif">Aktif</option>
                                    <option value="selesai">Selesai</option>
                                    <option value="arsip">Arsip</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai</label>
                                <input 
                                    type="datetime-local" 
                                    name="tanggal_mulai" 
                                    id="ujian-tanggal"
                                    class="form-input"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="submit" class="btn-orange flex-1">
                            <i class="ti ti-device-floppy"></i>
                            Simpan Ujian
                        </button>
                        <button type="button" onclick="closeUjianModal()" class="btn-secondary">
                            <i class="ti ti-x"></i>
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '../logic/admin-ujian-api.php';

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
                    message: 'Terjadi kesalahan koneksi'
                };
            }
        }

        // Search functionality
        document.getElementById('search-input').addEventListener('input', function(e) {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                const searchValue = e.target.value.trim();
                updateUrl({ search: searchValue, page: 1 });
            }, 500);
        });

        // Filter modal functions
        function showFilterModal() {
            document.getElementById('modal-filter').classList.add('show');
        }

        function closeFilterModal() {
            document.getElementById('modal-filter').classList.remove('show');
        }

        function applyFilters(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const params = {};
            
            for (const [key, value] of formData.entries()) {
                if (value) {
                    params[key] = value;
                }
            }
            
            params.page = 1; // Reset to first page
            updateUrl(params);
            closeFilterModal();
        }

        // Remove specific filter
        function removeFilter(filterType) {
            const params = { [filterType]: '' };
            if (filterType !== 'search') {
                params.page = 1;
            }
            updateUrl(params);
        }

        // Clear all filters
        function clearFilters() {
            updateUrl({ 
                search: '',
                status: '',
                mapel: '',
                guru: '',
                page: 1 
            });
        }

        // Update URL with new parameters
        function updateUrl(params) {
            const url = new URL(window.location);
            
            for (const [key, value] of Object.entries(params)) {
                if (value) {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            }
            
            window.location.href = url.toString();
        }

        // Sort table
        function sortTable(column) {
            const currentSort = new URLSearchParams(window.location.search).get('sort');
            const currentOrder = new URLSearchParams(window.location.search).get('order');
            
            let newOrder = 'ASC';
            if (currentSort === column && currentOrder === 'ASC') {
                newOrder = 'DESC';
            }
            
            updateUrl({ sort: column, order: newOrder, page: 1 });
        }

        // Ujian modal functions
        function showAddUjianModal() {
            document.getElementById('ujian-modal-title').textContent = 'Tambah Ujian Baru';
            document.getElementById('ujian-form').reset();
            document.getElementById('ujian-id').value = '';
            document.getElementById('modal-ujian').classList.add('show');
        }

        function closeUjianModal() {
            document.getElementById('modal-ujian').classList.remove('show');
        }

        // Edit ujian
        async function editUjian(ujianId) {
            const result = await apiCall('get_ujian', { ujian_id: ujianId });
            
            if (result.success) {
                document.getElementById('ujian-modal-title').textContent = 'Edit Ujian';
                document.getElementById('ujian-id').value = result.data.id;
                document.getElementById('ujian-judul').value = result.data.judul || result.data.namaUjian;
                document.getElementById('ujian-mapel').value = result.data.mata_pelajaran || result.data.mataPelajaran;
                document.getElementById('ujian-kelas').value = result.data.kelas_id;
                document.getElementById('ujian-guru').value = result.data.guru_id;
                document.getElementById('ujian-deskripsi').value = result.data.deskripsi || '';
                document.getElementById('ujian-durasi').value = result.data.durasi;
                document.getElementById('ujian-status').value = result.data.status;
                document.getElementById('ujian-tanggal').value = result.data.tanggal_mulai || '';
                document.getElementById('modal-ujian').classList.add('show');
            } else {
                showToast(result.message, 'error');
            }
        }

        // Save ujian
        async function saveUjian(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const ujianId = formData.get('ujian_id');
            const action = ujianId ? 'update_ujian' : 'create_ujian';
            
            const result = await apiCall(action, Object.fromEntries(formData));
            
            if (result.success) {
                showToast(result.message, 'success');
                closeUjianModal();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(result.message, 'error');
            }
        }

        // Delete ujian
        async function deleteUjian(ujianId) {
            if (confirm('Yakin ingin menghapus ujian ini? Tindakan ini tidak dapat dibatalkan!')) {
                const result = await apiCall('delete_ujian', { ujian_id: ujianId });
                
                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            }
        }

        // View ujian details
        function viewUjianDetails(ujianId) {
            // Redirect to ujian details page or open detail modal
            window.open(`ujian-detail.php?id=${ujianId}`, '_blank');
        }

        // Export ujian
        function exportUjian() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.open(`../logic/export-ujian.php?${params.toString()}`, '_blank');
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const filterModal = document.getElementById('modal-filter');
            const ujianModal = document.getElementById('modal-ujian');
            
            if (event.target === filterModal) {
                closeFilterModal();
            }
            
            if (event.target === ujianModal) {
                closeUjianModal();
            }
        });

        // Handle Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeFilterModal();
                closeUjianModal();
            }
        });

        // ===== INLINE EDITING FUNCTIONALITY =====
        
        let currentEditingCell = null;
        let isProcessing = false;

        // Initialize inline editing
        document.addEventListener('DOMContentLoaded', function() {
            initializeInlineEditing();
        });

        function initializeInlineEditing() {
            // Add click listeners to editable cells
            document.querySelectorAll('.editable-cell').forEach(cell => {
                cell.addEventListener('click', function(e) {
                    if (!this.classList.contains('editing') && !isProcessing) {
                        startEdit(this);
                    }
                });
            });
        }

        // Start editing a cell
        function startEdit(cell) {
            console.log('Starting edit for cell:', cell.dataset.field);
            
            // Cancel any other editing first
            if (currentEditingCell && currentEditingCell !== cell) {
                cancelEdit(currentEditingCell);
            }

            currentEditingCell = cell;
            cell.classList.add('editing');

            const displayText = cell.querySelector('.display-text');
            const editInput = cell.querySelector('.edit-input') || cell.querySelector('.edit-select');

            if (!displayText || !editInput) {
                console.error('Display text or edit input not found');
                return;
            }

            // Hide display text and show input
            displayText.style.display = 'none';
            editInput.style.display = 'block';
            
            // Focus and select
            setTimeout(() => {
                editInput.focus();
                if (editInput.type === 'text') {
                    editInput.select();
                }
            }, 50);

            // Add event listeners
            const keydownHandler = function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    saveEdit(cell);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelEdit(cell);
                }
            };

            const blurHandler = function(e) {
                // Small delay to allow for other events
                setTimeout(() => {
                    if (cell.classList.contains('editing')) {
                        saveEdit(cell);
                    }
                }, 100);
            };

            // Store handlers on the cell for later removal
            cell.keydownHandler = keydownHandler;
            cell.blurHandler = blurHandler;

            editInput.addEventListener('keydown', keydownHandler);
            editInput.addEventListener('blur', blurHandler);
        }

        // Save edit
        async function saveEdit(cell) {
            if (!cell.classList.contains('editing') || isProcessing) return;
            
            console.log('Saving edit for cell:', cell.dataset.field);
            isProcessing = true;

            const ujianId = cell.dataset.ujianId;
            const field = cell.dataset.field;
            const editInput = cell.querySelector('.edit-input') || cell.querySelector('.edit-select');
            const displayText = cell.querySelector('.display-text');
            
            if (!editInput || !displayText) {
                console.error('Edit input or display text not found');
                return;
            }

            const newValue = editInput.value.trim();
            const oldValue = getOriginalDisplayValue(displayText, field);

            // Skip if no change
            if (newValue === oldValue) {
                console.log('No change detected, cancelling edit');
                cancelEdit(cell);
                isProcessing = false;
                return;
            }

            // Validate input
            if (field !== 'tanggal_mulai' && !newValue) {
                showToast('Nilai tidak boleh kosong', 'error');
                cancelEdit(cell);
                isProcessing = false;
                return;
            }

            // Show saving state
            cell.classList.add('saving');

            try {
                const result = await apiCall('update_field', {
                    ujian_id: ujianId,
                    field: field,
                    value: newValue
                });

                if (result.success) {
                    updateDisplayAfterSave(cell, field, newValue);
                    showToast(result.message || `${getFieldLabel(field)} berhasil diperbarui`, 'success');
                } else {
                    throw new Error(result.message || 'Gagal menyimpan perubahan');
                }
            } catch (error) {
                console.error('Save error:', error);
                showToast(error.message, 'error');
            }

            // Always finish editing after save attempt
            finishEdit(cell);
            isProcessing = false;
        }

        // Cancel edit
        function cancelEdit(cell) {
            if (!cell.classList.contains('editing')) return;
            
            console.log('Cancelling edit for cell:', cell.dataset.field);

            finishEdit(cell);
        }

        // Finish editing - return to display mode
        function finishEdit(cell) {
            const displayText = cell.querySelector('.display-text');
            const editInput = cell.querySelector('.edit-input') || cell.querySelector('.edit-select');

            if (displayText && editInput) {
                // Remove event listeners
                if (cell.keydownHandler) {
                    editInput.removeEventListener('keydown', cell.keydownHandler);
                    delete cell.keydownHandler;
                }
                if (cell.blurHandler) {
                    editInput.removeEventListener('blur', cell.blurHandler);
                    delete cell.blurHandler;
                }

                // Show display text and hide input
                displayText.style.display = 'block';
                editInput.style.display = 'none';
            }

            // Remove classes
            cell.classList.remove('editing', 'saving');
            
            // Clear current editing cell if it's this one
            if (currentEditingCell === cell) {
                currentEditingCell = null;
            }

            console.log('Finished editing, returned to display mode');
        }

        // Cancel all edits
        function cancelAllEdits() {
            document.querySelectorAll('.editable-cell.editing').forEach(cell => {
                cancelEdit(cell);
            });
        }

        // Update display after successful save
        function updateDisplayAfterSave(cell, field, newValue) {
            const displayText = cell.querySelector('.display-text');
            const editInput = cell.querySelector('.edit-input') || cell.querySelector('.edit-select');
            
            if (!displayText) return;

            console.log('Updating display for field:', field, 'with value:', newValue);

            switch (field) {
                case 'judul':
                    displayText.innerHTML = `<span class="font-medium">${escapeHtml(newValue)}</span>`;
                    break;
                case 'durasi':
                    displayText.innerHTML = `<span class="duration-badge">${escapeHtml(newValue)} menit</span>`;
                    break;
                case 'status':
                    displayText.innerHTML = `<span class="status-badge status-${newValue}">${newValue.charAt(0).toUpperCase() + newValue.slice(1)}</span>`;
                    break;
                case 'tanggal_mulai':
                    if (newValue) {
                        const date = new Date(newValue);
                        const formattedDate = date.toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: '2-digit', 
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        displayText.innerHTML = `<span class="text-sm">${formattedDate}</span>`;
                    } else {
                        displayText.innerHTML = `<span class="text-sm">Belum ditentukan</span>`;
                    }
                    break;
                default:
                    displayText.textContent = newValue;
            }

            // Update the input value too
            if (editInput) {
                editInput.value = newValue;
            }
        }

        // Get original display value for comparison
        function getOriginalDisplayValue(displayElement, field) {
            const text = displayElement.textContent.trim();
            
            switch (field) {
                case 'durasi':
                    return text.replace(' menit', '');
                case 'status':
                    return text.toLowerCase();
                case 'tanggal_mulai':
                    return text === 'Belum ditentukan' ? '' : text;
                default:
                    return text;
            }
        }

        // Get field label for toast
        function getFieldLabel(field) {
            switch (field) {
                case 'judul': return 'Judul ujian';
                case 'durasi': return 'Durasi ujian';
                case 'status': return 'Status ujian';
                case 'tanggal_mulai': return 'Tanggal mulai';
                default: return 'Data';
            }
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Update API function to handle field updates
        const originalApiCall = apiCall;
        apiCall = async function(action, data = {}) {
            if (action === 'update_field') {
                // Special handling for field updates
                console.log('API call for field update:', data);
            }
            
            return originalApiCall(action, data);
        };
    </script>
    <script src="../script/menu-bar-script.js"></script>
</body>

</html>