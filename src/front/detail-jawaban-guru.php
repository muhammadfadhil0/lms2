<?php
session_start();
$currentPage = 'ujian';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    header('Location: ../../login.php');
    exit();
}

require_once '../logic/ujian-logic.php';
require_once '../logic/time-helper.php';

$ujianLogic = new UjianLogic();
$guru_id = $_SESSION['user']['id'];
$ujian_id = isset($_GET['ujian_id']) ? (int)$_GET['ujian_id'] : 0;
$ujian_siswa_id = isset($_GET['ujian_siswa_id']) ? (int)$_GET['ujian_siswa_id'] : 0;

if ($ujian_id <= 0 || $ujian_siswa_id <= 0) {
    header('Location: hasil-ujian.php?ujian_id=' . $ujian_id);
    exit();
}

// Get detail jawaban data for guru
$detailData = $ujianLogic->getDetailJawabanGuru($ujian_id, $ujian_siswa_id, $guru_id);

if (!$detailData || isset($detailData['error'])) {
    // Debug: tampilkan error untuk troubleshooting
    if (isset($detailData['error'])) {
        echo "Error: " . $detailData['message'];
        echo "<br>Debug info: ujian_id=$ujian_id, ujian_siswa_id=$ujian_siswa_id, guru_id=$guru_id";
        exit();
    }
    header('Location: hasil-ujian.php?ujian_id=' . $ujian_id . '&error=data_not_found');
    exit();
}

$ujian = $detailData['ujian'];
$siswa = $detailData['siswa'];
$soalList = $detailData['soal_list'];
$hasilUjian = $detailData['hasil_ujian'];

function statusBadgeClass($status) {
    switch ($status) {
        case 'selesai':
            return 'bg-green-100 text-green-700 ring-1 ring-green-200';
        case 'sedang_mengerjakan':
            return 'bg-blue-100 text-blue-700 ring-1 ring-blue-200';
        default:
            return 'bg-gray-100 text-gray-700 ring-1 ring-gray-200';
    }
}

function getJawabanStatus($soal, $is_answered) {
    if (!$is_answered) {
        return ['class' => 'bg-gray-100 text-gray-700', 'text' => 'Tidak Dijawab'];
    }
    
    if ($soal['benar'] === null) {
        return ['class' => 'bg-blue-100 text-blue-700', 'text' => 'Belum Dikoreksi'];
    }
    
    if ($soal['benar'] == 1) {
        return ['class' => 'bg-green-100 text-green-700', 'text' => 'Benar'];
    }
    
    return ['class' => 'bg-red-100 text-red-700', 'text' => 'Salah'];
}
?>

<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Detail Jawaban Siswa - <?= htmlspecialchars($siswa['namaLengkap']) ?> - <?= htmlspecialchars($ujian['namaUjian']) ?></title>
</head>

