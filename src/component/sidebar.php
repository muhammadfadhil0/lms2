<?php
// Use more robust path resolution
$componentDir = __DIR__;
$rootDir = dirname(dirname($componentDir));

// Include required files with proper path resolution
if (file_exists($componentDir . '/modal-logout.php')) {
    require $componentDir . '/modal-logout.php';
} else {
    require __DIR__ . '/modal-logout.php';
}

// Modal chat bantuan and floating button removed - now using help articles page

if (file_exists($componentDir . '/../logic/active-page-sidebar.php')) {
    require $componentDir . '/../logic/active-page-sidebar.php';
} else {
    require $rootDir . '/src/logic/active-page-sidebar.php';
}

if (file_exists($componentDir . '/../logic/profile-photo-helper.php')) {
    require $componentDir . '/../logic/profile-photo-helper.php';
} else {
    require $rootDir . '/src/logic/profile-photo-helper.php';
}

// Get user role from session
$userRole = $_SESSION['user']['role'] ?? 'siswa';
$userName = $_SESSION['user']['namaLengkap'] ?? 'User';
$userEmail = $_SESSION['user']['email'] ?? '';
$userId = $_SESSION['user']['id'] ?? null;

// Get fresh profile photo from database
$freshProfilePhotoUrl = null;
if ($userId) {
    $freshProfilePhotoUrl = getUserProfilePhotoUrl($userId);
}

// Function to get role display name
function getRoleDisplayName($role)
{
    switch ($role) {
        case 'admin':
            return 'Administrator';
        case 'guru':
            return 'Guru';
        case 'siswa':
            return 'Siswa';
        default:
            return 'User';
    }
}

// Function to get role-based navigation items
function getNavigationItems($role)
{
    switch ($role) {
        case 'admin':
            return [
                ['href' => '../front/beranda-admin.php', 'icon' => 'ti ti-home', 'text' => 'Beranda', 'page' => 'beranda'],
                ['href' => '../front/admin-users.php', 'icon' => 'ti ti-users', 'text' => 'Manajemen User', 'page' => 'users'],
                ['href' => '../front/admin-kelas.php', 'icon' => 'ti ti-school', 'text' => 'Manajemen Kelas', 'page' => 'kelas'],
                ['href' => '../front/admin-ujian.php', 'icon' => 'ti ti-clipboard-check', 'text' => 'Manajemen Ujian', 'page' => 'ujian'],
                ['href' => '../front/admin-ai.php', 'icon' => 'ti ti-article', 'text' => 'Manajemen Artikel', 'page' => 'ai'],
                ['href' => '../front/admin-settings.php', 'icon' => 'ti ti-settings', 'text' => 'Pengaturan Sistem', 'page' => 'system-settings']
            ];
        case 'guru':
            return [
                ['href' => '../front/beranda-guru.php', 'icon' => 'ti ti-home', 'text' => 'Beranda', 'page' => 'beranda'],
                ['href' => '../front/ujian-guru.php', 'icon' => 'ti ti-clipboard-check', 'text' => 'Ujian', 'page' => 'ujian'],
                ['href' => '../front/pingo.php', 'icon' => 'ti ti-sparkles', 'text' => 'Pingo AI', 'page' => 'pingo']
            ];
        case 'siswa':
        default:
            return [
                ['href' => '../front/beranda-user.php', 'icon' => 'ti ti-home', 'text' => 'Beranda', 'page' => 'beranda'],
                ['href' => '../front/kelas-beranda-user.php', 'icon' => 'ti ti-book', 'text' => 'Kelas', 'page' => 'kelas'],
                ['href' => '../front/ujian-user.php', 'icon' => 'ti ti-clipboard-check', 'text' => 'Ujian', 'page' => 'ujian'],
                ['href' => '../front/pingo.php', 'icon' => 'ti ti-sparkles', 'text' => 'Pingo AI', 'page' => 'pingo']
            ];
    }
}

$navigationItems = getNavigationItems($userRole);
?>

