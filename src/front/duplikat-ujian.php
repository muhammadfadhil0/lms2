<?php
session_start();

// Check auth & role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    if (isset($_GET['debug'])) {
        die(json_encode(['success' => false, 'message' => 'User tidak login sebagai guru']));
    }
    header('Location: ../../index.php');
    exit();
}

require_once '../logic/ujian-logic.php';
require_once '../logic/soal-logic.php';

$ujianLogic = new UjianLogic();
$soalLogic = new SoalLogic();
$guru_id = $_SESSION['user']['id'];

// Debug mode untuk development
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

// Validasi parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    if ($debug_mode) {
        die(json_encode(['success' => false, 'message' => 'Parameter ID tidak valid']));
    }
    header('Location: ujian-guru.php?err=notfound');
    exit();
}

$ujian_id = (int) $_GET['id'];

// Debug info
$debug_info = [
    'ujian_id' => $ujian_id,
    'guru_id' => $guru_id,
    'step' => 'start'
];

try {
    // Ambil data ujian yang akan diduplikasi
    $ujian = $ujianLogic->getUjianByIdAndGuru($ujian_id, $guru_id);
    
    $debug_info['step'] = 'get_ujian';
    $debug_info['ujian_found'] = !empty($ujian);
    
    if (!$ujian) {
        if ($debug_mode) {
            die(json_encode(['success' => false, 'message' => 'Ujian tidak ditemukan atau bukan milik guru', 'debug' => $debug_info]));
        }
        header('Location: ujian-guru.php?err=notfound');
        exit();
    }
    
    $debug_info['ujian_data'] = $ujian;
    $debug_info['step'] = 'ujian_validated';
    
    // Mulai duplikasi
    require_once '../logic/koneksi.php';
    $conn = getConnection();
    $conn->begin_transaction();
    
    $debug_info['step'] = 'transaction_started';
    
    // 1. Duplikasi data ujian utama dengan nama yang ditambahi "- salinan"
    $namaUjianBaru = $ujian['namaUjian'] . ' - salinan';
    
    $debug_info['step'] = 'preparing_insert';
    $debug_info['nama_ujian_baru'] = $namaUjianBaru;
    
    $sql = "INSERT INTO ujian (
        namaUjian, deskripsi, kelas_id, guru_id, mataPelajaran, 
        tanggalUjian, waktuMulai, waktuSelesai, durasi, status,
        shuffleQuestions, showScore, autoScore
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Gagal prepare statement: ' . $conn->error);
    }
    
    $shuffleQuestions = $ujian['shuffleQuestions'] ?? 0;
    $showScore = $ujian['showScore'] ?? 1;
    $autoScore = $ujian['autoScore'] ?? 0;
    
    $debug_info['bind_params'] = [
        'namaUjian' => $namaUjianBaru,
        'deskripsi' => $ujian['deskripsi'],
        'kelas_id' => $ujian['kelas_id'],
        'guru_id' => $guru_id,
        'mataPelajaran' => $ujian['mataPelajaran'],
        'tanggalUjian' => $ujian['tanggalUjian'],
        'waktuMulai' => $ujian['waktuMulai'],
        'waktuSelesai' => $ujian['waktuSelesai'],
        'durasi' => $ujian['durasi'],
        'shuffleQuestions' => $shuffleQuestions,
        'showScore' => $showScore,
        'autoScore' => $autoScore
    ];
    
    $stmt->bind_param(
        "ssiissssiiii",
        $namaUjianBaru,
        $ujian['deskripsi'],
        $ujian['kelas_id'],
        $guru_id,
        $ujian['mataPelajaran'],
        $ujian['tanggalUjian'],
        $ujian['waktuMulai'],
        $ujian['waktuSelesai'],
        $ujian['durasi'],
        $shuffleQuestions,
        $showScore,
        $autoScore
    );
    
    $debug_info['step'] = 'executing_insert_ujian';
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal menduplikasi data ujian: ' . $stmt->error);
    }
    
    $ujian_id_baru = $conn->insert_id;
    $debug_info['ujian_id_baru'] = $ujian_id_baru;
    $debug_info['step'] = 'ujian_inserted';
    
    // 2. Ambil semua soal dari ujian asli
    $sql_soal = "SELECT * FROM soal WHERE ujian_id = ? ORDER BY nomorSoal";
    $stmt_soal = $conn->prepare($sql_soal);
    $stmt_soal->bind_param("i", $ujian_id);
    $stmt_soal->execute();
    $soal_list = $stmt_soal->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $debug_info['step'] = 'soal_fetched';
    $debug_info['jumlah_soal'] = count($soal_list);
    $debug_info['soal_list'] = $soal_list;
    
    // 3. Duplikasi setiap soal
    $soal_counter = 0;
    foreach ($soal_list as $soal) {
        $soal_counter++;
        $debug_info['current_soal'] = $soal_counter;
        
        // Insert soal baru
        $sql_insert_soal = "INSERT INTO soal (
            ujian_id, nomorSoal, pertanyaan, tipeSoal, kunciJawaban, poin
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt_insert_soal = $conn->prepare($sql_insert_soal);
        $stmt_insert_soal->bind_param(
            "iisssi",
            $ujian_id_baru,
            $soal['nomorSoal'],
            $soal['pertanyaan'],
            $soal['tipeSoal'],
            $soal['kunciJawaban'],
            $soal['poin']
        );
        
        if (!$stmt_insert_soal->execute()) {
            throw new Exception('Gagal menduplikasi soal nomor ' . $soal['nomorSoal'] . ': ' . $stmt_insert_soal->error);
        }
        
        $soal_id_baru = $conn->insert_id;
        $debug_info['soal_' . $soal_counter . '_id'] = $soal_id_baru;
        
        // 4. Jika soal pilihan ganda, duplikasi pilihan jawabannya
        if ($soal['tipeSoal'] === 'pilihan_ganda') {
            $sql_pilihan = "SELECT * FROM pilihan_jawaban WHERE soal_id = ?";
            $stmt_pilihan = $conn->prepare($sql_pilihan);
            $stmt_pilihan->bind_param("i", $soal['id']);
            $stmt_pilihan->execute();
            $pilihan_list = $stmt_pilihan->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $debug_info['soal_' . $soal_counter . '_pilihan_count'] = count($pilihan_list);
            
            foreach ($pilihan_list as $pilihan) {
                $sql_insert_pilihan = "INSERT INTO pilihan_jawaban (
                    soal_id, opsi, teksJawaban, benar
                ) VALUES (?, ?, ?, ?)";
                
                $stmt_insert_pilihan = $conn->prepare($sql_insert_pilihan);
                $stmt_insert_pilihan->bind_param(
                    "issi",
                    $soal_id_baru,
                    $pilihan['opsi'],
                    $pilihan['teksJawaban'],
                    $pilihan['benar']
                );
                
                if (!$stmt_insert_pilihan->execute()) {
                    throw new Exception('Gagal menduplikasi pilihan jawaban: ' . $stmt_insert_pilihan->error);
                }
            }
        }
    }
    
    $debug_info['step'] = 'all_soal_duplicated';
    
    // 5. Update total soal di ujian baru
    $sql_update_total = "UPDATE ujian SET totalSoal = (
        SELECT COUNT(*) FROM soal WHERE ujian_id = ?
    ) WHERE id = ?";
    $stmt_update_total = $conn->prepare($sql_update_total);
    $stmt_update_total->bind_param("ii", $ujian_id_baru, $ujian_id_baru);
    $stmt_update_total->execute();
    
    $debug_info['step'] = 'total_soal_updated';
    
    $conn->commit();
    
    $debug_info['step'] = 'transaction_committed';
    $debug_info['success'] = true;
    
    // Jika debug mode, tampilkan hasil dan jangan redirect
    if ($debug_mode) {
        die(json_encode([
            'success' => true, 
            'message' => 'Ujian berhasil diduplikasi',
            'ujian_id_baru' => $ujian_id_baru,
            'debug' => $debug_info
        ]));
    }
    
    // Redirect ke halaman ujian dengan pesan sukses
    header('Location: ujian-guru.php?duplicated=1');
    exit();
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    $debug_info['step'] = 'error_occurred';
    $debug_info['error_message'] = $e->getMessage();
    $debug_info['error_trace'] = $e->getTraceAsString();
    
    // Jika debug mode, tampilkan error detail
    if ($debug_mode) {
        die(json_encode([
            'success' => false, 
            'message' => $e->getMessage(),
            'debug' => $debug_info
        ]));
    }
    
    // Redirect dengan pesan error
    header('Location: ujian-guru.php?err=dup');
    exit();
}
?>
