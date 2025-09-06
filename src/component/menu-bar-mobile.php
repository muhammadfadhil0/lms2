<!-- Mobile Bottom Tab Bar -->
<?php
// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Gunakan helper yang sama seperti sidebar agar konsisten
require_once __DIR__ . '/../logic/profile-photo-helper.php';
$userIdMobile = $_SESSION['user']['id'] ?? null;
$resolvedPhotoPath = null;
if ($userIdMobile) {
    $resolvedPhotoPath = getUserProfilePhotoUrl($userIdMobile); // sudah mengembalikan ../../uploads/profile/xxx
}

// Fallback langsung dari session jika helper null (misal belum tersimpan di DB tapi ada di session)
if (!$resolvedPhotoPath) {
    $rawFotoProfil = $_SESSION['user']['foto_profil'] ?? $_SESSION['user']['fotoProfil'] ?? '';
    if ($rawFotoProfil) {
        if (strpos($rawFotoProfil, 'uploads/profile/') === 0) {
            $resolvedPhotoPath = '../../' . $rawFotoProfil;
        } else {
            $resolvedPhotoPath = '../../uploads/profile/' . basename($rawFotoProfil);
        }
    }
}
?>
<div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 md:hidden z-50">
    <div class="grid grid-cols-4 gap-0 py-2">
        <?php 
        $userRole = $_SESSION['user']['role'] ?? 'siswa';
        $berandaLink = ($userRole == 'guru') ? '../front/beranda-guru.php' : '../front/beranda-user.php';
        $ujianLink = ($userRole == 'guru') ? '../front/ujian-guru.php' : '../front/ujian-user.php';
        ?>
        <a href="<?php echo $berandaLink; ?>" class="flex flex-col items-center justify-center py-3 px-2 min-h-[60px] rounded-lg mx-1 transition-all duration-200 <?php echo ($currentPage == 'beranda') ? 'text-orange bg-orange-tipis' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'; ?>">
            <i class="ti ti-home text-xl mb-1"></i>
            <span class="text-xs">Beranda</span>
        </a>
        
        <?php if ($userRole == 'guru'): ?>
            <!-- Menu untuk Guru: Beranda, Ujian, AI -->
            <a href="<?php echo $ujianLink; ?>" class="flex flex-col items-center justify-center py-3 px-2 min-h-[60px] rounded-lg mx-1 transition-all duration-200 <?php echo ($currentPage == 'ujian') ? 'text-orange bg-orange-tipis' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'; ?>">
                <i class="ti ti-clipboard-check text-xl mb-1"></i>
                <span class="text-xs">Ujian</span>
            </a>
            <a href="../front/pingo.php" class="flex flex-col items-center justify-center py-3 px-2 min-h-[60px] rounded-lg mx-1 transition-all duration-200 <?php echo ($currentPage == 'ai' || $currentPage == 'pingo') ? 'text-orange bg-orange-tipis' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'; ?>">
                <i class="ti ti-sparkles text-xl mb-1"></i>
                <span class="text-xs">Pingo</span>
            </a>
        <?php else: ?>
            <!-- Menu untuk Siswa: Beranda, Kelas, Ujian -->
            <a href="../front/kelas-beranda-user.php" class="flex flex-col items-center justify-center py-3 px-2 min-h-[60px] rounded-lg mx-1 transition-all duration-200 <?php echo ($currentPage == 'kelas') ? 'text-orange bg-orange-tipis' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'; ?>">
                <i class="ti ti-book text-xl mb-1"></i>
                <span class="text-xs">Kelas</span>
            </a>
            <a href="<?php echo $ujianLink; ?>" class="flex flex-col items-center justify-center py-3 px-2 min-h-[60px] rounded-lg mx-1 transition-all duration-200 <?php echo ($currentPage == 'ujian') ? 'text-orange bg-orange-tipis' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'; ?>">
                <i class="ti ti-clipboard-check text-xl mb-1"></i>
                <span class="text-xs">Ujian</span>
            </a>
        <?php endif; ?>
        <button onclick="toggleMobileProfile()" class="flex flex-col items-center justify-center py-3 px-2 min-h-[60px] rounded-lg mx-1 transition-all duration-200 text-gray-500 hover:text-gray-700 hover:bg-gray-50">
            <!-- Profile Photo or Icon -->
            <div class="w-6 h-6 rounded-full flex items-center justify-center overflow-hidden bg-gray-100 mb-1">
                <?php if (!empty($resolvedPhotoPath)): ?>
                    <img src="<?php echo htmlspecialchars($resolvedPhotoPath); ?>" alt="Profile Photo" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<?= '<i class=\'ti ti-user text-xl\'></i>' ?>';">
                <?php else: ?>
                    <i class="ti ti-user text-xl"></i>
                <?php endif; ?>
            </div>
            <span class="text-xs">Profile</span>
        </button>
    </div>
</div>

<!-- Mobile Profile Modal (Reworked with join-class modal UI style) -->
<div id="mobileProfileModal" class="fixed inset-0 z-50 hidden md:hidden">
    <div id="mobileProfileBackdrop" class="fixed inset-0 bg-gray-500/75 opacity-0 transition-opacity duration-300 ease-out" onclick="toggleMobileProfile()"></div>
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div id="mobileProfileContent" class="relative w-full max-w-sm sm:max-w-md transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all duration-300 ease-out translate-y-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:px-7 sm:pt-7 sm:pb-5">
                <div class="flex items-start">
                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-orange-100 sm:w-12 sm:h-12 overflow-hidden">
                        <?php if (!empty($resolvedPhotoPath)): ?>
                            <img src="<?php echo htmlspecialchars($resolvedPhotoPath); ?>" alt="Profile Photo" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<?= '<span class=\'ti ti-user text-lg text-orange-600\'></span>' ?>';">
                        <?php else: ?>
                            <span class="ti ti-user text-lg text-orange-600"></span>
                        <?php endif; ?>
                    </div>
                    <div class="ml-4 min-w-0">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 leading-tight">
                            <?php echo htmlspecialchars($_SESSION['user']['namaLengkap'] ?? 'User'); ?>
                        </h3>
                        <p class="mt-1 text-xs sm:text-sm text-gray-500 truncate">
                            <?php 
                                $userRole = $_SESSION['user']['role'] ?? 'siswa';
                                echo match($userRole) {
                                    'admin' => 'Administrator',
                                    'guru' => 'Guru',
                                    'siswa' => 'Siswa',
                                    default => 'User'
                                };
                            ?>
                        </p>
                    </div>
                    <button onclick="toggleMobileProfile()" class="ml-auto p-2 text-gray-400 hover:text-gray-600">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                <div class="mt-6 space-y-2">
                    <a href="../front/settings.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-md hover:bg-gray-50 transition">
                        <i class="ti ti-user text-gray-500"></i>
                        <span class="text-sm text-gray-700">Profile</span>
                    </a>
                    <a href="../front/settings.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-md hover:bg-gray-50 transition">
                        <i class="ti ti-settings text-gray-500"></i>
                        <span class="text-sm text-gray-700">Settings</span>
                    </a>
                    <button command="show-modal" commandfor="logout-modal" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-md hover:bg-red-50 text-red-600 transition">
                        <i class="ti ti-logout text-red-500"></i>
                        <span class="text-sm">Logout</span>
                    </button>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-4 sm:px-6 sm:py-5 border-t border-gray-100 text-center">
                <p class="text-[11px] text-gray-400">Tap area gelap untuk tutup</p>
            </div>
        </div>
    </div>
</div>
