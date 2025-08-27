<!-- cek sekarang ada di halaman apa -->
<?php $currentPage = 'pingo'; ?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>AI Chat - Pingo</title>
</head>
<body class="">

    <!-- Main Content -->
    <div data-main-content class="md:ml-64 h-screen pb-16 md:pb-0 transition-all duration-300 ease-in-out flex flex-col">
        <!-- Header -->
        <header class="bg-white p-3 md:p-6 flex-shrink-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-800">AI Assistant</h1>
                    <p class="text-xs md:text-sm text-gray-600">Tanya apapun tentang pembelajaran</p>
                </div>
                <div class="flex items-center space-x-1 md:space-x-4">
                    <button class="p-1.5 md:p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="ti ti-bell text-base md:text-xl"></i>
                    </button>
                    <button class="p-1.5 md:p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="ti ti-search text-base md:text-xl"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Chat Container -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Chat Messages -->
            <div class="flex-1 bg-white mx-3 md:mx-6 mt-3 md:mt-6 mb-2 md:mb-4 flex flex-col overflow-hidden">
                <div class="flex-1 p-3 md:p-6 overflow-y-auto">
                    <div class="space-y-3 md:space-y-4">
                        <!-- AI Message -->
                        <div class="flex items-start space-x-2 md:space-x-3">
                            <div class="w-6 h-6 md:w-8 md:h-8 bg-orange-500 rounded-full flex items-center justify-center text-white text-xs md:text-sm font-medium flex-shrink-0">
                                AI
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="bg-gray-100 rounded-lg p-2.5 md:p-3 max-w-[85%] sm:max-w-md">
                                    <p class="text-sm md:text-base text-gray-800">Halo! Saya adalah AI Assistant Pingo. Saya siap membantu Anda dengan pertanyaan seputar pembelajaran. Ada yang bisa saya bantu?</p>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">10:30 AM</p>
                            </div>
                        </div>

                        <!-- User Message -->
                        <div class="flex items-start space-x-2 md:space-x-3 justify-end">
                            <div class="flex-1 flex justify-end min-w-0">
                                <div class="bg-orange-500 rounded-lg p-2.5 md:p-3 max-w-[85%] sm:max-w-md text-white">
                                    <p class="text-sm md:text-base">Bisakah kamu jelaskan tentang rumus kuadrat dalam matematika?</p>
                                </div>
                            </div>
                            <div class="w-6 h-6 md:w-8 md:h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs md:text-sm font-medium flex-shrink-0">
                                U
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <p class="text-xs text-gray-500">10:32 AM</p>
                        </div>

                        <!-- AI Message -->
                        <div class="flex items-start space-x-2 md:space-x-3">
                            <div class="w-6 h-6 md:w-8 md:h-8 bg-orange-500 rounded-full flex items-center justify-center text-white text-xs md:text-sm font-medium flex-shrink-0">
                                AI
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="bg-gray-100 rounded-lg p-2.5 md:p-3 max-w-[85%] sm:max-w-md">
                                    <p class="text-sm md:text-base text-gray-800">Tentu! Rumus kuadrat adalah ax² + bx + c = 0. Untuk mencari akar-akarnya, kita bisa menggunakan rumus:</p>
                                    <p class="text-sm md:text-base text-gray-800 mt-2 font-mono bg-gray-200 p-2 rounded text-xs md:text-sm overflow-x-auto">x = (-b ± √(b²-4ac)) / 2a</p>
                                    <p class="text-sm md:text-base text-gray-800 mt-2">Di mana a, b, dan c adalah koefisien dari persamaan kuadrat. Apakah ada yang ingin ditanyakan lebih lanjut?</p>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">10:35 AM</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Input -->
                <div class="border-t border-gray-200 p-3 md:p-4 flex-shrink-0">
                    <div class="flex space-x-2 md:space-x-3">
                        <input 
                            type="text" 
                            placeholder="Ketik pesan Anda di sini..."
                            class="flex-1 px-3 py-2.5 md:px-4 md:py-2 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        >
                        <button class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-2.5 md:px-4 md:py-2 rounded-lg transition-colors flex items-center space-x-1 md:space-x-2 flex-shrink-0">
                            <i class="ti ti-send text-sm md:text-lg"></i>
                            <span class="hidden sm:inline text-sm md:text-base">Kirim</span>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../script/menu-bar-script.js"></script>
</body>
</html>