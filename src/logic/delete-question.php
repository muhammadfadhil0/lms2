<?php
session_start();
header('Content-Type: application/json');

// Quick validations
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='guru'){
    http_response_code(403); echo json_encode(['success'=>false]); exit();
}
if($_SERVER['REQUEST_METHOD']!=='POST'){
    http_response_code(405); echo json_encode(['success'=>false]); exit();
}

require_once 'koneksi.php';
require_once 'ujian-logic.php';

$ujianLogic = new UjianLogic();
$conn = getConnection();
$soal_id = (int)($_POST['soal_id'] ?? 0);

if(!$soal_id){ 
    echo json_encode(['success'=>false,'message'=>'ID tidak valid']); 
    exit(); 
}

// Get ujian_id and validate ownership in one query
$stmt = $conn->prepare('SELECT s.ujian_id, u.guru_id FROM soal s JOIN ujian u ON s.ujian_id = u.id WHERE s.id = ?');
$stmt->bind_param('i', $soal_id); 
$stmt->execute(); 
$row = $stmt->get_result()->fetch_assoc();

if(!$row){ 
    echo json_encode(['success'=>false,'message'=>'Soal tidak ditemukan']); 
    exit(); 
}

$ujian_id = (int)$row['ujian_id'];
$guru_id = $_SESSION['user']['id'];

if((int)$row['guru_id'] !== $guru_id){
    echo json_encode(['success'=>false,'message'=>'Tidak berhak']); 
    exit(); 
}

$conn->begin_transaction();
try {
    // Delete related data first
    $d1 = $conn->prepare('DELETE FROM pilihan_jawaban WHERE soal_id = ?'); 
    $d1->bind_param('i', $soal_id); 
    $d1->execute();
    
    // Delete the question
    $d2 = $conn->prepare('DELETE FROM soal WHERE id = ?'); 
    $d2->bind_param('i', $soal_id); 
    $d2->execute();
    
    // Update ujian stats efficiently
    $conn->query("UPDATE ujian SET 
        totalSoal = (SELECT COUNT(*) FROM soal WHERE ujian_id = $ujian_id), 
        totalPoin = (SELECT COALESCE(SUM(poin), 0) FROM soal WHERE ujian_id = $ujian_id) 
        WHERE id = $ujian_id");
    
    // Handle autoScore redistribution if needed
    $autoScoreCheck = $conn->query("SELECT autoScore FROM ujian WHERE id = $ujian_id");
    $autoScoreRow = $autoScoreCheck->fetch_assoc();
    
    if($autoScoreRow && (int)$autoScoreRow['autoScore'] === 1){
        // Get multiple choice questions
        $mcResult = $conn->query("SELECT id FROM soal WHERE ujian_id = $ujian_id AND tipeSoal = 'pilihan_ganda' ORDER BY nomorSoal ASC");
        $mcIds = [];
        while($mcRow = $mcResult->fetch_assoc()){
            $mcIds[] = (int)$mcRow['id'];
        }
        
        $count = count($mcIds);
        if($count > 0){
            // Distribute 100 points evenly
            $basePoints = intdiv(100, $count);
            $remainder = 100 - ($basePoints * $count);
            
            foreach($mcIds as $index => $mcId){
                $points = $basePoints + ($index < $remainder ? 1 : 0);
                $conn->query("UPDATE soal SET poin = $points WHERE id = $mcId");
            }
            $conn->query("UPDATE ujian SET totalPoin = 100 WHERE id = $ujian_id");
        } else {
            $conn->query("UPDATE ujian SET totalPoin = 0 WHERE id = $ujian_id");
        }
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);
} catch(Exception $e){
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
