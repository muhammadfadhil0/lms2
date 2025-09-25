<?php
// Minimalist exam page
session_start();

$currentPage = 'ujian';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'siswa') {
    header('Location: ../../login.php');
    exit();
}

require_once '../logic/ujian-logic.php';
require_once '../logic/soal-logic.php';
require_once '../logic/time-helper.php';

$ujianLogic = new UjianLogic();
$soalLogic = new SoalLogic();
$siswa_id = $_SESSION['user']['id'];

// AJAX HANDLERS FIRST - NO OUTPUT BEFORE THIS POINT
// Submit answer (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_answer') {
    // Clear any previous output
    if (ob_get_level()) ob_end_clean();

    $ujian_siswa_id = (int)$_POST['ujian_siswa_id'];
    $soal_id = (int)$_POST['soal_id'];
    $jawaban = $_POST['jawaban'] ?? '';
    $save_result = $ujianLogic->simpanJawaban($ujian_siswa_id, $soal_id, $jawaban);

    if (isset($_POST['finish_exam'])) {
        $finish_result = $ujianLogic->selesaiUjian($ujian_siswa_id);

        // Log for debugging
        error_log("Finish exam result: " . json_encode($finish_result));

        // Ensure clean JSON response
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);

        $response = [
            'success' => $finish_result['success'],
            'message' => $finish_result['message'],
            'finished' => true,
            'ujian_siswa_id' => $ujian_siswa_id
        ];

        $json_output = json_encode($response, JSON_UNESCAPED_UNICODE);

        // Check for JSON encoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON encoding error: " . json_last_error_msg());
            $json_output = json_encode(['success' => false, 'message' => 'JSON encoding error']);
        }

        echo $json_output;
        exit();
    }

    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    exit();
}

// Alternative: Direct form submit for finish exam (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'finish_direct') {
    $ujian_siswa_id = (int)$_POST['ujian_siswa_id'];
    $finish_result = $ujianLogic->selesaiUjian($ujian_siswa_id);

    if ($finish_result['success']) {
        header('Location: ujian-user.php?finished=1&direct=1');
    } else {
        header('Location: ujian-user.php?error=finish_failed&message=' . urlencode($finish_result['message']));
    }
    exit();
}

// Start output buffering for HTML content
ob_start();

// Parameters
$ujian_id = isset($_GET['ujian_id']) ? (int)$_GET['ujian_id'] : 0;
$ujian_siswa_id = isset($_GET['us_id']) ? (int)$_GET['us_id'] : 0;

if (!$ujian_id && !$ujian_siswa_id) {
    header('Location: ujian-user.php');
    exit();
}

if ($ujian_siswa_id) {
    $ujian_siswa = $ujianLogic->getUjianSiswaById($ujian_siswa_id);
    if ($ujian_siswa && $ujian_siswa['siswa_id'] == $siswa_id) {
        $ujian_id = $ujian_siswa['ujian_id'];
    } else {
        header('Location: ujian-user.php');
        exit();
    }
} else if ($ujian_id) {
    // Jika akses langsung via ujian_id, cek apakah siswa sudah pernah mengerjakan ujian ini
    $existing_record = $ujianLogic->getUjianSiswaByUjianIdAndSiswaId($ujian_id, $siswa_id);
    if ($existing_record && $existing_record['status'] === 'selesai') {
        header('Location: ujian-user.php?error=ujian_sudah_selesai');
        exit();
    }
}

// Start exam action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start') {
    $start_result = $ujianLogic->mulaiUjian($ujian_id, $siswa_id);
    if ($start_result['success']) {
        header('Location: kerjakan-ujian.php?us_id=' . $start_result['ujian_siswa_id']);
        exit();
    } else {
        $error_message = $start_result['message'];
    }
}

$ujian = null;
$soal_list = [];
$ujian_siswa = null;
$is_started = false;
if ($ujian_siswa_id) {
    $ujian_siswa = $ujianLogic->getUjianSiswaById($ujian_siswa_id);
    if ($ujian_siswa && (int)$ujian_siswa['siswa_id'] === (int)$siswa_id) {
        $ujian_id = (int)$ujian_siswa['ujian_id'];

        // Cek apakah ujian sudah selesai - jika sudah, redirect dengan pesan
        if ($ujian_siswa['status'] === 'selesai') {
            header('Location: ujian-user.php?error=ujian_sudah_selesai');
            exit();
        }

        $is_started = ($ujian_siswa['status'] === 'sedang_mengerjakan');
    } else {
        header('Location: ujian-user.php');
        exit();
    }
}
if ($ujian_id) {
    $ujian = $ujianLogic->getUjianById($ujian_id);
}
if (!$ujian) {
    header('Location: ujian-user.php');
    exit();
}

