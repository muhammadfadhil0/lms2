<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='guru'){
    header('Location: ../front/ujian-guru.php'); exit();
}
$guru_id = $_SESSION['user']['id'];
if(!isset($_GET['id'])){ header('Location: ../front/ujian-guru.php'); exit(); }
$ujian_id = (int)$_GET['id'];
require_once 'koneksi.php';
$conn = getConnection();
// Ambil ujian asli
$stmt = $conn->prepare('SELECT * FROM ujian WHERE id=? AND guru_id=?');
$stmt->bind_param('ii',$ujian_id,$guru_id);
$stmt->execute();
$ujian = $stmt->get_result()->fetch_assoc();
if(!$ujian){ header('Location: ../front/ujian-guru.php'); exit(); }
$conn->begin_transaction();
try {
    // Insert ujian baru (status draft agar bisa diedit dulu)
    $stmt = $conn->prepare("INSERT INTO ujian (namaUjian, deskripsi, kelas_id, guru_id, mataPelajaran, tanggalUjian, waktuMulai, waktuSelesai, durasi, status, shuffleQuestions, showScore, autoScore) VALUES (?,?,?,?,?,?,?,?,?,'draft',?,?,?)");
    $namaBaru = $ujian['namaUjian'].' (Duplikasi)';
    $stmt->bind_param('ssisssssiiii', $namaBaru, $ujian['deskripsi'], $ujian['kelas_id'], $guru_id, $ujian['mataPelajaran'], $ujian['tanggalUjian'], $ujian['waktuMulai'], $ujian['waktuSelesai'], $ujian['durasi'], $ujian['shuffleQuestions'], $ujian['showScore'], $ujian['autoScore']);
    $stmt->execute();
    $newExamId = $conn->insert_id;
    // Salin soal
    $soalMap = [];
    $res = $conn->prepare('SELECT * FROM soal WHERE ujian_id=? ORDER BY nomorSoal ASC');
    $res->bind_param('i',$ujian_id); $res->execute(); $rs = $res->get_result();
    while($s = $rs->fetch_assoc()){
        $stmtS = $conn->prepare('INSERT INTO soal (ujian_id, nomorSoal, pertanyaan, tipeSoal, kunciJawaban, poin, autoGrading) VALUES (?,?,?,?,?,?,?)');
        $stmtS->bind_param('iisssii', $newExamId, $s['nomorSoal'], $s['pertanyaan'], $s['tipeSoal'], $s['kunciJawaban'], $s['poin'], $s['autoGrading']);
        $stmtS->execute();
        $newSoalId = $conn->insert_id;
        $soalMap[$s['id']] = $newSoalId;
        // Pilihan jawaban bila tipe pilihan_ganda
        if($s['tipeSoal']==='pilihan_ganda'){
            $pj = $conn->prepare('SELECT * FROM pilihan_jawaban WHERE soal_id=?');
            $pj->bind_param('i',$s['id']); $pj->execute(); $pjRes=$pj->get_result();
            while($p=$pjRes->fetch_assoc()){
                $insP = $conn->prepare('INSERT INTO pilihan_jawaban (soal_id, opsi, teksJawaban, benar) VALUES (?,?,?,?)');
                $insP->bind_param('issi',$newSoalId,$p['opsi'],$p['teksJawaban'],$p['benar']);
                $insP->execute();
            }
        }
    }
    // Update agregat totalSoal & totalPoin
    $conn->query("UPDATE ujian SET totalSoal=(SELECT COUNT(*) FROM soal WHERE ujian_id=$newExamId), totalPoin=(SELECT COALESCE(SUM(poin),0) FROM soal WHERE ujian_id=$newExamId) WHERE id=$newExamId");
    $conn->commit();
    header('Location: ../front/buat-ujian-guru.php?ujian_id='.$newExamId.'&duplicated=1');
    exit();
}catch(Exception $e){
    $conn->rollback();
    header('Location: ../front/ujian-guru.php?err=dup');
    exit();
}
