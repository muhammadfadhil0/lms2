<?php
require_once 'koneksi.php';

class SoalLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Membuat soal pilihan ganda
    public function buatSoalPilihanGanda($ujian_id, $nomorSoal, $pertanyaan, $pilihan, $kunciJawaban, $poin = 10) {
        try {
            $this->conn->begin_transaction();
            
            // Insert soal
            $sql = "INSERT INTO soal (ujian_id, nomorSoal, pertanyaan, tipeSoal, kunciJawaban, poin) 
                    VALUES (?, ?, ?, 'pilihan_ganda', ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iissi", $ujian_id, $nomorSoal, $pertanyaan, $kunciJawaban, $poin);
            $stmt->execute();
            
            $soal_id = $this->conn->insert_id;
            
            // Insert pilihan jawaban
            $sql_pilihan = "INSERT INTO pilihan_jawaban (soal_id, opsi, teksJawaban, benar) VALUES (?, ?, ?, ?)";
            $stmt_pilihan = $this->conn->prepare($sql_pilihan);
            
            foreach ($pilihan as $opsi => $teks) {
                $benar = ($opsi == $kunciJawaban) ? 1 : 0;
                $stmt_pilihan->bind_param("issi", $soal_id, $opsi, $teks, $benar);
                $stmt_pilihan->execute();
            }
            
            // Update total soal di ujian
            $this->updateTotalSoalUjian($ujian_id);
            
            $this->conn->commit();
            // Jika ujian autoScore, redistribusi poin
            $this->redistributeAutoScoreIfNeeded($ujian_id);
            return ['success' => true, 'message' => 'Soal berhasil dibuat', 'soal_id' => $soal_id];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Membuat soal jawaban singkat/panjang
    public function buatSoalJawaban($ujian_id, $nomorSoal, $pertanyaan, $tipeSoal, $kunciJawaban = '', $poin = 10) {
        try {
            $sql = "INSERT INTO soal (ujian_id, nomorSoal, pertanyaan, tipeSoal, kunciJawaban, poin) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iisssi", $ujian_id, $nomorSoal, $pertanyaan, $tipeSoal, $kunciJawaban, $poin);
            
            if ($stmt->execute()) {
                $soal_id = $this->conn->insert_id;
                $this->updateTotalSoalUjian($ujian_id);
                $this->redistributeAutoScoreIfNeeded($ujian_id);
                return ['success' => true, 'message' => 'Soal berhasil dibuat', 'soal_id' => $soal_id];
            } else {
                return ['success' => false, 'message' => 'Gagal membuat soal'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    // Redistribute points to sum 100 if ujian.autoScore=1 and only multiple choice questions are counted
    private function redistributeAutoScoreIfNeeded($ujian_id){
        try {
            $res = $this->conn->prepare('SELECT autoScore FROM ujian WHERE id=?');
            $res->bind_param('i',$ujian_id); $res->execute(); $r=$res->get_result()->fetch_assoc();
            if(!$r || !(int)$r['autoScore']) return; // not enabled
            // Ambil semua soal multiple_choice (pilihan_ganda)
            $q = $this->conn->prepare("SELECT id FROM soal WHERE ujian_id=? AND tipeSoal='pilihan_ganda' ORDER BY nomorSoal ASC");
            $q->bind_param('i',$ujian_id); $q->execute(); $resSet=$q->get_result();
            $ids = []; while($row=$resSet->fetch_assoc()){ $ids[] = (int)$row['id']; }
            $n = count($ids); if($n===0) return; // nothing to distribute
            $base = intdiv(100, $n); $rem = 100 - ($base*$n);
            foreach($ids as $i=>$sid){ $val = $base + ($i < $rem ? 1 : 0); $up = $this->conn->prepare('UPDATE soal SET poin=? WHERE id=?'); $up->bind_param('ii',$val,$sid); $up->execute(); }
            // Update aggregate
            $this->updateTotalSoalUjian($ujian_id);
        } catch(Exception $e){ /* ignore */ }
    }
    
    // Update total soal dan poin di ujian
    private function updateTotalSoalUjian($ujian_id) {
        // Jika ujian autoScore aktif, hanya hitung soal pilihan ganda (soal lain dianggap non-aktif)
        $auto = 0; $chk = $this->conn->prepare('SELECT autoScore FROM ujian WHERE id=?');
        $chk->bind_param('i',$ujian_id); $chk->execute(); $res=$chk->get_result()->fetch_assoc();
        if($res){ $auto = (int)$res['autoScore']; }
        if($auto){
            $sql = "UPDATE ujian SET 
                        totalSoal = (SELECT COUNT(*) FROM soal WHERE ujian_id = ? AND tipeSoal='pilihan_ganda'),
                        totalPoin = (SELECT COALESCE(SUM(poin),0) FROM soal WHERE ujian_id = ? AND tipeSoal='pilihan_ganda')
                    WHERE id = ?";
        } else {
            $sql = "UPDATE ujian SET 
                        totalSoal = (SELECT COUNT(*) FROM soal WHERE ujian_id = ?),
                        totalPoin = (SELECT COALESCE(SUM(poin),0) FROM soal WHERE ujian_id = ?)
                    WHERE id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $ujian_id, $ujian_id, $ujian_id);
        $stmt->execute();
    }
    
    // Mendapatkan soal berdasarkan ujian
    public function getSoalByUjian($ujian_id) {
        try {
            $sql = "SELECT s.*, 
                           GROUP_CONCAT(
                               CONCAT(pj.opsi, ':', pj.teksJawaban, ':', pj.benar) 
                               ORDER BY pj.opsi SEPARATOR '|'
                           ) as pilihan
                    FROM soal s 
                    LEFT JOIN pilihan_jawaban pj ON s.id = pj.soal_id
                    WHERE s.ujian_id = ?
                    GROUP BY s.id
                    ORDER BY s.nomorSoal ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_id);
            $stmt->execute();
            
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Format pilihan jawaban
            foreach ($result as &$soal) {
                if ($soal['pilihan']) {
                    $pilihan = [];
                    $pilihanArray = explode('|', $soal['pilihan']);
                    foreach ($pilihanArray as $p) {
                        $parts = explode(':', $p);
                        if (count($parts) >= 3) {
                            $pilihan[$parts[0]] = [
                                'teks' => $parts[1],
                                'benar' => $parts[2] == '1'
                            ];
                        }
                    }
                    $soal['pilihan_array'] = $pilihan;
                }
            }
            
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Simpan jawaban siswa
    public function simpanJawaban($ujian_siswa_id, $soal_id, $jawaban, $pilihanJawaban = null) {
        try {
            // Cek apakah sudah ada jawaban
            $sql = "SELECT id FROM jawaban_siswa WHERE ujian_siswa_id = ? AND soal_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_siswa_id, $soal_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                // Update jawaban yang sudah ada
                $sql = "UPDATE jawaban_siswa SET jawaban = ?, pilihanJawaban = ?, waktuDijawab = NOW() 
                        WHERE ujian_siswa_id = ? AND soal_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ssii", $jawaban, $pilihanJawaban, $ujian_siswa_id, $soal_id);
            } else {
                // Insert jawaban baru
                $sql = "INSERT INTO jawaban_siswa (ujian_siswa_id, soal_id, jawaban, pilihanJawaban) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("iiss", $ujian_siswa_id, $soal_id, $jawaban, $pilihanJawaban);
            }
            
            if ($stmt->execute()) {
                // Auto grading untuk pilihan ganda
                if ($pilihanJawaban) {
                    $this->autoGrading($ujian_siswa_id, $soal_id, $pilihanJawaban);
                }
                return ['success' => true, 'message' => 'Jawaban berhasil disimpan'];
            } else {
                return ['success' => false, 'message' => 'Gagal menyimpan jawaban'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Auto grading untuk pilihan ganda
    private function autoGrading($ujian_siswa_id, $soal_id, $pilihanJawaban) {
        try {
            // Cek kunci jawaban
            $sql = "SELECT kunciJawaban, poin FROM soal WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $soal_id);
            $stmt->execute();
            $soal = $stmt->get_result()->fetch_assoc();
            
            $benar = ($pilihanJawaban == $soal['kunciJawaban']) ? 1 : 0;
            $poin = $benar ? $soal['poin'] : 0;
            
            // Update jawaban dengan penilaian
            $sql = "UPDATE jawaban_siswa SET benar = ?, poin = ? 
                    WHERE ujian_siswa_id = ? AND soal_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("idii", $benar, $poin, $ujian_siswa_id, $soal_id);
            $stmt->execute();
            
        } catch (Exception $e) {
            // Log error
        }
    }
    
    // Mendapatkan jawaban siswa
    public function getJawabanSiswa($ujian_siswa_id) {
        try {
            $sql = "SELECT js.*, s.pertanyaan, s.tipeSoal, s.kunciJawaban
                    FROM jawaban_siswa js
                    JOIN soal s ON js.soal_id = s.id
                    WHERE js.ujian_siswa_id = ?
                    ORDER BY s.nomorSoal ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_siswa_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Update soal
    public function updateSoal($soal_id, $pertanyaan, $kunciJawaban, $poin) {
        try {
            $sql = "UPDATE soal SET pertanyaan = ?, kunciJawaban = ?, poin = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssii", $pertanyaan, $kunciJawaban, $poin, $soal_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Soal berhasil diupdate'];
            } else {
                return ['success' => false, 'message' => 'Gagal mengupdate soal'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Hapus soal
    public function hapusSoal($soal_id) {
        try {
            $this->conn->begin_transaction();
            
            // Hapus pilihan jawaban
            $sql = "DELETE FROM pilihan_jawaban WHERE soal_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $soal_id);
            $stmt->execute();
            
            // Hapus soal
            $sql = "DELETE FROM soal WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $soal_id);
            $stmt->execute();
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Soal berhasil dihapus'];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>