$mulaiTs = strtotime($ujian['tanggalUjian'] . ' ' . $ujian['waktuMulai']);
$nowTs = time();

// Handle ujian yang melewati tengah malam
if ($ujian['waktuSelesai'] < $ujian['waktuMulai']) {
    // Ujian melewati tengah malam - waktu selesai di hari berikutnya
    $selesaiTs = strtotime($ujian['tanggalUjian'] . ' ' . $ujian['waktuSelesai']) + (24 * 60 * 60);
} else {
    // Ujian dalam hari yang sama
    $selesaiTs = strtotime($ujian['tanggalUjian'] . ' ' . $ujian['waktuSelesai']);
}

if (!$is_started && !$ujian_siswa_id) {
    if ($nowTs < $mulaiTs) {
        $timeStatusNote = 'Ujian belum dimulai. Mulai: ' . TimeHelper::format24Hour(date('H:i:s', $mulaiTs));
    } elseif ($nowTs > $selesaiTs) {
        $timeStatusNote = 'Waktu ujian sudah berakhir.';
    }
}
if ($is_started) {
    $durasiDetik = ((int)$ujian['durasi']) * 60;
    $startTs = strtotime($ujian_siswa['waktuMulai']);
    if ($nowTs > ($startTs + $durasiDetik) || $nowTs > $selesaiTs) {
        $ujianLogic->selesaiUjian($ujian_siswa_id);
        header('Location: ujian-user.php?expired=1');
        exit();
    }
}
if ($ujian) {
    $soal_list = $soalLogic->getSoalByUjian($ujian_id);
}
$saved_answers = [];
if ($is_started && $ujian_siswa_id) {
    $saved_answers = $ujianLogic->getJawabanSiswa($ujian_siswa_id);
    // Debug: uncomment the line below to see saved answers in browser console
    // error_log("DEBUG: Saved answers for ujian_siswa_id $ujian_siswa_id: " . json_encode($saved_answers));
}
// Basic assumed student info keys: nama, foto (optional path)
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title><?= htmlspecialchars($ujian['namaUjian']) ?> - CBT</title>
    <link rel="stylesheet" type="text/css" href="../css/kerjakan-soal.css">
</head>

