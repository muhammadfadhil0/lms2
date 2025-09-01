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
    echo '<h1>Ujian tidak ditemukan</h1>'; exit();
}
$soalList = $soalLogic->getSoalByUjian($ujian_id);
// Jika autoScore aktif tapi poin soal belum tersinkron 100, lakukan kalkulasi view fallback
$autoScoreActive = !empty($ujian['autoScore']);
if($autoScoreActive){
  $countMc = 0; foreach($soalList as $s){ if($s['tipeSoal']==='pilihan_ganda') $countMc++; }
  if($countMc>0){
    $base = intdiv(100,$countMc); $rem = 100 - ($base*$countMc); $i=0;
    foreach($soalList as &$s){ if($s['tipeSoal']==='pilihan_ganda'){ $s['poin'] = $base + ($i < $rem ? 1 : 0); $i++; } }
    unset($s);
  }
}
function badge($status){
    switch($status){
        case 'aktif': return 'bg-green-100 text-green-700';
        case 'selesai': return 'bg-blue-100 text-blue-700';
        default: return 'bg-gray-100 text-gray-700';
    }
}
?>
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<?php require '../../assets/head.php'; ?>
<title>Detail Ujian</title>
</head><body class="bg-gray-50">
<div data-main-content class="md:ml-64 min-h-screen pb-20 md:pb-0 transition-all">
<header class="bg-white p-4 md:p-6 border-b border-gray-200 flex items-center justify-between">
  <div class="flex items-center space-x-4">
    <a href="ujian-guru.php" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"><i class="ti ti-arrow-left text-xl"></i></a>
    <div>
      <h1 class="text-xl md:text-2xl font-bold text-gray-800">Detail Ujian</h1>
      <p class="text-sm text-gray-500">Informasi & daftar soal</p>
    </div>
  </div>
  <div class="text-xs px-3 py-1 rounded <?= badge($ujian['status']) ?> font-medium uppercase tracking-wide"><?= htmlspecialchars($ujian['status']) ?></div>
