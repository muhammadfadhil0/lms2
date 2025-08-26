<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Beranda</title>
</head>
<body class="bg-gray-50">
    <!-- Main Content -->
    <div class="md:ml-64 min-h-screen pb-20 md:pb-0">
        <!-- Header -->
        <header class="bg-white p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Beranda</h1>
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
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6 mb-6 md:mb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6">
                    <div class="flex items-center">
                        <div class="p-2 md:p-3 bg-blue-100 rounded-lg">
                            <i class="ti ti-book text-blue-600 text-lg md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-4">
                            <p class="text-xs md:text-sm text-gray-600">Total Kelas</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-800">12</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6">
                    <div class="flex items-center">
                        <div class="p-2 md:p-3 bg-green-100 rounded-lg">
                            <i class="ti ti-clipboard-check text-green-600 text-lg md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-4">
                            <p class="text-xs md:text-sm text-gray-600">Ujian Selesai</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-800">8</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6 sm:col-span-2 md:col-span-1">
                    <div class="flex items-center">
                        <div class="p-2 md:p-3 bg-yellow-100 rounded-lg">
                            <i class="ti ti-clock text-yellow-600 text-lg md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-4">
                            <p class="text-xs md:text-sm text-gray-600">Tugas Pending</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-800">4</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Classes Section -->
            <div class="mb-6">
                <h2 class="text-lg md:text-xl font-bold text-gray-800 mb-4">Kelas Tersedia</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <!-- Class Card 1 -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="h-32 sm:h-40 md:h-48 bg-gradient-to-br from-blue-400 to-blue-600 relative">
                            <img src="https://via.placeholder.com/400x200?text=Matematika" alt="Matematika" class="w-full h-full object-cover">
                            <div class="absolute top-2 md:top-4 right-2 md:right-4">
                                <span class="bg-white bg-opacity-90 text-blue-600 text-xs font-medium px-2 py-1 rounded-full">
                                    Matematika
                                </span>
                            </div>
                        </div>
                        <div class="p-4 md:p-6">
                            <div class="flex items-center mb-3 md:mb-4">
                                <img src="https://via.placeholder.com/40?text=GP" alt="Guru" class="w-8 h-8 md:w-10 md:h-10 rounded-full">
                                <div class="ml-2 md:ml-3">
                                    <p class="text-sm md:font-medium text-gray-800">Pak Ahmad</p>
                                    <p class="text-xs md:text-sm text-gray-500">Guru Matematika</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs md:text-sm text-gray-600 mb-3 md:mb-4">
                                <span class="flex items-center">
                                    <i class="ti ti-users mr-1"></i>
                                    35 siswa
                                </span>
                                <span class="flex items-center">
                                    <i class="ti ti-clock mr-1"></i>
                                    2 jam/minggu
                                </span>
                            </div>
                            <button class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors text-sm md:font-medium">
                                Ikuti Kelas
                            </button>
                        </div>
                    </div>

                    <!-- Class Card 2 -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="h-32 sm:h-40 md:h-48 bg-gradient-to-br from-green-400 to-green-600 relative">
                            <img src="https://via.placeholder.com/400x200?text=Bahasa+Indonesia" alt="Bahasa Indonesia" class="w-full h-full object-cover">
                            <div class="absolute top-2 md:top-4 right-2 md:right-4">
                                <span class="bg-white bg-opacity-90 text-green-600 text-xs font-medium px-2 py-1 rounded-full">
                                    Bahasa Indonesia
                                </span>
                            </div>
                        </div>
                        <div class="p-4 md:p-6">
                            <div class="flex items-center mb-3 md:mb-4">
                                <img src="https://via.placeholder.com/40?text=IB" alt="Guru" class="w-8 h-8 md:w-10 md:h-10 rounded-full">
                                <div class="ml-2 md:ml-3">
                                    <p class="text-sm md:font-medium text-gray-800">Bu Sari</p>
                                    <p class="text-xs md:text-sm text-gray-500">Guru Bahasa Indonesia</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs md:text-sm text-gray-600 mb-3 md:mb-4">
                                <span class="flex items-center">
                                    <i class="ti ti-users mr-1"></i>
                                    32 siswa
                                </span>
                                <span class="flex items-center">
                                    <i class="ti ti-clock mr-1"></i>
                                    3 jam/minggu
                                </span>
                            </div>
                            <button class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors text-sm md:font-medium">
                                Ikuti Kelas
                            </button>
                        </div>
                    </div>

                    <!-- Class Card 3 -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="h-32 sm:h-40 md:h-48 bg-gradient-to-br from-purple-400 to-purple-600 relative">
                            <img src="https://via.placeholder.com/400x200?text=Fisika" alt="Fisika" class="w-full h-full object-cover">
                            <div class="absolute top-2 md:top-4 right-2 md:right-4">
                                <span class="bg-white bg-opacity-90 text-purple-600 text-xs font-medium px-2 py-1 rounded-full">
                                    Fisika
                                </span>
                            </div>
                        </div>
                        <div class="p-4 md:p-6">
                            <div class="flex items-center mb-3 md:mb-4">
                                <img src="https://via.placeholder.com/40?text=BU" alt="Guru" class="w-8 h-8 md:w-10 md:h-10 rounded-full">
                                <div class="ml-2 md:ml-3">
                                    <p class="text-sm md:font-medium text-gray-800">Pak Budi</p>
                                    <p class="text-xs md:text-sm text-gray-500">Guru Fisika</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs md:text-sm text-gray-600 mb-3 md:mb-4">
                                <span class="flex items-center">
                                    <i class="ti ti-users mr-1"></i>
                                    28 siswa
                                </span>
                                <span class="flex items-center">
                                    <i class="ti ti-clock mr-1"></i>
                                    2 jam/minggu
                                </span>
                            </div>
                            <button class="w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors text-sm md:font-medium">
                                Ikuti Kelas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>


    <script>
    function toggleMobileProfile() {
        const modal = document.getElementById('mobileProfileModal');
        modal.classList.toggle('hidden');
    }

    // Close mobile profile modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('mobileProfileModal');
        const button = event.target.closest('button[onclick="toggleMobileProfile()"]');
        
        if (!button && modal && !modal.classList.contains('hidden') && !modal.querySelector('.bg-white').contains(event.target)) {
            modal.classList.add('hidden');
        }
    });
    </script>
</body>
</html>