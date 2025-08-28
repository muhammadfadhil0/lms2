    <!-- cek sekarang ada di halaman apa -->
    <?php 
    session_start();
    $currentPage = 'buat-soal'; 
    
    // Check if user is logged in and is a guru
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
        header("Location: ../../index.php");
        exit();
    }
    ?>
    <!-- includes -->
    <?php require '../component/sidebar.php'; ?>
    <?php require '../component/menu-bar-mobile.php'; ?>
    <?php require '../component/modal-add-class.php'; ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?php require '../../assets/head.php'; ?>
        <title>Buat Soal - LMS</title>
        <style>
            .question-card {
                transition: all 0.3s ease;
            }
            .question-card.active {
                border-color: rgb(255, 99, 71);
                box-shadow: 0 0 0 1px rgb(255, 99, 71);
            }
            .sidebar-tools {
                position: sticky;
                top: 20px;
            }
            .option-input {
                transition: all 0.2s ease;
            }
            .option-input:focus {
                border-color: rgb(255, 99, 71);
                box-shadow: 0 0 0 1px rgb(255, 99, 71);
            }
            .drag-handle {
                cursor: grab;
            }
            .drag-handle:active {
                cursor: grabbing;
            }
        </style>
    </head>
    <body class="bg-gray-50">

        <!-- Main Content -->
        <div data-main-content class="md:ml-64 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">
            <!-- Header -->
            <header class="bg-white p-4 md:p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="history.back()" class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-gray-100">
                            <i class="ti ti-arrow-left text-xl"></i>
                        </button>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Buat Soal Ujian</h1>
                            <p class="text-sm text-gray-600 mt-1">Tambahkan soal untuk ujian</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 md:space-x-4">
                        <span class="text-sm text-gray-500">Auto-saved</span>
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="p-4 md:p-6">
                <div class="max-w-7xl mx-auto">
                    <!-- Progress Indicator -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-medium">
                                    <i class="ti ti-check text-sm"></i>
                                </div>
                                <span class="text-sm text-green-600">Identitas Ujian</span>
                            </div>
                            <div class="flex-1 h-px bg-orange mx-4"></div>
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-orange text-white rounded-full flex items-center justify-center text-sm font-medium">
                                    2
                                </div>
                                <span class="text-sm font-medium text-orange">Buat Soal</span>
                            </div>
                        </div>
                    </div>

                    <!-- Layout Container -->
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        <!-- Main Content (3/4 width on lg screens) -->
                        <div class="lg:col-span-3 space-y-6">
                            <!-- Exam Identity Summary -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="ti ti-info-circle text-orange mr-2"></i>
                                    Identitas Ujian
                                </h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p class="text-gray-600">Nama Ujian:</p>
                                        <p class="font-medium text-gray-800" id="exam-name-display">Ujian Tengah Semester Matematika</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600">Kelas:</p>
                                        <p class="font-medium text-gray-800" id="exam-class-display">8A</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600">Mata Pelajaran:</p>
                                        <p class="font-medium text-gray-800" id="exam-subject-display">Matematika</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600">Materi:</p>
                                        <p class="font-medium text-gray-800" id="exam-topic-display">Aljabar dan Persamaan Linear</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600">Tanggal:</p>
                                        <p class="font-medium text-gray-800" id="exam-date-display">15 September 2025</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600">Waktu:</p>
                                        <p class="font-medium text-gray-800" id="exam-time-display">08:00 - 09:30 (90 menit)</p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <p class="text-gray-600 text-sm">Deskripsi:</p>
                                    <p class="text-gray-800 text-sm" id="exam-description-display">Ujian untuk mengukur pemahaman siswa terhadap materi aljabar dan persamaan linear yang telah dipelajari.</p>
                                </div>
                            </div>

                            <!-- Questions Container -->
                            <div id="questions-container" class="space-y-4">
                                <!-- Question 1 (Default) -->
                                <div class="question-card bg-white rounded-lg shadow-sm border border-gray-200 p-6 active" data-question-id="1">
                                    <!-- Question Header -->
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="drag-handle p-1 text-gray-400 hover:text-gray-600">
                                                <i class="ti ti-grip-vertical"></i>
                                            </div>
                                            <h3 class="text-lg font-medium text-gray-800">Soal 1</h3>
                                            <span class="text-sm text-gray-500">(Wajib)</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button class="duplicate-question p-2 text-gray-400 hover:text-orange transition-colors rounded-lg hover:bg-gray-50" title="Duplikat Soal">
                                                <i class="ti ti-copy"></i>
                                            </button>
                                            <button class="delete-question p-2 text-gray-400 hover:text-red-500 transition-colors rounded-lg hover:bg-gray-50" title="Hapus Soal">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Question Type Selector -->
                                    <div class="mb-4">
                                        <select class="question-type-select w-full md:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white">
                                            <option value="multiple_choice">Pilihan Ganda</option>
                                            <option value="short_answer">Jawaban Singkat</option>
                                            <option value="long_answer">Jawaban Panjang</option>
                                        </select>
                                    </div>

                                    <!-- Question Input -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pertanyaan</label>
                                        <textarea class="question-text w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none" 
                                                  rows="3" placeholder="Masukkan pertanyaan..."></textarea>
                                    </div>

                                    <!-- Question Image -->
                                    <div class="mb-4">
                                        <button class="add-image-btn flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                            <i class="ti ti-photo"></i>
                                            <span>Tambah Gambar</span>
                                        </button>
                                        <input type="file" class="image-input hidden" accept="image/*">
                                        <div class="image-preview mt-2 hidden">
                                            <img class="max-w-full h-auto rounded-lg border border-gray-200" alt="Preview">
                                            <button class="remove-image mt-2 text-red-500 hover:text-red-700 text-sm">
                                                <i class="ti ti-x"></i> Hapus Gambar
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Answer Options (Multiple Choice) -->
                                    <div class="answer-options mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan Jawaban</label>
                                        <div class="space-y-2">
                                            <div class="flex items-center space-x-3">
                                                <input type="radio" name="correct_answer_1" value="A" class="text-orange-500 focus:ring-orange-500">
                                                <span class="w-6 text-sm font-medium text-gray-600">A.</span>
                                                <input type="text" class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="Pilihan A">
                                                <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                                    <i class="ti ti-x"></i>
                                                </button>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <input type="radio" name="correct_answer_1" value="B" class="text-orange-500 focus:ring-orange-500">
                                                <span class="w-6 text-sm font-medium text-gray-600">B.</span>
                                                <input type="text" class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="Pilihan B">
                                                <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                                    <i class="ti ti-x"></i>
                                                </button>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <input type="radio" name="correct_answer_1" value="C" class="text-orange-500 focus:ring-orange-500">
                                                <span class="w-6 text-sm font-medium text-gray-600">C.</span>
                                                <input type="text" class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="Pilihan C">
                                                <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                                    <i class="ti ti-x"></i>
                                                </button>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <input type="radio" name="correct_answer_1" value="D" class="text-orange-500 focus:ring-orange-500">
                                                <span class="w-6 text-sm font-medium text-gray-600">D.</span>
                                                <input type="text" class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="Pilihan D">
                                                <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                                    <i class="ti ti-x"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <button class="add-option mt-2 text-orange hover:text-orange-600 text-sm font-medium">
                                            <i class="ti ti-plus"></i> Tambah Pilihan
                                        </button>
                                    </div>

                                    <!-- Short/Long Answer (Hidden by default) -->
                                    <div class="answer-key hidden mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Kunci Jawaban</label>
                                        <textarea class="answer-key-text w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none" 
                                                  rows="2" placeholder="Masukkan kunci jawaban..."></textarea>
                                    </div>

                                    <!-- Question Settings -->
                                    <div class="flex flex-wrap items-center justify-between pt-4 border-t border-gray-200">
                                        <div class="flex items-center space-x-4">
                                            <!-- Auto Grading Toggle -->
                                            <div class="flex items-center space-x-2">
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" class="auto-grading-toggle sr-only peer" checked>
                                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange"></div>
                                                </label>
                                                <span class="text-sm text-gray-700">Penilaian Otomatis</span>
                                            </div>

                                            <!-- Point Value -->
                                            <div class="flex items-center space-x-2">
                                                <span class="text-sm text-gray-700">Poin:</span>
                                                <input type="number" class="question-points w-16 px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500" value="10" min="1">
                                            </div>
                                        </div>

                                        <!-- Required Toggle -->
                                        <div class="flex items-center space-x-2">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" class="required-toggle sr-only peer" checked>
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange"></div>
                                            </label>
                                            <span class="text-sm text-gray-700">Wajib</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar Tools (1/4 width on lg screens) -->
                        <div class="lg:col-span-1">
                            <div class="sidebar-tools bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                                <h3 class="text-lg font-medium text-gray-800 mb-4">Tools</h3>
                                
                                <!-- Add Question -->
                                <button id="add-question-btn" class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors mb-3">
                                    <i class="ti ti-plus"></i>
                                    <span>Tambah Soal</span>
                                </button>

                                <!-- Add Description -->
                                <button id="add-description-btn" class="w-full flex items-center justify-center space-x-2 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors mb-3">
                                    <i class="ti ti-text"></i>
                                    <span>Tambah Deskripsi</span>
                                </button>

                                <!-- Question Navigation -->
                                <div class="mt-6">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Navigasi Soal</h4>
                                    <div id="question-nav" class="space-y-2">
                                        <button class="question-nav-item w-full text-left px-3 py-2 rounded-lg border border-orange bg-orange-50 text-orange font-medium" data-question="1">
                                            1. Soal
                                        </button>
                                    </div>
                                </div>

                                <!-- Quick Stats -->
                                <div class="mt-6 pt-4 border-t border-gray-200">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Statistik</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Total Soal:</span>
                                            <span id="total-questions" class="font-medium">1</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Total Poin:</span>
                                            <span id="total-points" class="font-medium">10</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 mt-8 pt-6 border-t border-gray-200">
                        <button onclick="history.back()" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                            Kembali
                        </button>
                        <button id="save-draft-btn" class="px-6 py-3 border border-orange text-orange rounded-lg hover:bg-orange-50 transition-colors font-medium">
                            Simpan Draft
                        </button>
                        <button id="preview-exam-btn" class="px-6 py-3 border border-blue-500 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors font-medium">
                            Preview Ujian
                        </button>
                        <button id="publish-exam-btn" class="flex-1 sm:flex-none px-8 py-3 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors font-medium">
                            Publikasikan Ujian
                        </button>
                    </div>
                </div>
            </main>
        </div>

        <!-- Scripts -->
        <script src="../script/menu-bar-script.js"></script>
        <script src="../script/buat-soal.js"></script>
    </body>
    </html>