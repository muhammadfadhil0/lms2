<?php
session_start();
$currentPage = 'kelas';

// Redirect jika belum login atau bukan admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

require_once '../logic/koneksi.php';

// Database connection
$db = getPDOConnection();

// Get parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$guru_filter = $_GET['guru'] ?? '';
$sort_by = $_GET['sort'] ?? 'id';
$sort_order = $_GET['order'] ?? 'DESC';
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = ['1=1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(k.namaKelas LIKE ? OR k.deskripsi LIKE ? OR u.namaLengkap LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "k.status = ?";
    $params[] = $status_filter;
}

if (!empty($guru_filter)) {
    $where_conditions[] = "k.guru_id = ?";
    $params[] = $guru_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Count total classes
$count_sql = "SELECT COUNT(*) FROM kelas k LEFT JOIN users u ON k.guru_id = u.id WHERE $where_clause";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_classes = $count_stmt->fetchColumn();
$total_pages = ceil($total_classes / $per_page);

// Get classes with pagination
$sql = "
    SELECT 
        k.*,
        u.namaLengkap as nama_guru,
        u.email as email_guru,
        (SELECT COUNT(*) FROM kelas_siswa ks WHERE ks.kelas_id = k.id AND ks.status = 'aktif') as jumlah_siswa,
        k.dibuat as created_at,
        k.updated_at
    FROM kelas k
    LEFT JOIN users u ON k.guru_id = u.id
    WHERE $where_clause
    ORDER BY k.$sort_by $sort_order
    LIMIT $per_page OFFSET $offset
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total_kelas,
        COUNT(CASE WHEN status = 'aktif' THEN 1 END) as kelas_aktif,
        COUNT(CASE WHEN status = 'nonaktif' THEN 1 END) as kelas_nonaktif,
        COUNT(CASE WHEN status = 'arsip' THEN 1 END) as kelas_arsip
    FROM kelas
";
$stats_stmt = $db->prepare($stats_sql);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get teachers for filter dropdown
$guru_sql = "SELECT id, namaLengkap as nama FROM users WHERE role = 'guru' ORDER BY namaLengkap";
$guru_stmt = $db->prepare($guru_sql);
$guru_stmt->execute();
$teachers = $guru_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Kelola Kelas - Admin Panel</title>
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
        .classes-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .classes-table th {
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

        .classes-table th:hover {
            background: #f1f5f9;
        }

        .classes-table th.sortable {
            position: relative;
        }

        .classes-table th.sortable::after {
            content: '↕';
            position: absolute;
            right: 8px;
            color: #9ca3af;
            font-size: 12px;
        }

        .classes-table th.sorted-asc::after {
            content: '↑';
            color: #f97316;
        }

        .classes-table th.sorted-desc::after {
            content: '↓';
            color: #f97316;
        }

        .classes-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        .classes-table tbody tr:hover {
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

        .status-aktif {
            background: #dcfce7;
            color: #166534;
        }

        .status-nonaktif {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-arsip {
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
        .form-input, .form-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-input:focus, .form-textarea:focus {
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

        .action-btn-activate {
            background: #dcfce7;
            color: #166534;
        }

        .action-btn-activate:hover {
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

        .editable-cell .edit-input, .editable-cell .edit-textarea, .editable-cell .edit-select {
            width: 100%;
            min-width: 120px;
            font-size: 14px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 6px 8px;
        }

        .editable-cell .edit-textarea {
            min-height: 60px;
            resize: vertical;
        }

        .editable-cell .edit-input:focus, .editable-cell .edit-textarea:focus, .editable-cell .edit-select:focus {
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

        /* Class Card for mobile view */
        .class-card {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .class-card-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .class-card-title {
            font-weight: 600;
            font-size: 16px;
            color: #1f2937;
        }

        .class-card-info {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .classes-table {
                display: none;
            }
            
            .mobile-view {
                display: block;
            }
            
            .desktop-view {
                display: none;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
                margin: 10px;
            }

            .search-container {
                max-width: 100%;
            }

            .control-buttons {
                flex-direction: column;
                gap: 8px;
            }
        }

        @media (min-width: 769px) {
            .mobile-view {
                display: none;
            }
            
            .desktop-view {
                display: block;
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
                    <h1 class="text-2xl font-bold text-gray-900">Kelola Kelas</h1>
                    <p class="text-gray-600 mt-1">Manajemen semua kelas dalam sistem</p>
                </div>
                <button onclick="showAddClassModal()" class="btn-orange">
                    <i class="ti ti-plus"></i>
                    Tambah Kelas
                </button>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="stat-card">
                        <div class="stat-number text-blue-600"><?= number_format($stats['total_kelas']) ?></div>
                        <div class="stat-label">Total Kelas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-green-600"><?= number_format($stats['kelas_aktif']) ?></div>
                        <div class="stat-label">Kelas Aktif</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-red-600"><?= number_format($stats['kelas_nonaktif']) ?></div>
                        <div class="stat-label">Kelas Nonaktif</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-yellow-600"><?= number_format($stats['kelas_arsip']) ?></div>
                        <div class="stat-label">Arsip</div>
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
                                placeholder="Cari nama kelas, deskripsi, atau guru..."
                                value="<?= htmlspecialchars($search) ?>"
                            >
                            <i class="ti ti-search search-icon"></i>
                        </div>
                        
                        <div class="control-buttons flex gap-2">
                            <button onclick="showFilterModal()" class="btn-secondary">
                                <i class="ti ti-filter"></i>
                                Filter
                            </button>
                            <button onclick="exportClasses()" class="btn-secondary">
                                <i class="ti ti-download"></i>
                                Export
                            </button>
                        </div>
                    </div>

                    <!-- Active Filters -->
                    <?php if (!empty($search) || !empty($status_filter) || !empty($guru_filter)): ?>
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

                <!-- Desktop Table View -->
                <div class="desktop-view">
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <table class="classes-table">
                            <thead>
                                <tr>
                                    <th onclick="sortTable('id')" class="sortable <?= $sort_by === 'id' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                        ID
                                    </th>
                                    <th onclick="sortTable('namaKelas')" class="sortable <?= $sort_by === 'namaKelas' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                        Nama Kelas
                                    </th>
                                    <th onclick="sortTable('mataPelajaran')" class="sortable <?= $sort_by === 'mataPelajaran' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                        Mata Pelajaran
                                    </th>
                                    <th onclick="sortTable('kodeKelas')" class="sortable <?= $sort_by === 'kodeKelas' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                        Kode Kelas
                                    </th>
                                    <th onclick="sortTable('nama_guru')" class="sortable <?= $sort_by === 'nama_guru' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                        Guru Pengajar
                                    </th>
                                    <th>Deskripsi</th>
                                    <th>Jumlah Siswa</th>
                                    <th onclick="sortTable('status')" class="sortable <?= $sort_by === 'status' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                        Status
                                    </th>
                                    <th onclick="sortTable('dibuat')" class="sortable <?= $sort_by === 'dibuat' ? 'sorted-' . strtolower($sort_order) : '' ?>">
                                        Dibuat
                                    </th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($classes)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-8 text-gray-500">
                                        <i class="ti ti-inbox text-3xl mb-3 block"></i>
                                        <?php if (!empty($search) || !empty($status_filter) || !empty($guru_filter)): ?>
                                            Tidak ada kelas yang sesuai dengan filter
                                        <?php else: ?>
                                            Belum ada kelas yang terdaftar
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($classes as $class): ?>
                                    <tr data-class-id="<?= $class['id'] ?>">
                                        <td>
                                            <span class="font-mono text-sm">#<?= $class['id'] ?></span>
                                        </td>
                                        <td class="editable-cell" data-field="namaKelas" data-class-id="<?= $class['id'] ?>">
                                            <div class="editable-content">
                                                <span class="display-text font-medium"><?= htmlspecialchars($class['namaKelas']) ?></span>
                                                <input type="text" class="edit-input" value="<?= htmlspecialchars($class['namaKelas']) ?>" style="display: none;">
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium text-blue-600"><?= htmlspecialchars($class['mataPelajaran']) ?></span>
                                        </td>
                                        <td>
                                            <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($class['kodeKelas']) ?></span>
                                        </td>
                                        <td>
                                            <div class="flex flex-col">
                                                <span class="font-medium text-sm"><?= htmlspecialchars($class['nama_guru'] ?? 'Belum ditentukan') ?></span>
                                                <?php if ($class['email_guru']): ?>
                                                <span class="text-xs text-gray-500"><?= htmlspecialchars($class['email_guru']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                            <!-- <div class="editable-content">
                                                <span class="display-text text-sm text-gray-600"><?= htmlspecialchars($class['deskripsi'] ?? '-') ?></span>
                                                <textarea class="edit-textarea" style="display: none;"><?= htmlspecialchars($class['deskripsi'] ?? '') ?></textarea>
                                            </div> -->
                                        </td>
                                        <td class="editable-cell" data-field="deskripsi" data-class-id="<?= $class['id'] ?>">
                                            <div class="editable-content">
                                                <span class="display-text text-sm text-gray-600"><?= htmlspecialchars($class['deskripsi'] ?? '-') ?></span>
                                                <textarea class="edit-textarea" style="display: none;"><?= htmlspecialchars($class['deskripsi'] ?? '') ?></textarea>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="font-medium"><?= $class['jumlah_siswa'] ?></span>
                                            <span class="text-xs text-gray-500">siswa</span>
                                        </td>
                                        <td class="editable-cell" data-field="status" data-class-id="<?= $class['id'] ?>">
                                            <div class="editable-content">
                                                <span class="display-text">
                                                    <span class="status-badge status-<?= $class['status'] ?>">
                                                        <?= ucfirst($class['status']) ?>
                                                    </span>
                                                </span>
                                                <select class="edit-select" style="display: none;">
                                                    <option value="aktif" <?= $class['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                                    <option value="nonaktif" <?= $class['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                                    <option value="draft" <?= $class['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                                </select>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-sm text-gray-600">
                                                <?= date('d/m/Y', strtotime($class['dibuat'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex gap-1">
                                                <button onclick="editClass(<?= $class['id'] ?>)" class="action-btn action-btn-edit" title="Edit">
                                                    <i class="ti ti-edit"></i>
                                                </button>
                                                <button onclick="deleteClass(<?= $class['id'] ?>)" class="action-btn action-btn-delete" title="Hapus">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                                <button onclick="viewClassDetails(<?= $class['id'] ?>)" class="action-btn action-btn-activate" title="Detail">
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
                </div>

                <!-- Mobile Card View -->
                <div class="mobile-view">
                    <?php if (empty($classes)): ?>
                    <div class="class-card text-center py-8 text-gray-500">
                        <i class="ti ti-inbox text-3xl mb-3 block"></i>
                        <?php if (!empty($search) || !empty($status_filter) || !empty($guru_filter)): ?>
                            Tidak ada kelas yang sesuai dengan filter
                        <?php else: ?>
                            Belum ada kelas yang terdaftar
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                        <?php foreach ($classes as $class): ?>
                        <div class="class-card" data-class-id="<?= $class['id'] ?>">
                            <div class="class-card-header">
                                <div class="flex-1">
                                    <div class="class-card-title"><?= htmlspecialchars($class['namaKelas']) ?></div>
                                    <div class="class-card-info">ID: #<?= $class['id'] ?></div>
                                </div>
                                <span class="status-badge status-<?= $class['status'] ?>">
                                    <?= ucfirst($class['status']) ?>
                                </span>
                            </div>
                            
                            <div class="class-card-info">
                                <strong>Mata Pelajaran:</strong> <?= htmlspecialchars($class['mataPelajaran']) ?>
                            </div>
                            <div class="class-card-info">
                                <strong>Kode:</strong> <?= htmlspecialchars($class['kodeKelas']) ?>
                            </div>
                            <div class="class-card-info">
                                <strong>Guru:</strong> <?= htmlspecialchars($class['nama_guru'] ?? 'Belum ditentukan') ?>
                            </div>
                            <div class="class-card-info">
                                <strong>Siswa:</strong> <?= $class['jumlah_siswa'] ?> siswa
                            </div>
                            <div class="class-card-info">
                                <strong>Dibuat:</strong> <?= date('d/m/Y H:i', strtotime($class['dibuat'])) ?>
                            </div>
                            
                            <?php if ($class['deskripsi']): ?>
                            <div class="class-card-info">
                                <strong>Deskripsi:</strong> <?= htmlspecialchars($class['deskripsi']) ?>
                            </div>
                            <?php endif; ?>

                            <div class="flex gap-2 mt-3">
                                <button onclick="editClass(<?= $class['id'] ?>)" class="action-btn action-btn-edit">
                                    <i class="ti ti-edit"></i> Edit
                                </button>
                                <button onclick="deleteClass(<?= $class['id'] ?>)" class="action-btn action-btn-delete">
                                    <i class="ti ti-trash"></i> Hapus
                                </button>
                                <button onclick="viewClassDetails(<?= $class['id'] ?>)" class="action-btn action-btn-activate">
                                    <i class="ti ti-eye"></i> Detail
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                    <h3 class="text-lg font-semibold">Filter Kelas</h3>
                    <span class="close" onclick="closeFilterModal()">&times;</span>
                </div>
                
                <form onsubmit="applyFilters(event)" id="filter-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="aktif" <?= $status_filter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= $status_filter === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                <option value="arsip" <?= $status_filter === 'arsip' ? 'selected' : '' ?>>Arsip</option>
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

    <!-- Modal Add/Edit Class -->
    <div id="modal-class" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="class-modal-title" class="text-lg font-semibold">Tambah Kelas Baru</h3>
                    <span class="close" onclick="closeClassModal()">&times;</span>
                </div>
                
                <form onsubmit="saveClass(event)" id="class-form">
                    <input type="hidden" name="class_id" id="class-id">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Kelas <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="namaKelas" 
                                id="class-nama" 
                                class="form-input" 
                                required
                                placeholder="Contoh: Kelas X IPA 1"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Mata Pelajaran <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="mataPelajaran" 
                                id="class-mapel" 
                                class="form-input" 
                                required
                                placeholder="Contoh: Matematika"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Kode Kelas <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="kodeKelas" 
                                id="class-kode" 
                                class="form-input" 
                                required
                                placeholder="Contoh: MTK-X1-2025"
                                maxlength="20"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Guru Pengajar <span class="text-red-500">*</span>
                            </label>
                            <select name="guru_id" id="class-guru" class="form-select" required>
                                <option value="">Pilih Guru</option>
                                <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                            <textarea 
                                name="deskripsi" 
                                id="class-deskripsi" 
                                class="form-textarea"
                                placeholder="Deskripsi kelas..."
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Max Siswa
                            </label>
                            <input 
                                type="number" 
                                name="maxSiswa" 
                                id="class-maxsiswa" 
                                class="form-input" 
                                value="30"
                                min="1"
                                max="100"
                                placeholder="Maksimal siswa per kelas"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select name="status" id="class-status" class="form-select" required>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                                <option value="arsip">Arsip</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="submit" class="btn-orange flex-1">
                            <i class="ti ti-device-floppy"></i>
                            Simpan Kelas
                        </button>
                        <button type="button" onclick="closeClassModal()" class="btn-secondary">
                            <i class="ti ti-x"></i>
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '../logic/admin-classes-api.php';

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
                if (value.trim()) {
                    params[key] = value.trim();
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

        // Class modal functions
        function showAddClassModal() {
            document.getElementById('class-modal-title').textContent = 'Tambah Kelas Baru';
            document.getElementById('class-form').reset();
            document.getElementById('class-id').value = '';
            document.getElementById('modal-class').classList.add('show');
        }

        function closeClassModal() {
            document.getElementById('modal-class').classList.remove('show');
        }

        // Edit class
        async function editClass(classId) {
            const result = await apiCall('get_class', { class_id: classId });
            
            if (result.success) {
                const classData = result.data;
                document.getElementById('class-modal-title').textContent = 'Edit Kelas';
                document.getElementById('class-id').value = classData.id;
                document.getElementById('class-nama').value = classData.namaKelas;
                document.getElementById('class-mapel').value = classData.mataPelajaran;
                document.getElementById('class-kode').value = classData.kodeKelas;
                document.getElementById('class-guru').value = classData.guru_id;
                document.getElementById('class-deskripsi').value = classData.deskripsi || '';
                document.getElementById('class-maxsiswa').value = classData.maxSiswa || 30;
                document.getElementById('class-status').value = classData.status;
                document.getElementById('modal-class').classList.add('show');
            } else {
                showToast(result.message, 'error');
            }
        }

        // Save class
        async function saveClass(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const classId = formData.get('class_id');
            const action = classId ? 'update_class' : 'create_class';
            
            const result = await apiCall(action, Object.fromEntries(formData));
            
            if (result.success) {
                showToast(result.message, 'success');
                closeClassModal();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(result.message, 'error');
            }
        }

        // Delete class
        async function deleteClass(classId) {
            if (confirm('Yakin ingin menghapus kelas ini? Tindakan ini tidak dapat dibatalkan!')) {
                const result = await apiCall('delete_class', { class_id: classId });
                
                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            }
        }

        // View class details
        function viewClassDetails(classId) {
            // Redirect to class detail page (you'll need to create this)
            window.location.href = `class-details.php?id=${classId}`;
        }

        // Export classes
        function exportClasses() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.open(`../logic/export-classes.php?${params.toString()}`, '_blank');
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const filterModal = document.getElementById('modal-filter');
            const classModal = document.getElementById('modal-class');
            
            if (event.target === filterModal) {
                closeFilterModal();
            }
            
            if (event.target === classModal) {
                closeClassModal();
            }
        });

        // Handle Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeFilterModal();
                closeClassModal();
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
                    e.preventDefault();
                    e.stopPropagation();
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
            const editInput = cell.querySelector('.edit-input, .edit-textarea, .edit-select');

            if (!displayText || !editInput) {
                console.error('Missing display or edit elements');
                return;
            }

            // Hide display text and show input
            displayText.style.display = 'none';
            editInput.style.display = 'block';
            
            // Focus and select
            setTimeout(() => {
                editInput.focus();
                if (editInput.type !== 'select-one') {
                    editInput.select();
                }
            }, 50);

            // Add event listeners
            const keydownHandler = function(e) {
                e.stopPropagation();
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    saveEdit(cell);
                } else if (e.key === 'Escape') {
                    cancelEdit(cell);
                }
            };

            const blurHandler = function(e) {
                // Delay to allow clicking save button
                setTimeout(() => {
                    if (currentEditingCell === cell) {
                        saveEdit(cell);
                    }
                }, 150);
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

            const classId = cell.dataset.classId;
            const field = cell.dataset.field;
            const editInput = cell.querySelector('.edit-input, .edit-textarea, .edit-select');
            const displayText = cell.querySelector('.display-text');
            
            if (!editInput || !displayText) {
                isProcessing = false;
                return;
            }

            const newValue = editInput.value.trim();
            const oldValue = getOriginalDisplayValue(displayText, field);

            // Skip if no change
            if (newValue === oldValue) {
                finishEdit(cell);
                isProcessing = false;
                return;
            }

            // Validate input
            if (field !== 'deskripsi' && !newValue) {
                showToast('Field ini tidak boleh kosong', 'error');
                finishEdit(cell);
                isProcessing = false;
                return;
            }

            // Show saving state
            cell.classList.add('saving');

            try {
                const result = await apiCall('update_class_field', {
                    class_id: classId,
                    field: field,
                    value: newValue
                });

                if (result.success) {
                    showToast(`${getFieldLabel(field)} berhasil diperbarui`, 'success');
                    updateDisplayAfterSave(cell, field, newValue);
                } else {
                    showToast(result.message || 'Gagal memperbarui data', 'error');
                }
            } catch (error) {
                showToast('Terjadi kesalahan saat menyimpan', 'error');
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
            const editInput = cell.querySelector('.edit-input, .edit-textarea, .edit-select');

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

                // Show display, hide input
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
            const editInput = cell.querySelector('.edit-input, .edit-textarea, .edit-select');
            
            if (!displayText) return;

            // Update input value
            if (editInput) {
                editInput.value = newValue;
            }

            // Update display based on field type
            if (field === 'status') {
                displayText.innerHTML = `<span class="status-badge status-${newValue}">${newValue.charAt(0).toUpperCase() + newValue.slice(1)}</span>`;
            } else if (field === 'deskripsi') {
                displayText.textContent = newValue || '-';
            } else {
                displayText.textContent = newValue;
            }
        }

        // Get original display value for comparison
        function getOriginalDisplayValue(displayElement, field) {
            if (field === 'status') {
                const badge = displayElement.querySelector('.status-badge');
                return badge ? badge.textContent.toLowerCase() : '';
            } else if (field === 'deskripsi') {
                const text = displayElement.textContent.trim();
                return text === '-' ? '' : text;
            } else {
                return displayElement.textContent.trim();
            }
        }

        // Get field label for toast
        function getFieldLabel(field) {
            const labels = {
                'namaKelas': 'Nama kelas',
                'deskripsi': 'Deskripsi',
                'status': 'Status'
            };
            return labels[field] || field;
        }
    </script>
    <script src="../script/menu-bar-script.js"></script>
</body>

</html>