<body class="bg-gray-50">
    <?php if ($is_started): ?>
    <!-- Mobile Timer Toggle - hanya muncul di mobile -->
    <div id="mobile-timer-toggle" class="mobile-timer-toggle">
        <div id="mobile-timer" class="mobile-timer-text">00:00:00</div>
        <div class="mobile-timer-label">Waktu Tersisa</div>
    </div>
    <?php endif; ?>
    
    <div class="layout">
        <aside class="left-col" id="left-sidebar">
            <div class="left-section border-b border-gray-200">
                <div class="flex items-center gap-4">
                    <?php $foto = $_SESSION['user']['foto'] ?? null; ?>
                    <img src="<?= $foto ? htmlspecialchars($foto) : '../../assets/img/logo.png' ?>" alt="Foto" class="avatar">
                    <div>
                        <div class="font-semibold text-gray-800 text-sm leading-tight"><?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Siswa') ?></div>
                        <div class="text-xs text-gray-500 mt-1">ID: <?= htmlspecialchars($siswa_id) ?></div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="section-title">Nama Kelas</div>
                    <div class="text-sm font-medium text-gray-700"><?= htmlspecialchars($ujian['namaKelas'] ?? ($ujian['namaUjian'] ?? 'Ujian')) ?></div>
                </div>
                <?php if ($is_started): ?>
                    <div class="mt-4">
                        <div class="section-title">Waktu Tersisa</div>
                        <div id="timer" class="text-2xl font-bold">00:00:00</div>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($is_started): ?>
                <div class="left-section border-b border-gray-200">
                    <div class="section-title">Status Jawaban</div>
                    
                    <!-- Single container untuk status yang berganti-ganti -->
                    <div id="answer-status-container" class="answer-status-container">
                        <!-- Status akan di-inject di sini oleh JavaScript -->
                        <div class="save-status status-idle">
                            <span class="icon"></span>
                            <span class="label">Siap menerima jawaban</span>
                        </div>
                    </div>
                </div>
                <div class="left-section flex-1 overflow-y-auto">
                    <div class="section-title">Peta Soal</div>
                    <div id="question-map" class="question-map"></div>
                    <div class="legend">
                        <div class="legend-item"><span class="legend-box lb-current"></span><span>Aktif</span></div>
                        <div class="legend-item"><span class="legend-box lb-answered"></span><span>Terjawab</span></div>
                        <div class="legend-item"><span class="legend-box lb-flagged" style="background:#f59e0b;"></span><span>Ditandai</span></div>
                        <div class="legend-item"><span class="legend-box lb-empty"></span><span>Kosong</span></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="left-section text-xs text-gray-500">Silakan mulai ujian untuk menampilkan peta soal.</div>
            <?php endif; ?>
        </aside>
        <div class="content-area">
            <div class="questions-wrapper">
                <?php if (!$is_started): ?>
                    <div class="bg-white border border-gray-200 rounded-xl p-10 text-center max-w-3xl">
                        <div class="text-gray-600 text-sm mb-6 leading-relaxed">
                            <?= nl2br(htmlspecialchars($ujian['deskripsi'])) ?>
                        </div>
                        <div class="grid grid-cols-3 gap-6 mb-10 text-sm">
                            <div class="p-4 rounded-xl bg-gray-50">
                                <div class="text-2xl font-semibold text-blue-600 mb-1"><?= count($soal_list) ?></div>
                                <div class="text-gray-600 uppercase text-2xs tracking-wide">Soal</div>
                            </div>
                            <div class="p-4 rounded-xl bg-gray-50">
                                <div class="text-2xl font-semibold text-green-600 mb-1"><?= htmlspecialchars($ujian['durasi']) ?></div>
                                <div class="text-gray-600 uppercase text-2xs tracking-wide">Menit</div>
                            </div>
                            <div class="p-4 rounded-xl bg-gray-50">
                                <div class="text-2xl font-semibold text-orange-600 mb-1"><?= htmlspecialchars($ujian['totalPoin']) ?></div>
                                <div class="text-gray-600 uppercase text-2xs tracking-wide">Poin</div>
                            </div>
                        </div>
                        <?php if (isset($error_message)): ?>
                            <div class="text-red-600 text-sm font-medium mb-4"><?= htmlspecialchars($error_message) ?></div>
                        <?php endif; ?>
                        <?php if (isset($timeStatusNote)): ?>
                            <div class="text-blue-600 text-xs font-medium mb-4"><?= htmlspecialchars($timeStatusNote) ?> (Server: <?= TimeHelper::format24Hour(date('H:i:s')) ?>)</div>
                        <?php endif; ?>
                        <form method="POST" onsubmit="return confirmStart()">
                            <input type="hidden" name="action" value="start">
                            <button type="submit" class="btn btn-primary text-base px-10 py-3">Mulai Ujian</button>
                        </form>
                    </div>
                <?php else: ?>
                    <?php foreach ($soal_list as $index => $soal): ?>
                        <div class="question-card <?= $index === 0 ? 'active' : '' ?>" data-question="<?= $index + 1 ?>">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="font-semibold text-gray-800">Soal <?= $soal['nomorSoal'] ?></h2>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium bg-blue-100 text-blue-700 px-2.5 py-1 rounded-md"><?= $soal['poin'] ?> poin</span>
                                    <span class="mobile-save-indicator hidden" data-soal-id="<?= $soal['id'] ?>">
                                        <i class="ti ti-check-circle"></i>
                                        <span class="indicator-text">Tersimpan</span>
                                    </span>
                                </div>
                            </div>
                            <div class="question-text text-gray-800 mb-6"><?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?></div>
                            <?php if ($soal['tipeSoal'] === 'pilihan_ganda'): ?>
                                <div class="space-y-3">
                                    <?php foreach ($soal['pilihan_array'] as $opsi => $pilihan): ?>
                                        <label class="answer-option flex items-start gap-3 p-4 border border-gray-200 rounded-xl cursor-pointer bg-white hover:bg-gray-50">
                                            <input type="radio" name="soal_<?= $soal['id'] ?>" value="<?= htmlspecialchars($opsi) ?>" class="mt-1" <?= isset($saved_answers[$soal['id']]) && $saved_answers[$soal['id']] === $opsi ? 'checked' : '' ?>>
                                            <div class="text-sm flex gap-2">
                                                <div class="opt-label font-semibold text-gray-800 mb-0.5 tracking-wide"><?= htmlspecialchars($opsi) ?>.</div>
                                                <div class="text-gray-700 leading-relaxed"><?= htmlspecialchars($pilihan['teks']) ?></div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <textarea name="soal_<?= $soal['id'] ?>" rows="6" class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" placeholder="Ketik jawaban Anda di sini..."><?= isset($saved_answers[$soal['id']]) ? htmlspecialchars($saved_answers[$soal['id']]) : '' ?></textarea>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($is_started): ?>
        <div class="bottom-bar">
            <div class="bar-left">
                <button type="button" class="btn btn-secondary" id="btn-prev" disabled>
                    <i class="ti ti-arrow-left"></i> 
                    <span class="btn-text">Sebelumnya</span>
                </button>
            </div>
            <div class="bar-center">
                <button type="button" class="btn btn-warning" id="btn-flag">
                    <i class="ti ti-flag"></i> 
                    <span class="btn-text">Tandai Soal</span>
                </button>
                <button type="button" class="btn btn-danger" id="btn-finish">
                    <i class="ti ti-check"></i> 
                    <span class="btn-text">Selesai</span>
                </button>
            </div>
            <div class="bar-right">
                <button type="button" class="btn btn-primary" id="btn-next">
                    <span class="btn-text">Selanjutnya</span> 
                    <i class="ti ti-arrow-right"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal Selesai -->
    <div id="finishModal" class="fixed inset-0 bg-black/50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl p-6 w-full max-w-sm">
                <h3 class="text-base font-semibold text-gray-800 mb-3">Selesai Ujian</h3>
                <p class="text-xs text-gray-600 mb-6 leading-relaxed">Apakah Anda yakin ingin menyelesaikan ujian? Setelah diselesaikan Anda tidak dapat mengubah jawaban.</p>
                <div class="flex justify-end gap-3 text-sm">
                    <button id="cancelFinish" class="btn btn-secondary px-4 py-2">Batal</button>
                    <button id="confirmFinish" class="btn btn-danger px-4 py-2">Ya, Selesai</button>
                </div>

                <!-- Debug: Alternative finish method -->
                <?php if (isset($_GET['debug'])): ?>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-xs text-gray-500 mb-2">Debug Mode - Alternative Finish:</p>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="finish_direct">
                            <input type="hidden" name="ujian_siswa_id" value="<?= $ujian_siswa_id ?? '' ?>">
                            <button type="submit" class="btn btn-primary px-3 py-1 text-xs">Direct Finish</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP data to JavaScript
        window.examData = {
            ujianSiswaId: <?= $ujian_siswa_id ?? 'null' ?>,
            duration: <?= ($ujian['durasi'] ?? 0) * 60 ?>,
            totalQuestions: <?= count($soal_list) ?>,
            soalList: <?= json_encode(array_map(fn($s) => ['id' => $s['id'], 'nomor' => $s['nomorSoal']], $soal_list)) ?>,
            isStarted: <?= $is_started ? 'true' : 'false' ?>
        };
        
        // Global Mobile Save Indicator Functions
        function showMobileSaveIndicator(soalId, status = 'saved') {
            // Only show on mobile view
            if (window.innerWidth <= 900) {
                const indicator = document.querySelector(`.mobile-save-indicator[data-soal-id="${soalId}"]`);
                if (indicator) {
                    // Remove all status classes
                    indicator.classList.remove('loading', 'saved');
                    
                    if (status === 'loading' || status === 'saving') {
                        // Show loading state
                        indicator.innerHTML = `
                            <i class="ti ti-loader-2"></i>
                            <span class="indicator-text">Menyimpan...</span>
                        `;
                        indicator.classList.add('show', 'loading');
                    } else if (status === 'saved') {
                        // Show saved state
                        indicator.innerHTML = `
                            <i class="ti ti-check-circle"></i>
                            <span class="indicator-text">Tersimpan</span>
                        `;
                        indicator.classList.add('show');
                        indicator.classList.remove('loading');
                        
                        // Auto hide after 2.5 seconds
                        setTimeout(() => {
                            indicator.classList.remove('show');
                        }, 2500);
                    }
                }
            }
        }

        function hideMobileSaveIndicator(soalId) {
            const indicator = document.querySelector(`.mobile-save-indicator[data-soal-id="${soalId}"]`);
            if (indicator) {
                indicator.classList.remove('show', 'loading');
            }
        }

        // Listen to question status changes from auto-save manager
        document.addEventListener('questionStatusChanged', function(event) {
            const { soalId, status } = event.detail;
            
            // Show mobile indicator based on status
            if (status === 'saving') {
                showMobileSaveIndicator(soalId, 'loading');
            } else if (status === 'saved') {
                showMobileSaveIndicator(soalId, 'saved');
            } else if (status === 'error') {
                hideMobileSaveIndicator(soalId);
            }
        });
        
        // Mobile Timer Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileTimerToggle = document.getElementById('mobile-timer-toggle');
            const leftSidebar = document.getElementById('left-sidebar');
            const mobileTimer = document.getElementById('mobile-timer');
            let sidebarVisible = false;
            
            // Function to check if we're in mobile view
            function isMobileView() {
                return window.innerWidth <= 900;
            }
            
            if (mobileTimerToggle && leftSidebar) {
                // Initial state - sidebar hidden only on mobile
                function updateSidebarState() {
                    if (isMobileView()) {
                        leftSidebar.classList.add('mobile-hidden');
                        leftSidebar.classList.remove('mobile-visible');
                        sidebarVisible = false;
                    } else {
                        // Remove mobile classes on desktop
                        leftSidebar.classList.remove('mobile-hidden', 'mobile-visible');
                        sidebarVisible = false;
                    }
                }
                
                updateSidebarState();
                
                // Update on resize
                window.addEventListener('resize', updateSidebarState);
                
                // Toggle sidebar when mobile timer is clicked (only on mobile)
                mobileTimerToggle.addEventListener('click', function() {
                    if (!isMobileView()) return;
                    
                    if (sidebarVisible) {
                        // Hide sidebar
                        leftSidebar.classList.remove('mobile-visible');
                        leftSidebar.classList.add('mobile-hidden');
                        sidebarVisible = false;
                    } else {
                        // Show sidebar
                        leftSidebar.classList.remove('mobile-hidden');
                        leftSidebar.classList.add('mobile-visible');
                        sidebarVisible = true;
                    }
                });
                
                // Hide sidebar when clicking outside (only on mobile)
                document.addEventListener('click', function(e) {
                    if (!isMobileView()) return;
                    
                    if (sidebarVisible && 
                        !leftSidebar.contains(e.target) && 
                        !mobileTimerToggle.contains(e.target)) {
                        leftSidebar.classList.remove('mobile-visible');
                        leftSidebar.classList.add('mobile-hidden');
                        sidebarVisible = false;
                    }
                });
            }
            
            // Sync mobile timer with main timer
            function syncMobileTimer() {
                const mainTimer = document.getElementById('timer');
                if (mainTimer && mobileTimer) {
                    mobileTimer.textContent = mainTimer.textContent;
                }
            }
            
            // Initial sync and setup interval
            if (window.examData.isStarted) {
                syncMobileTimer();
                setInterval(syncMobileTimer, 1000);
            }

            // Check for existing saved answers on page load and show indicators
            if (window.examData.isStarted && window.innerWidth <= 900) {
                const questionCards = document.querySelectorAll('.question-card');
                questionCards.forEach(card => {
                    const radioInputs = card.querySelectorAll('input[type="radio"]:checked');
                    const textareaInputs = card.querySelectorAll('textarea');
                    
                    // Check if this question has a saved answer
                    const hasAnswer = radioInputs.length > 0 || 
                                     (textareaInputs.length > 0 && textareaInputs[0].value.trim() !== '');
                    
                    if (hasAnswer) {
                        const soalId = card.querySelector('.mobile-save-indicator')?.getAttribute('data-soal-id');
                        if (soalId) {
                            const indicator = card.querySelector('.mobile-save-indicator');
                            if (indicator) {
                                indicator.classList.add('show');
                                // Auto hide existing answers after 1 second
                                setTimeout(() => {
                                    indicator.classList.remove('show');
                                }, 1000);
                            }
                        }
                    }
                });
            }
        });
    </script>
    <script src="../script/auto-save-manager.js"></script>
    <script src="../script/kerjakan-ujian.js"></script>
</body>

</html>