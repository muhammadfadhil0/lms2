<?php
session_start();
$currentPage = 'system-settings';

// Redirect jika belum login atau bukan admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

// Load real maintenance config
$maintenanceConfig = include '../config/maintenance.php';

// Load notifications from database
require_once '../logic/koneksi.php';
require_once '../logic/advertisement-logic.php';

// Get recent global notifications
$notifications_query = "SELECT gn.*, u.namaLengkap as created_by_name,
                               COUNT(nr.id) as read_count
                        FROM global_notifications gn
                        LEFT JOIN users u ON gn.created_by = u.id
                        LEFT JOIN notification_reads nr ON gn.id = nr.notification_id
                        WHERE gn.is_active = TRUE
                        GROUP BY gn.id
                        ORDER BY gn.created_at DESC
                        LIMIT 5";
$notifications_result = $koneksi->query($notifications_query);
$global_notifications = [];
while ($row = $notifications_result->fetch_assoc()) {
    $global_notifications[] = $row;
}

// Get advertisements from database
$advertisementLogic = new AdvertisementLogic();
$advertisements = $advertisementLogic->getAllAdvertisements();

// Get dynamic modals from database
$modals_query = "SELECT dm.*, u.namaLengkap as created_by_name 
                 FROM dynamic_modals dm
                 LEFT JOIN users u ON dm.created_by = u.id
                 ORDER BY dm.priority DESC, dm.created_at DESC";
$modals_result = $koneksi->query($modals_query);
$dynamic_modals = [];
while ($row = $modals_result->fetch_assoc()) {
    $row['target_files_array'] = json_decode($row['target_files'], true);
    $dynamic_modals[] = $row;
}

// Load API Keys
require_once '../logic/api-keys-logic.php';
$apiKeysLogic = new ApiKeysLogic();
$api_keys = $apiKeysLogic->getAllApiKeys();

// Settings data
$settings = [
    'maintenance_mode' => $maintenanceConfig['maintenance_mode'],
    'maintenance_header' => $maintenanceConfig['maintenance_header'] ?? 'Scheduled Infrastructure Upgrade',
    'maintenance_message' => $maintenanceConfig['maintenance_message'] ?? 'Kami sedang melakukan perbaikan sistem. Silakan coba lagi nanti.',
    'api_keys' => $api_keys,
    'global_notifications' => $global_notifications,
    'advertisements' => $advertisements,
    'dynamic_modals' => $dynamic_modals,
    'discounts' => [
        [
            'id' => 1,
            'code' => 'STUDENT2025',
            'percentage' => 25,
            'active' => true,
            'created_at' => '2025-09-15 09:15:00'
        ]
    ]
];
?>

