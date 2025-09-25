<?php
session_start();
$currentPage = 'users';

// Redirect jika belum login atau bukan admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

require_once '../logic/user-logic.php';

$userLogic = new UserLogic();

// Get parameters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sort_by = $_GET['sort'] ?? 'id';
$sort_order = $_GET['order'] ?? 'DESC';
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;

// Get users with filters
$users = $userLogic->getUsers($search, $role_filter, $status_filter, $sort_by, $sort_order, $page, $per_page);
$total_users = $userLogic->countUsers($search, $role_filter, $status_filter);
$total_pages = ceil($total_users / $per_page);

// Get statistics
$stats = $userLogic->getUserStats();
?>

<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Kelola User - Admin Panel</title>
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
        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .users-table th {
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

        .users-table th:hover {
            background: #f1f5f9;
        }

        .users-table th.sortable {
            position: relative;
        }

        .users-table th.sortable::after {
            content: '↕';
            position: absolute;
            right: 8px;
            color: #9ca3af;
            font-size: 12px;
        }

        .users-table th.sorted-asc::after {
            content: '↑';
            color: #f97316;
        }

        .users-table th.sorted-desc::after {
            content: '↓';
            color: #f97316;
        }

        .users-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        .users-table tbody tr:hover {
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

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-unverified {
            background: #e5e7eb;
            color: #4b5563;
        }

        /* Role Badge */
        .role-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }

        .role-admin {
            background: #dbeafe;
            color: #1e40af;
        }

        .role-guru {
            background: #dcfce7;
            color: #166534;
        }

        .role-siswa {
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
            max-width: 500px;
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

        /* Avatar */
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #e5e7eb;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            color: #6b7280;
            font-weight: 600;
            font-size: 14px;
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

        .editable-content {
            position: relative;
        }

        /* Pro Badge */
        .pro-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pro-free {
            background: #e0e7ff;
            color: #3730a3;
        }

        .pro-pro {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #92400e;
            box-shadow: 0 2px 4px rgba(251, 191, 36, 0.3);
            position: relative;
        }

        .pro-pro::after {
            content: '✨';
            margin-left: 4px;
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

        /* Non-editable cells (for admin's own account) */
        td:not(.editable-cell) {
            opacity: 0.7;
            cursor: not-allowed;
        }

        td:not(.editable-cell):hover {
            background: #f9fafb;
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

        /* Responsive */
        @media (max-width: 768px) {
            .users-table th,
            .users-table td {
                padding: 12px 8px;
                font-size: 14px;
            }

            .modal-content {
                width: 95%;
                padding: 16px;
            }

            .search-container {
                max-width: 100%;
                margin-bottom: 12px;
            }

            .control-buttons {
                flex-direction: column;
                gap: 8px;
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
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Kelola User</h1>
                        <p class="text-sm text-gray-600 mt-1">Manajemen semua pengguna sistem</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-xs px-3 py-1 rounded-full font-medium uppercase tracking-wide bg-blue-100 text-blue-700 ring-1 ring-blue-200">Admin Panel</span>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="stat-card">
                        <div class="stat-number text-blue-600"><?= $stats['total'] ?></div>
                        <div class="stat-label">Total User</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-green-600"><?= $stats['guru'] ?></div>
                        <div class="stat-label">Guru</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-yellow-600"><?= $stats['siswa'] ?></div>
                        <div class="stat-label">Siswa</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-red-600"><?= $stats['admin'] ?></div>
                        <div class="stat-label">Admin</div>
                    </div>
                </div>

                <!-- Control Panel -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="ti ti-search mr-2"></i>
                        Pencarian & Filter
                    </h2>
                    <div class="flex flex-col md:flex-row items-end gap-4">
                        <!-- Search Bar (expand to fill available space) -->
                        <div class="search-container flex-1" style="max-width: none;">
                            <input type="text" 
                                   id="search-input" 
                                   class="search-input w-full" 
                                   placeholder="Cari nama, email, atau ID user..."
                                   value="<?= htmlspecialchars($search) ?>">
                            <i class="ti ti-search search-icon"></i>
                        </div>

                        <!-- Action Buttons (fixed width, berada di sisi kanan) -->
                        <div class="flex gap-3 control-buttons items-center md:flex-none"></div>
                            <button onclick="showFilterModal()" class="btn-secondary">
                                <i class="ti ti-filter"></i>
                                Filter
                                <?php if ($role_filter || $status_filter): ?>
                                    <span class="filter-badge">
                                        <?= ($role_filter ? '1' : '0') + ($status_filter ? '1' : '0') ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                            
                            <button onclick="clearFilters()" class="btn-secondary">
                                <i class="ti ti-refresh"></i>
                                Reset
                            </button>
                            
                            <button onclick="showAddUserModal()" class="btn-orange">
                                <i class="ti ti-plus"></i>
                                Tambah User
                            </button>
                            
                            <button onclick="exportUsers()" class="btn-secondary">
                                <i class="ti ti-download"></i>
                                Export
                            </button>
                        </div>
                    </div>

                    <!-- Active Filters -->
                    <?php if ($role_filter || $status_filter || $search): ?>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="text-sm text-gray-600">Filter aktif:</span>
                        <?php if ($search): ?>
                            <span class="filter-badge">
                                Search: "<?= htmlspecialchars($search) ?>"
                                <button onclick="removeFilter('search')" class="ml-1">×</button>
                            </span>
                        <?php endif; ?>
                        <?php if ($role_filter): ?>
                            <span class="filter-badge">
                                Role: <?= ucfirst($role_filter) ?>
                                <button onclick="removeFilter('role')" class="ml-1">×</button>
                            </span>
                        <?php endif; ?>
                        <?php if ($status_filter): ?>
                            <span class="filter-badge">
                                Status: <?= ucfirst($status_filter) ?>
                                <button onclick="removeFilter('status')" class="ml-1">×</button>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Users Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">
                            Daftar User (<?= number_format($total_users) ?> total)
                        </h2>
                        <div class="text-sm text-gray-600">
                            Halaman <?= $page ?> dari <?= $total_pages ?>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th class="sortable" onclick="sortTable('id')">
                                        ID
                                        <?php if ($sort_by === 'id'): ?>
                                            <span class="sorted-<?= $sort_order === 'ASC' ? 'asc' : 'desc' ?>"></span>
                                        <?php endif; ?>
                                    </th>
                                    <th>Avatar</th>
                                    <th class="sortable" onclick="sortTable('nama')">
                                        Nama
                                        <?php if ($sort_by === 'nama'): ?>
                                            <span class="sorted-<?= $sort_order === 'ASC' ? 'asc' : 'desc' ?>"></span>
                                        <?php endif; ?>
                                    </th>
                                    <th class="sortable" onclick="sortTable('email')">
                                        Email
                                        <?php if ($sort_by === 'email'): ?>
                                            <span class="sorted-<?= $sort_order === 'ASC' ? 'asc' : 'desc' ?>"></span>
                                        <?php endif; ?>
                                    </th>
                                    <th class="sortable" onclick="sortTable('role')">
                                        Role
                                        <?php if ($sort_by === 'role'): ?>
                                            <span class="sorted-<?= $sort_order === 'ASC' ? 'asc' : 'desc' ?>"></span>
                                        <?php endif; ?>
                                    </th>
                                    <th class="sortable" onclick="sortTable('status')">
                                        Status
                                        <?php if ($sort_by === 'status'): ?>
                                            <span class="sorted-<?= $sort_order === 'ASC' ? 'asc' : 'desc' ?>"></span>
                                        <?php endif; ?>
                                    </th>
                                    <th>Pro Status</th>
                                    <th class="sortable" onclick="sortTable('tanggal_registrasi')">
                                        Terdaftar
                                        <?php if ($sort_by === 'tanggal_registrasi'): ?>
                                            <span class="sorted-<?= $sort_order === 'ASC' ? 'asc' : 'desc' ?>"></span>
                                        <?php endif; ?>
                                    </th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-8 text-gray-500">
                                            <i class="ti ti-users-off text-2xl mb-2 block"></i>
                                            Tidak ada user yang ditemukan
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr data-user-id="<?= $user['id'] ?>">
                                            <td class="font-mono text-sm">#<?= $user['id'] ?></td>
                                            <td>
                                                <div class="avatar">
                                                    <?php if (!empty($user['fotoProfil'])): ?>
                                                        <img src="../../uploads/profile/<?= htmlspecialchars($user['fotoProfil']) ?>" 
                                                             alt="Avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                        <div class="avatar-fallback" style="display: none;">
                                                            <?= strtoupper(substr($user['nama'], 0, 2)) ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="avatar-fallback">
                                                            <?= strtoupper(substr($user['nama'], 0, 2)) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="editable-cell" data-field="nama" data-user-id="<?= $user['id'] ?>">
                                                <div class="editable-content">
                                                    <span class="display-text font-medium text-gray-900"><?= htmlspecialchars($user['nama']) ?></span>
                                                    <input type="text" class="edit-input form-input" value="<?= htmlspecialchars($user['nama']) ?>" style="display: none;">
                                                    <?php if (!empty($user['username'])): ?>
                                                        <div class="text-sm text-gray-500">@<?= htmlspecialchars($user['username']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="editable-cell" data-field="email" data-user-id="<?= $user['id'] ?>">
                                                <span class="display-text text-sm"><?= htmlspecialchars($user['email']) ?></span>
                                                <input type="email" class="edit-input form-input" value="<?= htmlspecialchars($user['email']) ?>" style="display: none;">
                                            </td>
                                            <td class="<?= ($user['id'] == $_SESSION['user']['id']) ? '' : 'editable-cell' ?>" data-field="role" data-user-id="<?= $user['id'] ?>"
                                                <?= ($user['id'] == $_SESSION['user']['id']) ? 'title="Tidak dapat mengubah role akun sendiri"' : '' ?>>
                                                <span class="display-text role-badge role-<?= $user['role'] ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                    <?= ($user['id'] == $_SESSION['user']['id']) ? ' 🔒' : '' ?>
                                                </span>
                                                <?php if ($user['id'] != $_SESSION['user']['id']): ?>
                                                <select class="edit-input form-select" style="display: none;">
                                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                    <option value="guru" <?= $user['role'] === 'guru' ? 'selected' : '' ?>>Guru</option>
                                                    <option value="siswa" <?= $user['role'] === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                                                </select>
                                                <?php endif; ?>
                                            </td>
                                            <td class="<?= ($user['id'] == $_SESSION['user']['id']) ? '' : 'editable-cell' ?>" data-field="status" data-user-id="<?= $user['id'] ?>"
                                                <?= ($user['id'] == $_SESSION['user']['id']) ? 'title="Tidak dapat mengubah status akun sendiri"' : '' ?>>
                                                <span class="display-text status-badge status-<?= $user['status'] ?? 'aktif' ?>">
                                                    <?= ucfirst($user['status'] ?? 'aktif') ?>
                                                    <?= ($user['id'] == $_SESSION['user']['id']) ? ' 🔒' : '' ?>
                                                </span>
                                                <?php if ($user['id'] != $_SESSION['user']['id']): ?>
                                                <select class="edit-input form-select" style="display: none;">
                                                    <option value="aktif" <?= ($user['status'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                                    <option value="nonaktif" <?= ($user['status'] ?? 'aktif') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                                    <option value="pending" <?= ($user['status'] ?? 'aktif') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="unverified" <?= ($user['status'] ?? 'aktif') === 'unverified' ? 'selected' : '' ?>>Unverified</option>
                                                </select>
                                                <?php endif; ?>
                                            </td>
                                            <td class="editable-cell" data-field="pro_status" data-user-id="<?= $user['id'] ?>">
                                                <span class="display-text pro-badge pro-free">Free</span>
                                                <select class="edit-input form-select" style="display: none;">
                                                    <option value="free" selected>Free</option>
                                                    <option value="pro">Pro</option>
                                                </select>
                                            </td>
                                            <td class="text-sm text-gray-600">
                                                <?= date('d/m/Y H:i', strtotime($user['tanggal_registrasi'])) ?>
                                            </td>
                                            <td>
                                                <div class="flex gap-1">
                                                    <button onclick="editUser(<?= $user['id'] ?>)" 
                                                            class="action-btn action-btn-edit" 
                                                            title="Edit User">
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                    <?php if ($user['status'] === 'aktif'): ?>
                                                        <button onclick="toggleUserStatus(<?= $user['id'] ?>, 'nonaktif')" 
                                                                class="action-btn action-btn-delete" 
                                                                title="Nonaktifkan User">
                                                            <i class="ti ti-user-off"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button onclick="toggleUserStatus(<?= $user['id'] ?>, 'aktif')" 
                                                                class="action-btn action-btn-activate" 
                                                                title="Aktifkan User">
                                                            <i class="ti ti-user-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button onclick="deleteUser(<?= $user['id'] ?>)" 
                                                            class="action-btn action-btn-delete" 
                                                            title="Hapus User">
                                                        <i class="ti ti-trash"></i>
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
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">
                                    <i class="ti ti-chevrons-left"></i>
                                </a>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                    <i class="ti ti-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                    <i class="ti ti-chevron-right"></i>
                                </a>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">
                                    <i class="ti ti-chevrons-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Filter -->
    <div id="modal-filter" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Filter User</h3>
                    <button class="close" onclick="closeFilterModal()">&times;</button>
                </div>

                <form id="filter-form" onsubmit="applyFilters(event)">
                    <div class="space-y-4">
                        <!-- Role Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Filter berdasarkan Role
                            </label>
                            <select name="role" class="form-select">
                                <option value="">Semua Role</option>
                                <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="guru" <?= $role_filter === 'guru' ? 'selected' : '' ?>>Guru</option>
                                <option value="siswa" <?= $role_filter === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Filter berdasarkan Status
                            </label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="aktif" <?= $status_filter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= $status_filter === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="unverified" <?= $status_filter === 'unverified' ? 'selected' : '' ?>>Unverified</option>
                            </select>
                        </div>

                        <!-- Date Range Filter -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Terdaftar dari
                                </label>
                                <input type="date" name="date_from" class="form-input" 
                                       value="<?= $_GET['date_from'] ?? '' ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Terdaftar sampai
                                </label>
                                <input type="date" name="date_to" class="form-input" 
                                       value="<?= $_GET['date_to'] ?? '' ?>">
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="closeFilterModal()" class="btn-secondary flex-1">
                            <i class="ti ti-x"></i>
                            Batal
                        </button>
                        <button type="submit" class="btn-orange flex-1">
                            <i class="ti ti-filter"></i>
                            Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Add/Edit User -->
    <div id="modal-user" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="modal-user-title" class="text-lg font-semibold text-gray-800">Tambah User Baru</h3>
                    <button class="close" onclick="closeUserModal()">&times;</button>
                </div>

                <form id="user-form" onsubmit="saveUser(event)">
                    <input type="hidden" id="user-id" name="user_id">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Lengkap *
                            </label>
                            <input type="text" id="user-nama" name="nama" class="form-input" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email *
                            </label>
                            <input type="email" id="user-email" name="email" class="form-input" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Username
                            </label>
                            <input type="text" id="user-username" name="username" class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Role *
                            </label>
                            <select id="user-role" name="role" class="form-select" required>
                                <option value="">Pilih Role</option>
                                <option value="admin">Admin</option>
                                <option value="guru">Guru</option>
                                <option value="siswa">Siswa</option>
                            </select>
                        </div>

                        <div id="password-section">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Password <span id="password-required">*</span>
                            </label>
                            <input type="password" id="user-password" name="password" class="form-input">
                            <p class="text-xs text-gray-500 mt-1">
                                <span id="password-hint">Minimal 6 karakter</span>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Status
                            </label>
                            <select id="user-status" name="status" class="form-select">
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="closeUserModal()" class="btn-secondary flex-1">
                            <i class="ti ti-x"></i>
                            Batal
                        </button>
                        <button type="submit" class="btn-orange flex-1">
                            <i class="ti ti-check"></i>
                            Simpan User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '../logic/admin-users-api.php';

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
                    message: 'Network error'
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
                if (value.trim() !== '') {
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
                role: '', 
                status: '', 
                date_from: '', 
                date_to: '', 
                page: 1 
            });
        }

        // Update URL with new parameters
        function updateUrl(params) {
            const url = new URL(window.location);
            
            for (const [key, value] of Object.entries(params)) {
                if (value === '' || value === null || value === undefined) {
                    url.searchParams.delete(key);
                } else {
                    url.searchParams.set(key, value);
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

        // User modal functions
        function showAddUserModal() {
            document.getElementById('modal-user-title').textContent = 'Tambah User Baru';
            document.getElementById('user-form').reset();
            document.getElementById('user-id').value = '';
            document.getElementById('user-password').required = true;
            document.getElementById('password-required').style.display = 'inline';
            document.getElementById('password-hint').textContent = 'Minimal 6 karakter';
            document.getElementById('modal-user').classList.add('show');
        }

        function closeUserModal() {
            document.getElementById('modal-user').classList.remove('show');
        }

        // Edit user
        async function editUser(userId) {
            const result = await apiCall('get_user', { user_id: userId });
            
            if (result.success) {
                const user = result.data;
                document.getElementById('modal-user-title').textContent = 'Edit User';
                document.getElementById('user-id').value = user.id;
                document.getElementById('user-nama').value = user.nama;
                document.getElementById('user-email').value = user.email;
                document.getElementById('user-username').value = user.username || '';
                document.getElementById('user-role').value = user.role;
                document.getElementById('user-status').value = user.status || 'active';
                document.getElementById('user-password').required = false;
                document.getElementById('password-required').style.display = 'none';
                document.getElementById('password-hint').textContent = 'Kosongkan jika tidak ingin mengubah password';
                document.getElementById('modal-user').classList.add('show');
            } else {
                showToast('Gagal memuat data user', 'error');
            }
        }

        // Save user
        async function saveUser(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const userId = formData.get('user_id');
            const action = userId ? 'update_user' : 'create_user';
            
            const result = await apiCall(action, Object.fromEntries(formData));
            
            if (result.success) {
                showToast(result.message);
                closeUserModal();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(result.message, 'error');
            }
        }

        // Toggle user status
        async function toggleUserStatus(userId, newStatus) {
            const statusText = newStatus === 'active' ? 'mengaktifkan' : 'menonaktifkan';
            
            if (confirm(`Yakin ingin ${statusText} user ini?`)) {
                const result = await apiCall('toggle_status', { 
                    user_id: userId, 
                    status: newStatus 
                });
                
                if (result.success) {
                    showToast(result.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            }
        }

        // Delete user
        async function deleteUser(userId) {
            if (confirm('Yakin ingin menghapus user ini? Tindakan ini tidak dapat dibatalkan!')) {
                const result = await apiCall('delete_user', { user_id: userId });
                
                if (result.success) {
                    showToast(result.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            }
        }

        // Export users
        function exportUsers() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.open(`../logic/export-users.php?${params.toString()}`, '_blank');
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const filterModal = document.getElementById('modal-filter');
            const userModal = document.getElementById('modal-user');
            
            if (event.target === filterModal) {
                closeFilterModal();
            }
            
            if (event.target === userModal) {
                closeUserModal();
            }
        });

        // Handle Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeFilterModal();
                closeUserModal();
                cancelAllEdits();
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
            
            // Get current user ID from PHP session (we'll need to pass this from PHP)
            const currentUserId = <?= $_SESSION['user']['id'] ?? 0 ?>;
            const editingUserId = parseInt(cell.dataset.userId);
            const field = cell.dataset.field;
            
            // Prevent admin from changing their own status or role
            if (currentUserId === editingUserId && (field === 'status' || field === 'role')) {
                if (field === 'status') {
                    showToast('Tidak dapat mengubah status akun sendiri', 'error');
                } else {
                    showToast('Tidak dapat mengubah role akun sendiri', 'error');
                }
                return;
            }
            
            // Cancel any other editing first
            if (currentEditingCell && currentEditingCell !== cell) {
                cancelEdit(currentEditingCell);
            }

            currentEditingCell = cell;
            cell.classList.add('editing');

            const displayText = cell.querySelector('.display-text');
            const editInput = cell.querySelector('.edit-input');

            if (!displayText || !editInput) {
                console.error('Missing display-text or edit-input elements');
                return;
            }

            // Hide display text and show input
            displayText.style.display = 'none';
            editInput.style.display = 'block';
            
            // Focus and select
            setTimeout(() => {
                editInput.focus();
                if (editInput.tagName === 'INPUT') {
                    editInput.select();
                }
            }, 50);

            // Add event listeners
            const keydownHandler = function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveEdit(cell);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelEdit(cell);
                }
            };

            const blurHandler = function(e) {
                // Small delay to allow clicking other elements
                setTimeout(() => {
                    if (cell.classList.contains('editing') && !isProcessing) {
                        saveEdit(cell);
                    }
                }, 200);
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

            const userId = cell.dataset.userId;
            const field = cell.dataset.field;
            const editInput = cell.querySelector('.edit-input');
            const displayText = cell.querySelector('.display-text');
            
            if (!editInput || !displayText) {
                isProcessing = false;
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
            if (!newValue) {
                showToast('Nilai tidak boleh kosong', 'error');
                editInput.focus();
                isProcessing = false;
                return;
            }

            if (field === 'email' && !isValidEmail(newValue)) {
                showToast('Format email tidak valid', 'error');
                editInput.focus();
                isProcessing = false;
                return;
            }

            // Show saving state
            cell.classList.add('saving');

            try {
                console.log('Calling API to update field:', field, 'with value:', newValue);
                
                // Call API to update
                const result = await apiCall('update_field', {
                    user_id: userId,
                    field: field,
                    value: newValue
                });

                if (result.success) {
                    console.log('API call successful, updating display');
                    // Update display with new value
                    updateDisplayAfterSave(cell, field, newValue);
                    showToast(`${getFieldLabel(field)} berhasil diupdate`);
                } else {
                    console.log('API call failed:', result.message);
                    showToast(result.message || 'Gagal mengupdate data', 'error');
                    // Keep the original value
                }
            } catch (error) {
                console.error('Save error:', error);
                showToast('Error saat menyimpan data', 'error');
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
            const editInput = cell.querySelector('.edit-input');

            if (displayText && editInput) {
                // Remove event listeners
                if (cell.keydownHandler) {
                    editInput.removeEventListener('keydown', cell.keydownHandler);
                }
                if (cell.blurHandler) {
                    editInput.removeEventListener('blur', cell.blurHandler);
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
            const editInput = cell.querySelector('.edit-input');
            
            if (!displayText) return;

            console.log('Updating display for field:', field, 'with value:', newValue);

            switch (field) {
                case 'nama':
                    displayText.textContent = newValue;
                    break;
                case 'email':
                    displayText.textContent = newValue;
                    break;
                case 'role':
                    displayText.className = `display-text role-badge role-${newValue}`;
                    displayText.textContent = newValue.charAt(0).toUpperCase() + newValue.slice(1);
                    // Update the select options
                    if (editInput && editInput.tagName === 'SELECT') {
                        editInput.value = newValue;
                    }
                    break;
                case 'status':
                    displayText.className = `display-text status-badge status-${newValue}`;
                    displayText.textContent = newValue.charAt(0).toUpperCase() + newValue.slice(1);
                    // Update the select options
                    if (editInput && editInput.tagName === 'SELECT') {
                        editInput.value = newValue;
                    }
                    break;
                case 'pro_status':
                    displayText.className = `display-text pro-badge pro-${newValue}`;
                    displayText.textContent = newValue.charAt(0).toUpperCase() + newValue.slice(1);
                    // Update the select options
                    if (editInput && editInput.tagName === 'SELECT') {
                        editInput.value = newValue;
                    }
                    break;
            }
        }

        // Get original display value for comparison
        function getOriginalDisplayValue(displayElement, field) {
            if (field === 'role' || field === 'status' || field === 'pro_status') {
                // For badge elements, get the text and convert to lowercase
                let text = displayElement.textContent.trim();
                
                // Remove lock icon if present
                text = text.replace(' 🔒', '').trim();
                
                return text.toLowerCase();
            }
            return displayElement.textContent.trim();
        }

        // Get field label for toast
        function getFieldLabel(field) {
            const labels = {
                nama: 'Nama',
                email: 'Email',
                role: 'Role',
                status: 'Status',
                pro_status: 'Pro Status'
            };
            return labels[field] || field;
        }

        // Validate email
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Update API function to handle field updates
        const originalApiCall = apiCall;
        apiCall = async function(action, data = {}) {
            if (action === 'update_field') {
                // Handle inline field update
                const formData = new FormData();
                formData.append('action', 'update_user_field');
                formData.append('user_id', data.user_id);
                formData.append('field', data.field);
                formData.append('value', data.value);

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
            } else {
                return originalApiCall(action, data);
            }
        };
    </script>
    <script src="../script/menu-bar-script.js"></script>
</body>

</html>