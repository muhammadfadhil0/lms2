<!-- cek sekarang ada di halaman apa -->
<?php
session_start();
$currentPage = 'buat-ujian';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    header("Location: ../../login.php");
    exit();
}
require_once '../logic/kelas-logic.php';
require_once '../logic/ujian-logic.php';
require_once '../logic/time-helper.php';
$kelasLogic = new KelasLogic();
$kelasGuru = $kelasLogic->getKelasByGuru($_SESSION['user']['id']);
$errors = $_SESSION['flash_errors'] ?? [];
$old = $_SESSION['old_exam_form'] ?? [];
unset($_SESSION['flash_errors'], $_SESSION['old_exam_form']);

// Edit mode detection
$editing = false;
$ujian_id = isset($_GET['ujian_id']) ? (int) $_GET['ujian_id'] : 0;
$ujianData = null;
if ($ujian_id > 0) {
    $ujianLogic = new UjianLogic();
    $ujianData = $ujianLogic->getUjianByIdAndGuru($ujian_id, $_SESSION['user']['id']);
    if ($ujianData) {
        $editing = true;
        if (empty($old)) { // only prefill if not coming back from validation error
            // Sekarang topik sudah terpisah di database, tidak perlu parsing
            $topic = $ujianData['topik'] ?? '';
            $desc = $ujianData['deskripsi'] ?? '';
            
            $old = [
                'exam_name' => $ujianData['namaUjian'] ?? '',
                'exam_description' => $desc,
                'exam_topic' => $topic,
                'exam_class' => $ujianData['kelas_id'] ?? '',
                'exam_date' => $ujianData['tanggalUjian'] ?? '',
                'exam_end_date' => $ujianData['tanggalAkhir'] ?? $ujianData['tanggalUjian'] ?? '',
                'exam_start_time' => isset($ujianData['waktuMulai']) ? TimeHelper::format24Hour($ujianData['waktuMulai']) : '',
                'exam_end_time' => isset($ujianData['waktuSelesai']) ? TimeHelper::format24Hour($ujianData['waktuSelesai']) : '',
                'exam_duration' => $ujianData['durasi'] ?? '',
            ];
            if (isset($ujianData['shuffleQuestions']) && $ujianData['shuffleQuestions']) {
                $old['shuffle_questions'] = 1;
            }
            if (!isset($ujianData['showScore']) || $ujianData['showScore']) {
                $old['show_score'] = 1;
            }
            if (isset($ujianData['autoScore']) && $ujianData['autoScore']) {
                $old['auto_score'] = 1;
            }
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
                    <button onclick="history.back()"
                        class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-gray-100">
                        <i class="ti ti-arrow-left text-xl"></i>
                    </button>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                            <?= $editing ? 'Edit Ujian' : 'Buat Ujian Baru' ?></h1>
                        <p class="text-sm text-gray-600 mt-1">
                            <?= $editing ? 'Perbarui identitas dan detail ujian' : 'Atur identitas dan detail ujian' ?>
                        </p>
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
                            <div
                                class="w-8 h-8 bg-orange text-white rounded-full flex items-center justify-center text-sm font-medium">
                                1
                            </div>
                            <span class="text-sm font-medium text-orange">Identitas Ujian</span>
                        </div>
                        <div class="flex-1 h-px bg-gray-300 mx-4"></div>
                        <div class="flex items-center space-x-2">
                            <div
                                class="w-8 h-8 bg-gray-300 text-gray-500 rounded-full flex items-center justify-center text-sm font-medium">
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
                                <input type="hidden" name="ujian_id" value="<?= (int) $ujian_id ?>">
                            <?php endif; ?>
                            <?php if (!empty($errors)): ?>
                                <div class="mb-6 p-4 rounded-lg border border-red-200 bg-red-50 text-sm text-red-700">
                                    <ul class="list-disc ml-4 space-y-1">
                                        <?php foreach ($errors as $e): ?>
                                            <li><?= htmlspecialchars($e) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <!-- Nama Ujian -->
                            <div class="mb-6">
                                <label for="exam_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nama Ujian <span class="text-red-500">*</span>
                                    <span
                                        class="ml-2 inline-flex items-center text-[10px] font-medium px-2 py-1 rounded bg-orange-100 text-orange-700 border border-orange-200">
                                        <i class="ti ti-sparkles mr-1 text-[12px]"></i>Pingo AI mengakses data ini
                                    </span>
                                </label>
                                <input type="text" id="exam_name" name="exam_name" required
                                    value="<?= htmlspecialchars($old['exam_name'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors"
                                    placeholder="Contoh: Ujian Tengah Semester Matematika">
                                <p class="text-xs text-gray-500 mt-1">Berikan nama yang jelas dan mudah diidentifikasi
                                </p>
                            </div>
                            <!-- Deskripsi Ujian -->
                            <div class="mb-6">
                                <label for="exam_description" class="block text-sm font-medium text-gray-700 mb-2">
                                    Deskripsi Ujian
                                </label>
                                <textarea id="exam_description" name="exam_description" rows="4"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors resize-none"
                                    placeholder="Jelaskan tujuan ujian, materi yang akan diuji, atau instruksi khusus untuk siswa..."><?= htmlspecialchars($old['exam_description'] ?? '') ?></textarea>
                                <p class="text-xs text-gray-500 mt-1">Opsional: Berikan deskripsi untuk membantu siswa
                                    memahami ujian</p>
                            </div>

                            <!-- Pilih Kelas -->
                            <div class="mb-6">
                                <label for="exam_class" class="block text-sm font-medium text-gray-700 mb-2">
                                    Kelas <span class="text-red-500">*</span>
                                    <span
                                        class="ml-2 inline-flex items-center text-[10px] font-medium px-2 py-1 rounded bg-orange-100 text-orange-700 border border-orange-200">
                                        <i class="ti ti-sparkles mr-1 text-[12px]"></i>Pingo AI mengakses data ini
                                    </span>
                                </label>
                                
                                <?php if (empty($kelasGuru)): ?>
                                    <!-- Pesan jika tidak ada kelas -->
                                    <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                                        <div class="text-center">
                                            <i class="ti ti-school text-4xl text-gray-400 mb-2"></i>
                                            <h3 class="text-sm font-medium text-gray-900 mb-1">Belum Ada Kelas</h3>
                                            <p class="text-xs text-gray-500 mb-3">Anda perlu membuat kelas terlebih dahulu sebelum dapat membuat ujian</p>
                                            <button type="button" onclick="document.getElementById('add-class-modal').showModal()"
                                                class="inline-flex items-center px-3 py-2 text-xs font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700 transition-colors">
                                                <i class="ti ti-plus mr-1"></i>
                                                Buat Kelas Baru
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <select id="exam_class" name="exam_class" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors bg-white">
                                        <option value="">Pilih Kelas</option>
                                        <?php foreach ($kelasGuru as $k): ?>
                                            <option value="<?= (int) $k['id'] ?>" <?= (isset($old['exam_class']) && (int) $old['exam_class'] == (int) $k['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($k['namaKelas']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                                
                                <p class="text-xs text-gray-500 mt-1">Pilih kelas yang akan mengikuti ujian ini.</p>
                            </div>

                            <!-- Materi/Topik -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Materi/Topik <span class="text-gray-400 text-xs font-normal">(Opsional)</span>
                                    <span
                                        class="ml-2 inline-flex items-center text-[10px] font-medium px-2 py-1 rounded bg-orange-100 text-orange-700 border border-orange-200">
                                        <i class="ti ti-sparkles mr-1 text-[12px]"></i>Pingo AI mengakses data ini
                                    </span>
                                </label>

                                <div id="topicContainer" class="space-y-3">
                                    <!-- Topic input pertama -->
                                    <div class="topic-input-group">
                                        <input type="text" name="exam_topics[]"
                                            value="<?= htmlspecialchars($old['exam_topic'] ?? '') ?>"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors"
                                            placeholder="Contoh: Aljabar, Persamaan Linear">
                                    </div>
                                </div>

                                <!-- Tombol tambah topik -->
                                <button type="button" id="addTopicBtn"
                                    class="mt-3 inline-flex items-center px-3 py-2 text-sm border border-orange-300 text-orange-600 rounded-lg hover:bg-orange-50 transition-colors">
                                    <i class="ti ti-plus mr-2"></i>
                                    Tambah Topik
                                </button>

                                <p class="text-xs text-gray-500 mt-2">Sebutkan topik atau bab yang akan diujikan
                                    (maksimal 10 topik)</p>
                            </div> <!-- Pengaturan Waktu -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-800 mb-4">Pengaturan Waktu</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Tanggal Mulai Ujian -->
                                    <div>
                                        <label for="exam_start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                            Tanggal Mulai Ujian <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" id="exam_start_date" name="exam_start_date" required
                                            value="<?= htmlspecialchars($old['exam_date'] ?? '') ?>"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                    </div>

                                    <!-- Waktu Mulai -->
                                    <div>
                                        <label for="exam_start_time"
                                            class="block text-sm font-medium text-gray-700 mb-2">
                                            Waktu Mulai Ujian <span class="text-red-500">*</span>
                                        </label>
                                        <input type="time" id="exam_start_time" name="exam_start_time" required
                                            value="<?= htmlspecialchars($old['exam_start_time'] ?? '') ?>"
                                            step="60" data-format="24"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                    </div>

                                    <!-- Tanggal Akhir Ujian -->
                                    <div>
                                        <label for="exam_end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                            Tanggal Akhir Ujian <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" id="exam_end_date" name="exam_end_date" required
                                            value="<?= htmlspecialchars($old['exam_end_date'] ?? $old['exam_date'] ?? '') ?>"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                    </div>

                                    <!-- Waktu Selesai -->
                                    <div>
                                        <label for="exam_end_time"
                                            class="block text-sm font-medium text-gray-700 mb-2">
                                            Waktu Selesai Ujian <span class="text-red-500">*</span>
                                        </label>
                                        <input type="time" id="exam_end_time" name="exam_end_time" required
                                            value="<?= htmlspecialchars($old['exam_end_time'] ?? '') ?>"
                                            step="60" data-format="24"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                    </div>

                                    <!-- Durasi Ujian (Otomatis dihitung) -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Durasi Ujian
                                        </label>
                                        <input type="text" id="calculated_duration" readonly
                                            class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-500"
                                            placeholder="Akan dihitung otomatis berdasarkan rentang waktu">
                                        <input type="hidden" id="exam_duration" name="exam_duration" value="">
                                        <p class="text-xs text-gray-500 mt-1">Dihitung otomatis berdasarkan tanggal dan waktu mulai/selesai</p>
                                    </div>
                                    
                                    <!-- Info Rentang Waktu -->
                                    <div class="md:col-span-2 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                        <div class="flex items-start space-x-3">
                                            <i class="ti ti-info-circle text-blue-600 text-lg mt-0.5"></i>
                                            <div>
                                                <h4 class="text-sm font-medium text-blue-800 mb-1">Sistem Waktu Baru</h4>
                                                <p class="text-xs text-blue-700">
                                                    Sekarang Anda dapat mengatur ujian dengan rentang waktu yang lebih fleksibel. 
                                                    Ujian dapat dimulai di hari tertentu dan berakhir di hari yang berbeda.
                                                    Durasi akan dihitung otomatis berdasarkan rentang waktu yang Anda tentukan.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Legacy support - hidden field untuk backward compatibility -->
                                <input type="hidden" id="exam_date" name="exam_date" value="">
                                
                                <script>
                                // Auto-calculate duration and set hidden fields when time changes
                                function updateCalculations() {
                                    const startDate = document.getElementById('exam_start_date').value;
                                    const startTime = document.getElementById('exam_start_time').value;
                                    const endDate = document.getElementById('exam_end_date').value;
                                    const endTime = document.getElementById('exam_end_time').value;
                                    
                                    // Set legacy exam_date to start date for backward compatibility
                                    document.getElementById('exam_date').value = startDate;
                                    
                                    if (startDate && startTime && endDate && endTime) {
                                        const start = new Date(startDate + 'T' + startTime);
                                        const end = new Date(endDate + 'T' + endTime);
                                        
                                        if (end > start) {
                                            const diffMs = end - start;
                                            const diffMins = Math.round(diffMs / (1000 * 60));
                                            const hours = Math.floor(diffMins / 60);
                                            const minutes = diffMins % 60;
                                            
                                            let durationText = '';
                                            if (hours > 0) {
                                                durationText += hours + ' jam';
                                                if (minutes > 0) durationText += ' ' + minutes + ' menit';
                                            } else {
                                                durationText = minutes + ' menit';
                                            }
                                            
                                            document.getElementById('calculated_duration').value = durationText;
                                            document.getElementById('exam_duration').value = diffMins;
                                        } else {
                                            document.getElementById('calculated_duration').value = 'Waktu selesai harus setelah waktu mulai';
                                            document.getElementById('exam_duration').value = '';
                                        }
                                    }
                                }
                                
                                // Attach event listeners
                                ['exam_start_date', 'exam_start_time', 'exam_end_date', 'exam_end_time'].forEach(id => {
                                    document.getElementById(id)?.addEventListener('change', updateCalculations);
                                });
                                
                                // Initialize on load
                                document.addEventListener('DOMContentLoaded', updateCalculations);
                                </script>
                                
                                <!-- Add 24-hour format script -->
                                <?= TimeHelper::getJS24HourScript() ?>
                            </div>



                            <!-- Pengaturan Tambahan -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-800 mb-4">Pengaturan Tambahan</h3>

                                <div class="space-y-4">
                                    <!-- Acak Soal -->
                                    <div
                                        class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                        <div>
                                            <h4 class="font-medium text-gray-800">Acak Urutan Soal</h4>
                                            <p class="text-sm text-gray-600">Soal akan ditampilkan dalam urutan acak
                                                untuk setiap siswa</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="shuffle_questions" class="sr-only peer"
                                                <?= isset($old['shuffle_questions']) ? 'checked' : '' ?>>
                                            <div
                                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600">
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Lihat Hasil -->
                                    <div
                                        class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                        <div>
                                            <h4 class="font-medium text-gray-800">Siswa Dapat Melihat Hasil</h4>
                                            <p class="text-sm text-gray-600">Siswa dapat melihat nilai dan pembahasan
                                                setelah ujian selesai</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="show_score" class="sr-only peer"
                                                <?= isset($old['show_score']) ? 'checked' : 'checked' ?>>
                                            <div
                                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600">
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Auto Score -->
                                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg"
                                        id="autoScoreWrapper">
                                        <div>
                                            <h4 class="font-medium text-gray-800">Hitung Nilai Otomatis</h4>
                                            <p class="text-sm text-gray-600">Hanya pilihan ganda, total nilai akan
                                                diskalakan ke 100</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="auto_score" id="auto_score_toggle"
                                                class="sr-only peer" <?= isset($old['auto_score']) ? 'checked' : '' ?>>
                                            <div
                                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer transition-colors peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600">
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-4 pt-6 justify-between border-t border-gray-200">
                                <button type="button" onclick="history.back()"
                                    class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                                    Batal
                                </button>
                                <button type="button" id="saveAsDraft"
                                    class="hidden px-6 py-3 border border-orange text-orange rounded-lg hover:bg-orange-50 transition-colors font-medium">
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
    <script src="../script/kelas-management.js"></script>
    <script>
        function showToast(message, type = 'info') {
            const colors = {
                success: 'bg-green-600',
                error: 'bg-red-600',
                info: 'bg-blue-600',
                warning: 'bg-yellow-600 text-gray-900'
            };
            const c = document.getElementById('toast-container');
            if (!c) return alert(message);
            const el = document.createElement('div');
            el.className = `toast flex items-start text-sm text-white px-4 py-3 rounded-lg shadow-lg backdrop-blur-md bg-opacity-90 ${colors[type] || colors.info} animate-fade-in`;
            el.innerHTML = `<div class='mr-3 mt-0.5'><i class=\"ti ${type === 'success' ? 'ti-check' : type === 'error' ? 'ti-alert-circle' : type === 'warning' ? 'ti-alert-triangle' : 'ti-info-circle'}\"></i></div><div class='flex-1'>${message}</div><button class='ml-3 text-white/80 hover:text-white' onclick='this.parentElement.remove()'><i class=\"ti ti-x\"></i></button>`;
            c.appendChild(el);
            setTimeout(() => {
                el.classList.add('opacity-0', 'translate-x-2');
                setTimeout(() => el.remove(), 300);
            }, 4000);
        }
        (function () {
            const p = new URLSearchParams(location.search);
            if (p.get('duplicated') === '1') {
                showToast('Salinan ujian berhasil dibuat. Lakukan penyesuaian lalu lanjut ke pembuatan soal.', 'success');
                const url = new URL(location.href);
                url.searchParams.delete('duplicated');
                history.replaceState({}, '', url);
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
        (function () {
            const startVal = document.getElementById('exam_start_time').value;
            const durVal = document.getElementById('exam_duration').value;
            if (startVal && durVal) {
                calculateEndTime();
            }
        })();

        // Warn user when enabling auto score (non-MC questions will be inactive)
        const autoScoreToggle = document.getElementById('auto_score_toggle');
        if (autoScoreToggle) {
            autoScoreToggle.addEventListener('change', function () {
                if (this.checked) {
                    showToast('Penilaian otomatis diaktifkan: soal selain pilihan ganda akan menjadi non-aktif dan tidak diujikan.', 'warning');
                }
            });
        }

        // Set minimum date to today
        document.getElementById('exam_date').min = new Date().toISOString().split('T')[0];

        // Form validation
        document.getElementById('createExamForm').addEventListener('submit', function (e) {
            // Check if there are classes available first
            const examClassSelect = document.getElementById('exam_class');
            if (!examClassSelect) {
                e.preventDefault();
                alert('Tidak dapat membuat ujian karena belum ada kelas. Silakan buat kelas terlebih dahulu.');
                return;
            }

            // Basic validation
            const requiredFields = ['exam_name', 'exam_class', 'exam_date', 'exam_start_time'];
            let isValid = true;
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (input && !input.value.trim()) {
                    input.classList.add('border-red-500');
                    isValid = false;
                } else if (input) {
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

        // Dynamic topic inputs management
        let topicCount = 1;
        const maxTopics = 10;

        // Initialize existing topics (for edit mode)
        (function initializeTopics() {
            const existingTopic = document.querySelector('input[name="exam_topics[]"]').value.trim();
            if (existingTopic) {
                // If there's existing topic data, we might want to split it by comma or handle it
                // For now, we'll keep the single input with the existing value
            }
        })();

        document.getElementById('addTopicBtn').addEventListener('click', function () {
            if (topicCount >= maxTopics) {
                showToast('Maksimal 10 topik yang dapat ditambahkan', 'warning');
                return;
            }

            topicCount++;
            const container = document.getElementById('topicContainer');

            // Create new topic input group
            const newTopicGroup = document.createElement('div');
            newTopicGroup.className = 'topic-input-group flex items-center space-x-2';
            newTopicGroup.innerHTML = `
                    <input type="text" name="exam_topics[]" 
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors"
                        placeholder="Topik tambahan...">
                    <button type="button" class="remove-topic-btn p-3 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                        title="Hapus topik">
                        <i class="ti ti-trash text-lg"></i>
                    </button>
                `;

            container.appendChild(newTopicGroup);

            // Add remove functionality
            newTopicGroup.querySelector('.remove-topic-btn').addEventListener('click', function () {
                newTopicGroup.remove();
                topicCount--;
                updateAddButton();
            });

            updateAddButton();

            // Focus on new input
            newTopicGroup.querySelector('input').focus();
        });

        function updateAddButton() {
            const addBtn = document.getElementById('addTopicBtn');
            if (topicCount >= maxTopics) {
                addBtn.style.display = 'none';
            } else {
                addBtn.style.display = 'inline-flex';
            }
        }

        // Save as draft functionality
        document.getElementById('saveAsDraft').addEventListener('click', function () {
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
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateX(8px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-fade-in {
            animation: fade-in .25s ease-out;
        }

        #toast-container .toast {
            transition: all .3s ease;
        }

        .topic-input-group {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .remove-topic-btn:hover {
            transform: scale(1.05);
        }
    </style>
    
    <!-- Dynamic Modal Component -->
    <?php require '../component/modal-dynamic.php'; ?>
</body>

</html>