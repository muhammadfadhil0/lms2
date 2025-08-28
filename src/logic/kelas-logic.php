<?php
require_once 'koneksi.php';

class KelasLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Membuat kelas baru
    public function buatKelas($namaKelas, $deskripsi, $mataPelajaran, $guru_id, $maxSiswa = 30) {
        try {
            // Generate kode kelas unik
            $kodeKelas = $this->generateKodeKelas($mataPelajaran);
            
            $sql = "INSERT INTO kelas (namaKelas, deskripsi, mataPelajaran, kodeKelas, guru_id, maxSiswa, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'aktif')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssis", $namaKelas, $deskripsi, $mataPelajaran, $kodeKelas, $guru_id, $maxSiswa);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Kelas berhasil dibuat',
                    'kelas_id' => $this->conn->insert_id,
                    'kode_kelas' => $kodeKelas
                ];
            } else {
                return ['success' => false, 'message' => 'Gagal membuat kelas'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Generate kode kelas unik
    private function generateKodeKelas($mataPelajaran) {
        $prefix = strtoupper(substr($mataPelajaran, 0, 3));
        $number = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        return $prefix . $number;
    }
    
    // Mendapatkan semua kelas berdasarkan guru
    public function getKelasByGuru($guru_id) {
        try {
            $sql = "SELECT k.*, 
                           COUNT(DISTINCT ks.siswa_id) as jumlahSiswa,
                           COUNT(DISTINCT u.id) as jumlahUjian
                    FROM kelas k 
                    LEFT JOIN kelas_siswa ks ON k.id = ks.kelas_id AND ks.status = 'aktif'
                    LEFT JOIN ujian u ON k.id = u.kelas_id
                    WHERE k.guru_id = ? AND k.status = 'aktif'
                    GROUP BY k.id
                    ORDER BY k.dibuat DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $guru_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Mendapatkan kelas berdasarkan siswa
    public function getKelasBySiswa($siswa_id) {
        try {
            $sql = "SELECT k.*, u.namaLengkap as namaGuru,
                           COUNT(DISTINCT ks2.siswa_id) as jumlahSiswa,
                           COUNT(DISTINCT uj.id) as jumlahUjian
                    FROM kelas k 
                    JOIN kelas_siswa ks ON k.id = ks.kelas_id
                    JOIN users u ON k.guru_id = u.id
                    LEFT JOIN kelas_siswa ks2 ON k.id = ks2.kelas_id AND ks2.status = 'aktif'
                    LEFT JOIN ujian uj ON k.id = uj.kelas_id
                    WHERE ks.siswa_id = ? AND ks.status = 'aktif' AND k.status = 'aktif'
                    GROUP BY k.id
                    ORDER BY k.dibuat DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $siswa_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Join kelas dengan kode
    public function joinKelas($siswa_id, $kodeKelas) {
        try {
            // Cek apakah kelas ada
            $sql = "SELECT id, maxSiswa FROM kelas WHERE kodeKelas = ? AND status = 'aktif'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $kodeKelas);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                return ['success' => false, 'message' => 'Kode kelas tidak ditemukan'];
            }
            
            $kelas = $result->fetch_assoc();
            $kelas_id = $kelas['id'];
            
            // Cek apakah sudah join
            $sql = "SELECT id FROM kelas_siswa WHERE kelas_id = ? AND siswa_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $kelas_id, $siswa_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => 'Anda sudah tergabung dalam kelas ini'];
            }
            
            // Cek kapasitas kelas
            $sql = "SELECT COUNT(*) as jumlah FROM kelas_siswa WHERE kelas_id = ? AND status = 'aktif'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $kelas_id);
            $stmt->execute();
            $jumlahSiswa = $stmt->get_result()->fetch_assoc()['jumlah'];
            
            if ($jumlahSiswa >= $kelas['maxSiswa']) {
                return ['success' => false, 'message' => 'Kelas sudah penuh'];
            }
            
            // Join kelas
            $sql = "INSERT INTO kelas_siswa (kelas_id, siswa_id, status) VALUES (?, ?, 'aktif')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $kelas_id, $siswa_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Berhasil bergabung dengan kelas', 'kelas_id' => $kelas_id];
            } else {
                return ['success' => false, 'message' => 'Gagal bergabung dengan kelas'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Mendapatkan detail kelas
    public function getDetailKelas($kelas_id) {
        try {
            $sql = "SELECT k.*, u.namaLengkap as namaGuru, u.email as emailGuru
                    FROM kelas k 
                    JOIN users u ON k.guru_id = u.id
                    WHERE k.id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $kelas_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Mendapatkan siswa dalam kelas
    public function getSiswaKelas($kelas_id) {
        try {
            $sql = "SELECT u.id, u.namaLengkap, u.email, ks.tanggalBergabung
                    FROM users u 
                    JOIN kelas_siswa ks ON u.id = ks.siswa_id
                    WHERE ks.kelas_id = ? AND ks.status = 'aktif'
                    ORDER BY ks.tanggalBergabung ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $kelas_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Update kelas
    public function updateKelas($kelas_id, $namaKelas, $deskripsi, $mataPelajaran, $maxSiswa) {
        try {
            $sql = "UPDATE kelas SET namaKelas = ?, deskripsi = ?, mataPelajaran = ?, maxSiswa = ? 
                    WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssii", $namaKelas, $deskripsi, $mataPelajaran, $maxSiswa, $kelas_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Kelas berhasil diupdate'];
            } else {
                return ['success' => false, 'message' => 'Gagal mengupdate kelas'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Hapus siswa dari kelas
    public function hapusSiswaKelas($kelas_id, $siswa_id) {
        try {
            $sql = "UPDATE kelas_siswa SET status = 'keluar' WHERE kelas_id = ? AND siswa_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $kelas_id, $siswa_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Siswa berhasil dihapus dari kelas'];
            } else {
                return ['success' => false, 'message' => 'Gagal menghapus siswa'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>
