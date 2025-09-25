<?php
require_once __DIR__ . '/koneksi.php';

class AutoSaveLogic {
    private $conn;
    public $lastError = null;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function autoSaveJawaban($ujian_siswa_id, $soal_id, $jawaban, $siswa_id) {
        try {
            // Validasi ujian siswa
            $sql = "SELECT us.id, us.ujian_id, us.status, u.tanggalUjian, u.waktuMulai, u.waktuSelesai, u.durasi
                    FROM ujian_siswa us
                    JOIN ujian u ON us.ujian_id = u.id
                    WHERE us.id = ? AND us.siswa_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('ii', $ujian_siswa_id, $siswa_id);
            $stmt->execute();
            $ujianSiswa = $stmt->get_result()->fetch_assoc();

            if (!$ujianSiswa) {
                return ['success' => false, 'message' => 'Ujian tidak valid atau bukan milik Anda'];
            }

            if ($ujianSiswa['status'] !== 'sedang_mengerjakan') {
                return ['success' => false, 'message' => 'Ujian tidak dalam status mengerjakan'];
            }

            // Validasi soal
            $sqlSoal = "SELECT id, tipeSoal FROM soal WHERE id = ? AND ujian_id = ?";
            $stSoal = $this->conn->prepare($sqlSoal);
            $stSoal->bind_param('ii', $soal_id, $ujianSiswa['ujian_id']);
            $stSoal->execute();
            $soal = $stSoal->get_result()->fetch_assoc();
            
            if (!$soal) {
                return ['success' => false, 'message' => 'Soal tidak ditemukan'];
            }

            return $this->simpanJawabanToDatabase($ujian_siswa_id, $soal_id, $jawaban, $soal['tipeSoal']);
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('AutoSave Error: ' . $this->lastError);
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem'];
        }
    }

    private function simpanJawabanToDatabase($ujian_siswa_id, $soal_id, $jawaban, $tipeSoal) {
        try {
            // Ambil ujian_id dari ujian_siswa terlebih dahulu
            $sqlUjian = "SELECT ujian_id FROM ujian_siswa WHERE id = ?";
            $stUjian = $this->conn->prepare($sqlUjian);
            $stUjian->bind_param('i', $ujian_siswa_id);
            $stUjian->execute();
            $ujianData = $stUjian->get_result()->fetch_assoc();
            
            if (!$ujianData) {
                return ['success' => false, 'message' => 'Data ujian tidak ditemukan'];
            }
            
            $ujian_id = $ujianData['ujian_id'];
            
            // Ambil siswa_id dari ujian_siswa
            $sqlSiswa = "SELECT siswa_id FROM ujian_siswa WHERE id = ?";
            $stSiswa = $this->conn->prepare($sqlSiswa);
            $stSiswa->bind_param('i', $ujian_siswa_id);
            $stSiswa->execute();
            $siswaData = $stSiswa->get_result()->fetch_assoc();
            
            if (!$siswaData) {
                return ['success' => false, 'message' => 'Data siswa tidak ditemukan'];
            }
            
            $siswa_id = $siswaData['siswa_id'];

            // Cek apakah jawaban sudah ada
            $sql = "SELECT id FROM jawaban_siswa WHERE ujian_id = ? AND siswa_id = ? AND soal_id = ?";
            $st = $this->conn->prepare($sql);
            $st->bind_param('iii', $ujian_id, $siswa_id, $soal_id);
            $st->execute();
            $existing = $st->get_result()->fetch_assoc();

            // Siapkan pilihan jawaban untuk soal pilihan ganda
            $pilihanJawaban = null;
            if ($tipeSoal === 'pilihan_ganda' && strlen(trim((string)$jawaban)) === 1) {
                $pilihanJawaban = strtoupper(trim($jawaban));
            }

            if ($existing) {
                // Update jawaban yang sudah ada
                if ($pilihanJawaban !== null) {
                    $sql = "UPDATE jawaban_siswa SET jawaban = ?, pilihanJawaban = ?, waktuDijawab = NOW() WHERE id = ?";
                    $st2 = $this->conn->prepare($sql);
                    $st2->bind_param('ssi', $jawaban, $pilihanJawaban, $existing['id']);
                } else {
                    $sql = "UPDATE jawaban_siswa SET jawaban = ?, pilihanJawaban = NULL, waktuDijawab = NOW() WHERE id = ?";
                    $st2 = $this->conn->prepare($sql);
                    $st2->bind_param('si', $jawaban, $existing['id']);
                }
            } else {
                // Insert jawaban baru
                if ($pilihanJawaban !== null) {
                    $sql = "INSERT INTO jawaban_siswa (ujian_id, siswa_id, soal_id, jawaban, pilihanJawaban, waktuDijawab) VALUES (?, ?, ?, ?, ?, NOW())";
                    $st2 = $this->conn->prepare($sql);
                    $st2->bind_param('iiiss', $ujian_id, $siswa_id, $soal_id, $jawaban, $pilihanJawaban);
                } else {
                    $sql = "INSERT INTO jawaban_siswa (ujian_id, siswa_id, soal_id, jawaban, waktuDijawab) VALUES (?, ?, ?, ?, NOW())";
                    $st2 = $this->conn->prepare($sql);
                    $st2->bind_param('iiis', $ujian_id, $siswa_id, $soal_id, $jawaban);
                }
            }

            if ($st2->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Jawaban berhasil disimpan', 
                    'auto_save' => true, 
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }

            $this->lastError = $st2->error;
            error_log('AutoSave DB execute error: ' . $this->lastError);
            return ['success' => false, 'message' => 'Gagal menyimpan jawaban'];
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Database Save Error: ' . $this->lastError);
            return ['success' => false, 'message' => 'Gagal menyimpan ke database'];
        }
    }

