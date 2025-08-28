<!-- cek sekarang ada di halaman apa -->
<?php $currentPage = 'settings'; ?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
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
                    </nav>
                </div>

                <!-- Profile & Account Tab -->
                <div id="profile-tab" class="tab-content">
                    <!-- Profile Photo Section -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-4 md:mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 md:mb-6">Foto Profil</h3>
                        <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-6">
                            <div class="relative">
                                <img class="w-20 h-20 md:w-24 md:h-24 rounded-full object-cover bg-gray-200" 
                                     src="https://ui-avatars.com/api/?name=User&background=ff6347&color=fff" 
                                     alt="Profile Photo" id="profile-preview">
                                <button class="absolute -bottom-2 -right-2 bg-orange text-white rounded-full p-2 hover:bg-orange-600 transition-colors">
                                    <i class="ti ti-camera text-sm"></i>
                                </button>
                            </div>
                            <div class="flex-1 text-center sm:text-left">
                                <input type="file" id="profile-photo" class="hidden" accept="image/*">
                                <label for="profile-photo" class="bg-orange text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition-colors cursor-pointer inline-block text-sm md:text-base">
                                    Pilih Foto
                                </label>
                                <p class="text-xs md:text-sm text-gray-500 mt-2">JPG, PNG, atau GIF (maksimal 2MB)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Combined Profile & Account Information -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 md:mb-6">Informasi Profil & Akun</h3>
                        <form class="space-y-6">
                            <!-- Personal Information Section -->
                            <div>
                                <h4 class="text-base font-medium text-gray-800 mb-4 pb-2 border-b border-gray-100">
                                    <i class="ti ti-user mr-2 text-orange"></i>Informasi Pribadi
                                </h4>
                                <div class="space-y-4 md:space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                                            <input type="text" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base" 
                                                   value="John Doe" placeholder="Masukkan nama lengkap">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                            <input type="text" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base" 
                                                   value="johndoe" placeholder="Masukkan username">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                                        <textarea rows="3" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base" 
                                                  placeholder="Ceritakan sedikit tentang diri Anda"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information Section -->
                            <div>
                                <h4 class="text-base font-medium text-gray-800 mb-4 pb-2 border-b border-gray-100">
                                    <i class="ti ti-mail mr-2 text-orange"></i>Informasi Kontak
                                </h4>
                                <div class="space-y-4 md:space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                            <input type="email" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base" 
                                                   value="john@example.com" placeholder="Masukkan email">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Telepon</label>
                                            <input type="tel" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base" 
                                                   value="+62 812 3456 7890" placeholder="Masukkan nomor telepon">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Lahir</label>
                                        <input type="date" class="w-full md:w-1/2 border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base" 
                                               value="1990-01-01">
                                    </div>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 pt-4 border-t border-gray-100">
                                <button type="button" class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors text-sm md:text-base">
                                    Batal
                                </button>
                                <button type="submit" class="px-6 py-2 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors text-sm md:text-base">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security Tab -->
                <div id="security-tab" class="tab-content hidden">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-4 md:mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 md:mb-6">Ubah Password</h3>
                        <form class="space-y-4 md:space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Password Saat Ini</label>
                                <input type="password" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base" 
                                       placeholder="Masukkan password saat ini">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                                    <input type="password" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base" 
                                           placeholder="Masukkan password baru">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password Baru</label>
                                    <input type="password" class="w-full border px-3 py-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange focus:border-transparent text-sm md:text-base" 
                                           placeholder="Konfirmasi password baru">
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
            </div>
        </main>
    </div>

    <script src="../script/menu-bar-script.js"></script>
    <script src="../script/tab-settings.js"></script>
</body>
</html>