<body class="bg-gray-50">

    <div data-main-content class="md:ml-64 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">
        <!-- Header -->
        <header class="bg-white p-4 md:p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="hasil-ujian.php?ujian_id=<?= $ujian_id ?>" class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-gray-100">
                        <i class="ti ti-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Detail Jawaban Siswa</h1>
                        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($siswa['namaLengkap']) ?> - <?= htmlspecialchars($ujian['namaUjian']) ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-xs px-3 py-1 rounded-full font-medium uppercase tracking-wide <?= statusBadgeClass($hasilUjian['status'] ?? 'belum') ?>">
                        <?= ucfirst(str_replace('_', ' ', $hasilUjian['status'] ?? 'belum')) ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Main Content (col-span-3) -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Identitas Siswa & Ujian -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="ti ti-user-check text-orange mr-2"></i>
                                Informasi Siswa & Ujian
                            </h2>

                            <div class="space-y-4">
                                <!-- Header Info Siswa -->
                                <div class="flex items-center space-x-3 pb-4">
                                    <div class="w-12 h-12 bg-orange text-white rounded-full flex items-center justify-center text-lg font-bold">
                                        <?php if (isset($siswa['fotoProfil']) && !empty($siswa['fotoProfil'])): 
                                            $photoPath = '';
                                            if (strpos($siswa['fotoProfil'], 'uploads/profile/') === 0) {
                                                $photoPath = '../../' . $siswa['fotoProfil'];
                                            } else {
                                                $photoPath = '../../uploads/profile/' . $siswa['fotoProfil'];
                                            }
                                            $fallbackInitial = strtoupper(substr($siswa['namaLengkap'], 0, 1));
                                        ?>
                                            <img src="<?= htmlspecialchars($photoPath) ?>" 
                                                 alt="Profile Photo" 
                                                 class="w-full h-full object-cover rounded-full"
                                                 onerror="this.parentElement.innerHTML='<div class=&quot;w-full h-full bg-orange text-white rounded-full flex items-center justify-center text-sm font-medium&quot;><?= $fallbackInitial ?></div>'">
                                        <?php else: ?>
                                            <?= strtoupper(substr($siswa['namaLengkap'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600">Nama Siswa</div>
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($siswa['namaLengkap']) ?></div>
                                    </div>
                                </div>

                                <!-- Analisis Ujian Siswa dengan Pingo -->
                                <div class="border border-gray-200 rounded-lg p-6">
                                        <div class="text-center space-y-4">
                                        <div class="flex align-center space-x-3 mb-4">
                                            <div class="w-10 text-orange rounded-full flex items-center justify-center">
                                                <i class="ti ti-sparkles text-2xl"></i>
                                            </div>
                                            <div class="text-left content-center">
                                                <h3 class="text-xl font-bold">Analisis Ujian Siswa dengan Pingo</h3>
                                                <p class="text-sm mt-1">Klik di bawah untuk mendapatkan analisis lengkap</p>
                                            </div>
                                        </div>                                        <!-- Statistik Default -->
                                        <div id="statistik-default" class="bg-white/60 rounded-lg p-4 mb-4">
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                                <div class="text-center border border-r border-l-0 border-t-0 border-b-0 border-gray-200">
                                                    <div class="text-lg font-bold text-blue-700"><?= count($soalList) ?></div>
                                                    <div class="text-xs text-blue-600">Total Soal</div>
                                                </div>
                                                <div class="text-center border border-r border-l-0 border-t-0 border-b-0 border-gray-200">
                                                    <div class="text-lg font-bold text-green-600"><?= (int)($hasilUjian['jumlahBenar'] ?? 0) ?></div>
                                                    <div class="text-xs text-green-600">Benar</div>
                                                </div>
                                                <div class="text-center border border-r border-l-0 border-t-0 border-b-0 border-gray-200">
                                                    <div class="text-lg font-bold text-red-600"><?= (int)($hasilUjian['jumlahSalah'] ?? 0) ?></div>
                                                    <div class="text-xs text-red-600">Salah</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-lg font-bold text-orange-600">
                                                        <?= $hasilUjian['totalNilai'] !== null ? number_format($hasilUjian['totalNilai'], 0) : '-' ?>
                                                    </div>
                                                    <div class="text-xs text-orange-600">Nilai</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Container Hasil AI (Hidden by default) -->
                                        <div id="hasil-ai" class="hidden mb-4">
                                            <!-- Hasil AI akan ditampilkan di sini -->
                                        </div>

                                        <button id="btn-analisis" onclick="evaluateWithAI()" class="w-full bg-orange text-white font-medium py-3 px-6 rounded-lg transition-colors flex items-center justify-center space-x-2">
                                            <i class="ti ti-sparkles"></i>
                                            <span>Mulai Analisis dengan AI</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detail Jawaban Siswa -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="ti ti-list-details text-orange mr-2"></i>
                                Detail Jawaban Siswa
                                <span class="ml-2 text-xs font-medium text-gray-500">(<?= count($soalList) ?> Soal)</span>
                            </h2>
                            
                            <?php if (empty($soalList)): ?>
                                <div class="p-4 border border-dashed rounded-lg text-sm text-gray-500 bg-gray-50">
                                    Tidak ada soal yang ditemukan.
                                </div>
                            <?php else: ?>
                                <div class="space-y-6">
                                    <?php foreach ($soalList as $soal): 
                                        $is_answered = !empty($soal['jawaban']) || !empty($soal['pilihanJawaban']);
                                        $jawaban_status = getJawabanStatus($soal, $is_answered);
                                    ?>
                                        <div class="border border-gray-200 rounded-lg p-4 bg-white">
                                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                                <div class="flex items-center space-x-2">
                                                    <span class="inline-flex items-center justify-center w-7 h-7 text-sm font-semibold rounded bg-orange text-white">
                                                        <?= (int)$soal['nomorSoal'] ?>
                                                    </span>
                                                    <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-600 capitalize">
                                                        <?= htmlspecialchars(str_replace('_', ' ', $soal['tipeSoal'])) ?>
                                                    </span>
                                                    <span class="text-xs px-2 py-1 rounded <?= $jawaban_status['class'] ?>">
                                                        <?= $jawaban_status['text'] ?>
                                                    </span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <?php if ($soal['poin_jawaban'] !== null): ?>
                                                        <span class="text-xs px-2 py-1 rounded bg-blue-50 border border-blue-200 text-blue-700">
                                                            Nilai: <?= number_format($soal['poin_jawaban'], 1) ?>/<?= (int)$soal['poin'] ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-xs px-2 py-1 rounded bg-gray-50 border border-gray-200 text-gray-700">
                                                            Poin: <?= (int)$soal['poin'] ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Pertanyaan -->
                                            <div class="mb-4">
                                                <h4 class="font-medium text-gray-800 mb-2">Pertanyaan:</h4>
                                                <div class="text-gray-700 leading-relaxed">
                                                    <?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?>
                                                </div>
                                            </div>

                                            <?php if ($soal['tipeSoal'] === 'pilihan_ganda'): ?>
                                                <!-- Pilihan Ganda -->
                                                <div class="mb-4">
                                                    <h4 class="font-medium text-gray-800 mb-2">Pilihan Jawaban:</h4>
                                                    <ul class="space-y-2">
                                                        <?php if (!empty($soal['pilihan_array'])): foreach ($soal['pilihan_array'] as $opsi => $det): 
                                                            $is_student_choice = ($soal['pilihanJawaban'] === $opsi);
                                                            $is_correct = $det['benar'];
                                                            $choice_class = '';
                                                            
                                                            if ($is_correct) {
                                                                $choice_class = 'bg-green-50 border-green-200 text-green-800';
                                                            } elseif ($is_student_choice && !$is_correct) {
                                                                $choice_class = 'bg-red-50 border-red-200 text-red-800';
                                                            } else {
                                                                $choice_class = 'bg-gray-50 border-gray-200 text-gray-700';
                                                            }
                                                        ?>
                                                            <li class="flex items-start p-3 rounded border <?= $choice_class ?>">
                                                                <span class="inline-block w-6 font-medium"><?= htmlspecialchars($opsi) ?>.</span>
                                                                <span class="flex-1"><?= htmlspecialchars($det['teks']) ?></span>
                                                                <div class="flex items-center space-x-2 ml-2">
                                                                    <?php if ($is_correct): ?>
                                                                        <span class="text-[10px] px-2 py-1 rounded bg-green-100 text-green-700 font-medium">
                                                                            <i class="ti ti-check text-[10px] mr-1"></i>Kunci
                                                                        </span>
                                                                    <?php endif; ?>
                                                                    <?php if ($is_student_choice): ?>
                                                                        <span class="text-[10px] px-2 py-1 rounded bg-blue-100 text-blue-700 font-medium">
                                                                            <i class="ti ti-user text-[10px] mr-1"></i>Pilihan Siswa
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </li>
                                                        <?php endforeach; endif; ?>
                                                    </ul>
                                                </div>
                                            <?php else: ?>
                                                <!-- Essay/Isian -->
                                                <div class="mb-4">
                                                    <h4 class="font-medium text-gray-800 mb-2">Jawaban Siswa:</h4>
                                                    <div class="p-3 bg-gray-50 border border-gray-200 rounded text-sm text-gray-700">
                                                        <?php if ($is_answered): ?>
                                                            <?= nl2br(htmlspecialchars($soal['jawaban'])) ?>
                                                        <?php else: ?>
                                                            <em class="text-gray-500">Tidak dijawab</em>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <?php if (!empty($soal['kunciJawaban'])): ?>
                                                    <div class="mb-4">
                                                        <h4 class="font-medium text-gray-800 mb-2">Kunci Jawaban:</h4>
                                                        <div class="p-3 bg-green-50 border border-green-200 rounded text-sm text-green-800">
                                                            <?= nl2br(htmlspecialchars($soal['kunciJawaban'])) ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sidebar (col-span-1) -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sticky top-4">
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Ringkasan Hasil</h3>
                            
                            <!-- Skor Total -->
                            <div class="mb-6 p-4 bg-gradient-to-r from-orange-50 to-orange-100 border border-orange-200 rounded-lg">
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-orange-600 mb-1">
                                        <?= $hasilUjian['totalNilai'] !== null ? number_format($hasilUjian['totalNilai'], 1) : '-' ?>
                                    </div>
                                    <div class="text-sm text-orange-700">Nilai Total</div>
                                </div>
                            </div>

                            <!-- Statistik -->
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Soal</span>
                                    <span class="font-medium"><?= count($soalList) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Jawaban Benar</span>
                                    <span class="font-medium text-green-600"><?= (int)($hasilUjian['jumlahBenar'] ?? 0) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Jawaban Salah</span>
                                    <span class="font-medium text-red-600"><?= (int)($hasilUjian['jumlahSalah'] ?? 0) ?></span>
                                </div>
                                <?php 
                                $tidak_dijawab = count($soalList) - (int)($hasilUjian['jumlahBenar'] ?? 0) - (int)($hasilUjian['jumlahSalah'] ?? 0);
                                if ($tidak_dijawab > 0): 
                                ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Tidak Dijawab</span>
                                        <span class="font-medium text-gray-500"><?= $tidak_dijawab ?></span>
                                    </div>
                                <?php endif; ?>
                                <hr class="my-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Status Koreksi</span>
                                    <span class="font-medium" id="correction-status">
                                        <?php
                                        $belumDikoreksi = 0;
                                        foreach ($soalList as $soal) {
                                            if ($soal['benar'] === null && $soal['tipeSoal'] !== 'pilihan_ganda') {
                                                $belumDikoreksi++;
                                            }
                                        }
                                        echo $belumDikoreksi > 0 ? "Belum Selesai" : "Sudah Selesai";
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-6 space-y-3">
                                <button onclick="calculateFinalScore()" class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="ti ti-calculator"></i>
                                    <span>Hitung Nilai Akhir</span>
                                </button>
                                
                                <a href="hasil-ujian.php?ujian_id=<?= $ujian_id ?>" class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                    <i class="ti ti-arrow-left"></i>
                                    <span>Kembali ke Hasil Ujian</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../script/menu-bar-script.js"></script>
    <script>
        // Data untuk evaluasi AI
        const evaluationData = {
            ujian_id: <?= $ujian_id ?>,
            ujian_siswa_id: <?= $ujian_siswa_id ?>,
            guru_id: <?= $_SESSION['user']['id'] ?>
        };

        /**
         * Fungsi untuk memulai evaluasi AI
         */
        function evaluateWithAI() {
            const statistikDefault = document.getElementById('statistik-default');
            const hasilAI = document.getElementById('hasil-ai');
            const btnAnalisis = document.getElementById('btn-analisis');
            
            // Sembunyikan statistik default
            statistikDefault.classList.add('hidden');
            
            // Tampilkan container hasil AI dengan loading
            hasilAI.classList.remove('hidden');
            hasilAI.innerHTML = `
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex items-center justify-center py-8">
                        <div class="flex items-center space-x-3">
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                            <span class="text-blue-600 font-medium">AI sedang menganalisis hasil ujian...</span>
                        </div>
                    </div>
                </div>
            `;
            
            // Ubah tombol menjadi disabled
            btnAnalisis.disabled = true;
            btnAnalisis.innerHTML = `
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                <span>Menganalisis...</span>
            `;
            
            // Kirim request ke API
            fetch('../api/ai-evaluation-simple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(evaluationData)
            })
            .then(response => {
                // Log response status for debugging
                console.log('Response status:', response.status);
                
                // Get response text first
                return response.text().then(text => {
                    console.log('Response text:', text);
                    
                    // Try to parse as JSON
                    try {
                        const data = JSON.parse(text);
                        return { ok: response.ok, data: data };
                    } catch (e) {
                        // If not JSON, return the raw text as error
                        throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
                    }
                });
            })
            .then(result => {
                if (result.ok && result.data.success) {
                    displayEvaluation(result.data.evaluation);
                } else {
                    const errorMsg = result.data.error || 'Terjadi kesalahan saat evaluasi';
                    const debugInfo = result.data.debug ? ` (${result.data.debug.file}:${result.data.debug.line})` : '';
                    displayError(errorMsg + debugInfo);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayError('Error: ' + error.message);
            });
        }

        /**
         * Fungsi untuk menampilkan hasil evaluasi
         */
        function displayEvaluation(evaluation) {
            const hasilAI = document.getElementById('hasil-ai');
            const btnAnalisis = document.getElementById('btn-analisis');
            
            hasilAI.innerHTML = `
                <div class="space-y-4">
                    <!-- Deskripsi -->
                    <div class="border border-gray-300 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-2 flex items-center text-sm">
                            <i class="ti ti-file-description mr-2"></i>
                            Deskripsi Evaluasi
                        </h4>
                        <p class="text-gray-700 leading-relaxed text-left text-sm">${evaluation.deskripsi}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Materi Perlu Evaluasi -->
                        <div class="border border-gray-300 rounded-lg p-4">
                            <h4 class="font-semibold text-gray-800 mb-2 flex items-center text-sm">
                                <i class="ti ti-alert-triangle mr-2"></i>
                                Perlu Dievaluasi (Jawaban Salah)
                            </h4>
                            <div class="mb-2 text-left">
                                <span class="text-xs text-gray-600">
                                    ${evaluation.materi_perlu_evaluasi.jumlah_salah} dari ${evaluation.materi_perlu_evaluasi.total_soal} soal
                                </span>
                            </div>
                            <ul class="space-y-1">
                                ${evaluation.materi_perlu_evaluasi.topik.map(topik => `
                                    <li class="flex items-start space-x-2">
                                        <i class="ti ti-point text-gray-500 text-xs mt-1"></i>
                                        <span class="text-gray-700 text-xs leading-relaxed text-left">${topik}</span>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>

                        <!-- Materi Sudah Dikuasai -->
                        <div class="border border-gray-300 rounded-lg p-4">
                            <h4 class="font-semibold text-gray-800 mb-2 flex items-center text-sm">
                                <i class="ti ti-circle-check mr-2"></i>
                                Sudah Dikuasai
                            </h4>
                            <div class="mb-2 text-left">
                                <span class="text-xs text-gray-600">
                                    ${evaluation.materi_sudah_dikuasai.jumlah_benar} dari ${evaluation.materi_sudah_dikuasai.total_soal} soal
                                </span>
                            </div>
                            <ul class="space-y-1">
                                ${evaluation.materi_sudah_dikuasai.topik.map(topik => `
                                    <li class="flex items-start space-x-2">
                                        <i class="ti ti-check text-gray-500 text-xs mt-1"></i>
                                        <span class="text-gray-700 text-xs leading-relaxed text-left">${topik}</span>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>

                    <!-- Saran Pembelajaran -->
                    <div class="border border-gray-300 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-2 flex items-center text-sm">
                            <i class="ti ti-bulb mr-2"></i>
                            Saran Pembelajaran
                        </h4>
                        <ul class="space-y-2">
                            ${evaluation.saran_pembelajaran.map((saran, index) => `
                                <li class="flex items-start space-x-2">
                                    <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold rounded-full border border-gray-300 text-gray-700 mt-0.5">
                                        ${index + 1}
                                    </span>
                                    <span class="text-gray-700 text-xs leading-relaxed text-left">${saran}</span>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                </div>
            `;
            
            // Ubah tombol menjadi "Analisis Ulang"
            btnAnalisis.disabled = false;
            btnAnalisis.innerHTML = `
                <i class="ti ti-refresh"></i>
                <span>Analisis Ulang</span>
            `;
        }

        /**
         * Fungsi untuk menampilkan error
         */
        function displayError(errorMessage) {
            const hasilAI = document.getElementById('hasil-ai');
            const btnAnalisis = document.getElementById('btn-analisis');
            const statistikDefault = document.getElementById('statistik-default');
            
            hasilAI.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <i class="ti ti-alert-circle text-red-500 text-lg"></i>
                        <div>
                            <h4 class="font-semibold text-red-800 text-sm">Gagal Melakukan Evaluasi</h4>
                            <p class="text-red-600 text-xs mt-1">${errorMessage}</p>
                        </div>
                    </div>
                </div>
            `;
            
            // Kembalikan tombol ke keadaan semula
            btnAnalisis.disabled = false;
            btnAnalisis.innerHTML = `
                <i class="ti ti-sparkles"></i>
                <span>Mulai Analisis dengan AI</span>
            `;
            
            // Tampilkan kembali statistik default setelah 3 detik
            setTimeout(() => {
                hasilAI.classList.add('hidden');
                statistikDefault.classList.remove('hidden');
            }, 3000);
        }
    </script>

</body>

</html>