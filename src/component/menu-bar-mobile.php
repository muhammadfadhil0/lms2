<!-- Mobile Bottom Tab Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 md:hidden z-50">
        <div class="flex justify-around py-2">
            <a href="beranda-user.php" class="flex flex-col items-center p-2 <?php echo ($currentPage == 'beranda') ? 'text-orange bg-orange-tipis' : 'text-gray-500 hover:text-gray-700'; ?>">
                <i class="ti ti-home text-xl mb-1"></i>
                <span class="text-xs">Beranda</span>
            </a>
            <a href="kelas-beranda-user.php" class="flex flex-col items-center p-2 <?php echo ($currentPage == 'kelas') ? 'text-orange bg-orange-tipis' : 'text-gray-500 hover:text-gray-700'; ?>">
                <i class="ti ti-book text-xl mb-1"></i>
                <span class="text-xs">Kelas</span>
            </a>
            <a href="ujian-user.php" class="flex flex-col items-center p-2 <?php echo ($currentPage == 'ujian') ? 'text-orange bg-orange-tipis' : 'text-gray-500 hover:text-gray-700'; ?>">
                <i class="ti ti-clipboard-check text-xl mb-1"></i>
                <span class="text-xs">Ujian</span>
            </a>
            <button onclick="toggleMobileProfile()" class="flex flex-col items-center p-2 text-gray-500 hover:text-gray-700">
                <!-- Profile Photo or Icon -->
                <div class="w-6 h-6 rounded-full flex items-center justify-center overflow-hidden bg-gray-100 mb-1">
                    <?php if (isset($_SESSION['user']['foto_profil']) && !empty($_SESSION['user']['foto_profil'])): ?>
                        <?php
                        $fotoProfil = $_SESSION['user']['foto_profil'];
                        // Check if it already contains the full path
                        if (strpos($fotoProfil, 'uploads/profile/') === 0) {
                            $photoPath = '../../' . $fotoProfil;
                        } else {
                            $photoPath = '../../uploads/profile/' . $fotoProfil;
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($photoPath); ?>" 
                             alt="Profile Photo" 
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="ti ti-user text-xl"></i>
                    <?php endif; ?>
                </div>
                <span class="text-xs">Profile</span>
            </button>
        </div>
    </div>

    <!-- Mobile Profile Modal -->
    <div id="mobileProfileModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden md:hidden">
        <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-lg">
            <div class="p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Profile</h3>
                    <button onclick="toggleMobileProfile()" class="p-2 text-gray-500">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>
                <div class="flex items-center mb-4">
                    <!-- Profile Photo -->
                    <div class="w-12 h-12 rounded-full flex items-center justify-center overflow-hidden bg-gray-100">
                        <?php if (isset($_SESSION['user']['foto_profil']) && !empty($_SESSION['user']['foto_profil'])): ?>
                            <?php
                            $fotoProfil = $_SESSION['user']['foto_profil'];
                            // Check if it already contains the full path
                            if (strpos($fotoProfil, 'uploads/profile/') === 0) {
                                $photoPath = '../../' . $fotoProfil;
                            } else {
                                $photoPath = '../../uploads/profile/' . $fotoProfil;
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($photoPath); ?>" 
                                 alt="Profile Photo" 
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <!-- Fallback SVG avatar -->
                            <svg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 48 48'>
                                <circle cx='24' cy='24' r='24' fill='#e5e7eb'/>
                                <circle cx='24' cy='18' r='7' fill='#9ca3af'/>
                                <path d='M8 40c0-8 7.163-14.5 16-14.5s16 6.5 16 14.5' fill='#9ca3af'/>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($_SESSION['user']['namaLengkap'] ?? 'User'); ?></p>
                        <p class="text-sm text-gray-500"><?php 
                            $userRole = $_SESSION['user']['role'] ?? 'siswa';
                            switch ($userRole) {
                                case 'admin':
                                    echo 'Administrator';
                                    break;
                                case 'guru':
                                    echo 'Guru';
                                    break;
                                case 'siswa':
                                    echo 'Siswa';
                                    break;
                                default:
                                    echo 'User';
                            }
                        ?></p>
                    </div>
                </div>
                <div class="space-y-2">
                    <a href="settings.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50">
                        <i class="ti ti-user text-gray-500"></i>
                        <span class="text-gray-700">Profile</span>
                    </a>
                    <a href="settings.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50">
                        <i class="ti ti-settings text-gray-500"></i>
                        <span class="text-gray-700">Settings</span>
                    </a>
                    <button command="show-modal" commandfor="logout-modal" class="w-full flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 text-red-600">
                        <i class="ti ti-logout text-red-500"></i>
                        <span>Logout</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
