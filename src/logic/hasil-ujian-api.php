<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'ujian-logic.php';
require_once 'soal-logic.php';
require_once 'koneksi.php';

$ujianLogic = new UjianLogic();
$soalLogic = new SoalLogic();
$conn = getConnection();

$action = $_POST['action'] ?? '';
$ujian_id = (int)($_POST['ujian_id'] ?? 0);
$guru_id = $_SESSION['user']['id'];

// Validasi ujian milik guru
$ujian = $ujianLogic->getUjianByIdAndGuru($ujian_id, $guru_id);
if (!$ujian) {
    echo json_encode(['success' => false, 'message' => 'Ujian tidak ditemukan']);
    exit();
}

try {
    switch ($action) {
        case 'get_detail_jawaban':
            $ujian_siswa_id = (int)($_POST['ujian_siswa_id'] ?? 0);
            $detail = $ujianLogic->getDetailJawabanSiswa($ujian_siswa_id);
            $siswa = $conn->prepare("SELECT namaLengkap, email, fotoProfil FROM users WHERE id = (SELECT siswa_id FROM ujian_siswa WHERE id = ?)");
            $siswa->bind_param('i', $ujian_siswa_id);
            $siswa->execute();
            $siswa_data = $siswa->get_result()->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'data' => $detail,
                'siswa_nama' => $siswa_data['namaLengkap'] ?? 'Unknown',
                'siswa_email' => $siswa_data['email'] ?? null,
                'siswa_foto' => $siswa_data['fotoProfil'] ?? null
            ]);
            break;

        case 'get_swipe_data':
            $data = $ujianLogic->getDataKoreksiSwipe($ujian_id);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'get_formulir_data':
            // Get all students and their exam data for this ujian
            $sql = "SELECT 
                        us.id as ujian_siswa_id,
                        u.namaLengkap as nama,
                        u.fotoProfil,
                        us.totalNilai as nilai,
                        us.status,
                        us.jumlahBenar,
                        us.jumlahSalah
                    FROM ujian_siswa us 
                    JOIN users u ON us.siswa_id = u.id 
                    WHERE us.ujian_id = ? 
                    ORDER BY u.namaLengkap";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $ujian_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'periksa_otomatis_pg':
            $conn->begin_transaction();
            
            // Get all pilihan ganda questions for this ujian
            $sql = "SELECT s.id, s.kunciJawaban, s.poin FROM soal s WHERE s.ujian_id = ? AND s.tipeSoal = 'pilihan_ganda'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $ujian_id);
            $stmt->execute();
            $soal_pg = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $updated = 0;
            foreach ($soal_pg as $soal) {
                // Update all answers for this question using ujian_id and siswa_id
                $sql = "UPDATE jawaban_siswa js 
                       SET js.benar = CASE WHEN js.pilihanJawaban = ? THEN 1 ELSE 0 END,
                           js.poin = CASE WHEN js.pilihanJawaban = ? THEN ? ELSE 0 END
                       WHERE js.ujian_id = ? AND js.soal_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssiii', $soal['kunciJawaban'], $soal['kunciJawaban'], $soal['poin'], $ujian_id, $soal['id']);
                $stmt->execute();
                $updated += $stmt->affected_rows;
            }
            
            // Recalculate total scores for all students
            $sql = "UPDATE ujian_siswa us 
                   SET us.jumlahBenar = (
                       SELECT COUNT(*) FROM jawaban_siswa js WHERE js.ujian_id = us.ujian_id AND js.siswa_id = us.siswa_id AND js.benar = 1
                   ),
                   us.jumlahSalah = (
                       SELECT COUNT(*) FROM jawaban_siswa js WHERE js.ujian_id = us.ujian_id AND js.siswa_id = us.siswa_id AND js.benar = 0
                   ),
                   us.totalNilai = (
                       SELECT COALESCE(SUM(js.poin), 0) FROM jawaban_siswa js WHERE js.ujian_id = us.ujian_id AND js.siswa_id = us.siswa_id
                   )
                   WHERE us.ujian_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $ujian_id);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Berhasil memperbarui $updated jawaban"]);
            break;

        case 'save_manual_score':
            $ujian_siswa_id = (int)($_POST['ujian_siswa_id'] ?? 0);
            $soal_id = (int)($_POST['soal_id'] ?? 0);
            $benar = (int)($_POST['benar'] ?? 0);
            $poin = (float)($_POST['poin'] ?? 0);
            
            // Validasi ujian_siswa_id belongs to this ujian
            $check = $conn->prepare("SELECT id FROM ujian_siswa WHERE id = ? AND ujian_id = ?");
            $check->bind_param('ii', $ujian_siswa_id, $ujian_id);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ujian_siswa_id']);
                break;
            }
            
            $conn->begin_transaction();
            
            // Update jawaban_siswa
            $sql = "UPDATE jawaban_siswa SET benar = ?, poin = ? WHERE ujian_siswa_id = ? AND soal_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('idii', $benar, $poin, $ujian_siswa_id, $soal_id);
            $stmt->execute();
            
            // Recalculate totals for this student
            $sql = "UPDATE ujian_siswa 
                   SET jumlahBenar = (
                       SELECT COUNT(*) FROM jawaban_siswa WHERE ujian_siswa_id = ? AND benar = 1
                   ),
                   jumlahSalah = (
                       SELECT COUNT(*) FROM jawaban_siswa WHERE ujian_siswa_id = ? AND benar = 0
                   ),
                   totalNilai = (
                       SELECT COALESCE(SUM(poin), 0) FROM jawaban_siswa WHERE ujian_siswa_id = ?
                   )
                   WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iiii', $ujian_siswa_id, $ujian_siswa_id, $ujian_siswa_id, $ujian_siswa_id);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Nilai berhasil disimpan']);
            break;

        case 'batch_save_scores':
            $scores = json_decode($_POST['scores'] ?? '[]', true);
            if (empty($scores)) {
                echo json_encode(['success' => false, 'message' => 'Data kosong']);
                break;
            }
            
            $conn->begin_transaction();
            $updated = 0;
            
            foreach ($scores as $score) {
                $ujian_siswa_id = (int)($score['ujian_siswa_id'] ?? 0);
                $soal_id = (int)($score['soal_id'] ?? 0);
                $benar = (int)($score['benar'] ?? 0);
                $poin = (float)($score['poin'] ?? 0);
                
                if ($ujian_siswa_id && $soal_id) {
                    // Check if jawaban exists, create if not
                    $check = $conn->prepare("SELECT id FROM jawaban_siswa WHERE ujian_siswa_id = ? AND soal_id = ?");
                    $check->bind_param('ii', $ujian_siswa_id, $soal_id);
                    $check->execute();
                    
                    if ($check->get_result()->num_rows > 0) {
                        // Update existing
                        $sql = "UPDATE jawaban_siswa SET benar = ?, poin = ? WHERE ujian_siswa_id = ? AND soal_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('idii', $benar, $poin, $ujian_siswa_id, $soal_id);
                    } else {
                        // Insert new
                        $sql = "INSERT INTO jawaban_siswa (ujian_siswa_id, soal_id, jawaban, benar, poin) VALUES (?, ?, '', ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('iiid', $ujian_siswa_id, $soal_id, $benar, $poin);
                    }
                    
                    if ($stmt->execute()) {
                        $updated++;
                    }
                }
            }
            
            // Recalculate all totals for this ujian
            $sql = "UPDATE ujian_siswa us
                   SET us.jumlahBenar = (
                       SELECT COUNT(*) FROM jawaban_siswa js WHERE js.ujian_siswa_id = us.id AND js.benar = 1
                   ),
                   us.jumlahSalah = (
                       SELECT COUNT(*) FROM jawaban_siswa js WHERE js.ujian_siswa_id = us.id AND js.benar = 0
                   ),
                   us.totalNilai = (
                       SELECT COALESCE(SUM(js.poin), 0) FROM jawaban_siswa js WHERE js.ujian_siswa_id = us.id
                   )
                   WHERE us.ujian_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $ujian_id);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Berhasil menyimpan $updated nilai"]);
            break;

        case 'update_nilai':
            $ujian_siswa_id = (int)($_POST['ujian_siswa_id'] ?? 0);
            $nilai = (float)($_POST['nilai'] ?? 0);
            
            // Validasi ujian_siswa_id belongs to this ujian
            $check = $conn->prepare("SELECT id FROM ujian_siswa WHERE id = ? AND ujian_id = ?");
            $check->bind_param('ii', $ujian_siswa_id, $ujian_id);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ujian_siswa_id']);
                break;
            }
            
            // Update nilai
            $sql = "UPDATE ujian_siswa SET totalNilai = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('di', $nilai, $ujian_siswa_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Nilai berhasil diupdate']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal mengupdate nilai']);
            }
            break;

        case 'update_status':
            $ujian_siswa_id = (int)($_POST['ujian_siswa_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            // Validasi status
            if (!in_array($status, ['belum_dinilai', 'sudah_dinilai'])) {
                echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
                break;
            }
            
            // Validasi ujian_siswa_id belongs to this ujian
            $check = $conn->prepare("SELECT id FROM ujian_siswa WHERE id = ? AND ujian_id = ?");
            $check->bind_param('ii', $ujian_siswa_id, $ujian_id);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ujian_siswa_id']);
                break;
            }
            
            // Update status
            $sql = "UPDATE ujian_siswa SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $status, $ujian_siswa_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal mengupdate status']);
            }
            break;

        case 'save_corrections':
            $ujian_siswa_id = (int)($_POST['ujian_siswa_id'] ?? 0);
            $corrections = json_decode($_POST['corrections'] ?? '{}', true);

            if ($ujian_siswa_id <= 0 || empty($corrections)) {
                echo json_encode(['success' => false, 'message' => 'Data koreksi tidak valid']);
                break;
            }

            $conn->begin_transaction();

            try {
                // Get siswa_id from ujian_siswa
                $sql = "SELECT siswa_id FROM ujian_siswa WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $ujian_siswa_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if (!$result) {
                    throw new Exception('Data ujian siswa tidak ditemukan');
                }
                $siswa_id = $result['siswa_id'];

                // Update corrections for each soal
                foreach ($corrections as $soal_id => $correction) {
                    $benar = $correction['benar'] === 'null' ? null : (int)$correction['benar'];
                    $poin = (float)$correction['poin'];
                    $catatan = $correction['catatan'] ?? '';

                    // Update or insert jawaban_siswa
                    $sql = "INSERT INTO jawaban_siswa (ujian_id, siswa_id, soal_id, benar, poin, catatan) 
                            VALUES (?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE 
                            benar = VALUES(benar), 
                            poin = VALUES(poin), 
                            catatan = VALUES(catatan)";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iiiids", $ujian_id, $siswa_id, $soal_id, $benar, $poin, $catatan);
                    
                    if (!$stmt->execute()) {
                        throw new Exception('Gagal menyimpan koreksi soal ID: ' . $soal_id);
                    }
                }

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Koreksi berhasil disimpan']);

            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'calculate_final_score':
            $ujian_siswa_id = (int)($_POST['ujian_siswa_id'] ?? 0);

            if ($ujian_siswa_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID ujian siswa tidak valid']);
                break;
            }

            try {
                // Get siswa_id and ujian_id from ujian_siswa
                $sql = "SELECT siswa_id, ujian_id FROM ujian_siswa WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $ujian_siswa_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if (!$result) {
                    throw new Exception('Data ujian siswa tidak ditemukan');
                }
                
                $siswa_id = $result['siswa_id'];
                $current_ujian_id = $result['ujian_id'];

                // Calculate total scores
                $sql = "SELECT 
                            COUNT(*) as total_soal,
                            SUM(CASE WHEN js.benar = 1 THEN 1 ELSE 0 END) as jumlah_benar,
                            SUM(CASE WHEN js.benar = 0 THEN 1 ELSE 0 END) as jumlah_salah,
                            COALESCE(SUM(js.poin), 0) as total_poin,
                            SUM(s.poin) as max_poin
                        FROM soal s
                        LEFT JOIN jawaban_siswa js ON s.id = js.soal_id AND js.ujian_id = ? AND js.siswa_id = ?
                        WHERE s.ujian_id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iii", $current_ujian_id, $siswa_id, $current_ujian_id);
                $stmt->execute();
                $scores = $stmt->get_result()->fetch_assoc();

                // Calculate final score (percentage)
                $total_nilai = $scores['max_poin'] > 0 ? ($scores['total_poin'] / $scores['max_poin']) * 100 : 0;

                // Update ujian_siswa with final scores
                $sql = "UPDATE ujian_siswa SET 
                            totalNilai = ?, 
                            jumlahBenar = ?, 
                            jumlahSalah = ?,
                            status = 'selesai'
                        WHERE id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("diii", $total_nilai, $scores['jumlah_benar'], $scores['jumlah_salah'], $ujian_siswa_id);
                
                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Nilai akhir berhasil dihitung',
                        'data' => [
                            'totalNilai' => round($total_nilai, 2),
                            'jumlahBenar' => $scores['jumlah_benar'],
                            'jumlahSalah' => $scores['jumlah_salah']
                        ]
                    ]);
                } else {
                    throw new Exception('Gagal mengupdate nilai akhir');
                }

            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
            break;
    }
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
