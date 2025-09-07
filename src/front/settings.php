<!-- cek sekarang ada di halaman apa -->
<?php
session_start();
$currentPage = 'settings';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}
?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<?php 
// Include profile photo helper
require_once '../logic/profile-photo-helper.php';

// Get fresh profile photo URL for the current user
$freshProfilePhotoUrl = getUserProfilePhotoUrl($_SESSION['user']['id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?php echo $_SESSION['user']['id']; ?>">
    <?php require '../../assets/head.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <link rel="stylesheet" href="../css/profile-photo-modal.css">
    <link rel="stylesheet" href="../css/appearance-settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <title>Pengaturan Akun</title>
</head>

<body class="bg-gray-50">
    <!-- Main Content -->
    <div data-main-content class="md:ml-64 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">
        <!-- Header -->
        <header class="bg-white p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl md:text-xl font-bold text-gray-800">Pengaturan Akun</h1>
                    <p class="text-sm md:text-base text-gray-600 mt-1">Kelola informasi profil dan keamanan akun Anda</p>
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
            <!-- Settings Content -->
            <div class="max-w-4xl mx-auto">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200 mb-6 md:mb-8">
                    <nav class="-mb-px flex space-x-4 md:space-x-8 overflow-x-auto">
                        <button class="tab-btn active py-2 px-1 border-b-2 font-medium text-sm md:text-base whitespace-nowrap" data-tab="profile">
                            <i class="ti ti-user mr-2"></i>Profil & Akun
                        </button>
                        <button class="tab-btn py-2 px-1 border-b-2 font-medium text-sm md:text-base whitespace-nowrap" data-tab="security">
                            <i class="ti ti-shield mr-2"></i>Keamanan
                        </button>
                        <button class="tab-btn py-2 px-1 border-b-2 font-medium text-sm md:text-base whitespace-nowrap" data-tab="appearance">
                            <i class="ti ti-palette mr-2"></i>Tampilan
                        </button>
                    </nav>
                </div>

                <!-- Profile & Account Tab -->
                <div id="profile-tab" class="tab-content">
                    <!-- Profile Photo Section -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-4 md:mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 md:mb-6">Foto Profil</h3>
                        <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-6">
                            <div class="relative cursor-pointer" onclick="openProfilePhotoDropdown()">
                                <img class="w-20 h-20 md:w-24 md:h-24 rounded-full object-cover bg-gray-200 transition-all duration-300 hover:brightness-90 hover:scale-105"
                                    src="<?php 
                                    if ($freshProfilePhotoUrl) {
                                        echo htmlspecialchars($freshProfilePhotoUrl);
                                    } else {
                                        echo 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'96\' height=\'96\' viewBox=\'0 0 96 96\'%3E%3Crect width=\'96\' height=\'96\' fill=\'%23ff6347\'/%3E%3Ctext x=\'48\' y=\'56\' text-anchor=\'middle\' fill=\'white\' font-size=\'32\' font-family=\'Arial\'%3EU%3C/text%3E%3C/svg%3E';
                                    }
                                    ?>"
                                    alt="Profile Photo" id="profile-preview">
                                <!-- Edit button -->
                                <button class="absolute -bottom-1 -right-1 bg-orange text-white rounded-full p-2 hover:bg-orange-600 transition-colors shadow-lg border-2 border-white">
                                    <i class="ti ti-edit text-xs"></i>
                                </button>
                            </div>
                            <div class="flex-1 text-center sm:text-left">
                                <button onclick="openProfilePhotoDropdown()" class="bg-orange text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition-colors inline-block text-sm md:text-base">
                                    <i class="ti ti-photo mr-2"></i>Ubah Foto Profil
                                </button>
                                <p class="text-xs md:text-sm text-gray-500 mt-2">Klik untuk upload foto profil baru</p>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Pribadi & Username -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-4 md:mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 md:mb-6">
                            <i class="ti ti-user mr-2 text-orange"></i>Informasi Pribadi & Username
                        </h3>
                        <form id="username-form" class="space-y-4 md:space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                                    <input type="text" name="namaLengkap" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base"
                                        placeholder="Masukkan nama lengkap" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                    <input type="text" name="username" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base"
                                        placeholder="Masukkan username" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                                <textarea name="bio" rows="3" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base"
                                    placeholder="Ceritakan sedikit tentang diri Anda"></textarea>
                            </div>

                            <!-- Save Button -->
                            <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 pt-4 border-t border-gray-100">
                                <button type="button" class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors text-sm md:text-base">
                                    Batal
                                </button>
                                <button type="submit" class="px-6 py-2 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors text-sm md:text-base">
                                    <i class="ti ti-device-floppy mr-2"></i>Simpan Informasi Pribadi
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Informasi Kontak -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 md:mb-6">
                            <i class="ti ti-mail mr-2 text-orange"></i>Informasi Kontak
                        </h3>
                        <form id="contact-form" class="space-y-4 md:space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input type="email" name="email" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base"
                                        placeholder="Masukkan email" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Telepon</label>
                                    <input type="tel" name="nomorTelpon" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base"
                                        placeholder="Masukkan nomor telepon">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Lahir</label>
                                <input type="date" name="tanggalLahir" class="w-full md:w-1/2 border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base">
                            </div>

                            <!-- Save Button -->
                            <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 pt-4 border-t border-gray-100">
                                <button type="button" class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors text-sm md:text-base">
                                    Batal
                                </button>
                                <button type="submit" class="px-6 py-2 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors text-sm md:text-base">
                                    <i class="ti ti-device-floppy mr-2"></i>Simpan Informasi Kontak
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security Tab -->
                <div id="security-tab" class="tab-content hidden">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-4 md:mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 md:mb-6">Ubah Password</h3>
                        <form id="password-form" class="space-y-4 md:space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Password Saat Ini</label>
                                <input type="password" name="password_lama" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base"
                                    placeholder="Masukkan password saat ini" required>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                                    <input type="password" name="password_baru" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base"
                                        placeholder="Masukkan password baru" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password Baru</label>
                                    <input type="password" name="konfirmasi_password" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base"
                                        placeholder="Konfirmasi password baru" required>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
                                <button type="button" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors text-sm md:text-base">
                                    Batal
                                </button>
                                <button type="submit" class="px-4 py-2 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors text-sm md:text-base">
                                    Ubah Password
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 md:mb-6">Keamanan Akun</h3>
                        <div class="space-y-4">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 bg-orange-tipis rounded-lg space-y-3 sm:space-y-0">
                                <div>
                                    <h4 class="font-medium text-gray-900">Autentikasi Dua Faktor</h4>
                                    <p class="text-xs md:text-sm text-gray-600">Tambahkan lapisan keamanan ekstra untuk akun Anda</p>
                                </div>
                                <button class="bg-orange text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition-colors text-sm md:text-base w-full sm:w-auto">
                                    Aktifkan
                                </button>
                            </div>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 rounded-lg border space-y-3 sm:space-y-0">
                                <div>
                                    <h4 class="font-medium text-gray-900">Sesi Aktif</h4>
                                    <p class="text-xs md:text-sm text-gray-600">Kelola perangkat yang sedang masuk ke akun Anda</p>
                                </div>
                                <button class="text-orange hover:text-orange-600 font-medium text-sm md:text-base">
                                    Lihat Sesi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appearance Tab -->
                <div id="appearance-tab" class="tab-content hidden">
                    <!-- Dark Mode Section -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-4 md:mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 md:mb-6">
                            <i class="ti ti-moon mr-2 text-orange"></i>Mode Gelap
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Light Mode -->
                            <div class="theme-option bg-white border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-orange-300 transition-all" data-theme="light">
                                <div class="flex flex-col items-center space-y-3">
                                    <div class="w-16 h-16 rounded-lg bg-gradient-to-br fropm-yellow-200 to-orange-200 flex items-center justify-center relative">
                                        <i class="ti ti-sun text-2xl text-orange-600"></i>
                                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-orange text-white rounded-full flex items-center justify-center opacity-0 checkmark transition-opacity">
                                            <i class="ti ti-check text-xs"></i>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <h4 class="font-medium text-gray-900">Mode Terang</h4>
                                        <p class="text-sm text-gray-600">Tampilan terang klasik</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Dark Mode -->
                            <div class="theme-option bg-white border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-orange-300 transition-all" data-theme="dark">
                                <div class="flex flex-col items-center space-y-3">
                                    <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center relative">
                                        <i class="ti ti-moon text-2xl text-blue-300"></i>
                                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-orange text-white rounded-full flex items-center justify-center opacity-0 checkmark transition-opacity">
                                            <i class="ti ti-check text-xs"></i>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <h4 class="font-medium text-gray-900">Mode Gelap</h4>
                                        <p class="text-sm text-gray-600">Tampilan gelap untuk mata</p>
                                    </div>
                                </div>
                            </div>

                            <!-- System Mode -->
                            <div class="theme-option bg-white border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-orange-300 transition-all" data-theme="system">
                                <div class="flex flex-col items-center space-y-3">
                                    <div class="w-16 h-16 rounded-lg bg-gradient-to-r from-yellow-200 via-gray-300 to-gray-700 flex items-center justify-center relative">
                                        <i class="ti ti-device-desktop text-2xl text-gray-600"></i>
                                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-orange text-white rounded-full flex items-center justify-center opacity-0 checkmark transition-opacity">
                                            <i class="ti ti-check text-xs"></i>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <h4 class="font-medium text-gray-900">Mengikuti Sistem</h4>
                                        <p class="text-sm text-gray-600">Sesuai pengaturan perangkat</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Font Size Section -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 md:mb-6">
                            <i class="ti ti-typography mr-2 text-orange"></i>Ukuran Font
                        </h3>
                        <div class="space-y-4">
                            <p class="text-sm text-gray-600 mb-4">Pilih ukuran font yang nyaman untuk dibaca</p>
                            
                            <!-- Font Size Options -->
                            <div class="space-y-3">
                                <label class="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center">
                                        <input type="radio" name="fontSize" value="70" class="text-orange focus:ring-orange border-gray-300 mr-3">
                                        <span class="text-sm" style="font-size: 0.7em;">Sangat Kecil (70%)</span>
                                    </div>
                                    <span class="text-xs text-gray-500">70%</span>
                                </label>

                                <label class="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center">
                                        <input type="radio" name="fontSize" value="80" class="text-orange focus:ring-orange border-gray-300 mr-3">
                                        <span class="text-sm" style="font-size: 0.8em;">Kecil (80%)</span>
                                    </div>
                                    <span class="text-xs text-gray-500">80%</span>
                                </label>

                                <label class="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center">
                                        <input type="radio" name="fontSize" value="90" class="text-orange focus:ring-orange border-gray-300 mr-3">
                                        <span class="text-sm" style="font-size: 0.9em;">Agak Kecil (90%)</span>
                                    </div>
                                    <span class="text-xs text-gray-500">90%</span>
                                </label>

                                <label class="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center">
                                        <input type="radio" name="fontSize" value="100" class="text-orange focus:ring-orange border-gray-300 mr-3" checked>
                                        <span class="text-sm">Normal (100%)</span>
                                    </div>
                                    <span class="text-xs text-gray-500">100%</span>
                                </label>

                                <label class="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center">
                                        <input type="radio" name="fontSize" value="110" class="text-orange focus:ring-orange border-gray-300 mr-3">
                                        <span class="text-sm" style="font-size: 1.1em;">Besar (110%)</span>
                                    </div>
                                    <span class="text-xs text-gray-500">110%</span>
                                </label>
                            </div>

                            <!-- Save Button for Appearance -->
                            <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 pt-4 border-t border-gray-100">
                                <button type="button" class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors text-sm md:text-base">
                                    Reset
                                </button>
                                <button type="button" id="save-appearance" class="px-6 py-2 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors text-sm md:text-base">
                                    <i class="ti ti-device-floppy mr-2"></i>Simpan Pengaturan Tampilan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Profile Photo Dropdown Modal -->
    <?php include '../component/modal-profile-photo-dropdown.php'; ?>

    <!-- Crop Photo Modal -->
    <?php include '../component/modal-crop-photo.php'; ?>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script src="../script/menu-bar-script.js"></script>
    <script src="../script/tab-settings.js"></script>
    <script src="../script/settings.js"></script>
    <script src="../script/appearance-settings.js"></script>
    <script src="../script/profile-photo-handler.js"></script>
    <script src="../script/profile-sync.js"></script>
</body>

</html>