<?php
require_once 'koneksi.php';

class DashboardLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Dashboard Guru
    public function getDashboardGuru($guru_id) {
        try {
            $stats = [];
            
            // Total kelas
            $sql = "SELECT COUNT(*) as total FROM kelas WHERE guru_id = ? AND status = 'aktif'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $guru_id);
            $stmt->execute();
            $stats['totalKelas'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // Total siswa
            $sql = "SELECT COUNT(DISTINCT ks.siswa_id) as total 
                    FROM kelas k 
                    JOIN kelas_siswa ks ON k.id = ks.kelas_id 
                    WHERE k.guru_id = ? AND k.status = 'aktif' AND ks.status = 'aktif'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $guru_id);
            $stmt->execute();
            $stats['totalSiswa'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // Total ujian
            $sql = "SELECT COUNT(*) as total FROM ujian WHERE guru_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $guru_id);
            $stmt->execute();
            $stats['totalUjian'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // Ujian aktif
            $sql = "SELECT COUNT(*) as total FROM ujian WHERE guru_id = ? AND status = 'aktif'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $guru_id);
            $stmt->execute();
            $stats['ujianAktif'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // Kelas terbaru
            $sql = "SELECT k.*, 
                           COUNT(DISTINCT ks.siswa_id) as jumlahSiswa,
                           COUNT(DISTINCT u.id) as jumlahUjian
                    FROM kelas k 
                    LEFT JOIN kelas_siswa ks ON k.id = ks.kelas_id AND ks.status = 'aktif'
                    LEFT JOIN ujian u ON k.id = u.kelas_id
                    WHERE k.guru_id = ? AND k.status = 'aktif'
                    GROUP BY k.id
                    ORDER BY k.id DESC 
                    LIMIT 6";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $guru_id);
            $stmt->execute();
            $stats['kelasTerbaru'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Ujian mendatang
            $sql = "SELECT u.*, k.namaKelas 
                    FROM ujian u 
                    JOIN kelas k ON u.kelas_id = k.id
                    WHERE u.guru_id = ? AND u.tanggalUjian >= CURDATE() AND u.status = 'aktif'
                    ORDER BY u.tanggalUjian ASC, u.waktuMulai ASC
                    LIMIT 5";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $guru_id);
            $stmt->execute();
            $stats['ujianMendatang'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Dashboard Siswa
    public function getDashboardSiswa($siswa_id) {
        try {
            $stats = [];
            
            // Total kelas yang diikuti
            $sql = "SELECT COUNT(*) as total FROM kelas_siswa WHERE siswa_id = ? AND status = 'aktif'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $siswa_id);
            $stmt->execute();
            $stats['totalKelas'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // Total ujian yang tersedia
            $sql = "SELECT COUNT(DISTINCT u.id) as total 
                    FROM ujian u 
                    JOIN kelas k ON u.kelas_id = k.id
                    JOIN kelas_siswa ks ON k.id = ks.kelas_id
                    WHERE ks.siswa_id = ? AND ks.status = 'aktif' AND u.status = 'aktif'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $siswa_id);
            $stmt->execute();
            $stats['totalUjian'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // Ujian yang sudah dikerjakan
            $sql = "SELECT COUNT(*) as total FROM ujian_siswa WHERE siswa_id = ? AND status = 'selesai'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $siswa_id);
            $stmt->execute();
            $stats['ujianSelesai'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // Rata-rata nilai
            $sql = "SELECT AVG(totalNilai) as rata FROM ujian_siswa WHERE siswa_id = ? AND status = 'selesai'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $siswa_id);
            $stmt->execute();
            $rataResult = $stmt->get_result()->fetch_assoc();
            $stats['rataNilai'] = $rataResult['rata'] ? round($rataResult['rata'], 2) : 0;
            
            // Kelas yang diikuti
            $sql = "SELECT k.*, u.namaLengkap as namaGuru,
                           COUNT(DISTINCT ks2.siswa_id) as jumlahSiswa
                    FROM kelas k 
                    JOIN kelas_siswa ks ON k.id = ks.kelas_id
                    JOIN users u ON k.guru_id = u.id
                    LEFT JOIN kelas_siswa ks2 ON k.id = ks2.kelas_id AND ks2.status = 'aktif'
                    WHERE ks.siswa_id = ? AND ks.status = 'aktif' AND k.status = 'aktif'
                    GROUP BY k.id
                    ORDER BY ks.tanggalBergabung DESC
                    LIMIT 5";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $siswa_id);
            $stmt->execute();
            $stats['kelasTerbaru'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Ujian mendatang
            $sql = "SELECT u.*, k.namaKelas, us.status as statusPengerjaan
                    FROM ujian u 
                    JOIN kelas k ON u.kelas_id = k.id
                    JOIN kelas_siswa ks ON k.id = ks.kelas_id
                    LEFT JOIN ujian_siswa us ON u.id = us.ujian_id AND us.siswa_id = ?
                    WHERE ks.siswa_id = ? AND ks.status = 'aktif' 
                          AND u.status = 'aktif' AND u.tanggalUjian >= CURDATE()
                          AND (us.id IS NULL OR us.status != 'selesai')
                    ORDER BY u.tanggalUjian ASC, u.waktuMulai ASC
                    LIMIT 5";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $siswa_id, $siswa_id);
            $stmt->execute();
            $stats['ujianMendatang'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Nilai terbaru
            $sql = "SELECT u.namaUjian, k.namaKelas, us.totalNilai, us.waktuSelesai
                    FROM ujian_siswa us
                    JOIN ujian u ON us.ujian_id = u.id
                    JOIN kelas k ON u.kelas_id = k.id
                    WHERE us.siswa_id = ? AND us.status = 'selesai'
                    ORDER BY us.waktuSelesai DESC
                    LIMIT 5";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $siswa_id);
            $stmt->execute();
            $stats['nilaiTerbaru'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Dashboard Admin
    public function getDashboardAdmin() {
        try {
            $stats = [];
            
            // Total users
            $sql = "SELECT 
                        COUNT(*) as totalUser,
                        COUNT(CASE WHEN role = 'guru' THEN 1 END) as totalGuru,
                        COUNT(CASE WHEN role = 'siswa' THEN 1 END) as totalSiswa,
                        COUNT(CASE WHEN status = 'aktif' THEN 1 END) as userAktif
                    FROM users";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $userStats = $stmt->get_result()->fetch_assoc();
            $stats = array_merge($stats, $userStats);
            
            // Total kelas
            $sql = "SELECT COUNT(*) as totalKelas FROM kelas WHERE status = 'aktif'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['totalKelas'] = $stmt->get_result()->fetch_assoc()['totalKelas'];
            
            // Total ujian
            $sql = "SELECT COUNT(*) as totalUjian FROM ujian";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['totalUjian'] = $stmt->get_result()->fetch_assoc()['totalUjian'];
            
            // Aktivitas terbaru
            $sql = "SELECT 'user_baru' as tipe, namaLengkap as nama, dibuat as waktu
                    FROM users 
                    WHERE DATE(dibuat) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    UNION ALL
                    SELECT 'kelas_baru' as tipe, namaKelas as nama, dibuat as waktu
                    FROM kelas 
                    WHERE DATE(dibuat) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    UNION ALL
                    SELECT 'ujian_baru' as tipe, namaUjian as nama, dibuat as waktu
                    FROM ujian 
                    WHERE DATE(dibuat) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    ORDER BY waktu DESC
                    LIMIT 10";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['aktivitasTerbaru'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Statistik penggunaan harian
    public function getStatistikHarian($tanggal = null) {
        try {
            if (!$tanggal) {
                $tanggal = date('Y-m-d');
            }
            
            $stats = [];
            
            // Login hari ini
            $sql = "SELECT COUNT(*) as total FROM users WHERE DATE(terakhirLogin) = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $tanggal);
            $stmt->execute();
            $stats['loginHariIni'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // Ujian hari ini
            $sql = "SELECT COUNT(*) as total FROM ujian WHERE tanggalUjian = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $tanggal);
            $stmt->execute();
            $stats['ujianHariIni'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // Postingan hari ini
            $sql = "SELECT COUNT(*) as total FROM postingan_kelas WHERE DATE(dibuat) = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $tanggal);
            $stmt->execute();
            $stats['postinganHariIni'] = $stmt->get_result()->fetch_assoc()['total'];
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Top performing students
    public function getTopSiswa($limit = 10) {
        try {
            $sql = "SELECT u.namaLengkap, AVG(us.totalNilai) as rataNilai, COUNT(us.id) as jumlahUjian
                    FROM users u
                    JOIN ujian_siswa us ON u.id = us.siswa_id
                    WHERE u.role = 'siswa' AND us.status = 'selesai'
                    GROUP BY u.id
                    HAVING jumlahUjian >= 3
                    ORDER BY rataNilai DESC
                    LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get recent posts from all classes for a student
    public function getPostinganTerbaruSiswa($siswa_id, $limit = 10, $offset = 0) {
        try {
            $sql = "SELECT p.*, 
                           u.namaLengkap as namaPenulis, 
                           u.role as rolePenulis,
                           u.fotoProfil as fotoProfil,
                           k.namaKelas, 
                           k.restrict_comments,
                           COUNT(DISTINCT l.id) as jumlahLike,
                           COUNT(DISTINCT kom.id) as jumlahKomentar,
                           MAX(CASE WHEN l.user_id = ? THEN 1 ELSE 0 END) as userLiked,
                           t.id as assignment_id,
                           t.judul as assignment_title,
                           t.deskripsi as assignment_description,
                           t.deadline as assignment_deadline,
                           t.nilai_maksimal as assignment_max_score,
                           t.file_path as assignment_file_path,
                           pt.status as student_status,
                           pt.nilai as student_score
                    FROM postingan_kelas p
                    JOIN users u ON p.user_id = u.id
                    JOIN kelas k ON p.kelas_id = k.id
                    JOIN kelas_siswa ks ON k.id = ks.kelas_id
                    LEFT JOIN like_postingan l ON p.id = l.postingan_id
                    LEFT JOIN komentar_postingan kom ON p.id = kom.postingan_id
                    LEFT JOIN tugas t ON p.assignment_id = t.id
                    LEFT JOIN pengumpulan_tugas pt ON t.id = pt.assignment_id AND pt.siswa_id = ?
                    WHERE ks.siswa_id = ? AND ks.status = 'aktif' AND k.status = 'aktif'
                    GROUP BY p.id
                    ORDER BY p.dibuat DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiiii", $siswa_id, $siswa_id, $siswa_id, $limit, $offset);
            $stmt->execute();
            
            $postingan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get images for each post
            foreach ($postingan as &$post) {
                $post['gambar'] = $this->getGambarPostingan($post['id']);
                $post['files'] = $this->getFilePostingan($post['id']); // Add file attachments
                
                // Convert assignment file path to URL if exists
                if ($post['assignment_file_path']) {
                    // Convert absolute path to relative URL for web access
                    $webRoot = '/opt/lampp/htdocs';
                    $post['assignment_file_path'] = str_replace($webRoot, '', $post['assignment_file_path']);
                }
            }
            
            return $postingan;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Helper function to get post images
    private function getGambarPostingan($postingan_id) {
        try {
            $sql = "SELECT nama_file, path_gambar, ukuran_file, media_type, tipe_file FROM postingan_gambar WHERE postingan_id = ? ORDER BY urutan";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Helper function to get post files
    private function getFilePostingan($postingan_id) {
        try {
            $sql = "SELECT nama_file, path_file, ukuran_file, tipe_file, ekstensi_file FROM postingan_files WHERE postingan_id = ? ORDER BY urutan";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
?>
