<?php
// Minimalist exam page
session_start();
$currentPage = 'ujian';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'siswa') {
    header('Location: ../../index.php');
    exit();
}

require_once '../logic/ujian-logic.php';
require_once '../logic/soal-logic.php';

$ujianLogic = new UjianLogic();
$soalLogic = new SoalLogic();
$siswa_id = $_SESSION['user']['id'];

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

// Submit answer (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_answer') {
    $ujian_siswa_id = (int)$_POST['ujian_siswa_id'];
    $soal_id = (int)$_POST['soal_id'];
    $jawaban = $_POST['jawaban'] ?? '';
    $save_result = $ujianLogic->simpanJawaban($ujian_siswa_id, $soal_id, $jawaban);

    if (isset($_POST['finish_exam'])) {
        $finish_result = $ujianLogic->selesaiUjian($ujian_siswa_id);
        if ($finish_result['success']) {
            header('Location: ujian-user.php?finished=1');
            exit();
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

$ujian = null; $soal_list = []; $ujian_siswa = null; $is_started = false;
if ($ujian_siswa_id) {
    $ujian_siswa = $ujianLogic->getUjianSiswaById($ujian_siswa_id);
    if ($ujian_siswa && (int)$ujian_siswa['siswa_id'] === (int)$siswa_id) {
        $ujian_id = (int)$ujian_siswa['ujian_id'];
        $is_started = ($ujian_siswa['status'] === 'sedang_mengerjakan');
    } else {
        header('Location: ujian-user.php');
        exit();
    }
}
if ($ujian_id) { $ujian = $ujianLogic->getUjianById($ujian_id); }
if (!$ujian) { header('Location: ujian-user.php'); exit(); }

$mulaiTs = strtotime($ujian['tanggalUjian'].' '.$ujian['waktuMulai']);
$selesaiTs = strtotime($ujian['tanggalUjian'].' '.$ujian['waktuSelesai']);
$nowTs = time();
if (!$is_started && !$ujian_siswa_id) {
    if ($nowTs < $mulaiTs) { $timeStatusNote = 'Ujian belum dimulai. Mulai: '.date('H:i', $mulaiTs); }
    elseif ($nowTs > $selesaiTs) { $timeStatusNote = 'Waktu ujian sudah berakhir.'; }
}
if ($is_started) {
    $durasiDetik = ((int)$ujian['durasi']) * 60; $startTs = strtotime($ujian_siswa['waktuMulai']);
    if ($nowTs > ($startTs + $durasiDetik) || $nowTs > $selesaiTs) {
        $ujianLogic->selesaiUjian($ujian_siswa_id); header('Location: ujian-user.php?expired=1'); exit();
    }
}
if ($ujian) { $soal_list = $soalLogic->getSoalByUjian($ujian_id); }
$saved_answers = []; if ($is_started && $ujian_siswa_id) { $saved_answers = $ujianLogic->getJawabanSiswa($ujian_siswa_id); }
// Basic assumed student info keys: nama, foto (optional path)
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title><?= htmlspecialchars($ujian['namaUjian']) ?> - CBT</title>
    <style>
        body { font-family: ui-rounded, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif; }
        .layout { display:flex; min-height:100vh; }
        .left-col { width:280px; background:#ffffff; border-right:1px solid #ececec; display:flex; flex-direction:column; backdrop-filter:blur(10px) saturate(1.2); }
        .left-section { padding:18px 20px; }
        .avatar { width:82px; height:82px; border-radius:26px; object-fit:cover; border:3px solid #f97316; box-shadow:0 4px 12px -2px rgba(249,115,22,.45); }
        .section-title { font-size:.65rem; font-weight:600; letter-spacing:.08em; color:#9ca3af; text-transform:uppercase; margin-bottom:6px; }
        .question-map { display:grid; grid-template-columns:repeat(auto-fill,minmax(42px,1fr)); gap:6px; }
        .q-btn { height:42px; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:600; border-radius:13px; cursor:pointer; border:1px solid #ececec; background:linear-gradient(145deg,#fdfdfd,#f6f7f8); transition:.25s cubic-bezier(.4,.2,.2,1); position:relative; box-shadow:0 2px 4px rgba(0,0,0,.04); }
        .q-btn.current { background:linear-gradient(160deg,#ff912f,#f97316); color:#fff; border-color:#f97316; box-shadow:0 6px 16px -4px rgba(249,115,22,.55); }
        .q-btn.answered { background:linear-gradient(155deg,#22c55e,#16a34a); color:#fff; border-color:#16a34a; box-shadow:0 6px 14px -4px rgba(22,163,74,.5); }
        .q-btn.flagged { box-shadow:0 0 0 3px #f59e0b inset; }
        .q-btn:hover { transform:translateY(-2px); box-shadow:0 6px 18px -4px rgba(0,0,0,.12); }
        .save-status { display:flex; align-items:center; font-size:.8rem; font-weight:600; letter-spacing:.3px; }
        .save-status .icon { width:10px; height:10px; border-radius:50%; margin-right:8px; box-shadow:0 0 0 3px rgba(0,0,0,.05); }
        .status-saved { color:#16a34a; }
        .status-saved .icon { background:#16a34a; }
        .status-unsaved { color:#dc2626; }
        .status-unsaved .icon { background:#dc2626; }
        .timer-box { background:linear-gradient(145deg,#fff,#fff5ed); border:1px solid #ffd7ba; padding:10px 18px; border-radius:16px; display:inline-flex; align-items:center; gap:8px; font-weight:600; font-size:1rem; letter-spacing:.5px; color:#d65c05; box-shadow:0 4px 14px -4px rgba(249,115,22,.4); }
        .content-area { flex:1; display:flex; flex-direction:column; }
        .exam-header { padding:18px 30px 14px; background:rgba(255,255,255,.9); border-bottom:1px solid #ececec; display:flex; justify-content:space-between; align-items:center; backdrop-filter:blur(14px); position:sticky; top:0; z-index:30; }
        .questions-wrapper { flex:1; padding:34px clamp(18px,4vw,54px) 140px; max-width:1100px; }
        .question-card { background:#ffffff; border:1px solid #f1f1f1; border-radius:26px; padding:40px 44px 46px; margin:0 0 34px; display:none; box-shadow:0 8px 28px -6px rgba(0,0,0,.12), 0 2px 6px rgba(0,0,0,.04); }
        .question-card.active { display:block; animation:fadeIn .4s ease; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(8px);} to { opacity:1; transform:translateY(0);} }
        .question-text { font-size:1.25rem; line-height:1.7; font-weight:500; letter-spacing:.25px; }
        .question-card h2 { font-size:1.05rem; letter-spacing:.5px; }
        .answer-option { transition:.25s; }
        .answer-option:hover { border-color:#f97316; background:#fff8f2; }
        .answer-option input:checked + div .opt-label { color:#f97316; }
        .bottom-bar { position:fixed; bottom:0; left:0; right:0; background:rgba(255,255,255,.85); border-top:1px solid #f1f1f1; padding:16px clamp(16px,4vw,60px); display:flex; align-items:center; gap:16px; z-index:60; backdrop-filter:blur(18px) saturate(1.4); }
        .bar-left { flex:1; display:flex; justify-content:flex-start; }
        .bar-center { flex:1; display:flex; justify-content:center; gap:18px; }
        .bar-right { flex:1; display:flex; justify-content:flex-end; }
        .btn { display:inline-flex; align-items:center; gap:9px; font-size:.85rem; font-weight:600; padding:14px 26px; border-radius:22px; border:1px solid transparent; cursor:pointer; transition:.25s cubic-bezier(.4,.2,.2,1); letter-spacing:.3px; box-shadow:0 2px 4px rgba(0,0,0,.05); }
        .btn:active { transform:translateY(1px); }
        .btn-primary { background:linear-gradient(160deg,#ff973b,#f97316); color:#fff; box-shadow:0 6px 18px -4px rgba(249,115,22,.6); }
        .btn-primary:hover { background:linear-gradient(160deg,#ff8a22,#f57314); }
        .btn-secondary { background:#f5f6f7; color:#333; border-color:#ebecee; }
        .btn-secondary:hover { background:#ebecee; }
        .btn-danger { background:linear-gradient(150deg,#ef4444,#dc2626); color:#fff; box-shadow:0 6px 16px -4px rgba(220,38,38,.55); }
        .btn-danger:hover { background:linear-gradient(150deg,#f43f3f,#d61f1f); }
        .btn-warning { background:linear-gradient(155deg,#f8b649,#f59e0b); color:#fff; box-shadow:0 6px 16px -5px rgba(245,158,11,.55); }
        .btn-warning:hover { background:linear-gradient(155deg,#f6aa2f,#ec9505); }
        .legend { display:flex; gap:12px; margin-top:12px; flex-wrap:wrap; }
        .legend-item { display:flex; align-items:center; font-size:.6rem; font-weight:600; gap:6px; color:#6b7280; letter-spacing:.5px; }
        .legend-box { width:20px; height:20px; border-radius:8px; }
        .lb-current { background:#f97316; }
        .lb-answered { background:#16a34a; }
        .lb-flagged { background:#f59e0b; }
        .lb-empty { background:#f3f4f6; border:1px solid #e5e7eb; }
        textarea { resize:none; font-size:.95rem; line-height:1.5; }
        @media (max-width: 900px) {
            .layout { flex-direction:column; }
            .left-col { width:100%; border-right:none; border-bottom:1px solid #ececec; }
            .questions-wrapper { padding:26px 20px 180px; }
            .exam-header { flex-wrap:wrap; gap:12px; }
            .bottom-bar { flex-direction:column; align-items:stretch; }
            .bar-left, .bar-right, .bar-center { width:100%; justify-content:space-between; }
            .btn { flex:1; justify-content:center; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="layout">
        <aside class="left-col">
            <div class="left-section border-b">
                <div class="flex items-center gap-4">
                    <?php $foto = $_SESSION['user']['foto'] ?? null; ?>
                    <img src="<?= $foto ? htmlspecialchars($foto) : '../../assets/img/logo.png' ?>" alt="Foto" class="avatar">
                    <div>
                        <div class="font-semibold text-gray-800 text-sm leading-tight"><?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Siswa') ?></div>
                        <div class="text-xs text-gray-500 mt-1">ID: <?= htmlspecialchars($siswa_id) ?></div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="section-title">Mata Pelajaran</div>
                    <div class="text-sm font-medium text-gray-700"><?= htmlspecialchars($ujian['mataPelajaran'] ?? ($ujian['namaUjian'] ?? 'Ujian')) ?></div>
                </div>
                <?php if($is_started): ?>
                <div class="mt-4">
                    <div class="section-title">Waktu Tersisa</div>
                    <div id="timer" class="timer-box">00:00:00</div>
                </div>
                <?php endif; ?>
            </div>
            <?php if($is_started): ?>
            <div class="left-section border-b">
                <div class="section-title">Status Jawaban</div>
                <div id="save-status" class="save-status status-saved hidden">
                    <span class="icon"></span><span class="label">Tersimpan</span>
                </div>
                <div id="save-status-unsaved" class="save-status status-unsaved hidden">
                    <span class="icon"></span><span class="label">Belum Tersimpan</span>
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
            <div class="exam-header">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800 leading-tight"><?= htmlspecialchars($ujian['namaUjian']) ?></h1>
                    <?php if(!$is_started): ?>
                        <p class="text-xs text-gray-500 mt-1">Durasi: <?= htmlspecialchars($ujian['durasi']) ?> menit | Total Soal: <?= count($soal_list) ?></p>
                    <?php endif; ?>
                </div>
                <?php if($is_started): ?>
                <div class="hidden md:block">
                    <div class="text-xs font-medium text-gray-500 mb-1">Waktu Tersisa</div>
                    <div id="timer-top" class="timer-box">00:00:00</div>
                </div>
                <?php endif; ?>
            </div>
            <div class="questions-wrapper">
                <?php if(!$is_started): ?>
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
                        <?php if(isset($error_message)): ?>
                            <div class="text-red-600 text-sm font-medium mb-4"><?= htmlspecialchars($error_message) ?></div>
                        <?php endif; ?>
                        <?php if(isset($timeStatusNote)): ?>
                            <div class="text-blue-600 text-xs font-medium mb-4"><?= htmlspecialchars($timeStatusNote) ?> (Server: <?= date('H:i') ?>)</div>
                        <?php endif; ?>
                        <form method="POST" onsubmit="return confirmStart()">
                            <input type="hidden" name="action" value="start">
                            <button type="submit" class="btn btn-primary text-base px-10 py-3">Mulai Ujian</button>
                        </form>
                    </div>
                <?php else: ?>
                    <?php foreach ($soal_list as $index => $soal): ?>
                        <div class="question-card <?= $index===0 ? 'active' : '' ?>" data-question="<?= $index+1 ?>">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="font-semibold text-gray-800">Soal <?= $soal['nomorSoal'] ?></h2>
                                <span class="text-xs font-medium bg-blue-100 text-blue-700 px-2.5 py-1 rounded-md"><?= $soal['poin'] ?> poin</span>
                            </div>
                            <div class="question-text text-gray-800 mb-6"><?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?></div>
                            <?php if($soal['tipeSoal']==='pilihan_ganda'): ?>
                                <div class="space-y-3">
                                    <?php foreach($soal['pilihan_array'] as $opsi => $pilihan): ?>
                                        <label class="answer-option flex items-start gap-3 p-4 border border-gray-200 rounded-xl cursor-pointer bg-white hover:bg-gray-50">
                                            <input type="radio" name="soal_<?= $soal['id'] ?>" value="<?= htmlspecialchars($opsi) ?>" class="mt-1" <?= isset($saved_answers[$soal['id']]) && $saved_answers[$soal['id']] === $opsi ? 'checked' : '' ?>>
                                            <div class="text-sm">
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

    <?php if($is_started): ?>
    <div class="bottom-bar">
        <div class="bar-left">
            <button type="button" class="btn btn-secondary" id="btn-prev" disabled><i class="ti ti-arrow-left"></i> Sebelumnya</button>
        </div>
        <div class="bar-center">
            <button type="button" class="btn btn-warning" id="btn-flag"><i class="ti ti-flag"></i> Tandai Soal</button>
            <button type="button" class="btn btn-danger" id="btn-finish"><i class="ti ti-check"></i> Selesai</button>
        </div>
        <div class="bar-right">
            <button type="button" class="btn btn-primary" id="btn-next">Selanjutnya <i class="ti ti-arrow-right"></i></button>
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
            </div>
        </div>
    </div>

    <script>
        const examData = {
            ujianSiswaId: <?= $ujian_siswa_id ?? 'null' ?>,
            duration: <?= ($ujian['durasi'] ?? 0) * 60 ?>,
            totalQuestions: <?= count($soal_list) ?>,
            soalList: <?= json_encode(array_map(fn($s)=>['id'=>$s['id'],'nomor'=>$s['nomorSoal']], $soal_list)) ?>,
            isStarted: <?= $is_started ? 'true' : 'false' ?>
        };
        let currentQuestion = 1;
        let timeLeft = examData.duration;
        let examTimer, autoSaveTimer;
        let flagged = new Set();
        let latestSaveSuccess = true;
        let unsaved = new Set(); // question numbers with local change not yet saved

        document.addEventListener('DOMContentLoaded', () => {
            if (examData.isStarted) {
                initMap();
                initEvents();
                startTimer();
                startAutoSave();
                updateMap();
                updateNavButtons();
            }
        });

        function confirmStart(){ return confirm('Mulai ujian sekarang?'); }

        function initMap(){
            const map = document.getElementById('question-map');
            map.innerHTML = '';
            for(let i=1;i<=examData.totalQuestions;i++){
                const b = document.createElement('button');
                b.type='button';
                b.textContent=i;
                b.className='q-btn'+(i===1?' current':'');
                b.dataset.q=i;
                b.onclick=()=>go(i);
                map.appendChild(b);
            }
        }

        function initEvents(){
            document.getElementById('btn-prev').onclick=()=>go(currentQuestion-1);
            document.getElementById('btn-next').onclick=()=>go(currentQuestion+1);
            document.getElementById('btn-finish').onclick=showFinishModal;
            document.getElementById('btn-flag').onclick=toggleFlag;
            document.getElementById('cancelFinish').onclick=hideFinishModal;
            document.getElementById('confirmFinish').onclick=finishExam;
            document.addEventListener('change', e=>{
                if(e.target.name && e.target.name.startsWith('soal_')){
                    unsaved.add(currentQuestion);
                    showUnsaved();
                }
            });
            document.addEventListener('input', e=>{
                if(e.target.name && e.target.name.startsWith('soal_')){
                    unsaved.add(currentQuestion);
                    showUnsaved();
                }
            });
            window.addEventListener('beforeunload', e=>{
                if(examData.isStarted && timeLeft>0){ e.preventDefault(); e.returnValue=''; }
            });
        }

        function go(n){
            if(n<1||n>examData.totalQuestions) return;
            document.querySelector(`.question-card[data-question="${currentQuestion}"]`).classList.remove('active');
            currentQuestion=n;
            document.querySelector(`.question-card[data-question="${currentQuestion}"]`).classList.add('active');
            updateMap();
            updateNavButtons();
            window.scrollTo({top:0,behavior:'smooth'});
        }
        function updateNavButtons(){
            document.getElementById('btn-prev').disabled = currentQuestion===1;
            document.getElementById('btn-next').disabled = currentQuestion===examData.totalQuestions;
        }
        function updateMap(){
            document.querySelectorAll('#question-map .q-btn').forEach(btn=>{
                const q=+btn.dataset.q;
                btn.classList.remove('current','answered','flagged');
                if(q===currentQuestion) btn.classList.add('current');
                else if(isAnswered(q)) btn.classList.add('answered');
                if(flagged.has(q)) btn.classList.add('flagged');
            });
        }
        function isAnswered(q){
            const card=document.querySelector(`.question-card[data-question="${q}"]`);
            if(!card) return false;
            const r=card.querySelector('input[type=radio]:checked');
            const t=card.querySelector('textarea');
            return (r!==null) || (t && t.value.trim().length>0);
        }
        function getCurrentSoalId(){
            const card=document.querySelector(`.question-card[data-question="${currentQuestion}"]`);
            if(!card) return null;
            const el=card.querySelector('input[name^="soal_"],textarea[name^="soal_"]');
            return el? el.name.replace('soal_',''): null;
        }
        function getCurrentAnswer(){
            const card=document.querySelector(`.question-card[data-question="${currentQuestion}"]`);
            if(!card) return '';
            const r=card.querySelector('input[type=radio]:checked');
            if(r) return r.value;
            const t=card.querySelector('textarea');
            return t? t.value:'';
        }
        function saveCurrent(showNotify){
            const soalId=getCurrentSoalId();
            if(!soalId) return;
            const fd=new FormData();
            fd.append('action','submit_answer');
            fd.append('ujian_siswa_id', examData.ujianSiswaId);
            fd.append('soal_id',soalId);
            fd.append('jawaban',getCurrentAnswer());
            fetch('kerjakan-ujian.php',{method:'POST',body:fd})
                .then(r=>r.json())
                .then(d=>{ latestSaveSuccess=true; unsaved.delete(currentQuestion); showSaved(); updateMap(); if(showNotify) notify('Jawaban disimpan','success'); })
                .catch(()=>{ latestSaveSuccess=false; showUnsaved(); if(showNotify) notify('Gagal menyimpan','error'); });
        }
        function startTimer(){
            const el1=document.getElementById('timer');
            const el2=document.getElementById('timer-top');
            examTimer=setInterval(()=>{
                timeLeft--; if(timeLeft<0) timeLeft=0;
                const h=Math.floor(timeLeft/3600); const m=Math.floor((timeLeft%3600)/60); const s=timeLeft%60;
                const str=`${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
                if(el1) el1.textContent=str; if(el2) el2.textContent=str;
                if(timeLeft===300) notify('Sisa waktu 5 menit','warning');
                if(timeLeft===0){ clearInterval(examTimer); autoFinish(); }
            },1000);
        }
        function startAutoSave(){
            autoSaveTimer=setInterval(()=>{ if(isAnswered(currentQuestion) && unsaved.has(currentQuestion)) saveCurrent(false); },15000);
        }
        function toggleFlag(){
            if(flagged.has(currentQuestion)) flagged.delete(currentQuestion); else flagged.add(currentQuestion); updateMap();
        }
        function showSaved(){
            document.getElementById('save-status').classList.remove('hidden');
            document.getElementById('save-status-unsaved').classList.add('hidden');
        }
        function showUnsaved(){
            document.getElementById('save-status').classList.add('hidden');
            document.getElementById('save-status-unsaved').classList.remove('hidden');
        }
        function showFinishModal(){ document.getElementById('finishModal').classList.remove('hidden'); }
        function hideFinishModal(){ document.getElementById('finishModal').classList.add('hidden'); }
        function finishExam(){ saveCurrent(false); const fd=new FormData(); fd.append('action','submit_answer'); fd.append('ujian_siswa_id',examData.ujianSiswaId); fd.append('soal_id','0'); fd.append('jawaban',''); fd.append('finish_exam','1'); fetch('kerjakan-ujian.php',{method:'POST',body:fd}).then(()=>window.location='ujian-user.php?finished=1').catch(()=>window.location='ujian-user.php'); }
        function autoFinish(){ notify('Waktu habis! Menyelesaikan ujian...','warning'); setTimeout(finishExam,1500); }
        function notify(msg,type){ const n=document.createElement('div'); n.className='fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow text-white text-sm font-medium '+(type==='success'?'bg-green-600': type==='error'?'bg-red-600': type==='warning'?'bg-amber-500':'bg-blue-600'); n.textContent=msg; document.body.appendChild(n); setTimeout(()=>n.remove(),2500); }
    </script>
</body>
</html>
