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
            // Tipe parameter: s (namaUjian), s(deskripsi), i(kelas_id), i(guru_id), s(mataPelajaran), s(tanggalUjian), s(waktuMulai), s(waktuSelesai), i(durasi)
            $stmt->bind_param("ssiissssi", $namaUjian, $deskripsi, $kelas_id, $guru_id, $mataPelajaran, $tanggalUjian, $waktuMulai, $waktuSelesai, $durasi);
            
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

    // Update ujian existing milik guru (hanya field identitas & waktu)
    public function updateUjian($ujian_id, $guru_id, $namaUjian, $deskripsi, $kelas_id, $mataPelajaran, $tanggalUjian, $waktuMulai, $waktuSelesai, $durasi, $shuffleQuestions = null, $showScore = null, $autoScore = null) {
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
            // Correct order: s s i s s s s i
            $types = 'ssissssi';
            $params = [$namaUjian, $deskripsi, $kelas_id, $mataPelajaran, $tanggalUjian, $waktuMulai, $waktuSelesai, $durasi];

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
            $sql = "SELECT u.*, k.namaKelas, k.gambarKover,
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
    public function getUjianBySiswa($siswa_id, $force_refresh = false) {
        try {
            // Force refresh untuk debugging
            if ($force_refresh) {
                // Clear any potential query cache
                $this->conn->query("SELECT 1");
            }
            
            $sql = "SELECT u.*, k.namaKelas, k.gambarKover, us.status as statusPengerjaan, us.totalNilai, us.waktuMulai, us.waktuSelesai, us.id as ujian_siswa_id,
                           CASE 
                               WHEN us.id IS NULL THEN 
                                   CASE 
                                       WHEN CONCAT(u.tanggalUjian, ' ', u.waktuSelesai) < NOW() THEN 'terlambat'
                                       WHEN CONCAT(u.tanggalUjian, ' ', u.waktuMulai) <= NOW() AND CONCAT(u.tanggalUjian, ' ', u.waktuSelesai) >= NOW() THEN 'dapat_dikerjakan'
                                       WHEN CONCAT(u.tanggalUjian, ' ', u.waktuMulai) > NOW() THEN 'belum_dimulai'
                                       ELSE 'belum_dikerjakan'
                                   END
                               WHEN us.status = 'selesai' THEN 'selesai'
                               WHEN us.status = 'sedang_mengerjakan' THEN 
                                   CASE 
                                       WHEN CONCAT(u.tanggalUjian, ' ', u.waktuSelesai) < NOW() THEN 'waktu_habis'
                                       ELSE 'sedang_mengerjakan'
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
            $stmt->bind_param("ii", $siswa_id, $siswa_id);
            $stmt->execute();
            
            $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Debug log
            if ($force_refresh) {
                error_log("getUjianBySiswa results for siswa_id $siswa_id: " . json_encode($results));
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
            $waktuSelesaiUjian = strtotime($tanggalUjian . ' ' . $waktuSelesai);
            $waktuSekarang = time();
            
            // Validasi waktu ujian (dengan toleransi 5 menit sebelum waktu mulai)
            if ($waktuSekarang < ($waktuMulaiUjian - 300)) {
                return ['success' => false, 'message' => 'Ujian belum dimulai'];
            }
            
            if ($waktuSekarang > $waktuSelesaiUjian) {
                return ['success' => false, 'message' => 'Waktu ujian telah berakhir'];
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
            $sql = "SELECT us.id, us.ujian_id, us.siswa_id, us.totalNilai, us.jumlahBenar, us.jumlahSalah, us.status,
                           u.namaLengkap
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
            $sql = "SELECT js.*, s.pertanyaan, s.tipeSoal, s.kunciJawaban, s.poin as poin_soal, s.nomorSoal
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
    
    // Mendapatkan data untuk mode koreksi swipe
    public function getDataKoreksiSwipe($ujian_id) {
        try {
            $sql = "SELECT us.id as ujian_siswa_id, us.siswa_id, u.namaLengkap as siswa_nama,
                           s.id as soal_id, s.nomorSoal, s.pertanyaan, s.tipeSoal, s.kunciJawaban, s.poin,
                           js.jawaban, js.pilihanJawaban, js.benar, js.poin as poin_jawaban
                    FROM ujian_siswa us
                    JOIN users u ON us.siswa_id = u.id
                    CROSS JOIN soal s
                    LEFT JOIN jawaban_siswa js ON us.id = js.ujian_siswa_id AND s.id = js.soal_id
                    WHERE us.ujian_id = ? AND s.ujian_id = ?
                    ORDER BY us.siswa_id ASC, s.nomorSoal ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_id, $ujian_id);
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
            // Check if answer already exists
            $sql = "SELECT id FROM jawaban_siswa WHERE ujian_siswa_id = ? AND soal_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ujian_siswa_id, $soal_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                // Update existing answer
                $sql = "UPDATE jawaban_siswa SET jawaban = ?, waktuDijawab = NOW() WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("si", $jawaban, $existing['id']);
            } else {
                // Insert new answer
                $sql = "INSERT INTO jawaban_siswa (ujian_siswa_id, soal_id, jawaban, waktuDijawab) VALUES (?, ?, ?, NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("iis", $ujian_siswa_id, $soal_id, $jawaban);
            }
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Jawaban berhasil disimpan'];
            } else {
                return ['success' => false, 'message' => 'Gagal menyimpan jawaban'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Get student's saved answers
    public function getJawabanSiswa($ujian_siswa_id) {
        try {
            $sql = "SELECT soal_id, jawaban FROM jawaban_siswa WHERE ujian_siswa_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ujian_siswa_id);
            $stmt->execute();
            
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $answers = [];
            
            foreach ($result as $row) {
                $answers[$row['soal_id']] = $row['jawaban'];
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
}
?>
