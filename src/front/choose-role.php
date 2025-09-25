<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Role - Point</title>
    <?php require '../../assets/head.php'; ?>
</head>

<body>
    <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8 bg-gray-50">
        <div class="sm:mx-auto sm:w-full sm:max-w-4xl">
            <img src="../../assets/img/logo.png" alt="Point" class="mx-auto h-20 w-auto text-center" />
            <h2 class="mt-10 text-center text-3xl font-bold tracking-tight text-black">Pilih Role Anda</h2>
            <p class="mt-2 text-center text-lg text-gray-600">Daftar sebagai siswa atau guru untuk memulai perjalanan belajar Anda</p>
        </div>

        <div class="mt-16 sm:mx-auto sm:w-full sm:max-w-6xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12">
                <!-- Card Siswa -->
                <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 overflow-hidden">
                    <div class="p-8">
                        <!-- Icon Siswa -->
                        <div class="flex justify-center mb-6">
                            <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <h3 class="text-2xl font-bold text-center text-gray-900 mb-4">Saya Siswa</h3>
                        
                        <div class="space-y-4 mb-8">
                            <p class="text-gray-600 text-center leading-relaxed">
                                Bergabunglah sebagai siswa untuk mengakses materi pembelajaran, mengerjakan tugas, dan berinteraksi dengan guru dan teman sekelas.
                            </p>
                            
                            <div class="space-y-3">
                                <div class="flex items-center text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Akses materi pembelajaran dari guru</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Mengerjakan tugas dan ujian online</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Berinteraksi dengan guru dan teman</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Melihat nilai dan progress belajar</span>
                                </div>
                            </div>
                        </div>
                        
                        <a href="register.php?role=siswa" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 text-center block">
                            Daftar sebagai Siswa
                        </a>
                    </div>
                </div>

                <!-- Card Guru -->
                <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 overflow-hidden">
                    <div class="p-8">
                        <!-- Icon Guru -->
                        <div class="flex justify-center mb-6">
                            <div class="w-24 h-24 bg-orange-100 rounded-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <h3 class="text-2xl font-bold text-center text-gray-900 mb-4">Saya Guru</h3>
                        
                        <div class="space-y-4 mb-8">
                            <p class="text-gray-600 text-center leading-relaxed">
                                Bergabunglah sebagai guru untuk membuat kelas, mengelola materi pembelajaran, dan memantau progress siswa dengan mudah.
                            </p>
                            
                            <div class="space-y-3">
                                <div class="flex items-center text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Membuat dan mengelola kelas</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Upload materi dan sumber belajar</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Membuat tugas dan ujian online</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Memantau progress dan nilai siswa</span>
                                </div>
                            </div>
                        </div>
                        
                        <a href="register.php?role=guru" class="w-full bg-orange text-white font-semibold py-3 px-6 rounded-lg hover:bg-orange-600 transition-colors duration-200 text-center block">
                            Daftar sebagai Guru
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Link kembali ke login -->
        <div class="mt-12 text-center">
            <p class="text-sm text-gray-500">
                Sudah punya akun? 
                <a href="../../login.php" class="font-semibold text-orange hover:text-orange-600">Masuk sekarang</a>
            </p>
        </div>
    </div>
</body>

</html>