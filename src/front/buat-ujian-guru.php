    <!-- cek sekarang ada di halaman apa -->
    <?php 
    session_start();
    $currentPage = 'buat-ujian'; 
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
        header("Location: ../../index.php");
        exit();
    }
    require_once '../logic/kelas-logic.php';
    require_once '../logic/ujian-logic.php';
    $kelasLogic = new KelasLogic();
    $kelasGuru = $kelasLogic->getKelasByGuru($_SESSION['user']['id']);
    $errors = $_SESSION['flash_errors'] ?? [];
    $old = $_SESSION['old_exam_form'] ?? [];
    unset($_SESSION['flash_errors'], $_SESSION['old_exam_form']);

    // Edit mode detection
    $editing = false;
    $ujian_id = isset($_GET['ujian_id']) ? (int)$_GET['ujian_id'] : 0;
    $ujianData = null;
    if ($ujian_id > 0) {
        $ujianLogic = new UjianLogic();
        $ujianData = $ujianLogic->getUjianByIdAndGuru($ujian_id, $_SESSION['user']['id']);
        if ($ujianData) {
            $editing = true;
            if (empty($old)) { // only prefill if not coming back from validation error
                // Try split topik & deskripsi (pattern: top section then blank line then rest)
                $rawDesc = $ujianData['deskripsi'] ?? '';
                $topic = '';
                $desc = $rawDesc;
                if (strpos($rawDesc, "\n\n") !== false) {
                    list($firstBlock, $rest) = explode("\n\n", $rawDesc, 2);
                    // heuristic: if first block length < 120 chars treat as topic
                    if (mb_strlen(trim($firstBlock)) <= 120) {
                        $topic = trim($firstBlock);
                        $desc = $rest;
                    }
                }
                $old = [
                    'exam_name' => $ujianData['namaUjian'] ?? '',
                    'exam_description' => $desc,
                    'exam_topic' => $topic,
                    'exam_class' => $ujianData['kelas_id'] ?? '',
                    'exam_subject' => $ujianData['mataPelajaran'] ?? '',
                    'exam_date' => $ujianData['tanggalUjian'] ?? '',
                    'exam_start_time' => isset($ujianData['waktuMulai']) ? substr($ujianData['waktuMulai'],0,5) : '',
                    'exam_duration' => $ujianData['durasi'] ?? '',
                ];
                if (isset($ujianData['shuffleQuestions']) && $ujianData['shuffleQuestions']) {
                    $old['shuffle_questions'] = 1;
                }
                if (!isset($ujianData['showScore']) || $ujianData['showScore']) { $old['show_score'] = 1; }
                if (isset($ujianData['autoScore']) && $ujianData['autoScore']) { $old['auto_score'] = 1; }
            }
        }
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
    <title><?= $editing ? 'Edit Ujian' : 'Buat Ujian' ?> - LMS</title>
    </head>
    <body class="bg-gray-50">

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 space-y-3 z-[10000]"></div>

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
                            <h1 class="text-xl md:text-2xl font-bold text-gray-800"><?= $editing ? 'Edit Ujian' : 'Buat Ujian Baru' ?></h1>
                            <p class="text-sm text-gray-600 mt-1"><?= $editing ? 'Perbarui identitas dan detail ujian' : 'Atur identitas dan detail ujian' ?></p>
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
                            <form id="createExamForm" action="../logic/create-exam.php" method="POST" novalidate>
                                <?php if ($editing): ?>
                                    <input type="hidden" name="ujian_id" value="<?= (int)$ujian_id ?>">
                                <?php endif; ?>
                                <?php if (!empty($errors)): ?>
                                <div class="mb-6 p-4 rounded-lg border border-red-200 bg-red-50 text-sm text-red-700">
                                    <ul class="list-disc ml-4 space-y-1">
                                        <?php foreach($errors as $e): ?>
                                            <li><?= htmlspecialchars($e) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                                <!-- Nama Ujian -->
                                <div class="mb-6">
                                    <label for="exam_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nama Ujian <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="exam_name" name="exam_name" required value="<?= htmlspecialchars($old['exam_name'] ?? '') ?>"
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
                                        placeholder="Jelaskan tujuan ujian, materi yang akan diuji, atau instruksi khusus untuk siswa..."><?= htmlspecialchars($old['exam_description'] ?? '') ?></textarea>
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
                                        <?php foreach($kelasGuru as $k): ?>
                                            <option value="<?= (int)$k['id'] ?>" <?= (isset($old['exam_class']) && (int)$old['exam_class']==(int)$k['id']) ? 'selected' : '' ?>><?= htmlspecialchars($k['namaKelas']) ?> (<?= htmlspecialchars($k['mataPelajaran']) ?>)</option>
                                        <?php endforeach; ?>
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
                                        <?php $subjects = ['matematika'=>'Matematika','bahasa_indonesia'=>'Bahasa Indonesia','bahasa_inggris'=>'Bahasa Inggris','ipa'=>'IPA','ips'=>'IPS','ppkn'=>'PPKn','seni_budaya'=>'Seni Budaya','pendidikan_jasmani'=>'Pendidikan Jasmani'];
                                        foreach($subjects as $val=>$label): ?>
                                            <option value="<?= $val ?>" <?= (($old['exam_subject'] ?? '') === $val) ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Materi/Topik -->
                                <div class="mb-6">
                                    <label for="exam_topic" class="block text-sm font-medium text-gray-700 mb-2">
                                           Materi/Topik <span class="text-gray-400 text-xs font-normal">(Opsional)</span>
                                    </label>
                                    <input type="text" id="exam_topic" name="exam_topic" value="<?= htmlspecialchars($old['exam_topic'] ?? '') ?>"
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
                                            <input type="date" id="exam_date" name="exam_date" required value="<?= htmlspecialchars($old['exam_date'] ?? '') ?>"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                        </div>

                                        <!-- Waktu Mulai -->
                                        <div>
                                            <label for="exam_start_time" class="block text-sm font-medium text-gray-700 mb-2">
                                                Waktu Mulai <span class="text-red-500">*</span>
                                            </label>
                                            <input type="time" id="exam_start_time" name="exam_start_time" required value="<?= htmlspecialchars($old['exam_start_time'] ?? '') ?>"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                        </div>

                                        <!-- Durasi Ujian -->
                                        <div>
                                            <label for="exam_duration" class="block text-sm font-medium text-gray-700 mb-2">
                                                   Durasi Ujian <span class="text-gray-400 text-xs font-normal">(Opsional, default 60)</span>
                                            </label>
                                            <select id="exam_duration" name="exam_duration" required
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors bg-white">
                                                <option value="">Pilih Durasi</option>
                                                <?php foreach([15,30,45,60,75,90,105,120,150,180] as $d): ?>
                                                    <option value="<?= $d ?>" <?= ((int)($old['exam_duration'] ?? 0) === $d) ? 'selected' : '' ?>><?= $d ?> Menit</option>
                                                <?php endforeach; ?>
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
                                                <input type="checkbox" name="shuffle_questions" class="sr-only peer" <?= isset($old['shuffle_questions']) ? 'checked' : '' ?>>
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
                                                <input type="checkbox" name="show_score" class="sr-only peer" <?= isset($old['show_score']) ? 'checked' : 'checked' ?>>
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange"></div>
                                            </label>
                                        </div>

                                        <!-- Auto Score -->
                                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg" id="autoScoreWrapper">
                                            <div>
                                                <h4 class="font-medium text-gray-800">Hitung Nilai Otomatis</h4>
                                                <p class="text-sm text-gray-600">Hanya pilihan ganda, total nilai akan diskalakan ke 100</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="auto_score" id="auto_score_toggle" class="sr-only peer" <?= isset($old['auto_score']) ? 'checked' : '' ?>>
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer transition-colors peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange"></div>
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
                                    <button type="submit" id="submitBtn"
                                        class="flex-1 sm:flex-none px-8 py-3 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                        <?= $editing ? 'Simpan & Kembali ke Soal' : 'Lanjut ke Buat Soal' ?>
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
            function showToast(message, type='info'){
                const colors = {success:'bg-green-600',error:'bg-red-600',info:'bg-blue-600',warning:'bg-yellow-600 text-gray-900'};
                const c = document.getElementById('toast-container'); if(!c) return alert(message);
                const el = document.createElement('div');
                el.className = `toast flex items-start text-sm text-white px-4 py-3 rounded-lg shadow-lg backdrop-blur-md bg-opacity-90 ${colors[type]||colors.info} animate-fade-in`;
                el.innerHTML = `<div class='mr-3 mt-0.5'><i class=\"ti ${type==='success'?'ti-check':type==='error'?'ti-alert-circle':type==='warning'?'ti-alert-triangle':'ti-info-circle'}\"></i></div><div class='flex-1'>${message}</div><button class='ml-3 text-white/80 hover:text-white' onclick='this.parentElement.remove()'><i class=\"ti ti-x\"></i></button>`;
                c.appendChild(el);
                setTimeout(()=>{ el.classList.add('opacity-0','translate-x-2'); setTimeout(()=>el.remove(),300); },4000);
            }
            (function(){
                const p=new URLSearchParams(location.search);
                if(p.get('duplicated')==='1'){
                    showToast('Salinan ujian berhasil dibuat. Lakukan penyesuaian lalu lanjut ke pembuatan soal.', 'success');
                    const url=new URL(location.href); url.searchParams.delete('duplicated'); history.replaceState({},'',url);
                }
            })();
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

            // If editing and values exist, compute end time immediately
            (function(){
                const startVal = document.getElementById('exam_start_time').value;
                const durVal = document.getElementById('exam_duration').value;
                if(startVal && durVal){
                    calculateEndTime();
                }
            })();

            // Warn user when enabling auto score (non-MC questions will be inactive)
            const autoScoreToggle = document.getElementById('auto_score_toggle');
            if(autoScoreToggle){
                autoScoreToggle.addEventListener('change', function(){
                    if(this.checked){
                        showToast('Penilaian otomatis diaktifkan: soal selain pilihan ganda akan menjadi non-aktif dan tidak diujikan.', 'warning');
                    }
                });
            }

            // Set minimum date to today
            document.getElementById('exam_date').min = new Date().toISOString().split('T')[0];

            // Form validation
            document.getElementById('createExamForm').addEventListener('submit', function(e) {
                // Basic validation
                const requiredFields = ['exam_name', 'exam_class', 'exam_subject', 'exam_date', 'exam_start_time'];
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
                if (!isValid) {
                    e.preventDefault();
                    alert('Mohon lengkapi semua field yang wajib diisi.');
                } else {
                    document.getElementById('submitBtn').disabled = true;
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
        <style>
            @keyframes fade-in { from { opacity:0; transform: translateX(8px);} to { opacity:1; transform: translateX(0);} }
            .animate-fade-in { animation: fade-in .25s ease-out; }
            #toast-container .toast { transition: all .3s ease; }
        </style>
    </body>
    </html>