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
            // Cek apakah kelas ada dan tidak dikunci
            $sql = "SELECT id, maxSiswa, lock_class FROM kelas WHERE kodeKelas = ? AND status = 'aktif'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $kodeKelas);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                return ['success' => false, 'message' => 'Kode kelas tidak ditemukan'];
            }
            
            $kelas = $result->fetch_assoc();
            $kelas_id = $kelas['id'];
            
            // Cek apakah kelas dikunci
            if (isset($kelas['lock_class']) && $kelas['lock_class'] == 1) {
                return ['success' => false, 'message' => 'Kelas ini telah dikunci dan tidak menerima mahasiswa baru'];
            }
            
            // Cek apakah sudah join
            $sql = "SELECT id FROM kelas_siswa WHERE kelas_id = ? AND siswa_id = ? AND status = 'aktif'";
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
            $sql = "SELECT k.*, u.namaLengkap as namaGuru, u.email as emailGuru, u.fotoProfil as fotoProfilGuru
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
            $sql = "SELECT u.id, u.namaLengkap, u.email, u.fotoProfil, ks.tanggal_bergabung as tanggalBergabung
                    FROM users u 
                    JOIN kelas_siswa ks ON u.id = ks.siswa_id
                    WHERE ks.kelas_id = ? AND ks.status = 'aktif'
                    ORDER BY ks.tanggal_bergabung ASC";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("getSiswaKelas: Prepare failed - " . $this->conn->error);
                return [];
            }
            
            if (!$stmt->bind_param("i", $kelas_id)) {
                error_log("getSiswaKelas: Bind param failed - " . $stmt->error);
                return [];
            }
            
            if (!$stmt->execute()) {
                error_log("getSiswaKelas: Execute failed - " . $stmt->error);
                return [];
            }
            
            $result = $stmt->get_result();
            if (!$result) {
                error_log("getSiswaKelas: Get result failed - " . $stmt->error);
                return [];
            }
            
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("getSiswaKelas: Exception - " . $e->getMessage());
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

    // Update background kelas
    public function updateBackground($kelas_id, $gambar_kelas = null, $removeBackground = false) {
        try {
            if ($removeBackground) {
                $sql = "UPDATE kelas SET gambar_kelas = NULL WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $kelas_id);
            } else if ($gambar_kelas) {
                $sql = "UPDATE kelas SET gambar_kelas = ? WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("si", $gambar_kelas, $kelas_id);
            } else {
                return ['success' => false, 'message' => 'Tidak ada perubahan yang dilakukan'];
            }
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Latar belakang berhasil diperbarui'];
            } else {
                return ['success' => false, 'message' => 'Gagal memperbarui latar belakang'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Update permissions kelas
    public function updatePermissions($kelas_id, $permissions) {
        try {
            $restrict_posting = $permissions['restrict_posting'] ? 1 : 0;
            $restrict_comments = $permissions['restrict_comments'] ? 1 : 0;
            $lock_class = $permissions['lock_class'] ? 1 : 0;
            
            // First check if columns exist, if not add them
            $this->addPermissionColumns();
            
            $sql = "UPDATE kelas SET 
                    restrict_posting = ?, 
                    restrict_comments = ?, 
                    lock_class = ?
                    WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiii", $restrict_posting, $restrict_comments, $lock_class, $kelas_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Pengaturan perizinan berhasil diperbarui'];
            } else {
                return ['success' => false, 'message' => 'Gagal memperbarui pengaturan'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Helper function to add permission columns if they don't exist
    private function addPermissionColumns() {
        try {
            $columns = [
                'restrict_posting' => 'TINYINT(1) DEFAULT 0',
                'restrict_comments' => 'TINYINT(1) DEFAULT 0', 
                'lock_class' => 'TINYINT(1) DEFAULT 0'
            ];

            foreach ($columns as $column => $definition) {
                $sql = "SHOW COLUMNS FROM kelas LIKE '$column'";
                $result = $this->conn->query($sql);
                
                if ($result->num_rows == 0) {
                    $sql = "ALTER TABLE kelas ADD COLUMN $column $definition";
                    $this->conn->query($sql);
                }
            }
        } catch (Exception $e) {
            // Log error but don't fail the operation
            error_log("Error adding permission columns: " . $e->getMessage());
        }
    }

    // Upload dan handle file gambar
    public function handleImageUpload($file, $uploadDir = null) {
        try {
            // Set default upload directory 
            if ($uploadDir === null) {
                $uploadDir = __DIR__ . '/../../uploads/kelas/';
            }
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            $fileName = $file['name'];
            $fileSize = $file['size'];
            $fileTmp = $file['tmp_name'];
            $fileError = $file['error'];

            if ($fileError !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'Error uploading file: ' . $fileError];
            }

            if ($fileSize > $maxSize) {
                return ['success' => false, 'message' => 'File terlalu besar (maksimal 5MB)'];
            }

            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($fileExt, $allowedTypes)) {
                return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan: ' . implode(', ', $allowedTypes)];
            }

            $newFileName = uniqid() . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            // Debug logging
            error_log("Upload destination: " . $destination);
            error_log("Temp file: " . $fileTmp);
            error_log("Upload dir exists: " . (file_exists($uploadDir) ? 'yes' : 'no'));
            error_log("Upload dir writable: " . (is_writable($uploadDir) ? 'yes' : 'no'));

            if (move_uploaded_file($fileTmp, $destination)) {
                // Return relative path from web root
                return ['success' => true, 'filePath' => 'uploads/kelas/' . $newFileName];
            } else {
                return ['success' => false, 'message' => 'Gagal menyimpan file. Periksa permissions folder uploads.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Get user information by ID
    public function getUserById($user_id) {
        try {
            $sql = "SELECT id, namaLengkap as nama, email, fotoProfil, role FROM users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error in getUserById: " . $e->getMessage());
            return null;
        }
    }
}

// Handle AJAX requests - only if this file is accessed directly
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && basename($_SERVER['SCRIPT_NAME']) === 'kelas-logic.php') {
    session_start();
    
    // Check if user is logged in and is a guru
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $kelasLogic = new KelasLogic();
    $action = $_POST['action'];
    $kelas_id = isset($_POST['kelas_id']) ? intval($_POST['kelas_id']) : 0;

    switch ($action) {
        case 'update_background':
            $removeBackground = isset($_POST['remove_background']);
            $gambar_kelas = null;
            
            if (!$removeBackground && isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $kelasLogic->handleImageUpload($_FILES['background_image']);
                
                if ($uploadResult['success']) {
                    $gambar_kelas = $uploadResult['filePath'];
                } else {
                    echo json_encode($uploadResult);
                    exit();
                }
            }
            
            $result = $kelasLogic->updateBackground($kelas_id, $gambar_kelas, $removeBackground);
            echo json_encode($result);
            break;

        case 'update_class':
            $namaKelas = $_POST['namaKelas'] ?? '';
            $mataPelajaran = $_POST['mataPelajaran'] ?? '';
            $deskripsi = $_POST['deskripsi'] ?? '';
            $maxSiswa = isset($_POST['maxSiswa']) ? intval($_POST['maxSiswa']) : 30;
            
            echo json_encode($kelasLogic->updateKelas($kelas_id, $namaKelas, $deskripsi, $mataPelajaran, $maxSiswa));
            break;

        case 'remove_student':
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            echo json_encode($kelasLogic->hapusSiswaKelas($kelas_id, $student_id));
            break;

        case 'update_permissions':
            $permissions = [
                'restrict_posting' => isset($_POST['restrict_posting']) ? ($_POST['restrict_posting'] === 'on' || $_POST['restrict_posting'] === 'true' || $_POST['restrict_posting'] === true) : false,
                'restrict_comments' => isset($_POST['restrict_comments']) ? ($_POST['restrict_comments'] === 'on' || $_POST['restrict_comments'] === 'true' || $_POST['restrict_comments'] === true) : false,
                'lock_class' => isset($_POST['lock_class']) ? ($_POST['lock_class'] === 'on' || $_POST['lock_class'] === 'true' || $_POST['lock_class'] === true) : false
            ];
            
            echo json_encode($kelasLogic->updatePermissions($kelas_id, $permissions));
            break;

        case 'get_class_details':
            $detail = $kelasLogic->getDetailKelas($kelas_id);
            if ($detail) {
                echo json_encode(['success' => true, 'data' => $detail]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Kelas tidak ditemukan']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}
?>