    public function getStatusJawaban($ujian_siswa_id, $siswa_id) {
        try {
            // Validasi ujian siswa
            $sql = "SELECT ujian_id FROM ujian_siswa WHERE id = ? AND siswa_id = ?";
            $st = $this->conn->prepare($sql);
            $st->bind_param('ii', $ujian_siswa_id, $siswa_id);
            $st->execute();
            $ujianSiswa = $st->get_result()->fetch_assoc();
            
            if (!$ujianSiswa) {
                return ['success' => false, 'message' => 'Ujian tidak valid'];
            }

            // Ambil semua soal dan jawaban - perbaiki query untuk menggunakan ujian_id dan siswa_id
            $sql = "SELECT s.id as soal_id, s.nomorSoal, s.tipeSoal, 
                           js.id as jawaban_id, js.jawaban, js.pilihanJawaban, js.waktuDijawab
                    FROM soal s
                    LEFT JOIN jawaban_siswa js ON s.id = js.soal_id AND js.ujian_id = ? AND js.siswa_id = ?
                    WHERE s.ujian_id = ?
                    ORDER BY s.nomorSoal";
            $st2 = $this->conn->prepare($sql);
            $st2->bind_param('iii', $ujianSiswa['ujian_id'], $siswa_id, $ujianSiswa['ujian_id']);
            $st2->execute();
            $res = $st2->get_result();

            $status = [];
            while ($row = $res->fetch_assoc()) {
                $isAnswered = false;
                $jawaban = '';
                
                if ($row['jawaban_id']) {
                    if ($row['tipeSoal'] === 'pilihan_ganda') {
                        $isAnswered = !empty($row['pilihanJawaban']) || !empty($row['jawaban']);
                        $jawaban = $row['pilihanJawaban'] ?: $row['jawaban'];
                    } else {
                        $isAnswered = !empty(trim($row['jawaban'] ?? ''));
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
            error_log('Get Status Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal mengambil status jawaban'];
        }
    }

    public function hapusJawaban($ujian_siswa_id, $soal_id, $siswa_id) {
        try {
            // Validasi ujian siswa
            $sql = "SELECT ujian_id FROM ujian_siswa WHERE id = ? AND siswa_id = ? AND status = 'sedang_mengerjakan'";
            $st = $this->conn->prepare($sql);
            $st->bind_param('ii', $ujian_siswa_id, $siswa_id);
            $st->execute();
            $ujianData = $st->get_result()->fetch_assoc();
            
            if (!$ujianData) {
                return ['success' => false, 'message' => 'Ujian tidak valid'];
            }

            // Hapus jawaban berdasarkan ujian_id, siswa_id, dan soal_id
            $sql = "DELETE FROM jawaban_siswa WHERE ujian_id = ? AND siswa_id = ? AND soal_id = ?";
            $st2 = $this->conn->prepare($sql);
            $st2->bind_param('iii', $ujianData['ujian_id'], $siswa_id, $soal_id);
            
            if ($st2->execute()) {
                return ['success' => true, 'message' => 'Jawaban berhasil dihapus'];
            }
            
            return ['success' => false, 'message' => 'Gagal menghapus jawaban'];
            
        } catch (Exception $e) {
            error_log('Delete Answer Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus jawaban'];
        }
    }

    public function clearAllAnswers($ujian_siswa_id, $siswa_id) {
        try {
            // Validasi ujian siswa
            $sql = "SELECT ujian_id FROM ujian_siswa WHERE id = ? AND siswa_id = ? AND status = 'sedang_mengerjakan'";
            $st = $this->conn->prepare($sql);
            $st->bind_param('ii', $ujian_siswa_id, $siswa_id);
            $st->execute();
            $ujianData = $st->get_result()->fetch_assoc();
            
            if (!$ujianData) {
                return ['success' => false, 'message' => 'Ujian tidak valid'];
            }

            // Hapus semua jawaban berdasarkan ujian_id dan siswa_id
            $sql = "DELETE FROM jawaban_siswa WHERE ujian_id = ? AND siswa_id = ?";
            $st2 = $this->conn->prepare($sql);
            $st2->bind_param('ii', $ujianData['ujian_id'], $siswa_id);
            
            if ($st2->execute()) {
                $deleted_count = $st2->affected_rows;
                return [
                    'success' => true, 
                    'message' => "Berhasil menghapus {$deleted_count} jawaban", 
                    'deleted_count' => $deleted_count
                ];
            }
            
            return ['success' => false, 'message' => 'Gagal menghapus jawaban'];
            
        } catch (Exception $e) {
            error_log('Clear All Answers Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus semua jawaban'];
        }
    }
}
?>