<div id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg border-r border-gray-200 flex flex-col hidden md:flex transition-all duration-300 ease-in-out z-40">
    <!-- Logo/Header with Toggle -->
    <div class="p-4 flex items-center gap-2 border-b border-gray-200 min-h-[5.5rem] transition-all duration-200 ease-in-out">
        <button onclick="toggleSidebar()" class="hidden flex items-center justify-center p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors flex-shrink-0">
            <i id="toggleIcon" class="ti ti-menu-2 text-xl"></i>
        </button>
        <img src="../../assets/img/logo.png" alt="Logo" class="h-8 w-8 flex-shrink-0">
        <div id="logoTextContainer" class="transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap">
            <h1 id="logoText" class="text-3xl font-bold text-gray-800">Edupoint</h1>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 p-4">
        <ul class="space-y-2">
            <?php foreach ($navigationItems as $item): ?>
                <li>
                    <a href="<?php echo $item['href']; ?>" class="buttonSidebar flex items-center space-x-3 p-3 rounded-lg <?php echo isActivePage($item['page'], $currentPage) ? 'bg-orange text-white' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'; ?> font-medium group transition-colors">
                        <i class="<?php echo $item['icon']; ?> iconSidebar text-xl flex-shrink-0"></i>
                        <span class="nav-text transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap"><?php echo $item['text']; ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- Profile Section -->
    <div class="p-4 border-t border-gray-200 profile-section">
        <div class="relative">
            <button onclick="toggleProfileDropdown()" class="w-full flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group min-h-[64px]">
                <!-- Profile Photo -->
                <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden bg-gray-100">
                    <?php if ($freshProfilePhotoUrl): ?>
                        <img src="<?php echo htmlspecialchars($freshProfilePhotoUrl); ?>" 
                             alt="Profile Photo" 
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <!-- Fallback icon with role-based colors -->
                        <div class="w-full h-full rounded-full flex items-center justify-center <?php
                                                                                                    switch ($userRole) {
                                                                                                        case 'admin':
                                                                                                            echo 'bg-red-100 text-red-600';
                                                                                                            break;
                                                                                                        case 'guru':
                                                                                                            echo 'bg-blue-100 text-blue-600';
                                                                                                            break;
                                                                                                        case 'siswa':
                                                                                                            echo 'bg-green-100 text-green-600';
                                                                                                            break;
                                                                                                        default:
                                                                                                            echo 'bg-gray-100 text-gray-600';
                                                                                                    }
                                                                                                    ?>">
                            <i class="<?php
                                        switch ($userRole) {
                                            case 'admin':
                                                echo 'ti ti-shield-check';
                                                break;
                                            case 'guru':
                                                echo 'ti ti-school';
                                                break;
                                            case 'siswa':
                                                echo 'ti ti-user';
                                                break;
                                            default:
                                                echo 'ti ti-user';
                                        }
                                        ?> text-lg"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div id="profileTextContainer" class="flex-1 text-left transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap">
                    <div class="nav-text">
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($userName); ?></p>
                        <p class="text-xs text-gray-500"><?php echo getRoleDisplayName($userRole); ?></p>
                    </div>
                </div>
                <i class="ti ti-chevron-up text-gray-400 nav-text transition-all duration-300 ease-in-out flex-shrink-0"></i>
            </button>

            <!-- Dropdown Menu -->
            <div id="profileDropdown" class="hidden absolute bottom-full left-0 right-0 mb-2 bg-white border border-gray-200 rounded-lg shadow-lg">
                <!-- Profile Info -->
                <div class="p-3 border-b border-gray-200">
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($userName); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($userEmail); ?></p>
                    <div class="flex items-center mt-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php
                                                                                                            switch ($userRole) {
                                                                                                                case 'admin':
                                                                                                                    echo 'bg-red-100 text-red-800';
                                                                                                                    break;
                                                                                                                case 'guru':
                                                                                                                    echo 'bg-blue-100 text-blue-800';
                                                                                                                    break;
                                                                                                                case 'siswa':
                                                                                                                    echo 'bg-green-100 text-green-800';
                                                                                                                    break;
                                                                                                                default:
                                                                                                                    echo 'bg-gray-100 text-gray-800';
                                                                                                            }
                                                                                                            ?>">
                            <i class="<?php
                                        switch ($userRole) {
                                            case 'admin':
                                                echo 'ti ti-shield-check';
                                                break;
                                            case 'guru':
                                                echo 'ti ti-school';
                                                break;
                                            case 'siswa':
                                                echo 'ti ti-user';
                                                break;
                                            default:
                                                echo 'ti ti-user';
                                        }
                                        ?> mr-1"></i>
                            <?php echo getRoleDisplayName($userRole); ?>
                        </span>
                    </div>
                </div>

                <!-- Menu Items -->
                <a href="../front/settings.php" class="flex items-center space-x-2 p-3 hover:bg-gray-50 transition-colors">
                    <i class="ti ti-settings text-gray-500"></i>
                    <span class="text-sm text-gray-700">Pengaturan</span>
                </a>

                <?php if ($userRole === 'admin'): ?>
                    <a href="../front/admin-profile.php" class="flex items-center space-x-2 p-3 hover:bg-gray-50 transition-colors">
                        <i class="ti ti-user-cog text-gray-500"></i>
                        <span class="text-sm text-gray-700">Profil Admin</span>
                    </a>
                <?php endif; ?> 

                <a href="../front/help-articles.php" class="w-full flex items-center space-x-2 p-3 hover:bg-gray-50 transition-colors">
                    <i class="ti ti-help text-gray-500"></i>
                    <span class="text-sm text-gray-700">Bantuan</span>
                </a>

                <hr class="border-gray-200">
                <button command="show-modal" commandfor="logout-modal" class="w-full flex items-center space-x-2 p-3 hover:bg-gray-50 transition-colors text-red-600">
                    <i class="ti ti-logout text-red-500"></i>
                    <span class="text-sm">Keluar</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="../script/script-sidebar-collaps.js"></script>
<script src="../script/sidebar-roles.js"></script>
<script src="../script/profile-sync.js"></script>
<script>
    // Add user role data attribute for JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.setAttribute('data-user-role', '<?php echo $userRole; ?>');
            sidebar.setAttribute('data-user-id', '<?php echo $_SESSION['user']['id'] ?? ''; ?>');
        }
    });
</script>