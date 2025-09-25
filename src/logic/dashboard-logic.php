<?php
require_once 'koneksi.php';

class DashboardLogic {
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = getConnection();
            if (!$this->conn) {
                error_log("DashboardLogic: getConnection() returned null");
                throw new Exception("Database connection failed");
            }
            if ($this->conn->connect_error) {
                error_log("DashboardLogic: Connection error: " . $this->conn->connect_error);
                throw new Exception("Database connection error: " . $this->conn->connect_error);
            }
        } catch (Exception $e) {
            error_log("DashboardLogic constructor error: " . $e->getMessage());
            throw $e;
        }
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
            if (!$stmt) {
                error_log("Dashboard: Failed to prepare query 1: " . $this->conn->error);
                return [];
            }
            $stmt->bind_param("i", $siswa_id);
            if (!$stmt->execute()) {
                error_log("Dashboard: Failed to execute query 1: " . $stmt->error);
                return [];
            }
            $result = $stmt->get_result();
            if (!$result) {
                error_log("Dashboard: Failed to get result 1: " . $stmt->error);
                return [];
            }
            $stats['totalKelas'] = $result->fetch_assoc()['total'];
            
            // Total ujian yang tersedia
            $sql = "SELECT COUNT(DISTINCT u.id) as total 
                    FROM ujian u 
                    JOIN kelas k ON u.kelas_id = k.id
                    JOIN kelas_siswa ks ON k.id = ks.kelas_id
                    WHERE ks.siswa_id = ? AND ks.status = 'aktif' AND u.status = 'aktif'";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("Dashboard: Failed to prepare query 2: " . $this->conn->error);
                return [];
            }
            $stmt->bind_param("i", $siswa_id);
            if (!$stmt->execute()) {
                error_log("Dashboard: Failed to execute query 2: " . $stmt->error);
                return [];
            }
            $result = $stmt->get_result();
            if (!$result) {
                error_log("Dashboard: Failed to get result 2: " . $stmt->error);
                return [];
            }
            $stats['totalUjian'] = $result->fetch_assoc()['total'];
            
            // Ujian yang sudah dikerjakan (set to 0 if ujian_siswa table doesn't exist)
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'ujian_siswa'");
            if ($checkTable && $checkTable->num_rows > 0) {
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
            } else {
                $stats['ujianSelesai'] = 0;
                $stats['rataNilai'] = 0;
            }
            
            // Kelas yang diikuti
            $sql = "SELECT k.*, u.namaLengkap as namaGuru,
                           COUNT(DISTINCT ks2.siswa_id) as jumlahSiswa
                    FROM kelas k 
                    JOIN kelas_siswa ks ON k.id = ks.kelas_id
                    JOIN users u ON k.guru_id = u.id
                    LEFT JOIN kelas_siswa ks2 ON k.id = ks2.kelas_id AND ks2.status = 'aktif'
                    WHERE ks.siswa_id = ? AND ks.status = 'aktif' AND k.status = 'aktif'
                    GROUP BY k.id
                    ORDER BY ks.tanggal_bergabung DESC
                    LIMIT 5";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $siswa_id);
            $stmt->execute();
            $stats['kelasTerbaru'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Ujian mendatang
            if ($checkTable && $checkTable->num_rows > 0) {
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
            } else {
                // Simple ujian mendatang without ujian_siswa dependency
                $sql = "SELECT u.*, k.namaKelas
                        FROM ujian u 
                        JOIN kelas k ON u.kelas_id = k.id
                        JOIN kelas_siswa ks ON k.id = ks.kelas_id
                        WHERE ks.siswa_id = ? AND ks.status = 'aktif' 
                              AND u.status = 'aktif' AND u.tanggalUjian >= CURDATE()
                        ORDER BY u.tanggalUjian ASC, u.waktuMulai ASC
                        LIMIT 5";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $siswa_id);
                $stmt->execute();
                $stats['ujianMendatang'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
            
            // Nilai terbaru (only if ujian_siswa exists)
            if ($checkTable && $checkTable->num_rows > 0) {
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
            } else {
                $stats['nilaiTerbaru'] = [];
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Dashboard getDashboardSiswa error: " . $e->getMessage());
            error_log("Dashboard stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
    
    // Dashboard Admin
    public function getDashboardAdmin($admin_id = null) {
        try {
            $stats = [];
            
            // Total Appointments (menggunakan total ujian sebagai contoh)
            $sql = "SELECT COUNT(*) as total FROM ujian";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['totalAppointments'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // New Patients (user baru dalam 30 hari terakhir)
            $sql = "SELECT COUNT(*) as total FROM users WHERE DATE(dibuat) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['newPatients'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // Operations (total kelas aktif)
            $sql = "SELECT COUNT(*) as total FROM kelas WHERE status = 'aktif'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['operations'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // Earning (dummy data - bisa disesuaikan dengan kebutuhan)
            $stats['earning'] = '10,525';
            
            // Total users untuk statistik tambahan
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
            
            // Data untuk registration chart (default by month)
            $stats['registrationChart'] = $this->getRegistrationChartData('month', 12);
            
            // Data untuk stats cards baru
            $stats['penggunaGratis'] = $this->getPenggunaGratis();
            $stats['penggunaPremium'] = $this->getPenggunaPremium();
            $stats['totalKelasAktif'] = $this->getTotalKelasAktif();
            $stats['totalUjianAktif'] = $this->getTotalUjianAktif();
            
            // Update totalGuru dan totalSiswa untuk konsistensi
            $stats['totalGuru'] = $this->getTotalGuru();
            $stats['totalSiswa'] = $this->getTotalSiswa();
            
            // Data untuk heatmap calendar
            $stats['registrationHeatmap'] = $this->getRegistrationHeatmap(3);
            
            return $stats;
        } catch (Exception $e) {
            error_log("Dashboard getDashboardAdmin error: " . $e->getMessage());
            return [
                'totalAppointments' => 0,
                'newPatients' => 0,
                'operations' => 0,
                'earning' => '0',
                'totalUser' => 0,
                'totalGuru' => 0,
                'totalSiswa' => 0,
                'userAktif' => 0,
                'totalKelas' => 0,
                'totalUjian' => 0,
                'aktivitasTerbaru' => [],
                'registrationChart' => [],
                'penggunaGratis' => 0,
                'penggunaPremium' => 0,
                'totalKelasAktif' => 0,
                'totalUjianAktif' => 0,
                'registrationHeatmap' => []
            ];
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
                
                // Get assignment files if this is an assignment post
                if ($post['assignment_id']) {
                    $post['assignment_files'] = $this->getAssignmentFiles($post['assignment_id']);
                } else {
                    $post['assignment_files'] = [];
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
    
    // Helper function to get assignment files (multiple files support)
    private function getAssignmentFiles($assignment_id) {
        try {
            if (!$assignment_id) return [];
            
            $sql = "SELECT file_name as nama_file, file_path as path_file, file_size as ukuran_file, 
                           file_type as tipe_file, file_type as ekstensi_file 
                    FROM tugas_files 
                    WHERE tugas_id = ? 
                    ORDER BY upload_order, id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            
            $files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Convert absolute paths to relative URLs for web access
            $webRoot = '/opt/lampp/htdocs';
            foreach ($files as &$file) {
                // Ensure we have relative path for web access
                if ($file['path_file']) {
                    // If it starts with uploads/, it's already relative
                    if (strpos($file['path_file'], 'uploads/') === 0) {
                        // Keep as is
                    } else if (strpos($file['path_file'], $webRoot) === 0) {
                        $file['path_file'] = str_replace($webRoot, '', $file['path_file']);
                    }
                }
                
                // Get file extension from filename if not available
                if (empty($file['ekstensi_file']) && !empty($file['nama_file'])) {
                    $file['ekstensi_file'] = strtolower(pathinfo($file['nama_file'], PATHINFO_EXTENSION));
                }
            }
            
            return $files;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Ambil data pendaftaran untuk chart di admin dashboard dari tabel users
    public function getRegistrationChartData($period = 'month', $value = null) {
        try {
            $chartData = [];
            
            switch ($period) {
                case 'day':
                    return $this->getRegistrationByDay($value);
                case 'week':
                    return $this->getRegistrationByWeek($value);
                case 'month':
                    return $this->getRegistrationByMonth($value);
                case 'year':
                    return $this->getRegistrationByYear($value);
                case '2year':
                    return $this->getRegistrationBy2Years($value);
                default:
                    return $this->getRegistrationByMonth($value);
            }
        } catch (Exception $e) {
            error_log("Error getting registration chart data: " . $e->getMessage());
            return [
                'labels' => [],
                'guru' => [],
                'siswa' => []
            ];
        }
    }
    
    // Data pendaftaran per hari (30 hari terakhir)
    private function getRegistrationByDay($days = 30) {
        $days = $days ?: 30;
        $chartData = [
            'labels' => [],
            'guru' => [],
            'siswa' => []
        ];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $chartData['labels'][] = date('d/m', strtotime($date));
            
            // Hitung guru yang daftar pada hari ini
            $sql = "SELECT COUNT(*) as total FROM users WHERE DATE(dibuat) = ? AND role = 'guru'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $chartData['guru'][] = intval($stmt->get_result()->fetch_assoc()['total']);
            
            // Hitung siswa yang daftar pada hari ini
            $sql = "SELECT COUNT(*) as total FROM users WHERE DATE(dibuat) = ? AND role = 'siswa'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $chartData['siswa'][] = intval($stmt->get_result()->fetch_assoc()['total']);
        }
        
        return $chartData;
    }
    
    // Data pendaftaran per minggu (12 minggu terakhir)
    private function getRegistrationByWeek($weeks = 12) {
        $weeks = $weeks ?: 12;
        $chartData = [
            'labels' => [],
            'guru' => [],
            'siswa' => []
        ];
        
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $dayOffset = $i * 7;
            $startDate = date('Y-m-d', strtotime("-{$dayOffset} days"));
            $endDayOffset = (($i - 1) * 7 - 1);
            $endDate = date('Y-m-d', strtotime("-{$endDayOffset} days"));
            
            $chartData['labels'][] = 'W' . date('W', strtotime($startDate));
            
            // Hitung guru yang daftar pada minggu ini
            $sql = "SELECT COUNT(*) as total FROM users WHERE DATE(dibuat) BETWEEN ? AND ? AND role = 'guru'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $chartData['guru'][] = intval($stmt->get_result()->fetch_assoc()['total']);
            
            // Hitung siswa yang daftar pada minggu ini
            $sql = "SELECT COUNT(*) as total FROM users WHERE DATE(dibuat) BETWEEN ? AND ? AND role = 'siswa'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $chartData['siswa'][] = intval($stmt->get_result()->fetch_assoc()['total']);
        }
        
        return $chartData;
    }
    
    // Data pendaftaran per bulan (12 bulan terakhir)
    private function getRegistrationByMonth($months = 12) {
        $months = $months ?: 12;
        $chartData = [
            'labels' => [],
            'guru' => [],
            'siswa' => []
        ];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-{$i} months"));
            $chartData['labels'][] = date('M Y', strtotime($date . '-01'));
            
            // Hitung guru yang daftar pada bulan ini
            $sql = "SELECT COUNT(*) as total FROM users WHERE DATE_FORMAT(dibuat, '%Y-%m') = ? AND role = 'guru'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $chartData['guru'][] = intval($stmt->get_result()->fetch_assoc()['total']);
            
            // Hitung siswa yang daftar pada bulan ini
            $sql = "SELECT COUNT(*) as total FROM users WHERE DATE_FORMAT(dibuat, '%Y-%m') = ? AND role = 'siswa'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $chartData['siswa'][] = intval($stmt->get_result()->fetch_assoc()['total']);
        }
        
        return $chartData;
    }
    
    // Data pendaftaran per tahun (5 tahun terakhir)
    private function getRegistrationByYear($years = 5) {
        $years = $years ?: 5;
        $chartData = [
            'labels' => [],
            'guru' => [],
            'siswa' => []
        ];
        
        for ($i = $years - 1; $i >= 0; $i--) {
            $year = date('Y', strtotime("-{$i} years"));
            $chartData['labels'][] = $year;
            
            // Hitung guru yang daftar pada tahun ini
            $sql = "SELECT COUNT(*) as total FROM users WHERE YEAR(dibuat) = ? AND role = 'guru'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $year);
            $stmt->execute();
            $chartData['guru'][] = intval($stmt->get_result()->fetch_assoc()['total']);
            
            // Hitung siswa yang daftar pada tahun ini
            $sql = "SELECT COUNT(*) as total FROM users WHERE YEAR(dibuat) = ? AND role = 'siswa'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $year);
            $stmt->execute();
            $chartData['siswa'][] = intval($stmt->get_result()->fetch_assoc()['total']);
        }
        
        return $chartData;
    }
    
    // Data pendaftaran 2 tahun dengan breakdown per bulan
    private function getRegistrationBy2Years($startYear = null) {
        if (!$startYear) {
            $startYear = date('Y', strtotime('-1 year'));
        }
        
        $chartData = [
            'labels' => [],
            'guru' => [],
            'siswa' => []
        ];
        
        // 24 bulan terakhir (2 tahun)
        for ($i = 23; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-{$i} months"));
            $chartData['labels'][] = date('M Y', strtotime($date . '-01'));
            
            // Hitung guru yang daftar pada bulan ini
            $sql = "SELECT COUNT(*) as total FROM users WHERE DATE_FORMAT(dibuat, '%Y-%m') = ? AND role = 'guru'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $chartData['guru'][] = intval($stmt->get_result()->fetch_assoc()['total']);
            
            // Hitung siswa yang daftar pada bulan ini
            $sql = "SELECT COUNT(*) as total FROM users WHERE DATE_FORMAT(dibuat, '%Y-%m') = ? AND role = 'siswa'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $chartData['siswa'][] = intval($stmt->get_result()->fetch_assoc()['total']);
        }
        
        return $chartData;
    }

        // Fungsi untuk mendapatkan jumlah pengguna gratis (semua pengguna kecuali yang premium)
    private function getPenggunaGratis() {
        try {
            // Anggap semua users yang bukan premium sebagai gratis
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM users WHERE role IN ('guru', 'siswa')");
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['total'];
        } catch (Exception $e) {
            error_log("Error in getPenggunaGratis: " . $e->getMessage());
            return 0;
        }
    }

    // Fungsi untuk mendapatkan jumlah pengguna premium
    private function getPenggunaPremium() {
        try {
            // Untuk sementara return 0 karena belum ada sistem premium di database
            // Nanti bisa dikembangkan ketika sistem premium sudah diimplementasi
            return 0;
        } catch (Exception $e) {
            error_log("Error in getPenggunaPremium: " . $e->getMessage());
            return 0;
        }
    }

    // Fungsi untuk mendapatkan total kelas aktif (yang sedang berjalan)
    private function getTotalKelasAktif() {
        try {
            // Kelas aktif = status 'aktif' (semua kelas yang tersedia untuk pembelajaran)
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM kelas k 
                WHERE k.status = 'aktif'
            ");
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error in getTotalKelasAktif: " . $e->getMessage());
            // Fallback - hitung semua kelas dengan status aktif
            try {
                $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM kelas WHERE status = 'aktif'");
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                return $result['total'];
            } catch (Exception $e2) {
                return 0;
            }
        }
    }

    // Fungsi untuk mendapatkan total ujian aktif (yang bisa dikerjakan)
    private function getTotalUjianAktif() {
        try {
            // Ujian aktif = status 'aktif' dan tanggal ujian >= hari ini (belum kadaluarsa)
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM ujian u
                JOIN kelas k ON u.kelas_id = k.id 
                WHERE u.status = 'aktif' 
                AND k.status = 'aktif'
                AND u.tanggalUjian >= CURDATE()
            ");
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['total'];
        } catch (Exception $e) {
            error_log("Error in getTotalUjianAktif: " . $e->getMessage());
            // Fallback - hitung semua ujian dengan status aktif
            try {
                $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM ujian WHERE status = 'aktif'");
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                return $result['total'];
            } catch (Exception $e2) {
                return 0;
            }
        }
    }

    // Fungsi untuk mendapatkan total guru aktif
    private function getTotalGuru() {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'guru' AND status = 'aktif'");
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['total'];
        } catch (Exception $e) {
            error_log("Error in getTotalGuru: " . $e->getMessage());
            return 0;
        }
    }

    // Fungsi untuk mendapatkan total siswa aktif
    private function getTotalSiswa() {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'siswa' AND status = 'aktif'");
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['total'];
        } catch (Exception $e) {
            error_log("Error in getTotalSiswa: " . $e->getMessage());
            return 0;
        }
    }

    // Fungsi untuk mendapatkan data aktivitas pendaftaran untuk heatmap calendar
    public function getRegistrationHeatmap($months = 3) {
        try {
            $heatmapData = [];
            
            // Ambil data pendaftaran per hari untuk periode yang ditentukan
            $sql = "SELECT 
                        DATE(dibuat) as tanggal,
                        COUNT(*) as total_pendaftar,
                        COUNT(CASE WHEN role = 'guru' THEN 1 END) as guru,
                        COUNT(CASE WHEN role = 'siswa' THEN 1 END) as siswa
                    FROM users 
                    WHERE DATE(dibuat) >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                    GROUP BY DATE(dibuat)
                    ORDER BY tanggal DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $months);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $heatmapData[$row['tanggal']] = [
                    'total' => (int)$row['total_pendaftar'],
                    'guru' => (int)$row['guru'],
                    'siswa' => (int)$row['siswa']
                ];
            }
            
            return $heatmapData;
        } catch (Exception $e) {
            error_log("Error in getRegistrationHeatmap: " . $e->getMessage());
            return [];
        }
    }
    
    // Get recent users with pagination
    public function getRecentUsers($page = 1, $limit = 3) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Get users with pagination
            $sql = "SELECT 
                        u.id, 
                        u.namaLengkap,
                        u.email,
                        u.role,
                        u.dibuat,
                        u.status as user_status,
                        CASE 
                            WHEN u.status = 'premium' THEN 'Premium'
                            ELSE 'Gratis'
                        END as subscription_status
                    FROM users u
                    WHERE u.role IN ('guru', 'siswa')
                    ORDER BY u.dibuat DESC 
                    LIMIT ? OFFSET ?";
                    
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $users = [];
            while ($row = $result->fetch_assoc()) {
                // Format the registration date
                $dibuat = new DateTime($row['dibuat']);
                $now = new DateTime();
                $interval = $now->diff($dibuat);
                
                // Create relative time string
                if ($interval->days == 0) {
                    if ($interval->h == 0) {
                        $timeAgo = $interval->i . ' menit yang lalu';
                    } else {
                        $timeAgo = $interval->h . ' jam yang lalu';
                    }
                } elseif ($interval->days == 1) {
                    $timeAgo = '1 hari yang lalu';
                } elseif ($interval->days < 7) {
                    $timeAgo = $interval->days . ' hari yang lalu';
                } else {
                    $timeAgo = $dibuat->format('d M Y');
                }
                
                $users[] = [
                    'id' => (int)$row['id'],
                    'namaLengkap' => $row['namaLengkap'],
                    'email' => $row['email'],
                    'role' => $row['role'],
                    'dibuat' => $row['dibuat'],
                    'timeAgo' => $timeAgo,
                    'subscription_status' => $row['subscription_status']
                ];
            }
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM users WHERE role IN ('guru', 'siswa')";
            $countResult = $this->conn->query($countSql);
            $totalUsers = $countResult->fetch_assoc()['total'];
            $totalPages = ceil($totalUsers / $limit);
            
            return [
                'users' => $users,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalUsers' => (int)$totalUsers,
                    'hasNext' => $page < $totalPages,
                    'hasPrev' => $page > 1
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error in getRecentUsers: " . $e->getMessage());
            return [
                'users' => [],
                'pagination' => [
                    'currentPage' => 1,
                    'totalPages' => 0,
                    'totalUsers' => 0,
                    'hasNext' => false,
                    'hasPrev' => false
                ]
            ];
        }
    }
}
?>
