<!-- cek sekarang ada di halaman apa -->
<?php $currentPage = 'kelas'; ?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Kelas - Pemrograman Web</title>
</head>

<body class="bg-gray-50">
    <!-- Main Content -->
    <div class="md:ml-64 min-h-screen transition-all duration-300 ease-in-out" data-main-content>
        <!-- Jumbotron -->
        <div class="relative h-60 lg:h-80 bg-gradient-to-r from-blue-500 to-purple-600 overflow-hidden">
            <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1471&q=80"
                alt="Class Cover" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-black bg-opacity-40"></div>
            <div class="absolute bottom-4 lg:bottom-6 left-4 lg:left-6 text-white">
                <h1 class="text-2xl lg:text-4xl font-bold mb-2">Pemrograman Web</h1>
                <div class="flex items-center space-x-3 lg:space-x-4">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=150&q=80"
                        alt="Instructor" class="w-10 h-10 lg:w-12 lg:h-12 rounded-full">
                    <div>
                        <p class="text-base lg:text-lg font-medium">Dr. Ahmad Fulan, M.Kom</p>
                        <p class="text-xs lg:text-sm opacity-90">Dosen Pengampu</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="max-w-7xl mx-auto p-4 lg:p-6">
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Left Column - Posts -->
                <div class="flex-1 lg:w-2/3">
                    <!-- Create Post -->
                    <div class="bg-white rounded-lg p-4 lg:p-6 shadow-sm mb-6">
                        <div class="flex items-start space-x-3 lg:space-x-4">
                            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=150&q=80"
                                alt="Your Avatar" class="w-8 h-8 lg:w-10 lg:h-10 rounded-full">
                            <div class="flex-1">
                                <textarea placeholder="Bagikan sesuatu dengan kelas..."
                                    class="w-full p-3 rounded-lg resize-none focus:ring-2 focus:ring-orange-500 focus:outline-none bg-gray-50"
                                    rows="3"></textarea>
                                <div class="flex items-center justify-between mt-4">
                                    <div class="flex space-x-2 lg:space-x-4">
                                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                            <i class="ti ti-photo mr-1 lg:mr-2"></i>
                                            <span class="hidden sm:inline">Foto</span>
                                        </button>
                                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                            <i class="ti ti-file mr-1 lg:mr-2"></i>
                                            <span class="hidden sm:inline">File</span>
                                        </button>
                                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                            <i class="ti ti-link mr-1 lg:mr-2"></i>
                                            <span class="hidden sm:inline">Link</span>
                                        </button>
                                    </div>
                                    <button class="bg-orange text-white px-4 lg:px-6 py-2 rounded-lg hover:bg-orange-600 transition-colors text-sm lg:text-base">
                                        Posting
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Posts Feed -->
                    <div class="space-y-6">
                        <!-- Post 1 -->
                        <div class="bg-white rounded-lg shadow-sm">
                            <div class="p-4 lg:p-6">
                                <div class="flex items-start space-x-3 lg:space-x-4 mb-4">
                                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=150&q=80"
                                        alt="Instructor" class="w-10 h-10 lg:w-12 lg:h-12 rounded-full">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 text-sm lg:text-base">Dr. Ahmad Fulan, M.Kom</h3>
                                        <p class="text-xs lg:text-sm text-gray-600">Dosen • 2 jam yang lalu</p>
                                    </div>
                                    <button class="text-gray-400 hover:text-gray-600">
                                        <i class="ti ti-dots"></i>
                                    </button>
                                </div>
                                <div class="mb-4">
                                    <p class="text-gray-800 mb-3 text-sm lg:text-base">Selamat pagi semua! Reminder untuk tugas UTS yang deadline-nya besok ya. Jangan lupa upload di sistem pembelajaran online.</p>
                                    <div class="bg-orange-tipis rounded-lg p-3 lg:p-4">
                                        <div class="flex items-center">
                                            <i class="ti ti-file-text text-orange mr-3 text-lg lg:text-xl"></i>
                                            <div>
                                                <h4 class="font-medium text-gray-900 text-sm lg:text-base">Tugas UTS - Pemrograman Web</h4>
                                                <p class="text-xs lg:text-sm text-gray-600">Deadline: 23 November 2024, 23:59</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                    <div class="flex items-center space-x-4 lg:space-x-6">
                                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                            <i class="ti ti-heart mr-1 lg:mr-2"></i>
                                            <span>12</span>
                                        </button>
                                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                            <i class="ti ti-message-circle mr-1 lg:mr-2"></i>
                                            <span>5</span>
                                        </button>
                                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                            <i class="ti ti-share mr-1 lg:mr-2"></i>
                                            <span class="hidden sm:inline">Bagikan</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Post 2 -->
                        <div class="bg-white rounded-lg shadow-sm">
                            <div class="p-4 lg:p-6">
                                <div class="flex items-start space-x-3 lg:space-x-4 mb-4">
                                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=150&q=80"
                                        alt="Student" class="w-10 h-10 lg:w-12 lg:h-12 rounded-full">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 text-sm lg:text-base">Budi Santoso</h3>
                                        <p class="text-xs lg:text-sm text-gray-600">Mahasiswa • 5 jam yang lalu</p>
                                    </div>
                                    <button class="text-gray-400 hover:text-gray-600">
                                        <i class="ti ti-dots"></i>
                                    </button>
                                </div>
                                <div class="mb-4">
                                    <p class="text-gray-800 mb-3 text-sm lg:text-base">Ada yang bisa bantu untuk soal nomor 3 di latihan kemarin? Masih bingung dengan konsep OOP-nya 🤔</p>
                                </div>
                                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                    <div class="flex items-center space-x-4 lg:space-x-6">
                                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                            <i class="ti ti-heart mr-1 lg:mr-2"></i>
                                            <span>3</span>
                                        </button>
                                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                            <i class="ti ti-message-circle mr-1 lg:mr-2"></i>
                                            <span>8</span>
                                        </button>
                                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                            <i class="ti ti-share mr-1 lg:mr-2"></i>
                                            <span class="hidden sm:inline">Bagikan</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Post 3 -->
                        <div class="bg-white rounded-lg shadow-sm">
                            <div class="p-4 lg:p-6">
                                <div class="flex items-start space-x-3 lg:space-x-4 mb-4">
                                    <img src="https://images.unsplash.com/photo-1494790108755-2616b612b5e5?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=150&q=80"
                                        alt="Student" class="w-10 h-10 lg:w-12 lg:h-12 rounded-full">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 text-sm lg:text-base">Sari Indah</h3>
                                        <p class="text-xs lg:text-sm text-gray-600">Mahasiswa • 1 hari yang lalu</p>
                                    </div>
                                    <button class="text-gray-400 hover:text-gray-600">
                                        <i class="ti ti-dots"></i>
                                    </button>
                                </div>
                                <div class="mb-4">
                                    <p class="text-gray-800 mb-3 text-sm lg:text-base">Sharing project website yang sudah jadi! Terima kasih buat pak dosen dan teman-teman yang sudah membantu 🙏</p>
                                    <img src="https://images.unsplash.com/photo-1461749280684-dccba630e2f6?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                                        alt="Project Screenshot" class="w-full rounded-lg">
                                </div>
                                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                    <div class="flex items-center space-x-4 lg:space-x-6">
                                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                            <i class="ti ti-heart mr-1 lg:mr-2"></i>
                                            <span>15</span>
                                        </button>
                                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                            <i class="ti ti-message-circle mr-1 lg:mr-2"></i>
                                            <span>7</span>
                                        </button>
                                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                                            <i class="ti ti-share mr-1 lg:mr-2"></i>
                                            <span class="hidden sm:inline">Bagikan</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Class Details -->
                <div class="lg:w-1/3">
                    <div class="sticky top-6">


                        <!-- Quick Actions -->
                        <div class="bg-white rounded-lg p-4 lg:p-6 shadow-sm mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h3>
                            <div class="space-y-2">
                                <button class="w-full flex items-center p-3 text-left hover:bg-orange-50 rounded-lg transition-colors border border-transparent hover:border-orange-200">
                                    <i class="ti ti-file-plus mr-3 text-orange"></i>
                                    <span class="text-sm text-gray-700 font-medium">Buat Tugas</span>
                                </button>
                                <button class="w-full flex items-center p-3 text-left hover:bg-orange-50 rounded-lg transition-colors border border-transparent hover:border-orange-200">
                                    <i class="ti ti-users mr-3 text-orange"></i>
                                    <span class="text-sm text-gray-700 font-medium">Atur Siswa</span>
                                </button>
                                <button class="w-full flex items-center p-3 text-left hover:bg-orange-50 rounded-lg transition-colors border border-transparent hover:border-orange-200">
                                    <i class="ti ti-calendar-plus mr-3 text-orange"></i>
                                    <span class="text-sm text-gray-700 font-medium">Upload Jadwal Kelas</span>
                                </button>
                                <button class="w-full flex items-center p-3 text-left hover:bg-orange-50 rounded-lg transition-colors border border-transparent hover:border-orange-200">
                                    <i class="ti ti-upload mr-3 text-orange"></i>
                                    <span class="text-sm text-gray-700 font-medium">Upload Materi Pelajaran</span>
                                </button>
                            </div>
                        </div>


                        <!-- Class Stats -->
                        <div class="bg-white rounded-lg p-4 lg:p-6 shadow-sm mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Detail Kelas</h3>
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-orange-tipis rounded-lg flex items-center justify-center mr-3">
                                        <i class="ti ti-users text-orange"></i>
                                    </div>
                                    <div>
                                        <p class="text-xl font-bold text-gray-900">32</p>
                                        <p class="text-sm text-gray-600">Mahasiswa</p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-orange-tipis rounded-lg flex items-center justify-center mr-3">
                                        <i class="ti ti-file-text text-orange"></i>
                                    </div>
                                    <div>
                                        <p class="text-xl font-bold text-gray-900">8</p>
                                        <p class="text-sm text-gray-600">Tugas</p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-orange-tipis rounded-lg flex items-center justify-center mr-3">
                                        <i class="ti ti-message-circle text-orange"></i>
                                    </div>
                                    <div>
                                        <p class="text-xl font-bold text-gray-900">24</p>
                                        <p class="text-sm text-gray-600">Postingan</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../script/menu-bar-script.js"></script>
</body>

</html>