<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    
    <!-- Croppr.js for image cropping -->


    
    <title>Pengaturan Sistem - Admin Panel</title>
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

        .btn-success {
            background: #10b981;
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

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: #ef4444;
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

        .btn-danger:hover {
            background: #dc2626;
        }

        /* Settings Cards */
        .settings-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            font-size: 18px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 12px;
            transition: all 0.2s ease;
        }

        .setting-item:hover {
            border-color: #f97316;
            box-shadow: 0 2px 4px rgba(249, 115, 22, 0.1);
        }

        .setting-info {
            flex: 1;
        }

        .setting-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }

        .setting-description {
            font-size: 14px;
            color: #6b7280;
        }

        .setting-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #f97316;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(24px);
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

        .form-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            resize: vertical;
            min-height: 100px;
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
            background-color: white;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
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

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-maintenance {
            background: #fef3c7;
            color: #92400e;
        }

        /* List Items */
        .list-item {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .list-item:hover {
            border-color: #f97316;
            background: #fef7ed;
        }

        .list-item-content {
            flex: 1;
        }

        .list-item-title {
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }

        .list-item-description {
            font-size: 14px;
            color: #6b7280;
        }

        .list-item-meta {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 4px;
        }

        .list-item-actions {
            display: flex;
            gap: 8px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #d1d5db;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
        }

        /* File Upload */
        .file-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
            overflow: hidden;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.2s ease;
            width: 100%;
        }

        .file-upload:hover {
            border-color: #f97316;
            background: #fef7ed;
        }

        .file-upload input[type=file] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-content {
            pointer-events: none;
        }

        /* Image Preview */
        .image-preview {
            margin-top: 16px;
            display: none;
            text-align: center;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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

        /* Priority Badge */
        .priority-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 8px;
        }

        .priority-low {
            background: #f3f4f6;
            color: #6b7280;
        }

        .priority-medium {
            background: #dbeafe;
            color: #1e40af;
        }

        .priority-high {
            background: #fef3c7;
            color: #92400e;
        }

        .priority-urgent {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .setting-item {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .setting-actions {
                justify-content: flex-end;
            }

            .modal-content {
                width: 95%;
                padding: 16px;
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
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Pengaturan Sistem</h1>
                        <p class="text-sm text-gray-600 mt-1">Kelola konfigurasi dan pengaturan aplikasi</p>
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
                
                <!-- Umum Section -->
                <div class="settings-section">
                    <div class="section-header">
                        <i class="ti ti-settings"></i>
                        Umum
                    </div>

                    <!-- Maintenance Mode -->
                    <div class="setting-item">
                        <div class="setting-info">
                            <div class="setting-title">
                                Maintenance Mode
                                <span id="maintenance-status-badge" class="status-badge <?= $settings['maintenance_mode'] ? 'status-maintenance' : 'status-active' ?>">
                                    <?= $settings['maintenance_mode'] ? 'Maintenance' : 'Normal' ?>
                                </span>
                            </div>
                            <div class="setting-description">Aktifkan untuk mencegah user masuk sementara waktu</div>
                        </div>
                        <div class="setting-actions">
                            <button class="btn-secondary" onclick="showMaintenanceMessageModal()" style="margin-right: 8px;">
                                <i class="ti ti-message"></i>
                                Edit Pesan
                            </button>
                            <label class="toggle-switch">
                                <input type="checkbox" id="maintenance-toggle" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Current Maintenance Message -->
                    <div class="" style="margin-bottom: 12px;">
                        <div class="list-item">
                            <div class="list-item-content">
                                <div class="list-item-title">Pesan Maintenance Saat Ini</div>
                                <div class="list-item-description" id="current-maintenance-message">
                                    <?= htmlspecialchars($settings['maintenance_message']) ?>
                                </div>
                                <div class="list-item-meta">
                                    Diupdate: <?= date('d/m/Y H:i', strtotime($maintenanceConfig['last_updated'] ?? 'now')) ?>
                                    | Oleh: <?= htmlspecialchars($maintenanceConfig['updated_by'] ?? 'System') ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifikasi Global -->
                    <div class="setting-item">
                        <div class="setting-info">
                            <div class="setting-title">Notifikasi Global</div>
                            <div class="setting-description">Kelola notifikasi yang tampil untuk semua pengguna</div>
                        </div>
                        <div class="setting-actions">
                            <button class="btn-orange" onclick="showNotificationModal()">
                                <i class="ti ti-plus"></i>
                                Tambah Notifikasi
                            </button>
                        </div>
                    </div>

                    <!-- Daftar Notifikasi -->
                    <?php if (!empty($settings['global_notifications'])): ?>
                    <div class="ml-4 mb-4" id="admin-notifications-list">
                        <?php foreach ($settings['global_notifications'] as $notification): ?>
                        <div class="list-item">
                            <div class="list-item-content">
                                <div class="list-item-title">
                                    <i class="ti ti-<?= $notification['icon'] ?> mr-1"></i>
                                    <?= htmlspecialchars($notification['title']) ?>
                                    <span class="priority-badge priority-<?= $notification['priority'] ?>"><?= ucfirst($notification['priority']) ?></span>
                                </div>
                                <div class="list-item-description"><?= htmlspecialchars($notification['description']) ?></div>
                                <div class="list-item-meta">
                                    Dibuat: <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?> 
                                    | Oleh: <?= htmlspecialchars($notification['created_by_name'] ?? 'System') ?>
                                    | Dibaca: <?= $notification['read_count'] ?> user
                                    <?php 
                                    $targetRoles = null;
                                    if ($notification['target_roles']) {
                                        $decodedRoles = json_decode($notification['target_roles'], true);
                                        if (is_array($decodedRoles) && !empty($decodedRoles)) {
                                            $targetRoles = implode(', ', $decodedRoles);
                                        }
                                    }
                                    ?>
                                    | Target: <?= $targetRoles ? htmlspecialchars($targetRoles) : 'Semua pengguna' ?>
                                </div>
                            </div>
                            <div class="list-item-actions">
                                <button class="btn-secondary" onclick="editNotification(<?= $notification['id'] ?>)">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <button class="btn-danger" onclick="deleteNotification(<?= $notification['id'] ?>)">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                                    

                    <!-- Papan Iklan -->
                    <div class="setting-item">
                        <div class="setting-info">
                            <div class="setting-title">Papan Iklan</div>
                            <div class="setting-description">Kelola iklan dan banner yang ditampilkan di dashboard</div>
                        </div>
                        <div class="setting-actions">
                            <button class="btn-orange" onclick="showAdvertisementModal()">
                                <i class="ti ti-plus"></i>
                                Tambah Iklan
                            </button>
                        </div>
                    </div>

                    <!-- Daftar Iklan -->
                    <?php if (!empty($settings['advertisements'])): ?>
                    <div class="ml-4 mb-4">
                        <?php foreach ($settings['advertisements'] as $ad): ?>
                        <div class="list-item">
                            <div class="list-item-content">
                                <div class="list-item-title">
                                    <?= htmlspecialchars($ad['title']) ?>
                                    <span class="priority-badge priority-<?= $ad['is_active'] ? 'high' : 'low' ?>">
                                        <?= $ad['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </div>
                                <div class="list-item-description"><?= htmlspecialchars($ad['description']) ?></div>
                                <div class="list-item-meta">
                                    <?php if ($ad['image_path']): ?>
                                        Gambar: <?= htmlspecialchars(basename($ad['image_path'])) ?> | 
                                    <?php endif; ?>
                                    Dibuat: <?= date('d/m/Y H:i', strtotime($ad['created_at'])) ?>
                                    <?php if ($ad['created_by_name']): ?>
                                        | Oleh: <?= htmlspecialchars($ad['created_by_name']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="list-item-actions">
                                <button class="btn-secondary" onclick="editAdvertisement(<?= $ad['id'] ?>)">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <button class="btn-danger" onclick="deleteAdvertisement(<?= $ad['id'] ?>)">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Modal Management -->
                    <div class="setting-item">
                        <div class="setting-info">
                            <div class="setting-title">Modal Management</div>
                            <div class="setting-description">Kelola modal yang ditampilkan di berbagai halaman sistem</div>
                        </div>
                        <div class="setting-actions">
                            <button class="btn-orange" onclick="showDynamicModalForm()">
                                <i class="ti ti-plus"></i>
                                Tambah Modal
                            </button>
                        </div>
                    </div>

                    <!-- Daftar Modal -->
                    <?php if (!empty($settings['dynamic_modals'])): ?>
                    <div class="ml-4 mb-4">
                        <?php foreach ($settings['dynamic_modals'] as $modal): ?>
                        <div class="list-item">
                            <div class="list-item-content">
                                <div class="list-item-title">
                                    <?= htmlspecialchars($modal['title']) ?>
                                    <span class="priority-badge priority-<?= $modal['is_active'] ? 'high' : 'low' ?>">
                                        <?= $modal['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                    <span class="priority-badge priority-medium">
                                        Priority: <?= $modal['priority'] ?>
                                    </span>
                                </div>
                                <div class="list-item-description"><?= htmlspecialchars($modal['description']) ?></div>
                                <div class="list-item-meta">
                                    Target Files: <?= implode(', ', $modal['target_files_array']) ?> | 
                                    Frequency: <?= ucfirst(str_replace('_', ' ', $modal['display_frequency'])) ?> |
                                    <?php if ($modal['image_path']): ?>
                                        Gambar: <?= htmlspecialchars(basename($modal['image_path'])) ?> | 
                                    <?php endif; ?>
                                    Dibuat: <?= date('d/m/Y H:i', strtotime($modal['created_at'])) ?>
                                    <?php if ($modal['created_by_name']): ?>
                                        | Oleh: <?= htmlspecialchars($modal['created_by_name']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="list-item-actions">
                                <button class="btn-secondary" onclick="editDynamicModal(<?= $modal['id'] ?>)">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <button class="btn-danger" onclick="deleteDynamicModal(<?= $modal['id'] ?>)">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Email ke Semua User -->
                    <div class="setting-item">
                        <div class="setting-info">
                            <div class="setting-title">Email ke Semua User</div>
                            <div class="setting-description">Kirim email broadcast ke seluruh pengguna terdaftar</div>
                        </div>
                        <div class="setting-actions">
                            <button class="btn-orange" onclick="showEmailModal()">
                                <i class="ti ti-mail"></i>
                                Kirim Email
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Manajemen Akses AI Section -->
                <div class="settings-section">
                    <div class="section-header">
                        <i class="ti ti-brain"></i>
                        Manajemen Akses AI
                    </div>

                    <!-- Kelola API Keys -->
                    <div class="setting-item">
                        <div class="setting-info">
                            <div class="setting-title">Kelola API Keys</div>
                            <div class="setting-description">Kelola API keys untuk berbagai layanan AI (Groq, OpenAI, dll)</div>
                        </div>
                        <div class="setting-actions">
                            <button class="btn-orange" onclick="showApiKeyModal()">
                                <i class="ti ti-plus"></i>
                                Tambah API Key
                            </button>
                        </div>
                    </div>

                    <!-- API Keys List -->
                    <?php if (!empty($settings['api_keys'])): ?>
                    <div class="ml-4 mb-4" id="api-keys-list">
                        <?php foreach ($settings['api_keys'] as $apiKey): ?>
                        <div class="list-item">
                            <div class="list-item-content">
                                <div class="list-item-title">
                                    <i class="ti ti-key mr-1"></i>
                                    <?= htmlspecialchars($apiKey['service_label']) ?>
                                    <span class="priority-badge priority-<?= $apiKey['is_active'] ? 'high' : 'low' ?>">
                                        <?= $apiKey['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                    <?php if ($apiKey['test_status']): ?>
                                        <span class="priority-badge priority-<?= $apiKey['test_status'] === 'success' ? 'high' : ($apiKey['test_status'] === 'failed' ? 'urgent' : 'medium') ?>">
                                            <?= ucfirst($apiKey['test_status']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="list-item-description">
                                    <strong>Service:</strong> <?= htmlspecialchars($apiKey['service_name']) ?><br>
                                    <strong>API Key:</strong> <?= htmlspecialchars($apiKey['api_key_masked']) ?><br>
                                    <?php if ($apiKey['model_name']): ?>
                                        <strong>Model:</strong> <?= htmlspecialchars($apiKey['model_name']) ?><br>
                                    <?php endif; ?>
                                    <?php if ($apiKey['description']): ?>
                                        <?= htmlspecialchars($apiKey['description']) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="list-item-meta">
                                    Dibuat: <?= date('d/m/Y H:i', strtotime($apiKey['created_at'])) ?>
                                    <?php if ($apiKey['last_tested']): ?>
                                        | Last Test: <?= date('d/m/Y H:i', strtotime($apiKey['last_tested'])) ?>
                                    <?php endif; ?>
                                    <?php if ($apiKey['test_message']): ?>
                                        | Status: <?= htmlspecialchars($apiKey['test_message']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="list-item-actions">
                                <button class="btn-secondary" onclick="testApiKey(<?= $apiKey['id'] ?>)" title="Test API">
                                    <i class="ti ti-test-pipe"></i>
                                </button>
                                <button class="btn-secondary" onclick="editApiKey(<?= $apiKey['id'] ?>)" title="Edit">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <label class="toggle-switch" title="Toggle Status">
                                    <input type="checkbox" <?= $apiKey['is_active'] ? 'checked' : '' ?> 
                                           onchange="toggleApiKeyStatus(<?= $apiKey['id'] ?>, this.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                                <button class="btn-danger" onclick="deleteApiKey(<?= $apiKey['id'] ?>)" title="Delete">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="ml-4 mb-4">
                        <div class="empty-state">
                            <i class="ti ti-key-off"></i>
                            <h3>Belum Ada API Keys</h3>
                            <p>Tambahkan API keys untuk mengintegrasikan layanan AI eksternal</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Pengaturan Per Halaman -->
                    <div class="setting-item">
                        <div class="setting-info">
                            <div class="setting-title">Pengaturan Per Halaman</div>
                            <div class="setting-description">Tentukan API key yang digunakan untuk setiap halaman AI</div>
                        </div>
                    </div>

                    <!-- Page API Settings -->
                    <div class="ml-4 mb-4">
                        <!-- Pingo Chat Page -->
                        <div class="list-item">
                            <div class="list-item-content">
                                <div class="list-item-title">
                                    <i class="ti ti-message-circle mr-1"></i>
                                    Pingo Chat (pingo.php)
                                </div>
                                <div class="list-item-description">
                                    Halaman chat AI untuk pertanyaan pembelajaran
                                </div>
                            </div>
                            <div class="list-item-actions">
                                <select class="form-select" style="min-width: 200px;" onchange="updatePageApiKey('pingo', this.value)">
                                    <option value="">Pilih API Key</option>
                                    <?php foreach ($settings['api_keys'] as $apiKey): ?>
                                        <?php if ($apiKey['is_active']): ?>
                                            <option value="<?= $apiKey['id'] ?>">
                                                <?= htmlspecialchars($apiKey['service_label']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Question Generator Page -->
                        <div class="list-item">
                            <div class="list-item-content">
                                <div class="list-item-title">
                                    <i class="ti ti-brain mr-1"></i>
                                    Generator Soal (buat-soal-guru.php)
                                </div>
                                <div class="list-item-description">
                                    Halaman untuk membuat soal otomatis menggunakan AI
                                </div>
                            </div>
                            <div class="list-item-actions">
                                <select class="form-select" style="min-width: 200px;" onchange="updatePageApiKey('buat-soal', this.value)">
                                    <option value="">Pilih API Key</option>
                                    <?php foreach ($settings['api_keys'] as $apiKey): ?>
                                        <?php if ($apiKey['is_active']): ?>
                                            <option value="<?= $apiKey['id'] ?>">
                                                <?= htmlspecialchars($apiKey['service_label']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Vision AI for Image Analysis -->
                        <div class="list-item">
                            <div class="list-item-content">
                                <div class="list-item-title">
                                    <i class="ti ti-photo-ai mr-1"></i>
                                    Vision AI (Auto Switch for Images)
                                    <span class="priority-badge priority-high">Auto</span>
                                </div>
                                <div class="list-item-description">
                                    API key untuk analisis gambar. Akan otomatis digunakan ketika user mengirim gambar di chat Pingo.
                                </div>
                            </div>
                            <div class="list-item-actions">
                                <select class="form-select" style="min-width: 200px;" onchange="updatePageApiKey('vision', this.value)">
                                    <option value="">Pilih API Key Vision</option>
                                    <?php foreach ($settings['api_keys'] as $apiKey): ?>
                                        <?php if ($apiKey['is_active'] && $apiKey['service_name'] === 'groq_vision'): ?>
                                            <option value="<?= $apiKey['id'] ?>">
                                                <?= htmlspecialchars($apiKey['service_label']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pembayaran Section -->
                <div class="settings-section">
                    <div class="section-header">
                        <i class="ti ti-credit-card"></i>
                        Pembayaran
                    </div>

                    <!-- Diskon -->
                    <div class="setting-item">
                        <div class="setting-info">
                            <div class="setting-title">Kelola Diskon</div>
                            <div class="setting-description">Buat dan kelola kode diskon untuk pengguna</div>
                        </div>
                        <div class="setting-actions">
                            <button class="btn-orange" onclick="showDiscountModal()">
                                <i class="ti ti-percentage"></i>
                                Tambah Diskon
                            </button>
                        </div>
                    </div>

                    <!-- Daftar Diskon -->
                    <?php if (!empty($settings['discounts'])): ?>
                    <div class="ml-4">
                        <?php foreach ($settings['discounts'] as $discount): ?>
                        <div class="list-item">
                            <div class="list-item-content">
                                <div class="list-item-title">Kode: <?= htmlspecialchars($discount['code']) ?></div>
                                <div class="list-item-description">Diskon <?= $discount['percentage'] ?>%</div>
                                <div class="list-item-meta">
                                    <span class="status-badge <?= $discount['active'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $discount['active'] ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                    | Dibuat: <?= date('d/m/Y H:i', strtotime($discount['created_at'])) ?>
                                </div>
                            </div>
                            <div class="list-item-actions">
                                <button class="btn-secondary" onclick="editDiscount(<?= $discount['id'] ?>)">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <button class="btn-danger" onclick="deleteDiscount(<?= $discount['id'] ?>)">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <!-- Modal Notifikasi -->
    <div id="modal-notification" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="notification-modal-title" class="text-lg font-semibold text-gray-800">Tambah Notifikasi Global</h3>
                    <button class="close" onclick="closeNotificationModal()">&times;</button>
                </div>

                <form id="notification-form" onsubmit="saveNotification(event)">
                    <input type="hidden" id="notification-id" name="notification_id">
                    <div class="form-group">
                        <label class="form-label">Judul Notifikasi *</label>
                        <input type="text" id="notification-title" name="title" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi *</label>
                        <textarea id="notification-description" name="description" class="form-textarea" required></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-group">
                            <label class="form-label">Icon</label>
                            <select id="notification-icon" name="icon" class="form-select">
                                <option value="info-circle">Info</option>
                                <option value="bell">Notifikasi</option>
                                <option value="alert-triangle">Peringatan</option>
                                <option value="check">Sukses</option>
                                <option value="x">Error</option>
                                <option value="star">Penting</option>
                                <option value="speakerphone">Pengumuman</option>
                                <option value="mail">Email</option>
                                <option value="calendar">Kalender</option>
                                <option value="trophy">Prestasi</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Prioritas</label>
                            <select id="notification-priority" name="priority" class="form-select">
                                <option value="low">Rendah</option>
                                <option value="medium" selected>Sedang</option>
                                <option value="high">Tinggi</option>
                                <option value="urgent">Mendesak</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Target Pengguna</label>
                        <select id="notification-target" name="target_roles" class="form-select">
                            <option value="all">Semua Pengguna</option>
                            <option value="guru">Hanya Guru</option>
                            <option value="siswa">Hanya Siswa</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Waktu Kadaluarsa (opsional)</label>
                        <input type="datetime-local" id="notification-expires" name="expires_at" class="form-input">
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Kosongkan jika tidak ada batas waktu
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="closeNotificationModal()" class="btn-secondary flex-1">
                            <i class="ti ti-x"></i>
                            Batal
                        </button>
                        <button type="submit" class="btn-orange flex-1">
                            <i class="ti ti-check"></i>
                            Simpan Notifikasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Iklan -->
    <div id="modal-advertisement" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="advertisement-modal-title" class="text-lg font-semibold text-gray-800">Tambah Iklan</h3>
                    <button class="close" onclick="closeAdvertisementModal()">&times;</button>
                </div>

                <form id="advertisement-form" onsubmit="saveAdvertisement(event)">
                    <input type="hidden" id="advertisement-id" name="advertisement_id">
                    <div class="form-group">
                        <label class="form-label">Judul Iklan *</label>
                        <input type="text" id="advertisement-title" name="title" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi *</label>
                        <textarea id="advertisement-description" name="description" class="form-textarea" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Link URL</label>
                        <input type="url" id="advertisement-link" name="link_url" class="form-input" placeholder="https://example.com (opsional)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gambar</label>
                        <div class="file-upload" onclick="document.getElementById('advertisement-image').click()">
                            <input type="file" id="advertisement-image" name="image" accept="image/*" style="display: none;">
                            <div class="file-upload-content">
                                <i class="ti ti-cloud-upload" style="font-size: 24px; color: #6b7280; margin-bottom: 8px;"></i>
                                <div>Klik untuk pilih gambar</div>
                                <div style="font-size: 12px; color: #9ca3af;">JPG, PNG, atau GIF (max 5MB)</div>
                                <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">Rasio optimal: 4:2 (600x300px)</div>
                            </div>
                        </div>
                        
                        <!-- Image Preview -->
                        <div class="image-preview" id="advertisement-preview">
                            <img id="preview-image" alt="Preview">
                            <div style="margin-top: 8px;">

                                <button type="button" class="btn-secondary" onclick="removeAdvertisementImage()">
                                    <i class="ti ti-trash"></i>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="toggle-switch">
                            <input type="checkbox" id="advertisement-active" name="is_active" value="1" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <label for="advertisement-active" class="form-label" style="margin-left: 12px;">Aktif</label>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="closeAdvertisementModal()" class="btn-secondary flex-1">
                            <i class="ti ti-x"></i>
                            Batal
                        </button>
                        <button type="submit" class="btn-orange flex-1">
                            <i class="ti ti-check"></i>
                            Simpan Iklan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Email -->
    <div id="modal-email" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Kirim Email ke Semua User</h3>
                    <button class="close" onclick="closeEmailModal()">&times;</button>
                </div>

                <form id="email-form" onsubmit="sendBroadcastEmail(event)">
                    <div class="form-group">
                        <label class="form-label">Subjek Email *</label>
                        <input type="text" id="email-subject" name="subject" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Isi Email *</label>
                        <textarea id="email-content" name="content" class="form-textarea" style="min-height: 150px;" required></textarea>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start">
                            <i class="ti ti-alert-triangle text-yellow-600 mr-2 mt-1"></i>
                            <div>
                                <div class="font-medium text-yellow-800">Peringatan</div>
                                <div class="text-sm text-yellow-700">Email akan dikirim ke semua pengguna terdaftar. Pastikan konten sudah benar.</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="closeEmailModal()" class="btn-secondary flex-1">
                            <i class="ti ti-x"></i>
                            Batal
                        </button>
                        <button type="submit" class="btn-orange flex-1">
                            <i class="ti ti-send"></i>
                            Kirim Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Dynamic Modal -->
    <div id="modal-dynamic-modal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="dynamic-modal-title" class="text-lg font-semibold text-gray-800">Tambah Modal</h3>
                    <button class="close" onclick="closeDynamicModalForm()">&times;</button>
                </div>

                <form id="dynamic-modal-form" onsubmit="saveDynamicModal(event)">
                    <input type="hidden" id="dynamic-modal-id" name="modal_id">
                    
                    <div class="form-group">
                        <label class="form-label">Judul Modal *</label>
                        <input type="text" id="dynamic-modal-title-input" name="title" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi *</label>
                        <textarea id="dynamic-modal-description" name="description" class="form-textarea" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gambar Modal</label>
                        <div class="file-upload" onclick="document.getElementById('dynamic-modal-image').click()">
                            <input type="file" id="dynamic-modal-image" name="image" accept="image/*" style="display: none;">
                            <div class="file-upload-content">
                                <i class="ti ti-cloud-upload" style="font-size: 24px; color: #6b7280; margin-bottom: 8px;"></i>
                                <div>Klik untuk pilih gambar</div>
                                <div style="font-size: 12px; color: #9ca3af;">JPG, PNG, atau GIF (max 5MB)</div>
                                <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">Rasio optimal: 4:3 (400x300px)</div>
                            </div>
                        </div>
                        
                        <!-- Image Preview -->
                        <div class="image-preview" id="dynamic-modal-preview">
                            <img id="dynamic-modal-preview-image" alt="Preview">
                            <div style="margin-top: 8px;">
                                <button type="button" class="btn-secondary" onclick="removeDynamicModalImage()">
                                    <i class="ti ti-trash"></i>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Target File(s) *</label>
                        <div class="grid grid-cols-2 gap-2" id="target-files-container">
                            <label class="flex items-center">
                                <input type="checkbox" name="target_files[]" value="beranda-guru.php" class="mr-2">
                                <span>beranda-guru.php</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="target_files[]" value="beranda-user.php" class="mr-2">
                                <span>beranda-user.php</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="target_files[]" value="ujian-guru.php" class="mr-2">
                                <span>ujian-guru.php</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="target_files[]" value="ujian-siswa.php" class="mr-2">
                                <span>ujian-siswa.php</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="target_files[]" value="kelas.php" class="mr-2">
                                <span>kelas.php</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="target_files[]" value="profile.php" class="mr-2">
                                <span>profile.php</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="target_files[]" value="dashboard.php" class="mr-2">
                                <span>dashboard.php</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="target_files[]" value="tugas.php" class="mr-2">
                                <span>tugas.php</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="target_files[]" value="kelas-guru.php" class="mr-2">
                                <span>kelas-guru.php</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="target_files[]" value="kelas-user.php" class="mr-2">
                                <span>kelas-user.php</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="target_files[]" value="ujian-user.php" class="mr-2">
                                <span>ujian-user.php</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="target_files[]" value="buat-ujian-guru.php" class="mr-2">
                                <span>buat-ujian-guru.php</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="target_files[]" value="buat-soal-guru.php" class="mr-2">
                                <span>buat-soal-guru.php</span>
                            </label>
                        </div>
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Pilih halaman mana saja yang akan menampilkan modal ini
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kapan Modal Ditampilkan *</label>
                        <select id="display-frequency" name="display_frequency" class="form-input" required>
                            <option value="always">Setiap kali buka halaman</option>
                            <option value="once_per_session">1 kali per sesi login</option>
                            <option value="once_forever">1 kali selamanya</option>
                        </select>
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Atur frekuensi tampil modal kepada user
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Prioritas</label>
                        <input type="number" id="priority" name="priority" value="0" min="0" max="100" class="form-input">
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Angka lebih tinggi = prioritas lebih tinggi (0-100)
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="toggle-switch">
                            <input type="checkbox" id="dynamic-modal-active" name="is_active" value="1" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <label for="dynamic-modal-active" class="form-label" style="margin-left: 12px;">Aktif</label>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="closeDynamicModalForm()" class="btn-secondary flex-1">
                            <i class="ti ti-x"></i>
                            Batal
                        </button>
                        <button type="submit" class="btn-orange flex-1">
                            <i class="ti ti-check"></i>
                            Simpan Modal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal API Key -->
    <div id="modal-api-key" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="api-key-modal-title" class="text-lg font-semibold text-gray-800">Tambah API Key</h3>
                    <button class="close" onclick="closeApiKeyModal()">&times;</button>
                </div>

                <form id="api-key-form" onsubmit="saveApiKey(event)">
                    <input type="hidden" id="api-key-id" name="api_key_id">
                    
                    <div class="form-group">
                        <label class="form-label">Service *</label>
                        <select id="service-name" name="service_name" class="form-input" required onchange="updateServiceFields()">
                            <option value="">Pilih Service</option>
                            <option value="groq">Groq AI</option>
                            <option value="pingo_chat">Pingo Chat AI</option>
                            <option value="groq_vision">Groq AI Vision (Llama 4 Maverick)</option>
                            <option value="openai">OpenAI</option>
                            <option value="anthropic">Anthropic Claude</option>
                            <option value="custom">Custom Service</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Service Label *</label>
                        <input type="text" id="service-label" name="service_label" class="form-input" required
                               placeholder="Nama layanan yang ditampilkan">
                    </div>

                    <div class="form-group">
                        <label class="form-label">API Key *</label>
                        <input type="password" id="api-key-input" name="api_key" class="form-input" required
                               placeholder="Masukkan API key">
                        <div class="flex items-center mt-2">
                            <input type="checkbox" id="show-api-key" onchange="toggleApiKeyVisibility()">
                            <label for="show-api-key" class="ml-2 text-sm">Tampilkan API key</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">API URL</label>
                        <input type="url" id="api-url" name="api_url" class="form-input"
                               placeholder="https://api.service.com/v1/endpoint">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Model Name</label>
                        <input type="text" id="model-name" name="model_name" class="form-input"
                               placeholder="model-name (optional)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <textarea id="api-description" name="description" class="form-textarea"
                                  placeholder="Deskripsi penggunaan API key ini"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="toggle-switch">
                            <input type="checkbox" id="api-key-active" name="is_active" value="1" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <label for="api-key-active" class="form-label" style="margin-left: 12px;">Aktif</label>
                    </div>

                    <!-- Service-specific help -->
                    <div id="service-help" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4" style="display: none;">
                        <div class="flex items-start">
                            <i class="ti ti-info-circle text-blue-600 mr-2 mt-1"></i>
                            <div id="service-help-content">
                                <!-- Dynamic content based on selected service -->
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="closeApiKeyModal()" class="btn-secondary flex-1">
                            <i class="ti ti-x"></i>
                            Batal
                        </button>
                        <button type="submit" class="btn-orange flex-1">
                            <i class="ti ti-check"></i>
                            Simpan API Key
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Pesan Maintenance -->
    <div id="modal-maintenance-message" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Edit Pesan Maintenance</h3>
                    <button class="close" onclick="closeMaintenanceMessageModal()">&times;</button>
                </div>

                <form id="maintenance-message-form" onsubmit="saveMaintenanceMessage(event)">
                    <div class="form-group">
                        <label class="form-label">Header Maintenance *</label>
                        <input type="text" id="maintenance-header-input" name="header" class="form-input" 
                               value="<?= htmlspecialchars($settings['maintenance_header'] ?? 'Scheduled Infrastructure Upgrade') ?>" required>
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Header ini akan ditampilkan sebagai judul di halaman maintenance.
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pesan Maintenance *</label>
                        <textarea id="maintenance-message-input" name="message" class="form-textarea" 
                                  style="min-height: 120px;" required><?= htmlspecialchars($settings['maintenance_message']) ?></textarea>
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Pesan ini akan ditampilkan kepada pengguna saat maintenance mode aktif.
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start">
                            <i class="ti ti-info-circle text-blue-600 mr-2 mt-1"></i>
                            <div>
                                <div class="font-medium text-blue-800">Tips</div>
                                <div class="text-sm text-blue-700">Buat pesan yang jelas dan informatif untuk memberikan ekspektasi yang tepat kepada pengguna.</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="closeMaintenanceMessageModal()" class="btn-secondary flex-1">
                            <i class="ti ti-x"></i>
                            Batal
                        </button>
                        <button type="submit" class="btn-orange flex-1">
                            <i class="ti ti-check"></i>
                            Simpan Pesan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Diskon -->
    <div id="modal-discount" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="discount-modal-title" class="text-lg font-semibold text-gray-800">Tambah Diskon</h3>
                    <button class="close" onclick="closeDiscountModal()">&times;</button>
                </div>

                <form id="discount-form" onsubmit="saveDiscount(event)">
                    <input type="hidden" id="discount-id" name="discount_id">
                    <div class="form-group">
                        <label class="form-label">Kode Diskon *</label>
                        <input type="text" id="discount-code" name="code" class="form-input" 
                               placeholder="Masukkan kode diskon" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Persentase Diskon (%) *</label>
                        <input type="number" id="discount-percentage" name="percentage" class="form-input" 
                               min="1" max="100" placeholder="25" required>
                    </div>

                    <div class="form-group">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="discount-active" name="active" checked>
                            <span class="form-label" style="margin-bottom: 0;">Aktifkan diskon</span>
                        </label>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="closeDiscountModal()" class="btn-secondary flex-1">
                            <i class="ti ti-x"></i>
                            Batal
                        </button>
                        <button type="submit" class="btn-orange flex-1">
                            <i class="ti ti-check"></i>
                            Simpan Diskon
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <script>
        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            document.getElementById('toast-container').appendChild(toast);

            setTimeout(() => toast.remove(), 3000);
        }

        // API call helper for maintenance
        async function maintenanceApiCall(action, data = {}) {
            const DEBUG = false; // Set to false for production
            const formData = new FormData();
            formData.append('action', action);

            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            try {
                if (DEBUG) {
                    console.log('🚀 Making API call to:', '../logic/admin-maintenance-api.php');
                    console.log('📤 Action:', action);
                    console.log('📤 Data:', data);
                }
                
                const response = await fetch('../logic/admin-maintenance-api.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (DEBUG) {
                    console.log('📡 Response status:', response.status);
                    console.log('📡 Response ok:', response.ok);
                }
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const text = await response.text();
                if (DEBUG) console.log('📄 Raw response:', text);
                
                try {
                    const result = JSON.parse(text);
                    if (DEBUG) console.log('✅ Parsed JSON:', result);
                    return result;
                } catch (parseError) {
                    console.error('❌ JSON parse error:', parseError);
                    console.error('❌ Response text:', text);
                    return {
                        success: false,
                        message: 'Invalid response format'
                    };
                }
            } catch (error) {
                console.error('❌ Network/API Error:', error);
                return {
                    success: false,
                    message: 'Network error: ' + error.message
                };
            }
        }

        // Maintenance Mode Toggle
        document.getElementById('maintenance-toggle').addEventListener('change', async function() {
            const toggleElement = this;
            const statusBadge = document.getElementById('maintenance-status-badge');
            const originalState = !this.checked; // Store the previous state (opposite of current)
            
            try {
                const result = await maintenanceApiCall('toggle_maintenance');
                
                if (result && result.success) {
                    // Update UI based on actual result from server
                    toggleElement.checked = result.maintenance_mode;
                    
                    // Update status badge if it exists
                    if (statusBadge) {
                        if (result.maintenance_mode) {
                            statusBadge.textContent = 'Maintenance';
                            statusBadge.className = 'status-badge status-maintenance';
                        } else {
                            statusBadge.textContent = 'Normal';
                            statusBadge.className = 'status-badge status-active';
                        }
                    }
                    
                    showToast(result.message, 'success');
                } else {
                    // Revert toggle if failed
                    toggleElement.checked = originalState;
                    showToast(result?.message || 'Gagal mengubah maintenance mode', 'error');
                }
            } catch (error) {
                console.error('Toggle error:', error);
                // Revert toggle if failed
                toggleElement.checked = originalState;
                showToast('Gagal mengubah maintenance mode', 'error');
            }
        });

        // API call helper for notifications
        async function notificationApiCall(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);

            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            try {
                const response = await fetch('../logic/admin-notifications-api.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('🔍 Response status:', response.status);
                console.log('🔍 Response headers:', response.headers.get('content-type'));
                
                const responseText = await response.text();
                console.log('🔍 Raw response:', responseText);
                
                // Check if response starts with HTML (error page)
                if (responseText.trim().startsWith('<')) {
                    console.error('❌ Received HTML error instead of JSON:', responseText);
                    return {
                        success: false,
                        message: 'Server returned HTML error. Check PHP logs.'
                    };
                }
                
                return JSON.parse(responseText);
            } catch (error) {
                console.error('API Error:', error);
                return {
                    success: false,
                    message: 'Network error: ' + error.message
                };
            }
        }

        // Modal Functions - Notification
        function showNotificationModal() {
            document.getElementById('notification-modal-title').textContent = 'Tambah Notifikasi Global';
            document.getElementById('notification-form').reset();
            document.getElementById('notification-id').value = '';
            document.getElementById('modal-notification').classList.add('show');
        }

        function closeNotificationModal() {
            document.getElementById('modal-notification').classList.remove('show');
        }

        async function editNotification(id) {
            document.getElementById('notification-modal-title').textContent = 'Edit Notifikasi';
            document.getElementById('notification-id').value = id;
            
            // TODO: Load notification data from API
            // For now, just show modal
            document.getElementById('modal-notification').classList.add('show');
        }

        async function deleteNotification(id) {
            if (confirm('Yakin ingin menghapus notifikasi ini?')) {
                const result = await notificationApiCall('delete_notification', { id: id });
                
                if (result.success) {
                    showToast(result.message);
                    // Reload notifications list
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            }
        }

        async function saveNotification(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const notificationId = formData.get('notification_id');
            const action = notificationId ? 'update_notification' : 'create_notification';
            
            // Convert form data to object
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            // Handle target roles
            if (data.target_roles === 'all') {
                data.target_roles = null;
            }
            
            console.log('🚀 Sending notification data:', data);
            console.log('🎯 Action:', action);
            
            const result = await notificationApiCall(action, data);
            
            console.log('📡 API Response:', result);
            
            if (result.success) {
                showToast(result.message);
                closeNotificationModal();
                // Reload notifications list
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(result.message, 'error');
            }
        }

        // Modal Functions - Advertisement
        function showAdvertisementModal() {
            document.getElementById('advertisement-modal-title').textContent = 'Tambah Iklan';
            document.getElementById('advertisement-form').reset();
            document.getElementById('advertisement-id').value = '';
            
            // Reset image state
            resetAdvertisementImageState();
            
            document.getElementById('modal-advertisement').classList.add('show');
        }

        function closeAdvertisementModal() {
            document.getElementById('modal-advertisement').classList.remove('show');
            
            // Reset image state when closing
            resetAdvertisementImageState();
        }

        function resetAdvertisementImageState() {
            currentImageFile = null;
            
            // Reset file input
            document.getElementById('advertisement-image').value = '';
            
            // Hide preview
            document.getElementById('advertisement-preview').style.display = 'none';
            
            // Reset upload content
            const uploadContent = document.querySelector('.file-upload-content');
            uploadContent.innerHTML = `
                <i class="ti ti-cloud-upload" style="font-size: 24px; color: #6b7280; margin-bottom: 8px;"></i>
                <div>Klik untuk pilih gambar</div>
                <div style="font-size: 12px; color: #9ca3af;">JPG, PNG, atau GIF (max 5MB)</div>
                <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">Rasio optimal: 4:2 (600x300px)</div>
            `;
        }

        async function editAdvertisement(id) {
            try {
                const response = await fetch(`api-advertisements.php?action=get&id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const ad = result.data;
                    document.getElementById('advertisement-modal-title').textContent = 'Edit Iklan';
                    document.getElementById('advertisement-id').value = ad.id;
                    document.getElementById('advertisement-title').value = ad.title;
                    document.getElementById('advertisement-description').value = ad.description;
                    document.getElementById('advertisement-link').value = ad.link_url || '';
                    document.getElementById('advertisement-active').checked = ad.is_active == 1;
                    document.getElementById('modal-advertisement').classList.add('show');
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error loading advertisement:', error);
                showToast('Gagal memuat data iklan', 'error');
            }
        }

        async function deleteAdvertisement(id) {
            if (!confirm('Yakin ingin menghapus iklan ini?')) {
                return;
            }
            
            try {
                const response = await fetch('api-advertisements.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error deleting advertisement:', error);
                showToast('Gagal menghapus iklan', 'error');
            }
        }

        async function saveAdvertisement(event) {
            event.preventDefault();
            const formData = new FormData();
            const id = document.getElementById('advertisement-id').value;
            
            // Get form values
            formData.append('title', document.getElementById('advertisement-title').value);
            formData.append('description', document.getElementById('advertisement-description').value);
            formData.append('link_url', document.getElementById('advertisement-link').value);
            formData.append('is_active', document.getElementById('advertisement-active').checked ? 1 : 0);
            
            // Handle image upload
            if (currentImageFile) {
                formData.append('image', currentImageFile);
            }
            
            try {
                const url = id ? 
                    `api-advertisements.php?action=update` : 
                    `api-advertisements.php?action=create`;
                    
                if (id) {
                    formData.append('id', id);
                }
                
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message);
                    closeAdvertisementModal();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error saving advertisement:', error);
                showToast('Gagal menyimpan iklan', 'error');
            }
        }

        // Modal Functions - Email
        function showEmailModal() {
            document.getElementById('email-form').reset();
            document.getElementById('modal-email').classList.add('show');
        }

        function closeEmailModal() {
            document.getElementById('modal-email').classList.remove('show');
        }

        function sendBroadcastEmail(event) {
            event.preventDefault();
            if (confirm('Yakin ingin mengirim email ke semua pengguna?')) {
                const formData = new FormData(event.target);
                showToast('Email sedang dikirim...');
                closeEmailModal();
                // TODO: Send to backend
            }
        }

        // API Keys Management Functions
        async function apiKeyApiCall(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);

            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            try {
                const response = await fetch('../logic/api-keys-api.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    console.error('❌ HTTP Error:', response.status, response.statusText);
                    return {
                        success: false,
                        message: `HTTP Error: ${response.status} ${response.statusText}`
                    };
                }
                
                const result = await response.json();
                return result;
            } catch (error) {
                console.error('API Keys API Error:', error);
                return {
                    success: false,
                    message: 'Network error: ' + error.message
                };
            }
        }

        // Modal Functions - API Key
        function showApiKeyModal() {
            document.getElementById('api-key-modal-title').textContent = 'Tambah API Key';
            document.getElementById('api-key-form').reset();
            document.getElementById('api-key-id').value = '';
            document.getElementById('service-help').style.display = 'none';
            document.getElementById('modal-api-key').classList.add('show');
        }

        function closeApiKeyModal() {
            document.getElementById('modal-api-key').classList.remove('show');
        }

        async function editApiKey(id) {
            try {
                const result = await apiKeyApiCall('get_by_id', { id: id });
                
                if (result.success) {
                    const apiKey = result.data;
                    
                    document.getElementById('api-key-modal-title').textContent = 'Edit API Key';
                    document.getElementById('api-key-id').value = apiKey.id;
                    document.getElementById('service-name').value = apiKey.service_name;
                    document.getElementById('service-label').value = apiKey.service_label;
                    document.getElementById('api-key-input').value = apiKey.api_key;
                    document.getElementById('api-url').value = apiKey.api_url || '';
                    document.getElementById('model-name').value = apiKey.model_name || '';
                    document.getElementById('api-description').value = apiKey.description || '';
                    document.getElementById('api-key-active').checked = apiKey.is_active == 1;
                    
                    updateServiceFields();
                    document.getElementById('modal-api-key').classList.add('show');
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Gagal mengambil data API key', 'error');
            }
        }

        async function deleteApiKey(id) {
            if (confirm('Yakin ingin menghapus API key ini?')) {
                const result = await apiKeyApiCall('delete', { id: id });
                
                if (result.success) {
                    showToast(result.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            }
        }

        async function saveApiKey(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const apiKeyId = formData.get('api_key_id');
            const action = apiKeyId ? 'update' : 'create';
            
            // Convert form data to object
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            if (apiKeyId) {
                data.id = apiKeyId;
            }
            
            const result = await apiKeyApiCall(action, data);
            
            if (result.success) {
                showToast(result.message);
                closeApiKeyModal();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(result.message, 'error');
            }
        }

        async function testApiKey(id) {
            showToast('Testing API key...', 'info');
            
            const result = await apiKeyApiCall('test', { id: id });
            
            if (result.success) {
                showToast('✅ ' + result.message);
            } else {
                showToast('❌ ' + result.message, 'error');
            }
            
            // Refresh page to show updated test status
            setTimeout(() => window.location.reload(), 2000);
        }

        async function toggleApiKeyStatus(id, isActive) {
            const result = await apiKeyApiCall('toggle_status', { 
                id: id, 
                is_active: isActive ? 1 : 0 
            });
            
            if (result.success) {
                showToast(result.message || 'Status berhasil diubah');
            } else {
                showToast(result.message || 'Gagal mengubah status', 'error');
                // Revert toggle if failed - find the toggle element
                const toggleElement = document.querySelector(`input[onchange*="toggleApiKeyStatus(${id}"]`);
                if (toggleElement) {
                    toggleElement.checked = !isActive;
                }
            }
        }

        // Page API Key Management
        async function updatePageApiKey(pageName, apiKeyId) {
            try {
                const result = await fetch('../logic/api-switcher-endpoint.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=set_page_api_key&page=${pageName}&api_key_id=${apiKeyId}`
                });
                
                const response = await result.json();
                
                if (response.success) {
                    showToast(`API key untuk halaman ${pageName} berhasil diperbarui`);
                } else {
                    showToast(response.message || 'Gagal memperbarui API key', 'error');
                }
            } catch (error) {
                console.error('Error updating page API key:', error);
                showToast('Terjadi kesalahan saat memperbarui API key', 'error');
            }
        }

        // Load current page API settings when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadCurrentPageApiSettings();
        });

        async function loadCurrentPageApiSettings() {
            const pages = ['pingo', 'buat-soal', 'vision'];
            
            for (const page of pages) {
                try {
                    const result = await fetch('../logic/api-switcher-endpoint.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=get_page_api_key&page=${page}`
                    });
                    
                    const response = await result.json();
                    
                    if (response.success && response.data) {
                        // Find and set the select element
                        const selects = document.querySelectorAll('select.form-select');
                        for (const select of selects) {
                            if (select.getAttribute('onchange')?.includes(`'${page}'`)) {
                                select.value = response.data.id;
                                break;
                            }
                        }
                    }
                } catch (error) {
                    console.error(`Error loading API key for ${page}:`, error);
                }
            }
        }

        function updateServiceFields() {
            const serviceSelect = document.getElementById('service-name');
            const serviceName = serviceSelect.value;
            const serviceLabel = document.getElementById('service-label');
            const apiUrl = document.getElementById('api-url');
            const modelName = document.getElementById('model-name');
            const helpDiv = document.getElementById('service-help');
            const helpContent = document.getElementById('service-help-content');
            
            // Auto-fill based on service
            switch (serviceName) {
                case 'groq':
                    if (!serviceLabel.value) serviceLabel.value = 'Groq AI';
                    apiUrl.value = 'https://api.groq.com/openai/v1/chat/completions';
                    if (!modelName.value) modelName.value = 'llama3-8b-8192';
                    helpContent.innerHTML = `
                        <div class="font-medium text-blue-800">Groq AI</div>
                        <div class="text-sm text-blue-700">
                            Dapatkan API Key di <a href="https://console.groq.com/" target="_blank" class="text-orange underline">console.groq.com</a><br>
                            Model yang tersedia: llama3-8b-8192, llama3-70b-8192, mixtral-8x7b-32768
                        </div>
                    `;
                    helpDiv.style.display = 'block';
                    break;
                case 'pingo_chat':
                    if (!serviceLabel.value) serviceLabel.value = 'Pingo Chat AI';
                    apiUrl.value = 'https://api.groq.com/openai/v1/chat/completions';
                    if (!modelName.value) modelName.value = 'llama3-8b-8192';
                    helpContent.innerHTML = `
                        <div class="font-medium text-blue-800">Pingo Chat AI</div>
                        <div class="text-sm text-blue-700">
                            API khusus untuk Pingo Chat dan generasi soal.<br>
                            Menggunakan Groq API dengan konfigurasi optimal untuk chat.
                        </div>
                    `;
                    helpDiv.style.display = 'block';
                    break;
                case 'groq_vision':
                    if (!serviceLabel.value) serviceLabel.value = 'Groq AI Vision (Llama 4 Maverick)';
                    apiUrl.value = 'https://api.groq.com/openai/v1/chat/completions';
                    if (!modelName.value) modelName.value = 'meta-llama/llama-4-maverick-17b-128e-instruct';
                    helpContent.innerHTML = `
                        <div class="font-medium text-blue-800">Groq AI Vision</div>
                        <div class="text-sm text-blue-700">
                            Dapatkan API Key di <a href="https://console.groq.com/" target="_blank" class="text-orange underline">console.groq.com</a><br>
                            Model khusus untuk analisis gambar: meta-llama/llama-4-maverick-17b-128e-instruct<br>
                            <strong class="text-orange-600">⚠️ Model ini secara otomatis akan digunakan saat user mengirim gambar di chat</strong>
                        </div>
                    `;
                    helpDiv.style.display = 'block';
                    break;
                case 'openai':
                    if (!serviceLabel.value) serviceLabel.value = 'OpenAI GPT';
                    apiUrl.value = 'https://api.openai.com/v1/chat/completions';
                    if (!modelName.value) modelName.value = 'gpt-3.5-turbo';
                    helpContent.innerHTML = `
                        <div class="font-medium text-blue-800">OpenAI</div>
                        <div class="text-sm text-blue-700">
                            Dapatkan API Key di <a href="https://platform.openai.com/api-keys" target="_blank" class="text-orange underline">platform.openai.com</a><br>
                            Model yang tersedia: gpt-3.5-turbo, gpt-4, gpt-4-turbo
                        </div>
                    `;
                    helpDiv.style.display = 'block';
                    break;
                case 'anthropic':
                    if (!serviceLabel.value) serviceLabel.value = 'Anthropic Claude';
                    apiUrl.value = 'https://api.anthropic.com/v1/messages';
                    if (!modelName.value) modelName.value = 'claude-3-sonnet-20240229';
                    helpContent.innerHTML = `
                        <div class="font-medium text-blue-800">Anthropic Claude</div>
                        <div class="text-sm text-blue-700">
                            Dapatkan API Key di <a href="https://console.anthropic.com/" target="_blank" class="text-orange underline">console.anthropic.com</a><br>
                            Model yang tersedia: claude-3-sonnet, claude-3-opus, claude-3-haiku
                        </div>
                    `;
                    helpDiv.style.display = 'block';
                    break;
                case 'custom':
                    if (!serviceLabel.value) serviceLabel.value = 'Custom Service';
                    helpContent.innerHTML = `
                        <div class="font-medium text-blue-800">Custom Service</div>
                        <div class="text-sm text-blue-700">
                            Masukkan detail konfigurasi sesuai dengan dokumentasi API service Anda.
                        </div>
                    `;
                    helpDiv.style.display = 'block';
                    break;
                default:
                    helpDiv.style.display = 'none';
                    break;
            }
        }

        function toggleApiKeyVisibility() {
            const apiKeyInput = document.getElementById('api-key-input');
            const showCheckbox = document.getElementById('show-api-key');
            
            apiKeyInput.type = showCheckbox.checked ? 'text' : 'password';
        }

        // Modal Functions - Maintenance Message
        function showMaintenanceMessageModal() {
            document.getElementById('modal-maintenance-message').classList.add('show');
        }

        function closeMaintenanceMessageModal() {
            document.getElementById('modal-maintenance-message').classList.remove('show');
        }

        function saveMaintenanceMessage(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            maintenanceApiCall('update_maintenance_message', {
                header: formData.get('header'),
                message: formData.get('message')
            }).then(result => {
                if (result.success) {
                    showToast(result.message);
                    document.getElementById('current-maintenance-message').textContent = result.new_message;
                    closeMaintenanceMessageModal();
                } else {
                    showToast(result.message, 'error');
                }
            });
        }

        // Modal Functions - Discount
        function showDiscountModal() {
            document.getElementById('discount-modal-title').textContent = 'Tambah Diskon';
            document.getElementById('discount-form').reset();
            document.getElementById('discount-id').value = '';
            document.getElementById('modal-discount').classList.add('show');
        }

        function closeDiscountModal() {
            document.getElementById('modal-discount').classList.remove('show');
        }

        function editDiscount(id) {
            document.getElementById('discount-modal-title').textContent = 'Edit Diskon';
            document.getElementById('discount-id').value = id;
            // TODO: Load discount data
            document.getElementById('modal-discount').classList.add('show');
        }

        function deleteDiscount(id) {
            if (confirm('Yakin ingin menghapus diskon ini?')) {
                showToast('Diskon berhasil dihapus');
                // TODO: Send delete request to backend
            }
        }

        function saveDiscount(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            showToast('Diskon berhasil disimpan');
            closeDiscountModal();
            // TODO: Send to backend
        }

        // Modal Functions - Dynamic Modal Management
        let currentModalImageFile = null;
        console.log('🎯 Dynamic Modal functions loaded');

        function showDynamicModalForm() {
            console.log('🎯 showDynamicModalForm called');
            document.getElementById('dynamic-modal-title').textContent = 'Tambah Modal';
            document.getElementById('dynamic-modal-form').reset();
            document.getElementById('dynamic-modal-id').value = '';
            
            // Reset image state
            resetDynamicModalImageState();
            
            document.getElementById('modal-dynamic-modal').classList.add('show');
        }

        function closeDynamicModalForm() {
            document.getElementById('modal-dynamic-modal').classList.remove('show');
            
            // Reset image state when closing
            resetDynamicModalImageState();
        }

        function resetDynamicModalImageState() {
            currentModalImageFile = null;
            
            // Reset file input
            document.getElementById('dynamic-modal-image').value = '';
            
            // Hide preview
            document.getElementById('dynamic-modal-preview').style.display = 'none';
            
            // Reset upload content
            const uploadDiv = document.querySelector('#modal-dynamic-modal .file-upload-content');
            if (uploadDiv) {
                uploadDiv.innerHTML = `
                    <i class="ti ti-cloud-upload" style="font-size: 24px; color: #6b7280; margin-bottom: 8px;"></i>
                    <div>Klik untuk pilih gambar</div>
                    <div style="font-size: 12px; color: #9ca3af;">JPG, PNG, atau GIF (max 5MB)</div>
                    <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">Rasio optimal: 4:3 (400x300px)</div>
                `;
            }
        }

        async function editDynamicModal(id) {
            console.log('🎯 editDynamicModal called with id:', id);
            try {
                const response = await fetch(`../logic/api-dynamic-modal.php?action=get&id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const modal = result.data;
                    document.getElementById('dynamic-modal-title').textContent = 'Edit Modal';
                    document.getElementById('dynamic-modal-id').value = modal.id;
                    document.getElementById('dynamic-modal-title-input').value = modal.title;
                    document.getElementById('dynamic-modal-description').value = modal.description;
                    document.getElementById('display-frequency').value = modal.display_frequency;
                    document.getElementById('priority').value = modal.priority;
                    document.getElementById('dynamic-modal-active').checked = modal.is_active == 1;
                    
                    // Set target files
                    const targetFiles = JSON.parse(modal.target_files);
                    document.querySelectorAll('input[name="target_files[]"]').forEach(checkbox => {
                        checkbox.checked = targetFiles.includes(checkbox.value);
                    });
                    
                    document.getElementById('modal-dynamic-modal').classList.add('show');
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error loading modal:', error);
                showToast('Gagal memuat data modal', 'error');
            }
        }

        async function deleteDynamicModal(id) {
            if (!confirm('Yakin ingin menghapus modal ini?')) {
                return;
            }
            
            try {
                const response = await fetch('../logic/api-dynamic-modal.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error deleting modal:', error);
                showToast('Gagal menghapus modal', 'error');
            }
        }

        async function saveDynamicModal(event) {
            event.preventDefault();
            const formData = new FormData();
            const id = document.getElementById('dynamic-modal-id').value;
            
            // Get form values
            formData.append('title', document.getElementById('dynamic-modal-title-input').value);
            formData.append('description', document.getElementById('dynamic-modal-description').value);
            formData.append('display_frequency', document.getElementById('display-frequency').value);
            formData.append('priority', document.getElementById('priority').value);
            formData.append('is_active', document.getElementById('dynamic-modal-active').checked ? 1 : 0);
            
            // Get selected target files
            const targetFiles = [];
            document.querySelectorAll('input[name="target_files[]"]:checked').forEach(checkbox => {
                targetFiles.push(checkbox.value);
            });
            
            if (targetFiles.length === 0) {
                showToast('Pilih minimal satu target file', 'error');
                return;
            }
            
            formData.append('target_files', JSON.stringify(targetFiles));
            
            // Handle image upload
            if (currentModalImageFile) {
                formData.append('image', currentModalImageFile);
            }
            
            try {
                const url = id ? 
                    `../logic/api-dynamic-modal.php?action=update` : 
                    `../logic/api-dynamic-modal.php?action=create`;
                    
                if (id) {
                    formData.append('id', id);
                }
                
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message);
                    closeDynamicModalForm();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error saving modal:', error);
                showToast('Gagal menyimpan modal', 'error');
            }
        }

        function removeDynamicModalImage() {
            currentModalImageFile = null;
            document.getElementById('dynamic-modal-image').value = '';
            document.getElementById('dynamic-modal-preview').style.display = 'none';
            
            // Reset upload content
            const uploadDiv = document.querySelector('#modal-dynamic-modal .file-upload-content');
            if (uploadDiv) {
                uploadDiv.innerHTML = `
                    <i class="ti ti-cloud-upload" style="font-size: 24px; color: #6b7280; margin-bottom: 8px;"></i>
                    <div>Klik untuk pilih gambar</div>
                    <div style="font-size: 12px; color: #9ca3af;">JPG, PNG, atau GIF (max 5MB)</div>
                    <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">Rasio optimal: 4:3 (400x300px)</div>
                `;
            }
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const modals = [
                'modal-notification',
                'modal-advertisement', 
                'modal-email',
                'modal-api-key',
                'modal-maintenance-message',
                'modal-discount',
                'modal-dynamic-modal'
            ];
            
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.classList.remove('show');
                    if (modalId === 'modal-advertisement') {
                        resetAdvertisementImageState();
                    } else if (modalId === 'modal-dynamic-modal') {
                        resetDynamicModalImageState();
                    }
                }
            });


        });

        // Handle Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = [
                    'modal-notification',
                    'modal-advertisement', 
                    'modal-email',
                    'modal-api-key',
                    'modal-maintenance-message',
                    'modal-discount',
                    'modal-dynamic-modal'
                ];
                
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (modal.classList.contains('show')) {
                        modal.classList.remove('show');
                        if (modalId === 'modal-advertisement') {
                            resetAdvertisementImageState();
                        } else if (modalId === 'modal-dynamic-modal') {
                            resetDynamicModalImageState();
                        }
                    }
                });
            }
        });

        // Global variables for image handling
        let currentImageFile = null;

        // File upload preview and handling
        document.getElementById('advertisement-image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    showToast('Hanya file gambar yang diperbolehkan', 'error');
                    return;
                }

                // Validate file size (5MB)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    showToast('Ukuran file terlalu besar. Maksimal 5MB', 'error');
                    return;
                }

                currentImageFile = file;
                showImagePreview(file);
            }
        });

        function showImagePreview(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewContainer = document.getElementById('advertisement-preview');
                const previewImage = document.getElementById('preview-image');
                
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
                
                // Update upload content
                const uploadContent = document.querySelector('.file-upload-content');
                uploadContent.innerHTML = `
                    <i class="ti ti-check" style="font-size: 24px; color: #10b981; margin-bottom: 8px;"></i>
                    <div>File dipilih: ${file.name}</div>
                    <div style="font-size: 12px; color: #9ca3af;">${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                    <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">Gambar siap digunakan</div>
                `;
            };
            reader.readAsDataURL(file);
        }

        // File upload for dynamic modal
        document.getElementById('dynamic-modal-image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    showToast('Hanya file gambar yang diperbolehkan', 'error');
                    return;
                }

                // Validate file size (5MB)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    showToast('Ukuran file terlalu besar. Maksimal 5MB', 'error');
                    return;
                }

                currentModalImageFile = file;
                showDynamicModalImagePreview(file);
            }
        });

        function showDynamicModalImagePreview(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewContainer = document.getElementById('dynamic-modal-preview');
                const previewImage = document.getElementById('dynamic-modal-preview-image');
                
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
                
                // Update upload content
                const uploadContent = document.querySelector('#modal-dynamic-modal .file-upload-content');
                if (uploadContent) {
                    uploadContent.innerHTML = `
                        <i class="ti ti-check" style="font-size: 24px; color: #10b981; margin-bottom: 8px;"></i>
                        <div>File dipilih: ${file.name}</div>
                        <div style="font-size: 12px; color: #9ca3af;">${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                        <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">Gambar siap digunakan</div>
                    `;
                }
            };
            reader.readAsDataURL(file);
        }








        // Debug function to test crop status
        function testCropStatus() {
            console.log('🔧 === CROP DEBUG STATUS ===');
            
            const cropImage = document.getElementById('crop-image');
            const cropModal = document.getElementById('crop-modal');
            const previewImage = document.getElementById('preview-image');
            
            console.log('Croppr library available:', typeof Croppr !== 'undefined');
            console.log('Croppr instance:', croppr);
            console.log('Current zoom:', currentZoom);
            console.log('Crop image element:', cropImage);
            console.log('Crop image src:', cropImage ? cropImage.src : 'N/A');
            console.log('Modal visible:', cropModal ? cropModal.classList.contains('show') : false);
            console.log('Preview image:', previewImage);
            console.log('Preview image src:', previewImage ? previewImage.src : 'N/A');
            console.log('Current image file:', currentImageFile);
            
            let message = 'Debug Info:\n';
            message += `- Croppr Library: ${typeof Croppr !== 'undefined' ? '✅' : '❌'}\n`;
            message += `- Croppr Instance: ${croppr ? '✅' : '❌'}\n`;
            message += `- Current Zoom: ${currentZoom}\n`;
            message += `- Image Element: ${cropImage ? '✅' : '❌'}\n`;
            message += `- Image Has Src: ${cropImage && cropImage.src ? '✅' : '❌'}\n`;
            message += `- Modal Visible: ${cropModal && cropModal.classList.contains('show') ? '✅' : '❌'}\n`;
            message += `- Preview Available: ${previewImage && previewImage.src ? '✅' : '❌'}`;
            
            alert(message);
            
            // Fix missing image source if possible
            if (cropImage && previewImage && previewImage.src && !cropImage.src) {
                console.log('� Fixing missing crop image source...');
                cropImage.src = previewImage.src;
                showToast('Crop image source diperbaiki!');
            }
            
            // Try to reinitialize if needed
            if (typeof Croppr !== 'undefined' && cropImage && cropImage.src && !croppr) {
                console.log('🔄 Attempting to reinitialize Croppr...');
                try {
                    croppr = new Croppr(cropImage, {
                        aspectRatio: 2,
                        startSize: [70, 35, '%'],
                    });
                    showToast('Croppr berhasil diinisialisasi ulang!');
                    console.log('✅ Reinitialize successful');
                } catch (error) {
                    console.error('❌ Reinitialize failed:', error);
                    showToast('Gagal inisialisasi ulang: ' + error.message, 'error');
                }
            }
        }

        function removeAdvertisementImage() {
            currentImageFile = null;
            
            // Reset file input
            document.getElementById('advertisement-image').value = '';
            
            // Hide preview
            document.getElementById('advertisement-preview').style.display = 'none';
            
            // Reset upload content
            const uploadContent = document.querySelector('.file-upload-content');
            uploadContent.innerHTML = `
                <i class="ti ti-cloud-upload" style="font-size: 24px; color: #6b7280; margin-bottom: 8px;"></i>
                <div>Klik untuk pilih gambar</div>
                <div style="font-size: 12px; color: #9ca3af;">JPG, PNG, atau GIF (max 5MB)</div>
                <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">Rasio optimal: 4:2 (600x300px)</div>
            `;
            
            showToast('Gambar telah dihapus');
        }
    </script>
    <script src="../script/menu-bar-script.js"></script>
</body>

</html>