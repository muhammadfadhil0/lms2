<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='guru'){
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}
if($_SERVER['REQUEST_METHOD']!=='POST'){
    echo json_encode(['success'=>false,'message'=>'Invalid method']); exit;
}
$ujian_id = (int)($_POST['ujian_id'] ?? 0);
if($ujian_id<=0){ echo json_encode(['success'=>false,'message'=>'ID tidak valid']); exit; }
require_once 'koneksi.php';
$conn = getConnection();
// Hapus seluruh relasi terkait ujian (jawaban siswa, ujian_siswa, soal, pilihan_jawaban)
$conn->begin_transaction();
try {
    // Ambil semua soal id
    $soalIds = [];
    if($res=$conn->prepare('SELECT id FROM soal WHERE ujian_id=?')){ $res->bind_param('i',$ujian_id); $res->execute(); $r=$res->get_result(); while($row=$r->fetch_assoc()){ $soalIds[]=$row['id']; } }
    // Hapus jawaban siswa per soal
    if($soalIds){
        $in = implode(',', array_fill(0,count($soalIds),'?'));
        $types = str_repeat('i', count($soalIds));
        $stmt = $conn->prepare("DELETE FROM jawaban_siswa WHERE soal_id IN ($in)");
        $stmt->bind_param($types, ...$soalIds);
        $stmt->execute();
        // Hapus pilihan jawaban
        $stmt = $conn->prepare("DELETE FROM pilihan_jawaban WHERE soal_id IN ($in)");
        $stmt->bind_param($types, ...$soalIds);
        $stmt->execute();
    }
    // Hapus soal
    $stmt = $conn->prepare('DELETE FROM soal WHERE ujian_id=?');
    if($stmt){ $stmt->bind_param('i',$ujian_id); $stmt->execute(); }
    // Hapus relasi ujian_siswa (jawaban siswa terhadap ujian sudah dihapus by soal)
    $stmt = $conn->prepare('DELETE FROM ujian_siswa WHERE ujian_id=?');
    if($stmt){ $stmt->bind_param('i',$ujian_id); $stmt->execute(); }
    $stmt2 = $conn->prepare('DELETE FROM ujian WHERE id=? AND guru_id=?');
    $guru_id = $_SESSION['user']['id'];
    if(!$stmt2){ throw new Exception('Prepare gagal'); }
    $stmt2->bind_param('ii',$ujian_id,$guru_id);
    $stmt2->execute();
    if($stmt2->affected_rows<1){ throw new Exception('Ujian tidak ditemukan / bukan milik Anda'); }
    $conn->commit();
    echo json_encode(['success'=>true]);
} catch(Exception $e){
    $conn->rollback();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