</header>
<main class="p-4 md:p-6 space-y-6 max-w-6xl mx-auto">
  <div class="grid md:grid-cols-3 gap-6">
    <div class="md:col-span-2 space-y-6">
      <div class="bg-white border rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center"><i class="ti ti-info-circle text-orange mr-2"></i>Identitas Ujian</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 text-sm">
          <div><dt class="text-gray-500">Nama Ujian</dt><dd class="font-medium text-gray-800"><?= htmlspecialchars($ujian['namaUjian']) ?></dd></div>
          <div><dt class="text-gray-500">Kelas</dt><dd class="font-medium text-gray-800"><?= htmlspecialchars($ujian['namaKelas'] ?? '-') ?></dd></div>
          <div><dt class="text-gray-500">Mata Pelajaran</dt><dd class="font-medium text-gray-800"><?= htmlspecialchars($ujian['mataPelajaran']) ?></dd></div>
          <div><dt class="text-gray-500">Durasi</dt><dd class="font-medium text-gray-800"><?= (int)$ujian['durasi'] ?> menit</dd></div>
          <div><dt class="text-gray-500">Tanggal</dt><dd class="font-medium text-gray-800"><?= htmlspecialchars(date('d M Y', strtotime($ujian['tanggalUjian']))) ?></dd></div>
          <div><dt class="text-gray-500">Waktu</dt><dd class="font-medium text-gray-800"><?= htmlspecialchars(substr($ujian['waktuMulai'],0,5)) ?> - <?= htmlspecialchars(substr($ujian['waktuSelesai'],0,5)) ?></dd></div>
          <div><dt class="text-gray-500">Total Soal</dt><dd class="font-medium text-gray-800"><?= (int)($ujian['totalSoal'] ?? count($soalList)) ?></dd></div>
          <div><dt class="text-gray-500">Total Poin</dt><dd class="font-medium text-gray-800"><?= (int)($ujian['totalPoin'] ?? 0) ?></dd></div>
        </dl>
        <?php if(!empty($ujian['deskripsi'])): ?>
        <div class="mt-4">
          <p class="text-gray-500 text-sm mb-1">Deskripsi:</p>
          <div class="text-sm text-gray-800 whitespace-pre-line"><?= nl2br(htmlspecialchars($ujian['deskripsi'])) ?></div>
        </div>
        <?php endif; ?>
      </div>
      <div class="bg-white border rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center"><i class="ti ti-list-details text-orange mr-2"></i>Daftar Soal</h2>
        <?php if(empty($soalList)): ?>
          <div class="text-sm text-gray-500">Belum ada soal. <a href="buat-soal-guru.php?ujian_id=<?= (int)$ujian['id'] ?>" class="text-orange hover:underline">Tambah sekarang</a>.</div>
        <?php else: ?>
          <ul class="space-y-4">
          <?php foreach($soalList as $s): ?>
            <li class="border rounded-lg p-4 text-sm bg-gray-50">
              <div class="flex justify-between mb-2">
                <span class="font-semibold text-gray-800">Soal <?= (int)$s['nomorSoal'] ?></span>
                <span class="text-xs px-2 py-1 rounded bg-white border text-gray-600">Poin: <?= (int)$s['poin'] ?></span>
              </div>
              <div class="text-gray-800 mb-2"><?= nl2br(htmlspecialchars($s['pertanyaan'])) ?></div>
              <div class="text-xs text-gray-500 mb-1">Tipe: <?= htmlspecialchars($s['tipeSoal']) ?></div>
              <?php if($s['tipeSoal']==='pilihan_ganda'): ?>
                <ul class="ml-4 list-disc text-gray-700 mb-2">
                  <?php if(!empty($s['pilihan_array'])): foreach($s['pilihan_array'] as $opsi=>$det): ?>
                    <li class="<?= $det['benar']?'font-semibold text-green-600':'' ?>">
                      <span class="inline-block w-5"><?= htmlspecialchars($opsi) ?>.</span> <?= htmlspecialchars($det['teks']) ?>
                      <?php if($det['benar']): ?><span class="ml-2 text-[10px] px-1 py-0.5 rounded bg-green-100 text-green-700">Kunci</span><?php endif; ?>
                    </li>
                  <?php endforeach; endif; ?>
                </ul>
              <?php elseif(!empty($s['kunciJawaban'])): ?>
                <div class="text-xs text-gray-600"><span class="font-medium">Kunci:</span> <?= htmlspecialchars($s['kunciJawaban']) ?></div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
    <div class="space-y-6">
      <div class="bg-white border rounded-lg p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Aksi</h3>
        <div class="space-y-2">
          <a href="buat-soal-guru.php?ujian_id=<?= (int)$ujian['id'] ?>" class="block w-full text-center text-sm px-4 py-2 rounded bg-orange text-white hover:bg-orange-600">Tambah/Edit Soal</a>
          <?php if($ujian['status']==='draft'): ?>
          <form method="post" action="../logic/update-status-ujian.php" onsubmit="return confirm('Aktifkan ujian sekarang?');">
            <input type="hidden" name="ujian_id" value="<?= (int)$ujian['id'] ?>">
            <input type="hidden" name="status" value="aktif">
            <button class="w-full text-sm px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">Aktifkan</button>
          </form>
          <?php elseif($ujian['status']==='aktif'): ?>
          <form method="post" action="../logic/update-status-ujian.php" onsubmit="return confirm('Tandai selesai?');">
            <input type="hidden" name="ujian_id" value="<?= (int)$ujian['id'] ?>">
            <input type="hidden" name="status" value="selesai">
            <button class="w-full text-sm px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Tandai Selesai</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <div class="bg-white border rounded-lg p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Statistik Cepat</h3>
        <ul class="text-sm text-gray-700 space-y-1">
          <li>Total Soal: <span class="font-medium"><?= (int)($ujian['totalSoal'] ?? count($soalList)) ?></span></li>
          <li>Total Poin: <span class="font-medium"><?= (int)($ujian['totalPoin'] ?? 0) ?></span></li>
          <li>Status: <span class="font-medium capitalize"><?= htmlspecialchars($ujian['status']) ?></span></li>
        </ul>
      </div>
    </div>
  </div>
</main>
</div>
<script src="../script/menu-bar-script.js"></script>
</body></html>
