<?php
require_once 'koneksi.php';

class UjianLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Membuat ujian baru (backward compatibility: tanggalAkhir default sama dengan tanggalUjian)
    public function buatUjian($namaUjian, $deskripsi, $kelas_id, $guru_id, $mataPelajaran, $tanggalUjian, $waktuMulai, $waktuSelesai, $durasi, $topik = null, $tanggalAkhir = null) {
        try {
            // Jika tanggalAkhir tidak diberikan, gunakan tanggalUjian
            if ($tanggalAkhir === null) {
                $tanggalAkhir = $tanggalUjian;
            }
            
            $sql = "INSERT INTO ujian (namaUjian, deskripsi, topik, kelas_id, guru_id, mataPelajaran, tanggalUjian, tanggalAkhir, waktuMulai, waktuSelesai, durasi, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')";
            $stmt = $this->conn->prepare($sql);
            // Tipe parameter: s (namaUjian), s(deskripsi), s(topik), i(kelas_id), i(guru_id), s(mataPelajaran), s(tanggalUjian), s(tanggalAkhir), s(waktuMulai), s(waktuSelesai), i(durasi)
            $stmt->bind_param("sssiisssssi", $namaUjian, $deskripsi, $topik, $kelas_id, $guru_id, $mataPelajaran, $tanggalUjian, $tanggalAkhir, $waktuMulai, $waktuSelesai, $durasi);
            
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

    // Update ujian existing milik guru (hanya field identitas & waktu) - with backward compatibility
    public function updateUjian($ujian_id, $guru_id, $namaUjian, $deskripsi, $kelas_id, $mataPelajaran, $tanggalUjian, $waktuMulai, $waktuSelesai, $durasi, $shuffleQuestions = null, $showScore = null, $autoScore = null, $topik = null, $tanggalAkhir = null) {
        try {
            // Pastikan ujian milik guru
            $cek = $this->getUjianByIdAndGuru($ujian_id, $guru_id);
            if (!$cek) {
                return ['success' => false, 'message' => 'Ujian tidak ditemukan'];
            }

            $fields = [
                'namaUjian = ?',      // s
                'deskripsi = ?',       // s
                'kelas_id = ?',        // i
                'mataPelajaran = ?',   // s
                'tanggalUjian = ?',    // s
                'waktuMulai = ?',      // s
                'waktuSelesai = ?',    // s
                'durasi = ?'           // i
            ];
            $types = 'sssisssi';
            $params = [$namaUjian, $deskripsi, $kelas_id, $mataPelajaran, $tanggalUjian, $waktuMulai, $waktuSelesai, $durasi];
            
            // Add tanggalAkhir if provided, otherwise default to tanggalUjian
            if ($tanggalAkhir !== null) {
                $fields[] = 'tanggalAkhir = ?';
                $types .= 's';
                $params[] = $tanggalAkhir;
            } else {
                // Set tanggalAkhir sama dengan tanggalUjian untuk backward compatibility
                $fields[] = 'tanggalAkhir = ?';
                $types .= 's';
                $params[] = $tanggalUjian;
            }
            
            // Add topik if provided
            if ($topik !== null) {
                $fields[] = 'topik = ?';
                $types .= 's';
                $params[] = $topik;
            }

            // Tambahkan optional settings jika kolom ada dan argumen diberikan
            // Optional settings checks in one describe pass each
            if ($shuffleQuestions !== null) {
                if ($res = $this->conn->query("SHOW COLUMNS FROM ujian LIKE 'shuffleQuestions'")) {
                    if ($res->num_rows > 0) {
                        $fields[] = 'shuffleQuestions = ?';
                        $types .= 'i';
                        $params[] = (int)$shuffleQuestions;
                    }
                }
            }
            if ($showScore !== null) {
                if ($res = $this->conn->query("SHOW COLUMNS FROM ujian LIKE 'showScore'")) {
                    if ($res->num_rows > 0) {
                        $fields[] = 'showScore = ?';
                        $types .= 'i';
                        $params[] = (int)$showScore;
                    }
                }
            }
            if ($autoScore !== null) {
                if ($res = $this->conn->query("SHOW COLUMNS FROM ujian LIKE 'autoScore'")) {
                    if ($res->num_rows > 0) {
                        $fields[] = 'autoScore = ?';
                        $types .= 'i';
                        $params[] = (int)$autoScore;
                    }
                }
            }

            $sql = 'UPDATE ujian SET '.implode(', ', $fields).' WHERE id = ? AND guru_id = ?';
            $types .= 'ii';
            $params[] = $ujian_id;
            $params[] = $guru_id;

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return ['success' => false, 'message' => 'Prepare gagal'];
            }

            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Ujian berhasil diupdate'];
            }
            return ['success' => false, 'message' => 'Gagal update ujian'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }

    // Mendapatkan ujian milik guru tertentu (validasi kepemilikan)
    public function getUjianByIdAndGuru($ujian_id, $guru_id) {
        try {
            $sql = "SELECT u.*, k.namaKelas FROM ujian u LEFT JOIN kelas k ON u.kelas_id = k.id WHERE u.id = ? AND u.guru_id = ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_id, $guru_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Mendapatkan ujian berdasarkan guru
    public function getUjianByGuru($guru_id) {
        try {
            $sql = "SELECT u.*, k.namaKelas, k.gambar_kelas,
                       COUNT(DISTINCT us.siswa_id) as jumlahPeserta,
                       COUNT(DISTINCT s.id) as jumlahSoal
                   FROM ujian u
                   LEFT JOIN kelas k ON u.kelas_id = k.id
                   LEFT JOIN ujian_siswa us ON u.id = us.ujian_id
                   LEFT JOIN soal s ON u.id = s.ujian_id
                   WHERE u.guru_id = ? AND u.status != 'selesai'
                   GROUP BY u.id
                   ORDER BY u.dibuat DESC";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log('getUjianByGuru prepare failed: ' . $this->conn->error);
                return [];
            }

            $stmt->bind_param("i", $guru_id);
            $stmt->execute();

            if (method_exists($stmt, 'get_result')) {
                $result = $stmt->get_result();
                return $result !== false ? $result->fetch_all(MYSQLI_ASSOC) : [];
            }

            // Fallback to bind_result if get_result not available
            $meta = $stmt->result_metadata();
            if (!$meta) return [];
            $fields = [];
            $row = [];
            while ($field = $meta->fetch_field()) {
                $fields[] = &$row[$field->name];
            }
            call_user_func_array([$stmt, 'bind_result'], $fields);
            $out = [];
            while ($stmt->fetch()) {
                $copy = [];
                foreach ($row as $key => $val) $copy[$key] = $val;
                $out[] = $copy;
            }
            return $out;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Mendapatkan ujian berdasarkan siswa
    public function getUjianBySiswa($siswa_id, $force_refresh = false) {
        try {
            // Force refresh untuk debugging
            if ($force_refresh) {
                // Clear any potential query cache
                $this->conn->query("SELECT 1");
            }
            
            // Detect whether ujian_siswa table exists; if not, use a simplified query
            $hasUjianSiswa = false;
            $check = $this->conn->query("SHOW TABLES LIKE 'ujian_siswa'");
            if ($check && $check->num_rows > 0) $hasUjianSiswa = true;

            if ($hasUjianSiswa) {
                $sql = "SELECT u.*, k.namaKelas, k.gambar_kelas, us.status as statusPengerjaan, us.totalNilai, us.waktuMulai as waktu_mulai_siswa, us.waktuSelesai as waktu_selesai_siswa, us.id as ujian_siswa_id,
                               (SELECT COALESCE(SUM(s.poin), COUNT(*) * 10) FROM soal s WHERE s.ujian_id = u.id) as totalBobot,
                           CASE 
                               WHEN us.id IS NULL THEN 
                                   CASE 
                                       -- Ujian bisa beda hari (tanggalUjian dan tanggalAkhir berbeda)
                                       WHEN u.tanggalUjian != COALESCE(u.tanggalAkhir, u.tanggalUjian) THEN 
                                           CASE 
                                               WHEN NOW() < CONCAT(u.tanggalUjian, ' ', u.waktuMulai) THEN 'belum_dimulai'
                                               WHEN NOW() >= CONCAT(u.tanggalUjian, ' ', u.waktuMulai) AND NOW() <= CONCAT(COALESCE(u.tanggalAkhir, u.tanggalUjian), ' ', u.waktuSelesai) THEN 'dapat_dikerjakan'
                                               ELSE 'terlambat'
                                           END
                                       -- Ujian dalam hari yang sama (backward compatibility)
                                       WHEN u.waktuSelesai < u.waktuMulai THEN 
                                           -- Ujian melewati tengah malam dalam 1 hari
                                           CASE 
                                               WHEN (DATE(NOW()) = u.tanggalUjian AND TIME(NOW()) >= u.waktuMulai) OR 
                                                    (DATE(NOW()) = DATE_ADD(u.tanggalUjian, INTERVAL 1 DAY) AND TIME(NOW()) <= u.waktuSelesai) THEN 'dapat_dikerjakan'
                                               WHEN (DATE(NOW()) > DATE_ADD(u.tanggalUjian, INTERVAL 1 DAY)) OR 
                                                    (DATE(NOW()) = DATE_ADD(u.tanggalUjian, INTERVAL 1 DAY) AND TIME(NOW()) > u.waktuSelesai) THEN 'terlambat'
                                               ELSE 'belum_dimulai'
                                           END
                                       ELSE 
                                           -- Ujian dalam hari yang sama
                                           CASE 
                                               WHEN CONCAT(u.tanggalUjian, ' ', u.waktuSelesai) < NOW() THEN 'terlambat'
                                               WHEN CONCAT(u.tanggalUjian, ' ', u.waktuMulai) <= NOW() AND CONCAT(u.tanggalUjian, ' ', u.waktuSelesai) >= NOW() THEN 'dapat_dikerjakan'
                                               WHEN CONCAT(u.tanggalUjian, ' ', u.waktuMulai) > NOW() THEN 'belum_dimulai'
                                               ELSE 'belum_dikerjakan'
                                           END
                                   END
                               WHEN us.status = 'selesai' THEN 'selesai'
                               WHEN us.status = 'sedang_mengerjakan' THEN 
                                   CASE 
                                       -- Ujian bisa beda hari (tanggalUjian dan tanggalAkhir berbeda)
                                       WHEN u.tanggalUjian != COALESCE(u.tanggalAkhir, u.tanggalUjian) THEN 
                                           CASE 
                                               WHEN NOW() > CONCAT(COALESCE(u.tanggalAkhir, u.tanggalUjian), ' ', u.waktuSelesai) THEN 'waktu_habis'
                                               ELSE 'sedang_mengerjakan'
                                           END
                                       WHEN u.waktuSelesai < u.waktuMulai THEN 
                                           -- Ujian melewati tengah malam dalam 1 hari
                                           CASE 
                                               WHEN (DATE(NOW()) > DATE_ADD(u.tanggalUjian, INTERVAL 1 DAY)) OR 
                                                    (DATE(NOW()) = DATE_ADD(u.tanggalUjian, INTERVAL 1 DAY) AND TIME(NOW()) > u.waktuSelesai) THEN 'waktu_habis'
                                               ELSE 'sedang_mengerjakan'
                                           END
                                       ELSE 
                                           -- Ujian dalam hari yang sama
                                           CASE 
                                               WHEN CONCAT(u.tanggalUjian, ' ', u.waktuSelesai) < NOW() THEN 'waktu_habis'
                                               ELSE 'sedang_mengerjakan'
                                           END
                                   END
                               ELSE 'belum_dikerjakan'
                           END as status_ujian
                    FROM ujian u 
                    JOIN kelas k ON u.kelas_id = k.id
                    JOIN kelas_siswa ks ON k.id = ks.kelas_id
                    LEFT JOIN ujian_siswa us ON u.id = us.ujian_id AND us.siswa_id = ?
                    WHERE ks.siswa_id = ? AND ks.status = 'aktif' AND u.status = 'aktif'
                    ORDER BY u.tanggalUjian ASC, u.waktuMulai ASC";
                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    return [];
                }
                $stmt->bind_param("ii", $siswa_id, $siswa_id);
            } else {
                // Simplified query when ujian_siswa not available
                $sql = "SELECT u.*, k.namaKelas, k.gambar_kelas, NULL as statusPengerjaan, NULL as totalNilai, NULL as waktu_mulai_siswa, NULL as waktu_selesai_siswa, NULL as ujian_siswa_id,
                               (SELECT COALESCE(SUM(s.poin), COUNT(*) * 10) FROM soal s WHERE s.ujian_id = u.id) as totalBobot,
                           CASE 
                               -- Ujian bisa beda hari (tanggalUjian dan tanggalAkhir berbeda)
                               WHEN u.tanggalUjian != COALESCE(u.tanggalAkhir, u.tanggalUjian) THEN 
                                   CASE 
                                       WHEN NOW() < CONCAT(u.tanggalUjian, ' ', u.waktuMulai) THEN 'belum_dimulai'
                                       WHEN NOW() >= CONCAT(u.tanggalUjian, ' ', u.waktuMulai) AND NOW() <= CONCAT(COALESCE(u.tanggalAkhir, u.tanggalUjian), ' ', u.waktuSelesai) THEN 'dapat_dikerjakan'
                                       ELSE 'terlambat'
                                   END
                               WHEN u.waktuSelesai < u.waktuMulai THEN 
                                   -- Ujian melewati tengah malam dalam 1 hari
                                   CASE 
                                       WHEN (DATE(NOW()) = u.tanggalUjian AND TIME(NOW()) >= u.waktuMulai) OR 
                                            (DATE(NOW()) = DATE_ADD(u.tanggalUjian, INTERVAL 1 DAY) AND TIME(NOW()) <= u.waktuSelesai) THEN 'dapat_dikerjakan'
                                       WHEN (DATE(NOW()) > DATE_ADD(u.tanggalUjian, INTERVAL 1 DAY)) OR 
                                            (DATE(NOW()) = DATE_ADD(u.tanggalUjian, INTERVAL 1 DAY) AND TIME(NOW()) > u.waktuSelesai) THEN 'terlambat'
                                       ELSE 'belum_dimulai'
                                   END
                               ELSE 
                                   -- Ujian dalam hari yang sama
                                   CASE 
                                       WHEN CONCAT(u.tanggalUjian, ' ', u.waktuSelesai) < NOW() THEN 'terlambat'
                                       WHEN CONCAT(u.tanggalUjian, ' ', u.waktuMulai) <= NOW() AND CONCAT(u.tanggalUjian, ' ', u.waktuSelesai) >= NOW() THEN 'dapat_dikerjakan'
                                       WHEN CONCAT(u.tanggalUjian, ' ', u.waktuMulai) > NOW() THEN 'belum_dimulai'
                                       ELSE 'belum_dikerjakan'
                                   END
                           END as status_ujian
                    FROM ujian u
                    JOIN kelas k ON u.kelas_id = k.id
                    JOIN kelas_siswa ks ON k.id = ks.kelas_id
                    WHERE ks.siswa_id = ? AND ks.status = 'aktif' AND u.status = 'aktif'
                    ORDER BY u.tanggalUjian ASC, u.waktuMulai ASC";
                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    return [];
                }
                $stmt->bind_param("i", $siswa_id);
            }

            $stmt->execute();

            if (method_exists($stmt, 'get_result')) {
                $resObj = $stmt->get_result();
                $results = $resObj !== false ? $resObj->fetch_all(MYSQLI_ASSOC) : [];
            } else {
                $results = [];
            }
            
            // Debug log
            if ($force_refresh) {
                error_log("getUjianBySiswa results for siswa_id $siswa_id: " . json_encode($results));
            }

            if (isset($_GET['debug']) && $_GET['debug'] === '1') {
                error_log('DEBUG getUjianBySiswa siswa_id=' . $siswa_id . ' rows=' . count($results));
                error_log('DEBUG getUjianBySiswa sample: ' . json_encode(array_slice($results, 0, 10)));
            }
            
            return $results;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Mulai ujian
    public function mulaiUjian($ujian_id, $siswa_id) {
        try {
            // Cek informasi ujian terlebih dahulu
            $sqlUjian = "SELECT * FROM ujian WHERE id = ?";
            $stmtUjian = $this->conn->prepare($sqlUjian);
            $stmtUjian->bind_param("i", $ujian_id);
            $stmtUjian->execute();
            $ujian = $stmtUjian->get_result()->fetch_assoc();
            
            if (!$ujian) {
                return ['success' => false, 'message' => 'Ujian tidak ditemukan'];
            }
            
            // Cek apakah ujian sudah dimulai sebelumnya
            $sql = "SELECT id, status, waktuMulai FROM ujian_siswa WHERE ujian_id = ? AND siswa_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_id, $siswa_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                // Jika ujian sudah selesai, tidak bisa masuk lagi
                if ($existing['status'] === 'selesai') {
                    return ['success' => false, 'message' => 'Ujian sudah diselesaikan sebelumnya'];
                }
                
                // Jika masih sedang mengerjakan, lanjutkan ujian yang ada
                if ($existing['status'] === 'sedang_mengerjakan') {
                    // Cek apakah waktu ujian masih valid (belum expired)
                    $waktuMulai = strtotime($existing['waktuMulai']);
                    $durasiDetik = $ujian['durasi'] * 60;
                    $waktuSekarang = time();
                    
                    // Jika waktu ujian sudah habis, otomatis selesaikan ujian
                    if (($waktuSekarang - $waktuMulai) > $durasiDetik) {
                        $this->selesaiUjian($existing['id']);
                        return ['success' => false, 'message' => 'Waktu ujian telah habis'];
                    }
                    
                    // Ujian masih berlangsung, lanjutkan
                    return [
                        'success' => true, 
                        'message' => 'Melanjutkan ujian',
                        'ujian_siswa_id' => $existing['id']
                    ];
                }
            }
            
            // Cek apakah masih dalam waktu ujian yang diizinkan
            $tanggalUjian = $ujian['tanggalUjian'];
            $waktuMulai = $ujian['waktuMulai'];
            $waktuSelesai = $ujian['waktuSelesai'];
            
            $waktuMulaiUjian = strtotime($tanggalUjian . ' ' . $waktuMulai);
            $waktuSekarang = time();
            
            // Handle ujian yang melewati tengah malam
            if ($waktuSelesai < $waktuMulai) {
                // Ujian melewati tengah malam
                $waktuSelesaiUjian = strtotime($tanggalUjian . ' ' . $waktuSelesai) + (24 * 60 * 60); // Tambah 1 hari
                
                // Validasi khusus untuk ujian yang melewati tengah malam
                if ($waktuSekarang < ($waktuMulaiUjian - 300)) {
                    return ['success' => false, 'message' => 'Ujian belum dimulai'];
                }
                
                if ($waktuSekarang > $waktuSelesaiUjian) {
                    return ['success' => false, 'message' => 'Waktu ujian telah berakhir'];
                }
            } else {
                // Ujian dalam hari yang sama
                $waktuSelesaiUjian = strtotime($tanggalUjian . ' ' . $waktuSelesai);
                
                // Validasi waktu ujian (dengan toleransi 5 menit sebelum waktu mulai)
                if ($waktuSekarang < ($waktuMulaiUjian - 300)) {
                    return ['success' => false, 'message' => 'Ujian belum dimulai'];
                }
                
                if ($waktuSekarang > $waktuSelesaiUjian) {
                    return ['success' => false, 'message' => 'Waktu ujian telah berakhir'];
                }
            }
            
            // Mulai ujian baru
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
            // Debug: log sebelum update
            error_log("Attempting to finish ujian_siswa_id: " . $ujian_siswa_id);
            
            // Update status ujian siswa
            $sql = "UPDATE ujian_siswa SET waktuSelesai = NOW(), status = 'selesai' WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_siswa_id);
            
            if ($stmt->execute()) {
                // Debug: cek apakah update berhasil
                $affected_rows = $stmt->affected_rows;
                error_log("Update affected rows: " . $affected_rows);
                
                if ($affected_rows > 0) {
                    // Hitung nilai
                    $this->hitungNilai($ujian_siswa_id);
                    
                    // Debug: verify update
                    $verify_sql = "SELECT status FROM ujian_siswa WHERE id = ?";
                    $verify_stmt = $this->conn->prepare($verify_sql);
                    $verify_stmt->bind_param("i", $ujian_siswa_id);
                    $verify_stmt->execute();
                    $result = $verify_stmt->get_result()->fetch_assoc();
                    error_log("Verified status after update: " . ($result['status'] ?? 'NULL'));
                    
                    return ['success' => true, 'message' => 'Ujian selesai'];
                } else {
                    error_log("No rows affected - ujian_siswa_id might not exist: " . $ujian_siswa_id);
                    return ['success' => false, 'message' => 'Data ujian tidak ditemukan'];
                }
            } else {
                error_log("SQL execute failed: " . $stmt->error);
                return ['success' => false, 'message' => 'Gagal menyelesaikan ujian'];
            }
        } catch (Exception $e) {
            error_log("Exception in selesaiUjian: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Hitung nilai ujian
    private function hitungNilai($ujian_siswa_id) {
        try {
            // First get ujian_id and siswa_id from ujian_siswa_id
            $sql = "SELECT ujian_id, siswa_id FROM ujian_siswa WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_siswa_id);
            $stmt->execute();
            $ujian_data = $stmt->get_result()->fetch_assoc();
            
            if (!$ujian_data) {
                error_log("hitungNilai: ujian_siswa_id $ujian_siswa_id tidak ditemukan");
                return;
            }
            
            // Auto-evaluate semua pilihan ganda terlebih dahulu
            $this->autoEvaluatePilihanGanda($ujian_data['ujian_id'], $ujian_data['siswa_id']);
            
            // Hitung jawaban benar, salah, dan total poin
            $sql = "SELECT 
                        COUNT(CASE WHEN js.benar = 1 THEN 1 END) as jumlahBenar,
                        COUNT(CASE WHEN js.benar = 0 THEN 1 END) as jumlahSalah,
                        COALESCE(SUM(js.poin), 0) as totalPoin
                    FROM jawaban_siswa js
                    WHERE js.ujian_id = ? AND js.siswa_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_data['ujian_id'], $ujian_data['siswa_id']);
            $stmt->execute();
            $hasil = $stmt->get_result()->fetch_assoc();
            
            // Debug logging
            error_log("hitungNilai for ujian_siswa_id $ujian_siswa_id: benar=" . $hasil['jumlahBenar'] . ", salah=" . $hasil['jumlahSalah'] . ", total=" . $hasil['totalPoin']);
            
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
            
            if ($stmt->execute()) {
                error_log("hitungNilai: berhasil update ujian_siswa_id $ujian_siswa_id");
            } else {
                error_log("hitungNilai: gagal update ujian_siswa_id $ujian_siswa_id: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            error_log("Exception in hitungNilai: " . $e->getMessage());
        }
    }
    
    // Auto-evaluate pilihan ganda untuk siswa tertentu
    private function autoEvaluatePilihanGanda($ujian_id, $siswa_id) {
        try {
            // Get all pilihan ganda questions for this ujian
            $sql = "SELECT s.id, s.kunciJawaban, s.poin 
                    FROM soal s 
                    WHERE s.ujian_id = ? AND s.tipeSoal = 'pilihan_ganda'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $ujian_id);
            $stmt->execute();
            $soal_pg = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            foreach ($soal_pg as $soal) {
                // Update jawaban untuk soal pilihan ganda ini
                $sql = "UPDATE jawaban_siswa 
                       SET benar = CASE WHEN pilihanJawaban = ? THEN 1 ELSE 0 END,
                           poin = CASE WHEN pilihanJawaban = ? THEN ? ELSE 0 END
                       WHERE ujian_id = ? AND siswa_id = ? AND soal_id = ? AND pilihanJawaban IS NOT NULL";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('ssiiii', $soal['kunciJawaban'], $soal['kunciJawaban'], $soal['poin'], $ujian_id, $siswa_id, $soal['id']);
                $stmt->execute();
            }
            
        } catch (Exception $e) {
            error_log("Exception in autoEvaluatePilihanGanda: " . $e->getMessage());
        }
    }
    
    // Public method untuk re-evaluate semua pilihan ganda dalam ujian (utility function)
    public function reEvaluateAllPilihanGanda($ujian_id) {
        try {
            // Get all pilihan ganda questions for this ujian
            $sql = "SELECT s.id, s.kunciJawaban, s.poin 
                    FROM soal s 
                    WHERE s.ujian_id = ? AND s.tipeSoal = 'pilihan_ganda'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $ujian_id);
            $stmt->execute();
            $soal_pg = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $updated = 0;
            foreach ($soal_pg as $soal) {
                // Update all answers for this question
                $sql = "UPDATE jawaban_siswa 
                       SET benar = CASE WHEN pilihanJawaban = ? THEN 1 ELSE 0 END,
                           poin = CASE WHEN pilihanJawaban = ? THEN ? ELSE 0 END
                       WHERE ujian_id = ? AND soal_id = ? AND pilihanJawaban IS NOT NULL";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('ssiiii', $soal['kunciJawaban'], $soal['kunciJawaban'], $soal['poin'], $ujian_id, $soal['id']);
                $stmt->execute();
                $updated += $stmt->affected_rows;
            }
            
            // Recalculate totals for all students in this ujian
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
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $ujian_id);
            $stmt->execute();
            
            return ['success' => true, 'updated' => $updated, 'message' => "Berhasil memperbarui $updated jawaban"];
            
        } catch (Exception $e) {
            error_log("Exception in reEvaluateAllPilihanGanda: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
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
            $sql = "SELECT us.id, us.ujian_id, us.siswa_id, us.totalNilai, us.jumlahBenar, us.jumlahSalah, us.status,
                           u.namaLengkap, u.fotoProfil
                    FROM ujian_siswa us 
                    JOIN users u ON us.siswa_id = u.id
                    WHERE us.ujian_id = ?
                    ORDER BY us.totalNilai DESC, u.namaLengkap ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Mendapatkan detail jawaban siswa untuk ujian tertentu
    public function getDetailJawabanSiswa($ujian_siswa_id) {
        try {
            // First get ujian_id and siswa_id from ujian_siswa_id
            $sql = "SELECT ujian_id, siswa_id FROM ujian_siswa WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_siswa_id);
            $stmt->execute();
            $ujian_data = $stmt->get_result()->fetch_assoc();
            
            if (!$ujian_data) {
                return [];
            }
            
            // Get all questions for this exam and left join with student answers
            $sql = "SELECT s.id as soal_id, s.pertanyaan, s.tipeSoal, s.kunciJawaban, s.poin as poin_soal, s.nomorSoal,
                           js.jawaban, js.pilihanJawaban, js.benar, js.poin as poin_jawaban, js.waktuDijawab
                    FROM soal s
                    LEFT JOIN jawaban_siswa js ON s.id = js.soal_id AND js.ujian_id = ? AND js.siswa_id = ?
                    WHERE s.ujian_id = ?
                    ORDER BY s.nomorSoal ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $ujian_data['ujian_id'], $ujian_data['siswa_id'], $ujian_data['ujian_id']);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // Mendapatkan data review ujian untuk siswa
    public function getReviewUjianSiswa($ujian_id, $siswa_id) {
        try {
            // Get ujian data with showScore permission
            $sql = "SELECT u.*, k.namaKelas, us.id as ujian_siswa_id, us.totalNilai, us.jumlahBenar, us.jumlahSalah,
                           us.waktuMulai, us.waktuSelesai, us.status,
                           COALESCE(u.showScore, 1) as showScore
                    FROM ujian u
                    JOIN kelas k ON u.kelas_id = k.id
                    JOIN ujian_siswa us ON u.id = us.ujian_id
                    WHERE u.id = ? AND us.siswa_id = ? AND us.status = 'selesai'";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_id, $siswa_id);
            $stmt->execute();
            $ujian_data = $stmt->get_result()->fetch_assoc();
            
            if (!$ujian_data) {
                return null;
            }
            
            // Check if student is allowed to see results
            if (!$ujian_data['showScore']) {
                return ['error' => 'not_allowed', 'message' => 'Guru tidak mengizinkan siswa melihat hasil ujian ini'];
            }
            
            // Get soal data with student answers 
            $sql = "SELECT s.id as soal_id, s.pertanyaan, s.tipeSoal, s.kunciJawaban, s.poin as poin_soal, 
                           s.nomorSoal,
                           js.jawaban, js.pilihanJawaban, js.benar, js.poin as poin_jawaban, js.waktuDijawab
                    FROM soal s
                    LEFT JOIN jawaban_siswa js ON s.id = js.soal_id AND js.ujian_id = ? AND js.siswa_id = ?
                    WHERE s.ujian_id = ?
                    ORDER BY s.nomorSoal ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $ujian_id, $siswa_id, $ujian_id);
            $stmt->execute();
            $soal_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            if (empty($soal_data)) {
                return null;
            }
            
            // Get pilihan jawaban for multiple choice questions
            foreach ($soal_data as &$soal) {
                if ($soal['tipeSoal'] === 'pilihan_ganda') {
                    $sql_pilihan = "SELECT opsi, teksJawaban, benar FROM pilihan_jawaban WHERE soal_id = ? ORDER BY opsi";
                    $stmt_pilihan = $this->conn->prepare($sql_pilihan);
                    $stmt_pilihan->bind_param("i", $soal['soal_id']);
                    $stmt_pilihan->execute();
                    $pilihan_result = $stmt_pilihan->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    $soal['pilihan_array'] = [];
                    foreach ($pilihan_result as $pilihan) {
                        $soal['pilihan_array'][$pilihan['opsi']] = [
                            'teks' => $pilihan['teksJawaban'],
                            'benar' => (bool)$pilihan['benar']
                        ];
                    }
                }
            }
            unset($soal);
            
            return [
                'ujian' => $ujian_data,
                'soal_list' => $soal_data
            ];
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Mendapatkan data untuk mode koreksi swipe
    public function getDataKoreksiSwipe($ujian_id) {
        try {
            $sql = "SELECT us.id as ujian_siswa_id, us.siswa_id, u.namaLengkap as siswa_nama, u.fotoProfil,
                           s.id as soal_id, s.nomorSoal, s.pertanyaan, s.tipeSoal, s.kunciJawaban, s.poin,
                           js.jawaban, js.pilihanJawaban, js.benar, js.poin as poin_jawaban
                    FROM ujian_siswa us
                    JOIN users u ON us.siswa_id = u.id
                    JOIN soal s ON s.ujian_id = us.ujian_id
                    LEFT JOIN jawaban_siswa js ON us.ujian_id = js.ujian_id AND us.siswa_id = js.siswa_id AND s.id = js.soal_id
                    WHERE us.ujian_id = ? AND us.status = 'selesai'
                    ORDER BY us.siswa_id ASC, s.nomorSoal ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get ujian by ID (for all users)
    public function getUjianById($ujian_id) {
        try {
            $sql = "SELECT * FROM ujian WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Get ujian_siswa by ID
    public function getUjianSiswaById($ujian_siswa_id) {
        try {
            $sql = "SELECT us.*, u.namaUjian, u.durasi FROM ujian_siswa us 
                    JOIN ujian u ON us.ujian_id = u.id 
                    WHERE us.id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_siswa_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Save student answer
    public function simpanJawaban($ujian_siswa_id, $soal_id, $jawaban) {
        try {
            // First get ujian_id and siswa_id from ujian_siswa table
            $sql_us = "SELECT ujian_id, siswa_id FROM ujian_siswa WHERE id = ?";
            $stmt_us = $this->conn->prepare($sql_us);
            $stmt_us->bind_param("i", $ujian_siswa_id);
            $stmt_us->execute();
            $ujian_siswa = $stmt_us->get_result()->fetch_assoc();
            
            if (!$ujian_siswa) {
                return ['success' => false, 'message' => 'Ujian siswa tidak ditemukan'];
            }
            
            // Get soal data including kunciJawaban and poin
            $sql = "SELECT tipeSoal, kunciJawaban, poin FROM soal WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $soal_id);
            $stmt->execute();
            $soal = $stmt->get_result()->fetch_assoc();
            
            if (!$soal) {
                return ['success' => false, 'message' => 'Soal tidak ditemukan'];
            }
            
            // Check if answer already exists
            $sql = "SELECT id FROM jawaban_siswa WHERE ujian_id = ? AND siswa_id = ? AND soal_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $ujian_siswa['ujian_id'], $ujian_siswa['siswa_id'], $soal_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            // Determine if this is a multiple choice answer and evaluate it
            $pilihanJawaban = null;
            $benar = null;
            $poin = null;
            
            if ($soal['tipeSoal'] === 'pilihan_ganda' && strlen(trim($jawaban)) === 1) {
                $pilihanJawaban = strtoupper(trim($jawaban));
                // Auto-evaluate multiple choice answer
                $benar = ($pilihanJawaban === strtoupper($soal['kunciJawaban'])) ? 1 : 0;
                $poin = $benar ? $soal['poin'] : 0;
            }
            
            if ($existing) {
                // Update existing answer
                if ($pilihanJawaban !== null) {
                    $sql = "UPDATE jawaban_siswa SET jawaban = ?, pilihanJawaban = ?, benar = ?, poin = ?, waktuDijawab = NOW() WHERE id = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("ssidi", $jawaban, $pilihanJawaban, $benar, $poin, $existing['id']);
                } else {
                    // For essay questions, keep benar and poin as NULL for manual grading
                    $sql = "UPDATE jawaban_siswa SET jawaban = ?, pilihanJawaban = NULL, benar = NULL, poin = NULL, waktuDijawab = NOW() WHERE id = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("si", $jawaban, $existing['id']);
                }
            } else {
                // Insert new answer
                if ($pilihanJawaban !== null) {
                    $sql = "INSERT INTO jawaban_siswa (ujian_id, siswa_id, soal_id, jawaban, pilihanJawaban, benar, poin, waktuDijawab) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("iiissid", $ujian_siswa['ujian_id'], $ujian_siswa['siswa_id'], $soal_id, $jawaban, $pilihanJawaban, $benar, $poin);
                } else {
                    // For essay questions, keep benar and poin as NULL for manual grading
                    $sql = "INSERT INTO jawaban_siswa (ujian_id, siswa_id, soal_id, jawaban, waktuDijawab) VALUES (?, ?, ?, ?, NOW())";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("iiis", $ujian_siswa['ujian_id'], $ujian_siswa['siswa_id'], $soal_id, $jawaban);
                }
            }
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Jawaban berhasil disimpan'];
            } else {
                return ['success' => false, 'message' => 'Gagal menyimpan jawaban: ' . $stmt->error];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Get student's saved answers
    public function getJawabanSiswa($ujian_siswa_id) {
        try {
            // First get ujian_id and siswa_id from ujian_siswa table
            $sql_us = "SELECT ujian_id, siswa_id FROM ujian_siswa WHERE id = ?";
            $stmt_us = $this->conn->prepare($sql_us);
            $stmt_us->bind_param("i", $ujian_siswa_id);
            $stmt_us->execute();
            $ujian_siswa = $stmt_us->get_result()->fetch_assoc();
            
            if (!$ujian_siswa) {
                return [];
            }
            
            $sql = "SELECT js.soal_id, js.jawaban, js.pilihanJawaban, s.tipeSoal 
                    FROM jawaban_siswa js 
                    JOIN soal s ON js.soal_id = s.id 
                    WHERE js.ujian_id = ? AND js.siswa_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_siswa['ujian_id'], $ujian_siswa['siswa_id']);
            $stmt->execute();
            
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $answers = [];
            
            foreach ($result as $row) {
                // For multiple choice, use pilihanJawaban if available, otherwise jawaban
                if ($row['tipeSoal'] === 'pilihan_ganda' && !empty($row['pilihanJawaban'])) {
                    $answers[$row['soal_id']] = $row['pilihanJawaban'];
                } else {
                    $answers[$row['soal_id']] = $row['jawaban'];
                }
            }
            
            return $answers;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Reset ujian siswa (untuk guru) - hapus progress ujian siswa tertentu
    public function resetUjianSiswa($ujian_id, $siswa_id, $guru_id) {
        try {
            // Pastikan ujian milik guru yang sedang login
            $sqlCek = "SELECT id FROM ujian WHERE id = ? AND guru_id = ?";
            $stmtCek = $this->conn->prepare($sqlCek);
            $stmtCek->bind_param("ii", $ujian_id, $guru_id);
            $stmtCek->execute();
            
            if ($stmtCek->get_result()->num_rows === 0) {
                return ['success' => false, 'message' => 'Ujian tidak ditemukan atau bukan milik Anda'];
            }
            
            $this->conn->begin_transaction();
            
            // Hapus jawaban siswa terlebih dahulu
            $sqlJawaban = "DELETE js FROM jawaban_siswa js 
                          JOIN ujian_siswa us ON js.ujian_siswa_id = us.id 
                          WHERE us.ujian_id = ? AND us.siswa_id = ?";
            $stmtJawaban = $this->conn->prepare($sqlJawaban);
            $stmtJawaban->bind_param("ii", $ujian_id, $siswa_id);
            $stmtJawaban->execute();
            
            // Hapus record ujian_siswa
            $sqlUjianSiswa = "DELETE FROM ujian_siswa WHERE ujian_id = ? AND siswa_id = ?";
            $stmtUjianSiswa = $this->conn->prepare($sqlUjianSiswa);
            $stmtUjianSiswa->bind_param("ii", $ujian_id, $siswa_id);
            $stmtUjianSiswa->execute();
            
            $this->conn->commit();
            
            return ['success' => true, 'message' => 'Ujian siswa berhasil direset'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Reset semua siswa dalam ujian (untuk guru)
    public function resetSemuaSiswaUjian($ujian_id, $guru_id) {
        try {
            // Pastikan ujian milik guru yang sedang login
            $sqlCek = "SELECT id FROM ujian WHERE id = ? AND guru_id = ?";
            $stmtCek = $this->conn->prepare($sqlCek);
            $stmtCek->bind_param("ii", $ujian_id, $guru_id);
            $stmtCek->execute();
            
            if ($stmtCek->get_result()->num_rows === 0) {
                return ['success' => false, 'message' => 'Ujian tidak ditemukan atau bukan milik Anda'];
            }
            
            $this->conn->begin_transaction();
            
            // Hapus semua jawaban siswa untuk ujian ini
            $sqlJawaban = "DELETE js FROM jawaban_siswa js 
                          JOIN ujian_siswa us ON js.ujian_siswa_id = us.id 
                          WHERE us.ujian_id = ?";
            $stmtJawaban = $this->conn->prepare($sqlJawaban);
            $stmtJawaban->bind_param("i", $ujian_id);
            $stmtJawaban->execute();
            
            // Hapus semua record ujian_siswa untuk ujian ini
            $sqlUjianSiswa = "DELETE FROM ujian_siswa WHERE ujian_id = ?";
            $stmtUjianSiswa = $this->conn->prepare($sqlUjianSiswa);
            $stmtUjianSiswa->bind_param("i", $ujian_id);
            $stmtUjianSiswa->execute();
            
            $this->conn->commit();
            
            return ['success' => true, 'message' => 'Semua progress siswa berhasil direset'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Get ujian siswa by ujian_id and siswa_id
    public function getUjianSiswaByUjianIdAndSiswaId($ujian_id, $siswa_id) {
        try {
            $sql = "SELECT * FROM ujian_siswa WHERE ujian_id = ? AND siswa_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_id, $siswa_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }

    // Mendapatkan ujian yang diarsipkan (status selesai) berdasarkan guru
    public function getArchivedUjianByGuru($guru_id) {
        try {
            $sql = "SELECT u.*, k.namaKelas, k.gambar_kelas,
                           COUNT(DISTINCT us.siswa_id) as jumlahPeserta,
                           COUNT(DISTINCT s.id) as jumlahSoal,
                           u.dibuat as updatedAt
                    FROM ujian u 
                    LEFT JOIN kelas k ON u.kelas_id = k.id
                    LEFT JOIN ujian_siswa us ON u.id = us.ujian_id
                    LEFT JOIN soal s ON u.id = s.ujian_id
                    WHERE u.guru_id = ? AND u.status = 'selesai'
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

    // Get detail jawaban untuk guru (untuk halaman detail-jawaban-guru.php)
    public function getDetailJawabanGuru($ujian_id, $ujian_siswa_id, $guru_id) {
        try {
            // Verify ujian belongs to guru and get ujian data
            $sql = "SELECT u.*, k.namaKelas, usr.namaLengkap as namaGuru, usr.email as emailGuru, usr.fotoProfil as fotoGuru
                    FROM ujian u
                    JOIN kelas k ON u.kelas_id = k.id
                    JOIN users usr ON u.guru_id = usr.id
                    WHERE u.id = ? AND u.guru_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_id, $guru_id);
            $stmt->execute();
            $ujian_data = $stmt->get_result()->fetch_assoc();
            
            if (!$ujian_data) {
                return ['error' => 'not_authorized', 'message' => 'Ujian tidak ditemukan atau tidak memiliki akses'];
            }
            
            // Get siswa data and hasil ujian
            $sql = "SELECT u.id, u.namaLengkap, u.email, u.fotoProfil, us.id as ujian_siswa_id, us.totalNilai, 
                           us.jumlahBenar, us.jumlahSalah, us.waktuMulai, us.waktuSelesai, us.status
                    FROM ujian_siswa us
                    JOIN users u ON us.siswa_id = u.id
                    WHERE us.id = ? AND us.ujian_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_siswa_id, $ujian_id);
            $stmt->execute();
            $siswa_data = $stmt->get_result()->fetch_assoc();
            
            if (!$siswa_data) {
                return ['error' => 'student_not_found', 'message' => 'Data siswa tidak ditemukan'];
            }
            
            // Get soal data with student answers 
            $sql = "SELECT s.id, s.pertanyaan, s.tipeSoal, s.kunciJawaban, s.poin, s.nomorSoal,
                           js.jawaban, js.pilihanJawaban, js.benar, js.poin as poin_jawaban, 
                           js.waktuDijawab
                    FROM soal s
                    LEFT JOIN jawaban_siswa js ON s.id = js.soal_id AND js.ujian_id = ? AND js.siswa_id = ?
                    WHERE s.ujian_id = ?
                    ORDER BY s.nomorSoal ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $ujian_id, $siswa_data['id'], $ujian_id);
            $stmt->execute();
            $soal_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            if (empty($soal_data)) {
                return ['error' => 'no_questions', 'message' => 'Tidak ada soal ditemukan'];
            }
            
            // Get pilihan jawaban for multiple choice questions
            foreach ($soal_data as &$soal) {
                if ($soal['tipeSoal'] === 'pilihan_ganda') {
                    $sql_pilihan = "SELECT opsi, teksJawaban, benar FROM pilihan_jawaban WHERE soal_id = ? ORDER BY opsi";
                    $stmt_pilihan = $this->conn->prepare($sql_pilihan);
                    $stmt_pilihan->bind_param("i", $soal['id']);
                    $stmt_pilihan->execute();
                    $pilihan_result = $stmt_pilihan->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    $soal['pilihan_array'] = [];
                    foreach ($pilihan_result as $pilihan) {
                        $soal['pilihan_array'][$pilihan['opsi']] = [
                            'teks' => $pilihan['teksJawaban'],
                            'benar' => (bool)$pilihan['benar']
                        ];
                    }
                }
            }
            unset($soal);
            
            return [
                'ujian' => $ujian_data,
                'siswa' => $siswa_data,
                'soal_list' => $soal_data,
                'hasil_ujian' => $siswa_data // Alias for compatibility
            ];
            
        } catch (Exception $e) {
            return ['error' => 'database_error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // ===== ADMIN METHODS =====
    
    public function getUjian($search = '', $status_filter = '', $mapel_filter = '', $guru_filter = '', $sort_by = 'id', $sort_order = 'DESC', $page = 1, $per_page = 20) {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT u.*, 
                       us.namaLengkap as nama_guru, 
                       us.email as email_guru,
                       (SELECT COUNT(*) FROM soal WHERE ujian_id = u.id) as total_soal,
                       u.namaUjian as judul,
                       u.mataPelajaran as mata_pelajaran,
                       CONCAT(u.tanggalUjian, ' ', u.waktuMulai) as tanggal_mulai,
                       u.dibuat as created_at
                FROM ujian u 
                LEFT JOIN users us ON u.guru_id = us.id 
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        // Add search filter
        if (!empty($search)) {
            $sql .= " AND (u.namaUjian LIKE ? OR u.deskripsi LIKE ? OR u.mataPelajaran LIKE ? OR us.namaLengkap LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= 'ssss';
        }

        // Add status filter
        if (!empty($status_filter)) {
            $sql .= " AND u.status = ?";
            $params[] = $status_filter;
            $types .= 's';
        }

        // Add mata pelajaran filter
        if (!empty($mapel_filter)) {
            $sql .= " AND u.mataPelajaran = ?";
            $params[] = $mapel_filter;
            $types .= 's';
        }

        // Add guru filter
        if (!empty($guru_filter)) {
            $sql .= " AND u.guru_id = ?";
            $params[] = $guru_filter;
            $types .= 'i';
        }

        // Add sorting
        $allowed_sort = ['id', 'judul', 'mata_pelajaran', 'status', 'tanggal_mulai', 'created_at', 'nama_guru'];
        if (in_array($sort_by, $allowed_sort)) {
            $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
            if ($sort_by === 'nama_guru') {
                $sql .= " ORDER BY us.namaLengkap $sort_order";
            } else if ($sort_by === 'judul') {
                $sql .= " ORDER BY u.namaUjian $sort_order";
            } else if ($sort_by === 'mata_pelajaran') {
                $sql .= " ORDER BY u.mataPelajaran $sort_order";
            } else if ($sort_by === 'tanggal_mulai') {
                $sql .= " ORDER BY u.tanggalUjian $sort_order, u.waktuMulai $sort_order";
            } else if ($sort_by === 'created_at') {
                $sql .= " ORDER BY u.dibuat $sort_order";
            } else {
                $sql .= " ORDER BY u.$sort_by $sort_order";
            }
        }
        
        // Add limit
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function countUjian($search = '', $status_filter = '', $mapel_filter = '', $guru_filter = '') {
        $sql = "SELECT COUNT(*) as total 
                FROM ujian u 
                LEFT JOIN users us ON u.guru_id = us.id 
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        // Add search filter
        if (!empty($search)) {
            $sql .= " AND (u.namaUjian LIKE ? OR u.deskripsi LIKE ? OR u.mataPelajaran LIKE ? OR us.namaLengkap LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= 'ssss';
        }
        
        // Add status filter
        if (!empty($status_filter)) {
            $sql .= " AND u.status = ?";
            $params[] = $status_filter;
            $types .= 's';
        }
        
        // Add mata pelajaran filter
        if (!empty($mapel_filter)) {
            $sql .= " AND u.mataPelajaran = ?";
            $params[] = $mapel_filter;
            $types .= 's';
        }
        
        // Add guru filter
        if (!empty($guru_filter)) {
            $sql .= " AND u.guru_id = ?";
            $params[] = $guru_filter;
            $types .= 'i';
        }
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }
    
    public function getUjianStats() {
        $stats = [
            'total_ujian' => 0,
            'ujian_aktif' => 0,
            'ujian_selesai' => 0,
            'ujian_draft' => 0,
            'ujian_arsip' => 0
        ];
        
        $sql = "SELECT 
                    COUNT(*) as total_ujian,
                    SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as ujian_aktif,
                    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as ujian_selesai,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as ujian_draft,
                    SUM(CASE WHEN status = 'arsip' THEN 1 ELSE 0 END) as ujian_arsip
                FROM ujian";
        
        $result = $this->conn->query($sql);
        if ($result) {
            $stats = $result->fetch_assoc();
        }
        
        return $stats;
    }
    
    public function getTeachers() {
        $sql = "SELECT id, namaLengkap as nama, email FROM users WHERE role = 'guru' ORDER BY namaLengkap ASC";
        $result = $this->conn->query($sql);
        
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    public function getMataPelajaran() {
        $sql = "SELECT DISTINCT mataPelajaran FROM ujian WHERE mataPelajaran IS NOT NULL ORDER BY mataPelajaran ASC";
        $result = $this->conn->query($sql);
        
        $mapel_list = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $mapel_list[] = $row['mataPelajaran'];
            }
        }
        
        // Add common subjects if not in database
        $common_subjects = ['Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'IPA', 'IPS', 'PKN', 'Agama', 'Olahraga', 'Seni', 'TIK'];
        foreach ($common_subjects as $subject) {
            if (!in_array($subject, $mapel_list)) {
                $mapel_list[] = $subject;
            }
        }
        
        sort($mapel_list);
        return $mapel_list;
    }
    
    public function getKelas() {
        $sql = "SELECT id, namaKelas, mataPelajaran FROM kelas WHERE status = 'aktif' ORDER BY namaKelas ASC";
        $result = $this->conn->query($sql);
        
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    public function createUjianAdmin($data) {
        $sql = "INSERT INTO ujian (namaUjian, mataPelajaran, kelas_id, guru_id, deskripsi, durasi, status, tanggalUjian, waktuMulai, dibuat) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        // Parse datetime for tanggal_mulai
        $tanggal_ujian = date('Y-m-d'); // Default ke hari ini
        $waktu_mulai = '08:00:00'; // Default jam 8 pagi
        if (!empty($data['tanggal_mulai'])) {
            $datetime = new DateTime($data['tanggal_mulai']);
            $tanggal_ujian = $datetime->format('Y-m-d');
            $waktu_mulai = $datetime->format('H:i:s');
        }
        
        // Ambil waktu selesai berdasarkan durasi (tambah durasi ke waktu mulai)
        $waktu_selesai_datetime = new DateTime($tanggal_ujian . ' ' . $waktu_mulai);
        $waktu_selesai_datetime->add(new DateInterval('PT' . $data['durasi'] . 'M'));
        $waktu_selesai = $waktu_selesai_datetime->format('H:i:s');
        
        // Update SQL untuk menyertakan waktuSelesai
        $sql = "INSERT INTO ujian (namaUjian, mataPelajaran, kelas_id, guru_id, deskripsi, durasi, status, tanggalUjian, waktuMulai, waktuSelesai, dibuat) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssiisissss', 
            $data['judul'],
            $data['mata_pelajaran'],
            $data['kelas_id'],
            $data['guru_id'],
            $data['deskripsi'],
            $data['durasi'],
            $data['status'],
            $tanggal_ujian,
            $waktu_mulai,
            $waktu_selesai
        );
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Ujian berhasil dibuat',
                'id' => $this->conn->insert_id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Gagal membuat ujian: ' . $this->conn->error
            ];
        }
    }
    
    public function updateUjianAdmin($id, $data) {
        $sql = "UPDATE ujian SET 
                    namaUjian = ?, 
                    mataPelajaran = ?, 
                    kelas_id = ?,
                    guru_id = ?, 
                    deskripsi = ?, 
                    durasi = ?, 
                    status = ?, 
                    tanggalUjian = ?,
                    waktuMulai = ?,
                    waktuSelesai = ?
                WHERE id = ?";
        
        // Parse datetime for tanggal_mulai
        $tanggal_ujian = date('Y-m-d');
        $waktu_mulai = '08:00:00';
        if (!empty($data['tanggal_mulai'])) {
            $datetime = new DateTime($data['tanggal_mulai']);
            $tanggal_ujian = $datetime->format('Y-m-d');
            $waktu_mulai = $datetime->format('H:i:s');
        }
        
        // Hitung waktu selesai berdasarkan durasi
        $waktu_selesai_datetime = new DateTime($tanggal_ujian . ' ' . $waktu_mulai);
        $waktu_selesai_datetime->add(new DateInterval('PT' . $data['durasi'] . 'M'));
        $waktu_selesai = $waktu_selesai_datetime->format('H:i:s');
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssiisissssi', 
            $data['judul'],
            $data['mata_pelajaran'],
            $data['kelas_id'],
            $data['guru_id'],
            $data['deskripsi'],
            $data['durasi'],
            $data['status'],
            $tanggal_ujian,
            $waktu_mulai,
            $waktu_selesai,
            $id
        );
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return [
                    'success' => true,
                    'message' => 'Ujian berhasil diperbarui'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Tidak ada perubahan data atau ujian tidak ditemukan'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Gagal memperbarui ujian: ' . $this->conn->error
            ];
        }
    }
    
    public function deleteUjianAdmin($id) {
        // Check if ujian has soal
        $check_sql = "SELECT COUNT(*) as count FROM soal WHERE ujian_id = ?";
        $check_stmt = $this->conn->prepare($check_sql);
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $soal_count = $check_stmt->get_result()->fetch_assoc()['count'];
        
        if ($soal_count > 0) {
            return [
                'success' => false,
                'message' => 'Tidak dapat menghapus ujian yang sudah memiliki soal. Hapus soal terlebih dahulu.'
            ];
        }
        
        // Check if ujian has jawaban siswa
        $check_jawaban_sql = "SELECT COUNT(*) as count FROM ujian_siswa WHERE ujian_id = ?";
        $check_jawaban_stmt = $this->conn->prepare($check_jawaban_sql);
        $check_jawaban_stmt->bind_param('i', $id);
        $check_jawaban_stmt->execute();
        $jawaban_count = $check_jawaban_stmt->get_result()->fetch_assoc()['count'];
        
        if ($jawaban_count > 0) {
            return [
                'success' => false,
                'message' => 'Tidak dapat menghapus ujian yang sudah memiliki jawaban siswa.'
            ];
        }
        
        $sql = "DELETE FROM ujian WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return [
                    'success' => true,
                    'message' => 'Ujian berhasil dihapus'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ujian tidak ditemukan'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Gagal menghapus ujian: ' . $this->conn->error
            ];
        }
    }
    
    public function updateField($id, $field, $value) {
        $allowed_fields = ['namaUjian', 'mataPelajaran', 'durasi', 'status', 'tanggal_mulai', 'deskripsi'];
        
        if (!in_array($field, $allowed_fields)) {
            return [
                'success' => false,
                'message' => 'Field tidak diizinkan untuk diubah'
            ];
        }
        
        // Validate specific fields
        if ($field === 'durasi' && (!is_numeric($value) || $value < 1)) {
            return [
                'success' => false,
                'message' => 'Durasi harus berupa angka positif'
            ];
        }
        
        if ($field === 'status' && !in_array($value, ['draft', 'aktif', 'selesai', 'arsip'])) {
            return [
                'success' => false,
                'message' => 'Status tidak valid'
            ];
        }
        
        if ($field === 'tanggal_mulai' && !empty($value) && !strtotime($value)) {
            return [
                'success' => false,
                'message' => 'Format tanggal tidak valid'
            ];
        }
        
        // Handle field mapping and special cases
        if ($field === 'judul') {
            $db_field = 'namaUjian';
        } else if ($field === 'mata_pelajaran') {
            $db_field = 'mataPelajaran';
        } else if ($field === 'tanggal_mulai') {
            // Handle datetime field specially
            if (empty($value)) {
                // Set both tanggalUjian and waktuMulai to NULL
                $sql = "UPDATE ujian SET tanggalUjian = NULL, waktuMulai = NULL WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('i', $id);
            } else {
                $datetime = new DateTime($value);
                $tanggal_ujian = $datetime->format('Y-m-d');
                $waktu_mulai = $datetime->format('H:i:s');
                
                $sql = "UPDATE ujian SET tanggalUjian = ?, waktuMulai = ? WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('ssi', $tanggal_ujian, $waktu_mulai, $id);
            }
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    return [
                        'success' => true,
                        'message' => 'Tanggal mulai berhasil diperbarui'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Tidak ada perubahan data atau ujian tidak ditemukan'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal memperbarui tanggal: ' . $this->conn->error
                ];
            }
        } else {
            $db_field = $field;
        }
        
        // Handle normal field update
        if ($field !== 'tanggal_mulai') {
            $sql = "UPDATE ujian SET $db_field = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            
            if ($field === 'durasi') {
                $stmt->bind_param('ii', $value, $id);
            } else {
                $stmt->bind_param('si', $value, $id);
            }
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    return [
                        'success' => true,
                        'message' => 'Data berhasil diperbarui'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Tidak ada perubahan data atau ujian tidak ditemukan'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal memperbarui data: ' . $this->conn->error
                ];
            }
        }
    }
    
    public function getUjianByIdAdmin($id) {
        $sql = "SELECT u.*, us.namaLengkap as nama_guru, us.email as email_guru,
                       u.namaUjian as judul,
                       u.mataPelajaran as mata_pelajaran,
                       CASE 
                           WHEN u.tanggalUjian IS NOT NULL AND u.waktuMulai IS NOT NULL 
                           THEN CONCAT(u.tanggalUjian, 'T', u.waktuMulai)
                           ELSE NULL
                       END as tanggal_mulai
                FROM ujian u 
                LEFT JOIN users us ON u.guru_id = us.id 
                WHERE u.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
