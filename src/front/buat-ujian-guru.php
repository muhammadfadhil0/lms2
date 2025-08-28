    <!-- cek sekarang ada di halaman apa -->
    <?php 
    session_start();
    $currentPage = 'buat-ujian'; 
    
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
        <title>Buat Ujian - LMS</title>
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
                            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Buat Ujian Baru</h1>
                            <p class="text-sm text-gray-600 mt-1">Atur identitas dan detail ujian</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 md:space-x-4">
                        <button class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="ti ti-help text-lg md:text-xl"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="p-4 md:p-6">
                <div class="max-w-4xl mx-auto">
                    <!-- Progress Indicator -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-orange text-white rounded-full flex items-center justify-center text-sm font-medium">
                                    1
                                </div>
                                <span class="text-sm font-medium text-orange">Identitas Ujian</span>
                            </div>
                            <div class="flex-1 h-px bg-gray-300 mx-4"></div>
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-gray-300 text-gray-500 rounded-full flex items-center justify-center text-sm font-medium">
                                    2
                                </div>
                                <span class="text-sm text-gray-500">Buat Soal</span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Card -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 md:p-8">
                            <form id="createExamForm" action="../logic/create-exam.php" method="POST">
                                <!-- Nama Ujian -->
                                <div class="mb-6">
                                    <label for="exam_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nama Ujian <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="exam_name" name="exam_name" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors"
                                        placeholder="Contoh: Ujian Tengah Semester Matematika">
                                    <p class="text-xs text-gray-500 mt-1">Berikan nama yang jelas dan mudah diidentifikasi</p>
                                </div>

                                <!-- Deskripsi Ujian -->
                                <div class="mb-6">
                                    <label for="exam_description" class="block text-sm font-medium text-gray-700 mb-2">
                                        Deskripsi Ujian
                                    </label>
                                    <textarea id="exam_description" name="exam_description" rows="4"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors resize-none"
                                        placeholder="Jelaskan tujuan ujian, materi yang akan diuji, atau instruksi khusus untuk siswa..."></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Opsional: Berikan deskripsi untuk membantu siswa memahami ujian</p>
                                </div>

                                <!-- Pilih Kelas -->
                                <div class="mb-6">
                                    <label for="exam_class" class="block text-sm font-medium text-gray-700 mb-2">
                                        Kelas <span class="text-red-500">*</span>
                                    </label>
                                    <select id="exam_class" name="exam_class" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors bg-white">
                                        <option value="">Pilih Kelas</option>
                                        <option value="7A">Kelas 7A</option>
                                        <option value="7B">Kelas 7B</option>
                                        <option value="8A">Kelas 8A</option>
                                        <option value="8B">Kelas 8B</option>
                                        <option value="9A">Kelas 9A</option>
                                        <option value="9B">Kelas 9B</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Pilih kelas yang akan mengikuti ujian ini</p>
                                </div>

                                <!-- Materi Ujian -->
                                <div class="mb-6">
                                    <label for="exam_subject" class="block text-sm font-medium text-gray-700 mb-2">
                                        Mata Pelajaran <span class="text-red-500">*</span>
                                    </label>
                                    <select id="exam_subject" name="exam_subject" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors bg-white">
                                        <option value="">Pilih Mata Pelajaran</option>
                                        <option value="matematika">Matematika</option>
                                        <option value="bahasa_indonesia">Bahasa Indonesia</option>
                                        <option value="bahasa_inggris">Bahasa Inggris</option>
                                        <option value="ipa">IPA</option>
                                        <option value="ips">IPS</option>
                                        <option value="ppkn">PPKn</option>
                                        <option value="seni_budaya">Seni Budaya</option>
                                        <option value="pendidikan_jasmani">Pendidikan Jasmani</option>
                                    </select>
                                </div>

                                <!-- Materi/Topik -->
                                <div class="mb-6">
                                    <label for="exam_topic" class="block text-sm font-medium text-gray-700 mb-2">
                                        Materi/Topik <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="exam_topic" name="exam_topic" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors"
                                        placeholder="Contoh: Aljabar, Persamaan Linear">
                                    <p class="text-xs text-gray-500 mt-1">Sebutkan topik atau bab yang akan diujikan</p>
                                </div>

                                <!-- Pengaturan Waktu -->
                                <div class="mb-8">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4">Pengaturan Waktu</h3>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Tanggal Ujian -->
                                        <div>
                                            <label for="exam_date" class="block text-sm font-medium text-gray-700 mb-2">
                                                Tanggal Ujian <span class="text-red-500">*</span>
                                            </label>
                                            <input type="date" id="exam_date" name="exam_date" required
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                        </div>

                                        <!-- Waktu Mulai -->
                                        <div>
                                            <label for="exam_start_time" class="block text-sm font-medium text-gray-700 mb-2">
                                                Waktu Mulai <span class="text-red-500">*</span>
                                            </label>
                                            <input type="time" id="exam_start_time" name="exam_start_time" required
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                        </div>

                                        <!-- Durasi Ujian -->
                                        <div>
                                            <label for="exam_duration" class="block text-sm font-medium text-gray-700 mb-2">
                                                Durasi Ujian <span class="text-red-500">*</span>
                                            </label>
                                            <select id="exam_duration" name="exam_duration" required
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors bg-white">
                                                <option value="">Pilih Durasi</option>
                                                <option value="30">30 Menit</option>
                                                <option value="45">45 Menit</option>
                                                <option value="60">1 Jam</option>
                                                <option value="90">1,5 Jam</option>
                                                <option value="120">2 Jam</option>
                                                <option value="180">3 Jam</option>
                                            </select>
                                        </div>

                                        <!-- Waktu Selesai (Otomatis) -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Waktu Selesai
                                            </label>
                                            <input type="text" id="exam_end_time" readonly
                                                class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-500"
                                                placeholder="Akan dihitung otomatis">
                                            <p class="text-xs text-gray-500 mt-1">Dihitung otomatis berdasarkan waktu mulai dan durasi</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pengaturan Tambahan -->
                                <div class="mb-8">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4">Pengaturan Tambahan</h3>
                                    
                                    <div class="space-y-4">
                                        <!-- Acak Soal -->
                                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                            <div>
                                                <h4 class="font-medium text-gray-800">Acak Urutan Soal</h4>
                                                <p class="text-sm text-gray-600">Soal akan ditampilkan dalam urutan acak untuk setiap siswa</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="randomize_questions" class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange"></div>
                                            </label>
                                        </div>

                                        <!-- Lihat Hasil -->
                                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                            <div>
                                                <h4 class="font-medium text-gray-800">Siswa Dapat Melihat Hasil</h4>
                                                <p class="text-sm text-gray-600">Siswa dapat melihat nilai dan pembahasan setelah ujian selesai</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="show_results" class="sr-only peer" checked>
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                                    <button type="button" onclick="history.back()" 
                                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                                        Batal
                                    </button>
                                    <button type="button" id="saveAsDraft"
                                        class="px-6 py-3 border border-orange text-orange rounded-lg hover:bg-orange-50 transition-colors font-medium">
                                        Simpan sebagai Draft
                                    </button>
                                    <button type="submit"
                                        class="flex-1 sm:flex-none px-8 py-3 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors font-medium">
                                        Lanjut ke Buat Soal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Scripts -->
        <script src="../script/menu-bar-script.js"></script>
        <script>
            // Auto calculate end time when start time and duration change
            function calculateEndTime() {
                const startTime = document.getElementById('exam_start_time').value;
                const duration = document.getElementById('exam_duration').value;
                const endTimeInput = document.getElementById('exam_end_time');

                if (startTime && duration) {
                    const [hours, minutes] = startTime.split(':').map(Number);
                    const durationMinutes = parseInt(duration);
                    
                    const startDate = new Date();
                    startDate.setHours(hours, minutes, 0, 0);
                    
                    const endDate = new Date(startDate.getTime() + durationMinutes * 60000);
                    
                    const endTimeString = endDate.toTimeString().slice(0, 5);
                    endTimeInput.value = endTimeString;
                } else {
                    endTimeInput.value = '';
                }
            }

            // Add event listeners
            document.getElementById('exam_start_time').addEventListener('change', calculateEndTime);
            document.getElementById('exam_duration').addEventListener('change', calculateEndTime);

            // Set minimum date to today
            document.getElementById('exam_date').min = new Date().toISOString().split('T')[0];

            // Form validation
            document.getElementById('createExamForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Basic validation
                const requiredFields = ['exam_name', 'exam_class', 'exam_subject', 'exam_topic', 'exam_date', 'exam_start_time', 'exam_duration'];
                let isValid = true;
                
                requiredFields.forEach(field => {
                    const input = document.getElementById(field);
                    if (!input.value.trim()) {
                        input.classList.add('border-red-500');
                        isValid = false;
                    } else {
                        input.classList.remove('border-red-500');
                    }
                });

                if (isValid) {
                    // Here you would normally submit the form
                    alert('Form berhasil divalidasi! Akan melanjutkan ke halaman buat soal.');
                    // this.submit(); // Uncomment when backend is ready
                } else {
                    alert('Mohon lengkapi semua field yang wajib diisi.');
                }
            });

            // Save as draft functionality
            document.getElementById('saveAsDraft').addEventListener('click', function() {
                // Save current form data to localStorage or send to server
                const formData = new FormData(document.getElementById('createExamForm'));
                const data = Object.fromEntries(formData);
                
                // For now, just show a message
                alert('Draft berhasil disimpan!');
                
                // In real implementation, you would send this to server
                // fetch('../logic/save-exam-draft.php', {
                //     method: 'POST',
                //     body: formData
                // });
            });
        </script>
    </body>
    </html>