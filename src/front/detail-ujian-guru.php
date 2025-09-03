<?php
session_start();
$currentPage = 'ujian';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
  header('Location: ../../index.php');
  exit();
}
require_once '../logic/ujian-logic.php';
require_once '../logic/soal-logic.php';
$ujianLogic = new UjianLogic();
$soalLogic = new SoalLogic();
$guru_id = $_SESSION['user']['id'];
$ujian_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ujian = $ujian_id ? $ujianLogic->getUjianByIdAndGuru($ujian_id, $guru_id) : null;
if (!$ujian) {
  http_response_code(404);
  echo '<h1>Ujian tidak ditemukan</h1>';
  exit();
}
$soalList = $soalLogic->getSoalByUjian($ujian_id);
// AutoScore view fallback distribusi poin 100 jika perlu
$autoScoreActive = !empty($ujian['autoScore']);
if ($autoScoreActive) {
  $countMc = 0;
  foreach ($soalList as $s) {
    if ($s['tipeSoal'] === 'pilihan_ganda') $countMc++;
  }
  if ($countMc > 0) {
    $base = intdiv(100, $countMc);
    $rem = 100 - ($base * $countMc);
    $i = 0;
    foreach ($soalList as &$s) {
      if ($s['tipeSoal'] === 'pilihan_ganda') {
        $s['poin'] = $base + ($i < $rem ? 1 : 0);
        $i++;
      }
    }
    unset($s);
  }
}
function statusBadgeClass($status)
{
  switch ($status) {
    case 'aktif':
      return 'bg-green-100 text-green-700 ring-1 ring-green-200';
    case 'selesai':
      return 'bg-blue-100 text-blue-700 ring-1 ring-blue-200';
    default:
      return 'bg-gray-100 text-gray-700 ring-1 ring-gray-200';
  }
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
  <title>Detail Ujian</title>
  <style>
    /* Minor adjustments placeholder (no Tailwind @apply in inline CSS) */
  </style>
</head>

<body class="bg-gray-50">

  <div data-main-content class="md:ml-64 min-h-screen pb-20 md:pb-0 transition-all duration-300 ease-in-out">
    <!-- Header -->
    <header class="bg-white p-4 md:p-6 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <a href="ujian-guru.php" class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-gray-100">
            <i class="ti ti-arrow-left text-xl"></i>
          </a>
          <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Detail Ujian</h1>
            <p class="text-sm text-gray-600 mt-1">Informasi & daftar soal ujian</p>
          </div>
        </div>
        <div class="flex items-center space-x-3">
          <?php if ($autoScoreActive): ?>
            <span class="hidden sm:inline-flex items-center text-xs px-3 py-1 rounded-full bg-amber-100 text-amber-700 font-medium">Auto Score</span>
          <?php endif; ?>
          <span class="text-xs px-3 py-1 rounded-full font-medium uppercase tracking-wide <?= statusBadgeClass($ujian['status']) ?>"><?= htmlspecialchars($ujian['status']) ?></span>
        </div>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="p-4 md:p-6">
      <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
          <!-- Main (col-span-3) -->
          <div class="lg:col-span-3 space-y-6">
            <!-- Identitas Ujian -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="ti ti-info-circle text-orange mr-2"></i>
                Identitas Ujian
              </h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                  <p class="text-gray-600">Nama Ujian:</p>
                  <p class="font-medium text-gray-800"><?= htmlspecialchars($ujian['namaUjian']) ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Kelas:</p>
                  <p class="font-medium text-gray-800"><?= htmlspecialchars($ujian['namaKelas'] ?? '-') ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Mata Pelajaran:</p>
                  <p class="font-medium text-gray-800"><?= htmlspecialchars($ujian['mataPelajaran']) ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Durasi:</p>
                  <p class="font-medium text-gray-800"><?= (int)$ujian['durasi'] ?> menit</p>
                </div>
                <div>
                  <p class="text-gray-600">Tanggal:</p>
                  <p class="font-medium text-gray-800"><?= htmlspecialchars(date('d M Y', strtotime($ujian['tanggalUjian']))) ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Waktu:</p>
                  <p class="font-medium text-gray-800"><?= htmlspecialchars(substr($ujian['waktuMulai'], 0, 5)) ?> - <?= htmlspecialchars(substr($ujian['waktuSelesai'], 0, 5)) ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Total Soal:</p>
                  <p class="font-medium text-gray-800"><?= (int)($ujian['totalSoal'] ?? count($soalList)) ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Total Poin:</p>
                  <p class="font-medium text-gray-800"><?= (int)($ujian['totalPoin'] ?? ($autoScoreActive ? 100 : 0)) ?></p>
                </div>
              </div>
              <?php if (!empty($ujian['deskripsi'])): ?>
                <div class="mt-4">
                  <p class="text-gray-600 text-sm mb-1">Deskripsi:</p>
                  <p class="text-gray-800 text-sm whitespace-pre-line"><?= nl2br(htmlspecialchars($ujian['deskripsi'])) ?></p>
                </div>
              <?php endif; ?>
            </div>

            <!-- Daftar Soal -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="ti ti-list-details text-orange mr-2"></i>
                Daftar Soal
                <span class="ml-2 text-xs font-medium text-gray-500">(<?= count($soalList) ?> Soal)</span>
              </h2>
              <?php if (empty($soalList)): ?>
                <div class="p-4 border border-dashed rounded-lg text-sm text-gray-500 bg-gray-50">
                  Belum ada soal. <a href="buat-soal-guru.php?ujian_id=<?= (int)$ujian['id'] ?>" class="text-orange hover:underline font-medium">Tambah sekarang</a>.
                </div>
              <?php else: ?>
                <ul class="space-y-4">
                  <?php foreach ($soalList as $s): ?>
                    <li class="border border-gray-200 rounded-lg p-4 text-sm bg-white shadow-sm">
                      <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <div class="flex items-center space-x-2">
                          <span class="inline-flex items-center justify-center w-7 h-7 text-sm font-semibold rounded bg-orange text-white"><?= (int)$s['nomorSoal'] ?></span>
                          <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-600 capitalize"><?= htmlspecialchars(str_replace('_', ' ', $s['tipeSoal'])) ?></span>
                        </div>
                        <span class="text-xs px-2 py-1 rounded bg-green-50 border border-green-200 text-green-700">Poin: <?= (int)$s['poin'] ?></span>
                      </div>
                      <div class="text-gray-800 mb-3 leading-relaxed">
                        <?= nl2br(htmlspecialchars($s['pertanyaan'])) ?>
                      </div>
                      <?php if ($s['tipeSoal'] === 'pilihan_ganda'): ?>
                        <ul class="ml-1 space-y-1">
                          <?php if (!empty($s['pilihan_array'])): foreach ($s['pilihan_array'] as $opsi => $det): ?>
                              <li class="flex items-start text-gray-700 <?= $det['benar'] ? 'font-semibold' : '' ?>">
                                <span class="inline-block w-5"><?= htmlspecialchars($opsi) ?>.</span>
                                <span><?= htmlspecialchars($det['teks']) ?></span>
                                <?php if ($det['benar']): ?><span class="ml-2 text-[10px] px-1 py-0.5 rounded bg-green-100 text-green-700">Benar</span><?php endif; ?>
                              </li>
                          <?php endforeach;
                          endif; ?>
                        </ul>
                      <?php elseif (!empty($s['kunciJawaban'])): ?>
                        <div class="mt-2 text-xs text-gray-600"><span class="font-medium">Kunci:</span> <?= htmlspecialchars($s['kunciJawaban']) ?></div>
                      <?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>

          <!-- Sidebar (col-span-1) -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sidebar-tools">
              <h3 class="text-lg font-medium text-gray-800 mb-4">Tools</h3>
              <div class="space-y-3">
                <a href="buat-soal-guru.php?ujian_id=<?= (int)$ujian['id'] ?>" class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors">
                  <i class="ti ti-edit"></i><span>Edit Soal</span>
                </a>
                <a href="buat-ujian-guru.php?ujian_id=<?= (int)$ujian['id'] ?>" class="w-full flex items-center justify-center space-x-2 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                  <i class="ti ti-adjustments"></i><span>Edit Identitas</span>
                </a>
                <a href="hasil-ujian.php?ujian_id=<?= (int)$ujian['id'] ?>" class="w-full flex items-center justify-center space-x-2 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                  <i class="ti ti-circle-check"></i><span>Hasil Ujian</span>
                </a>
                <?php if ($ujian['status'] === 'draft'): ?>
                  <form method="post" action="../logic/update-status-ujian.php" onsubmit="return confirm('Aktifkan ujian sekarang?');" class="pt-1">
                    <input type="hidden" name="ujian_id" value="<?= (int)$ujian['id'] ?>">
                    <input type="hidden" name="status" value="aktif">
                    <button class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                      <i class="ti ti-player-play"></i><span>Aktifkan</span>
                    </button>
                  </form>
                <?php elseif ($ujian['status'] === 'aktif'): ?>
                  <button id="finishExamBtn" data-ujian-id="<?= (int)$ujian['id'] ?>" class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                    <i class="ti ti-check"></i><span>Tandai Selesai</span>
                  </button>
                <?php endif; ?>
              </div>

              <!-- Statistik -->
              <div class="mt-6 pt-4 border-t border-gray-200">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Statistik</h4>
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between"><span class="text-gray-600">Total Soal</span><span class="font-medium"><?= (int)($ujian['totalSoal'] ?? count($soalList)) ?></span></div>
                  <div class="flex justify-between"><span class="text-gray-600">Total Poin</span><span class="font-medium"><?= (int)($ujian['totalPoin'] ?? ($autoScoreActive ? 100 : 0)) ?></span></div>
                  <div class="flex justify-between"><span class="text-gray-600">Status</span><span class="font-medium capitalize"><?= htmlspecialchars($ujian['status']) ?></span></div>
                  <?php if ($autoScoreActive): ?>
                    <div class="flex justify-between"><span class="text-gray-600">Auto Score</span><span class="font-medium text-amber-600">Aktif</span></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Include Modal -->
  <?php require '../component/modal-confirm-finish-exam.php'; ?>

  <!-- Toast Container -->
  <div id="toast-container" class="fixed top-4 right-4 space-y-3 z-[10000]"></div>

  <script src="../script/menu-bar-script.js"></script>
  <script>
    // Toast notification system
    function showToast(message, type = 'info', duration = 5000) {
      const colors = {
        success: 'bg-green-600',
        error: 'bg-red-600',
        info: 'bg-blue-600',
        warning: 'bg-yellow-600 text-gray-900'
      };
      const container = document.getElementById('toast-container');
      if (!container) return alert(message);
      
      const el = document.createElement('div');
      el.className = `toast flex items-start text-sm text-white px-4 py-3 rounded-lg shadow-lg backdrop-blur-md bg-opacity-90 ${colors[type] || colors.info} animate-fade-in`;
      el.innerHTML = `
        <div class="mr-3 mt-0.5">
          <i class="ti ${type === 'success' ? 'ti-check' : type === 'error' ? 'ti-alert-circle' : type === 'warning' ? 'ti-alert-triangle' : 'ti-info-circle'}"></i>
        </div>
        <div class="flex-1">${message}</div>
        <button class="ml-3 text-white/80 hover:text-white" onclick="this.parentElement.remove()">
          <i class="ti ti-x"></i>
        </button>
      `;
      
      container.appendChild(el);
      
      setTimeout(() => {
        el.classList.add('opacity-0', 'translate-x-2');
        setTimeout(() => el.remove(), 300);
      }, duration);
    }

    // Modal handling
    const finishModal = document.getElementById('confirmFinishModal');
    const finishBtn = document.getElementById('finishExamBtn');
    const confirmFinishBtn = document.getElementById('confirmFinishBtn');
    const cancelFinishBtn = document.getElementById('cancelFinishBtn');
    
    let currentUjianId = null;

    // Open modal when finish button is clicked
    if (finishBtn) {
      finishBtn.addEventListener('click', () => {
        currentUjianId = finishBtn.dataset.ujianId;
        finishModal.showModal();
        // Add a small delay to ensure the modal is rendered before animation
        setTimeout(() => {
          finishModal.classList.add('modal-open');
        }, 10);
      });
    }

    // Close modal handlers
    function closeFinishModal() {
      finishModal.classList.remove('modal-open');
      // Wait for animation to complete before closing
      setTimeout(() => {
        finishModal.close();
        currentUjianId = null;
      }, 300);
    }

    if (cancelFinishBtn) {
      cancelFinishBtn.addEventListener('click', closeFinishModal);
    }

    // Close modal when clicking backdrop
    finishModal.addEventListener('click', (e) => {
      if (e.target === finishModal) {
        closeFinishModal();
      }
    });

    // Handle confirm finish
    if (confirmFinishBtn) {
      confirmFinishBtn.addEventListener('click', async () => {
        if (!currentUjianId) return;

        // Show loading state
        const btnText = confirmFinishBtn.querySelector('.finish-btn-text');
        const btnLoader = confirmFinishBtn.querySelector('.finish-btn-loading');
        
        btnText.textContent = 'Memproses...';
        btnLoader.classList.remove('hidden');
        confirmFinishBtn.disabled = true;

        try {
          const response = await fetch('../logic/update-status-ujian.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
              ujian_id: currentUjianId,
              status: 'selesai'
            })
          });

          const result = await response.json();

          if (result.success) {
            closeFinishModal();
            // Show success alert for 10 seconds
            showToast('Ujian telah selesai, cek arsip untuk melihatnya kembali', 'success', 10000);
            
            // Redirect back to ujian list after a brief delay
            setTimeout(() => {
              window.location.href = 'ujian-guru.php?updated=1';
            }, 1500);
          } else {
            throw new Error(result.message || 'Gagal menandai ujian selesai');
          }
        } catch (error) {
          showToast(error.message || 'Terjadi kesalahan saat memproses permintaan', 'error');
        } finally {
          // Reset button state
          btnText.textContent = 'Tandai Selesai';
          btnLoader.classList.add('hidden');
          confirmFinishBtn.disabled = false;
        }
      });
    }
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

    dialog {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      padding: 1rem;
      margin: 0;
      border: none;
      background: transparent;
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    dialog:not([open]) {
      display: none;
    }

    /* Mobile: modal di bawah */
    @media (max-width: 639px) {
      dialog {
        align-items: flex-end;
        padding-bottom: 2rem;
      }
    }

    /* Desktop: modal di tengah */
    @media (min-width: 640px) {
      dialog {
        align-items: center;
        padding: 2rem;
      }
    }

    dialog::backdrop {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      animation: backdrop-fade-in 0.3s ease;
    }

    @keyframes backdrop-fade-in {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }

    /* Animation untuk modal content */
    .modal-content {
      transform: scale(0.9) translateY(20px);
      opacity: 0;
      transition: all 0.3s ease;
      max-width: 32rem;
      width: 100%;
    }

    dialog.modal-open .modal-content {
      transform: scale(1) translateY(0);
      opacity: 1;
    }

    /* Mobile modal animation */
    @media (max-width: 639px) {
      .modal-content {
        transform: translateY(100%);
      }
      
      dialog.modal-open .modal-content {
        transform: translateY(0);
      }
    }
  </style>
</body>

</html>