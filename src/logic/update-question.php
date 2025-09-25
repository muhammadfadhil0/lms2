<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='guru'){
    http_response_code(403); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit();
}
if($_SERVER['REQUEST_METHOD']!=='POST'){
    http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit();
}
require_once 'ujian-logic.php';
require_once 'soal-logic.php';
require_once 'koneksi.php';
$ujianLogic = new UjianLogic();
$soalLogic = new SoalLogic();
$guru_id = $_SESSION['user']['id'];
$soal_id = (int)($_POST['soal_id'] ?? 0);
$pertanyaan = trim($_POST['pertanyaan'] ?? '');
$poin = (int)($_POST['poin'] ?? 10);
$tipe = $_POST['tipe'] ?? '';
$kunci = trim($_POST['kunci'] ?? '');
$pilihan = $_POST['pilihan'] ?? [];
$kunciPilihan = $_POST['kunci_pilihan'] ?? '';
if(!$soal_id || !$pertanyaan || !$tipe){ echo json_encode(['success'=>false,'message'=>'Data tidak lengkap']); exit(); }
$conn = getConnection();
// Ambil soal + ujian id
$stmt = $conn->prepare('SELECT ujian_id, tipeSoal FROM soal WHERE id=?');
$stmt->bind_param('i',$soal_id); $stmt->execute(); $row = $stmt->get_result()->fetch_assoc();
if(!$row){ echo json_encode(['success'=>false,'message'=>'Soal tidak ditemukan']); exit(); }
$ujian_id = (int)$row['ujian_id'];
$ujian = $ujianLogic->getUjianByIdAndGuru($ujian_id,$guru_id);
if(!$ujian){ echo json_encode(['success'=>false,'message'=>'Tidak berhak']); exit(); }
$conn->begin_transaction();
try {
    if($tipe==='multiple_choice'){
        if(!$kunciPilihan || empty($pilihan) || !isset($pilihan[$kunciPilihan])){ throw new Exception('Kunci jawaban tidak valid'); }
        // Update soal
        $stmt = $conn->prepare('UPDATE soal SET pertanyaan=?, tipeSoal="pilihan_ganda", kunciJawaban=?, poin=? WHERE id=?');
        $stmt->bind_param('ssii',$pertanyaan,$kunciPilihan,$poin,$soal_id); $stmt->execute();
        // Hapus pilihan lama
        $del = $conn->prepare('DELETE FROM pilihan_jawaban WHERE soal_id=?');
        $del->bind_param('i',$soal_id); $del->execute();
        // Insert baru
        $ins = $conn->prepare('INSERT INTO pilihan_jawaban (soal_id, opsi, teksJawaban, benar) VALUES (?,?,?,?)');
        foreach($pilihan as $opsi=>$teks){ $benar = ($opsi===$kunciPilihan)?1:0; $ins->bind_param('issi',$soal_id,$opsi,$teks,$benar); $ins->execute(); }
    } elseif(in_array($tipe,['short_answer','long_answer'])){
        $tipeDb = $tipe==='short_answer'?'isian_singkat':'essay';
        $stmt = $conn->prepare('UPDATE soal SET pertanyaan=?, tipeSoal=?, kunciJawaban=?, poin=? WHERE id=?');
        $stmt->bind_param('sssii',$pertanyaan,$tipeDb,$kunci,$poin,$soal_id); $stmt->execute();
        // Hapus pilihan jika sebelumnya PG
        $del = $conn->prepare('DELETE FROM pilihan_jawaban WHERE soal_id=?');
        $del->bind_param('i',$soal_id); $del->execute();
    } else {
        throw new Exception('Tipe tidak dikenal');
    }
    // Update agregat
        $conn->query("UPDATE ujian SET totalSoal=(SELECT COUNT(*) FROM soal WHERE ujian_id=$ujian_id), totalPoin=(SELECT COALESCE(SUM(poin),0) FROM soal WHERE ujian_id=$ujian_id) WHERE id=$ujian_id");
        // Redistribute if autoScore
        if($res=$conn->prepare('SELECT autoScore FROM ujian WHERE id=?')){ $res->bind_param('i',$ujian_id); $res->execute(); $flag=$res->get_result()->fetch_assoc(); if($flag && (int)$flag['autoScore']){
            // replicate redistribution logic (lightweight - not reusing class private method here)
            if($sids=$conn->prepare("SELECT id FROM soal WHERE ujian_id=? AND tipeSoal='pilihan_ganda' ORDER BY nomorSoal ASC")){
                $sids->bind_param('i',$ujian_id); $sids->execute(); $rs=$sids->get_result(); $ids=[]; while($r=$rs->fetch_assoc()){ $ids[]=(int)$r['id']; }
                $n=count($ids); if($n>0){ $base=intdiv(100,$n); $rem=100-($base*$n); foreach($ids as $i=>$sid){ $val=$base+($i<$rem?1:0); $up=$conn->prepare('UPDATE soal SET poin=? WHERE id=?'); $up->bind_param('ii',$val,$sid); $up->execute(); } $conn->query("UPDATE ujian SET totalPoin=100 WHERE id=$ujian_id"); }
            }
        } }
    $conn->commit();
    echo json_encode(['success'=>true]);
}catch(Exception $e){
    $conn->rollback();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
