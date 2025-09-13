<?php
require_once 'koneksi.php';

class AutoSaveLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Auto save jawaban siswa dengan validasi keamanan
     * @param int $ujian_siswa_id
     * @param int $soal_id
     * @param string $jawaban
     * @param int $siswa_id - untuk validasi keamanan
     * @return array
     */
    public function autoSaveJawaban($ujian_siswa_id, $soal_id, $jawaban, $siswa_id) {
        try {
            // Validasi bahwa ujian_siswa_id milik siswa yang sedang login
            $sqlValidasi = "SELECT us.id, us.ujian_id, us.status, u.tanggalUjian, u.waktuMulai, u.waktuSelesai, u.durasi 
                           FROM ujian_siswa us 
                           JOIN ujian u ON us.ujian_id = u.id 
                           WHERE us.id = ? AND us.siswa_id = ?";
            $stmtValidasi = $this->conn->prepare($sqlValidasi);
            $stmtValidasi->bind_param("ii", $ujian_siswa_id, $siswa_id);
            $stmtValidasi->execute();
            $ujianSiswa = $stmtValidasi->get_result()->fetch_assoc();
            
            if (!$ujianSiswa) {
                return ['success' => false, 'message' => 'Ujian tidak valid atau bukan milik Anda'];
            }
            
            // Cek apakah ujian masih dalam status mengerjakan
            if ($ujianSiswa['status'] !== 'sedang_mengerjakan') {
                return ['success' => false, 'message' => 'Ujian sudah selesai atau belum dimulai'];
            }
            
            // Cek apakah waktu ujian masih valid
            $now = time();
            $mulaiTs = strtotime($ujianSiswa['tanggalUjian'] . ' ' . $ujianSiswa['waktuMulai']);
            $selesaiTs = strtotime($ujianSiswa['tanggalUjian'] . ' ' . $ujianSiswa['waktuSelesai']);
            $durasiDetik = $ujianSiswa['durasi'] * 60;
            
            if ($now > $selesaiTs || $now > ($mulaiTs + $durasiDetik)) {
                return ['success' => false, 'message' => 'Waktu ujian sudah habis'];
            }
            
            // Validasi bahwa soal ada dan milik ujian yang sedang dikerjakan
            $sqlSoal = "SELECT id, tipeSoal FROM soal WHERE id = ? AND ujian_id = ?";
            $stmtSoal = $this->conn->prepare($sqlSoal);
            $stmtSoal->bind_param("ii", $soal_id, $ujianSiswa['ujian_id']);
            $stmtSoal->execute();
            $soal = $stmtSoal->get_result()->fetch_assoc();
            
            if (!$soal) {
                return ['success' => false, 'message' => 'Soal tidak ditemukan'];
            }
            
            // Simpan jawaban
            return $this->simpanJawabanToDatabase($ujian_siswa_id, $soal_id, $jawaban, $soal['tipeSoal']);
            
        } catch (Exception $e) {
            error_log("AutoSave Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem'];
        }
    }
    
    /**
     * Simpan jawaban ke database
     * @param int $ujian_siswa_id
     * @param int $soal_id
     * @param string $jawaban
     * @param string $tipeSoal
     * @return array
     */
    private function simpanJawabanToDatabase($ujian_siswa_id, $soal_id, $jawaban, $tipeSoal) {
        try {
            // Cek apakah jawaban sudah ada
            $sqlCek = "SELECT id FROM jawaban_siswa WHERE ujian_siswa_id = ? AND soal_id = ?";
            $stmtCek = $this->conn->prepare($sqlCek);
            $stmtCek->bind_param("ii", $ujian_siswa_id, $soal_id);
            $stmtCek->execute();
            $existing = $stmtCek->get_result()->fetch_assoc();
            
            // Tentukan apakah ini pilihan ganda
            $pilihanJawaban = null;
            if ($tipeSoal === 'pilihan_ganda' && strlen(trim($jawaban)) === 1) {
                $pilihanJawaban = strtoupper(trim($jawaban));
            }
            
            if ($existing) {
                // Update jawaban yang sudah ada
                if ($pilihanJawaban) {
                    $sql = "UPDATE jawaban_siswa SET jawaban = ?, pilihanJawaban = ?, waktuDijawab = NOW() WHERE id = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("ssi", $jawaban, $pilihanJawaban, $existing['id']);
                } else {
                    $sql = "UPDATE jawaban_siswa SET jawaban = ?, pilihanJawaban = NULL, waktuDijawab = NOW() WHERE id = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("si", $jawaban, $existing['id']);
                }
            } else {
                // Insert jawaban baru
                if ($pilihanJawaban) {
                    $sql = "INSERT INTO jawaban_siswa (ujian_siswa_id, soal_id, jawaban, pilihanJawaban, waktuDijawab) VALUES (?, ?, ?, ?, NOW())";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("iiss", $ujian_siswa_id, $soal_id, $jawaban, $pilihanJawaban);
                } else {
                    $sql = "INSERT INTO jawaban_siswa (ujian_siswa_id, soal_id, jawaban, waktuDijawab) VALUES (?, ?, ?, NOW())";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("iis", $ujian_siswa_id, $soal_id, $jawaban);
                }
            }
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Jawaban berhasil disimpan',
                    'auto_save' => true,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            } else {
                return ['success' => false, 'message' => 'Gagal menyimpan jawaban: ' . $stmt->error];
            }
            
        } catch (Exception $e) {
            error_log("Database Save Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menyimpan ke database'];
        }
    }
    
    /**
     * Get status jawaban untuk semua soal dalam ujian
     * @param int $ujian_siswa_id
     * @param int $siswa_id
     * @return array
     */
    public function getStatusJawaban($ujian_siswa_id, $siswa_id) {
        try {
            // Validasi ujian siswa
            $sqlValidasi = "SELECT ujian_id FROM ujian_siswa WHERE id = ? AND siswa_id = ?";
            $stmtValidasi = $this->conn->prepare($sqlValidasi);
            $stmtValidasi->bind_param("ii", $ujian_siswa_id, $siswa_id);
            $stmtValidasi->execute();
            $ujianSiswa = $stmtValidasi->get_result()->fetch_assoc();
            
            if (!$ujianSiswa) {
                return ['success' => false, 'message' => 'Ujian tidak valid'];
            }
            
            // Get semua soal dan status jawabannya
            $sql = "SELECT s.id as soal_id, s.nomorSoal, s.tipeSoal,
                           js.id as jawaban_id, js.jawaban, js.pilihanJawaban, js.waktuDijawab
                    FROM soal s
                    LEFT JOIN jawaban_siswa js ON s.id = js.soal_id AND js.ujian_siswa_id = ?
                    WHERE s.ujian_id = ?
                    ORDER BY s.nomorSoal";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_siswa_id, $ujianSiswa['ujian_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $status = [];
            while ($row = $result->fetch_assoc()) {
                $isAnswered = false;
                $jawaban = '';
                
                if ($row['jawaban_id']) {
                    if ($row['tipeSoal'] === 'pilihan_ganda') {
                        $isAnswered = !empty($row['pilihanJawaban']) || !empty($row['jawaban']);
                        $jawaban = $row['pilihanJawaban'] ?: $row['jawaban'];
                    } else {
                        $isAnswered = !empty(trim($row['jawaban']));
                        $jawaban = $row['jawaban'];
                    }
                }
                
                $status[$row['soal_id']] = [
                    'nomor_soal' => $row['nomorSoal'],
                    'is_answered' => $isAnswered,
                    'jawaban' => $jawaban,
                    'waktu_dijawab' => $row['waktuDijawab'],
                    'tipe_soal' => $row['tipeSoal']
                ];
            }
            
            return ['success' => true, 'data' => $status];
            
        } catch (Exception $e) {
            error_log("Get Status Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal mengambil status jawaban'];
        }
    }
    
    /**
     * Hapus jawaban tertentu (jika siswa ingin mengosongkan jawaban)
     * @param int $ujian_siswa_id
     * @param int $soal_id
     * @param int $siswa_id
     * @return array
     */
    public function hapusJawaban($ujian_siswa_id, $soal_id, $siswa_id) {
        try {
            // Validasi ujian siswa
            $sqlValidasi = "SELECT id FROM ujian_siswa WHERE id = ? AND siswa_id = ? AND status = 'sedang_mengerjakan'";
            $stmtValidasi = $this->conn->prepare($sqlValidasi);
            $stmtValidasi->bind_param("ii", $ujian_siswa_id, $siswa_id);
            $stmtValidasi->execute();
            
            if ($stmtValidasi->get_result()->num_rows === 0) {
                return ['success' => false, 'message' => 'Ujian tidak valid'];
            }
            
            // Hapus jawaban
            $sql = "DELETE FROM jawaban_siswa WHERE ujian_siswa_id = ? AND soal_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_siswa_id, $soal_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Jawaban berhasil dihapus'];
            } else {
                return ['success' => false, 'message' => 'Gagal menghapus jawaban'];
            }
            
        } catch (Exception $e) {
            error_log("Delete Answer Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus jawaban'];
        }
    }
    
    /**
     * Clear semua jawaban untuk ujian tertentu (untuk reset)
     * @param int $ujian_siswa_id
     * @param int $siswa_id
     * @return array
     */
    public function clearAllAnswers($ujian_siswa_id, $siswa_id) {
        try {
            // Validasi ujian siswa
            $sqlValidasi = "SELECT id FROM ujian_siswa WHERE id = ? AND siswa_id = ? AND status = 'sedang_mengerjakan'";
            $stmtValidasi = $this->conn->prepare($sqlValidasi);
            $stmtValidasi->bind_param("ii", $ujian_siswa_id, $siswa_id);
            $stmtValidasi->execute();
            
            if ($stmtValidasi->get_result()->num_rows === 0) {
                return ['success' => false, 'message' => 'Ujian tidak valid'];
            }
            
            // Hapus semua jawaban
            $sql = "DELETE FROM jawaban_siswa WHERE ujian_siswa_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_siswa_id);
            
            if ($stmt->execute()) {
                $deleted_count = $stmt->affected_rows;
                return [
                    'success' => true, 
                    'message' => "Berhasil menghapus {$deleted_count} jawaban",
                    'deleted_count' => $deleted_count
                ];
            } else {
                return ['success' => false, 'message' => 'Gagal menghapus jawaban'];
            }
            
        } catch (Exception $e) {
            error_log("Clear All Answers Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus semua jawaban'];
        }
    }
}
?>
