<?php
require_once 'koneksi.php';

class UjianLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Membuat ujian baru
    public function buatUjian($namaUjian, $deskripsi, $kelas_id, $guru_id, $mataPelajaran, $tanggalUjian, $waktuMulai, $waktuSelesai, $durasi) {
        try {
            $sql = "INSERT INTO ujian (namaUjian, deskripsi, kelas_id, guru_id, mataPelajaran, tanggalUjian, waktuMulai, waktuSelesai, durasi, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssiisssssi", $namaUjian, $deskripsi, $kelas_id, $guru_id, $mataPelajaran, $tanggalUjian, $waktuMulai, $waktuSelesai, $durasi);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Ujian berhasil dibuat',
                    'ujian_id' => $this->conn->insert_id
                ];
            } else {
                return ['success' => false, 'message' => 'Gagal membuat ujian'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Mendapatkan ujian berdasarkan guru
    public function getUjianByGuru($guru_id) {
        try {
            $sql = "SELECT u.*, k.namaKelas,
                           COUNT(DISTINCT us.siswa_id) as jumlahPeserta,
                           COUNT(DISTINCT s.id) as jumlahSoal
                    FROM ujian u 
                    LEFT JOIN kelas k ON u.kelas_id = k.id
                    LEFT JOIN ujian_siswa us ON u.id = us.ujian_id
                    LEFT JOIN soal s ON u.id = s.ujian_id
                    WHERE u.guru_id = ?
                    GROUP BY u.id
                    ORDER BY u.dibuat DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $guru_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Mendapatkan ujian berdasarkan siswa
    public function getUjianBySiswa($siswa_id) {
        try {
            $sql = "SELECT u.*, k.namaKelas, us.status as statusPengerjaan, us.totalNilai, us.waktuMulai, us.waktuSelesai,
                           CASE 
                               WHEN us.id IS NULL THEN 'belum_dikerjakan'
                               WHEN us.status = 'selesai' THEN 'selesai'
                               WHEN us.status = 'sedang_mengerjakan' THEN 'sedang_mengerjakan'
                               ELSE 'belum_dikerjakan'
                           END as status_ujian
                    FROM ujian u 
                    JOIN kelas k ON u.kelas_id = k.id
                    JOIN kelas_siswa ks ON k.id = ks.kelas_id
                    LEFT JOIN ujian_siswa us ON u.id = us.ujian_id AND us.siswa_id = ?
                    WHERE ks.siswa_id = ? AND ks.status = 'aktif' AND u.status = 'aktif'
                    ORDER BY u.tanggalUjian ASC, u.waktuMulai ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $siswa_id, $siswa_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Mulai ujian
    public function mulaiUjian($ujian_id, $siswa_id) {
        try {
            // Cek apakah ujian sudah dimulai
            $sql = "SELECT id FROM ujian_siswa WHERE ujian_id = ? AND siswa_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_id, $siswa_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => 'Ujian sudah dimulai sebelumnya'];
            }
            
            // Mulai ujian
            $sql = "INSERT INTO ujian_siswa (ujian_id, siswa_id, waktuMulai, status) VALUES (?, ?, NOW(), 'sedang_mengerjakan')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_id, $siswa_id);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Ujian dimulai',
                    'ujian_siswa_id' => $this->conn->insert_id
                ];
            } else {
                return ['success' => false, 'message' => 'Gagal memulai ujian'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Selesai ujian
    public function selesaiUjian($ujian_siswa_id) {
        try {
            // Update status ujian siswa
            $sql = "UPDATE ujian_siswa SET waktuSelesai = NOW(), status = 'selesai' WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_siswa_id);
            
            if ($stmt->execute()) {
                // Hitung nilai
                $this->hitungNilai($ujian_siswa_id);
                return ['success' => true, 'message' => 'Ujian selesai'];
            } else {
                return ['success' => false, 'message' => 'Gagal menyelesaikan ujian'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Hitung nilai ujian
    private function hitungNilai($ujian_siswa_id) {
        try {
            // Hitung jawaban benar dan total poin
            $sql = "SELECT 
                        COUNT(CASE WHEN js.benar = 1 THEN 1 END) as jumlahBenar,
                        COUNT(CASE WHEN js.benar = 0 THEN 1 END) as jumlahSalah,
                        SUM(js.poin) as totalPoin
                    FROM jawaban_siswa js
                    WHERE js.ujian_siswa_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_siswa_id);
            $stmt->execute();
            $hasil = $stmt->get_result()->fetch_assoc();
            
            // Update ujian_siswa dengan hasil
            $sql = "UPDATE ujian_siswa SET 
                        jumlahBenar = ?, 
                        jumlahSalah = ?, 
                        totalNilai = ?
                    WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iidi", 
                $hasil['jumlahBenar'], 
                $hasil['jumlahSalah'], 
                $hasil['totalPoin'], 
                $ujian_siswa_id
            );
            $stmt->execute();
            
        } catch (Exception $e) {
            // Log error
        }
    }
    
    // Update status ujian (aktif/draft/selesai)
    public function updateStatusUjian($ujian_id, $status) {
        try {
            $sql = "UPDATE ujian SET status = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $status, $ujian_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Status ujian berhasil diupdate'];
            } else {
                return ['success' => false, 'message' => 'Gagal mengupdate status ujian'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Mendapatkan detail ujian
    public function getDetailUjian($ujian_id) {
        try {
            $sql = "SELECT u.*, k.namaKelas, usr.namaLengkap as namaGuru
                    FROM ujian u 
                    JOIN kelas k ON u.kelas_id = k.id
                    JOIN users usr ON u.guru_id = usr.id
                    WHERE u.id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Mendapatkan hasil ujian
    public function getHasilUjian($ujian_id) {
        try {
            $sql = "SELECT * FROM view_hasil_ujian WHERE id IN (
                        SELECT us.id FROM ujian_siswa us WHERE us.ujian_id = ?
                    ) ORDER BY totalNilai DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
?>
