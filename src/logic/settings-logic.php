<?php
require_once 'koneksi.php';
require_once 'user-logic.php';

class SettingsLogic {
    private $conn;
    private $userLogic;
    
    public function __construct() {
        $this->conn = getConnection();
        $this->userLogic = new UserLogic();
    }
    
    // Update profil lengkap
    public function updateProfil($user_id, $data) {
        try {
            // Validasi input
            if (empty($data['namaLengkap']) || empty($data['username']) || empty($data['email'])) {
                return ['success' => false, 'message' => 'Nama lengkap, username, dan email tidak boleh kosong'];
            }
            
            // Validasi email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Format email tidak valid'];
            }
            
            // Cek apakah username sudah digunakan user lain
            $checkUsername = "SELECT id FROM users WHERE username = ? AND id != ?";
            $stmt = $this->conn->prepare($checkUsername);
            $stmt->bind_param("si", $data['username'], $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => 'Username sudah digunakan oleh user lain'];
            }
            
            // Cek apakah email sudah digunakan user lain
            $checkEmail = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = $this->conn->prepare($checkEmail);
            $stmt->bind_param("si", $data['email'], $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => 'Email sudah digunakan oleh user lain'];
            }
            
            // Update data profil
            $sql = "UPDATE users SET 
                        namaLengkap = ?, 
                        username = ?, 
                        email = ?, 
                        bio = ?, 
                        nomorTelpon = ?, 
                        tanggalLahir = ?,
                        diperbarui = NOW()
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssssi", 
                $data['namaLengkap'],
                $data['username'],
                $data['email'],
                $data['bio'],
                $data['nomorTelpon'],
                $data['tanggalLahir'],
                $user_id
            );
            
            if ($stmt->execute()) {
                // Update session data jika berhasil
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['namaLengkap'] = $data['namaLengkap'];
                    $_SESSION['user']['username'] = $data['username'];
                    $_SESSION['user']['email'] = $data['email'];
                    $_SESSION['user']['bio'] = $data['bio'] ?? '';
                    $_SESSION['user']['nomorTelpon'] = $data['nomorTelpon'] ?? '';
                    $_SESSION['user']['tanggalLahir'] = $data['tanggalLahir'] ?? '';
                }
                
                return ['success' => true, 'message' => 'Profil berhasil diperbarui'];
            } else {
                return ['success' => false, 'message' => 'Gagal memperbarui profil'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Upload foto profil
    public function uploadFotoProfil($user_id, $file) {
        try {
            // Validasi file
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF'];
            }
            
            if ($file['size'] > $maxSize) {
                return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 2MB'];
            }
            
            // Buat nama file unik
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'profile_' . $user_id . '_' . time() . '.' . $extension;
            $uploadDir = '/opt/lampp/htdocs/lms/uploads/profile/';
            $uploadPath = $uploadDir . $fileName;
            
            // Buat direktori jika belum ada
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Hapus foto profil lama jika ada
            $oldPhoto = $this->getFotoProfil($user_id);
            if ($oldPhoto && strpos($oldPhoto, 'uploads/profile/') === 0) {
                $oldPhotoPath = '/opt/lampp/htdocs/lms/' . $oldPhoto;
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }
            
            // Upload file baru
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Update database dengan relative path
                $relativePath = 'uploads/profile/' . $fileName;
                $sql = "UPDATE users SET fotoProfil = ? WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("si", $relativePath, $user_id);
                
                if ($stmt->execute()) {
                    // Update session data jika berhasil
                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    if (isset($_SESSION['user'])) {
                        $_SESSION['user']['foto_profil'] = $relativePath;
                        $_SESSION['user']['fotoProfil'] = $relativePath; // Backup field name
                    }
                    
                    return [
                        'success' => true, 
                        'message' => 'Foto profil berhasil diperbarui',
                        'fileName' => $fileName,
                        'relativePath' => $relativePath
                    ];
                } else {
                    // Hapus file jika gagal update database
                    unlink($uploadPath);
                    return ['success' => false, 'message' => 'Gagal menyimpan foto profil ke database'];
                }
            } else {
                return ['success' => false, 'message' => 'Gagal mengupload file'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Delete foto profil
    public function deleteFotoProfil($user_id) {
        try {
            // Get current photo filename
            $currentPhoto = $this->getFotoProfil($user_id);
            
            // Update database to remove photo reference
            $sql = "UPDATE users SET fotoProfil = NULL WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                // Delete physical file if exists
                if ($currentPhoto && !empty($currentPhoto)) {
                    $uploadDir = '../../uploads/profile/';
                    $filePath = $uploadDir . basename($currentPhoto);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                // Update session
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['foto_profil'] = null;
                }
                
                return [
                    'success' => true, 
                    'message' => 'Foto profil berhasil dihapus'
                ];
            } else {
                return ['success' => false, 'message' => 'Gagal menghapus foto profil dari database'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Mendapatkan foto profil user
    private function getFotoProfil($user_id) {
        $sql = "SELECT fotoProfil FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result['fotoProfil'] : null;
    }
    
    // Ganti password dengan validasi keamanan
    public function gantiPassword($user_id, $passwordLama, $passwordBaru, $konfirmasiPassword) {
        try {
            // Validasi input
            if (empty($passwordLama) || empty($passwordBaru) || empty($konfirmasiPassword)) {
                return ['success' => false, 'message' => 'Semua field password harus diisi'];
            }
            
            if ($passwordBaru !== $konfirmasiPassword) {
                return ['success' => false, 'message' => 'Password baru dan konfirmasi tidak cocok'];
            }
            
            // Validasi kekuatan password
            if (strlen($passwordBaru) < 8) {
                return ['success' => false, 'message' => 'Password baru minimal 8 karakter'];
            }
            
            if (!preg_match('/[A-Z]/', $passwordBaru)) {
                return ['success' => false, 'message' => 'Password baru harus mengandung huruf besar'];
            }
            
            if (!preg_match('/[a-z]/', $passwordBaru)) {
                return ['success' => false, 'message' => 'Password baru harus mengandung huruf kecil'];
            }
            
            if (!preg_match('/[0-9]/', $passwordBaru)) {
                return ['success' => false, 'message' => 'Password baru harus mengandung angka'];
            }
            
            // Gunakan fungsi dari UserLogic
            return $this->userLogic->gantiPassword($user_id, $passwordLama, $passwordBaru);
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Mendapatkan data profil lengkap
    public function getProfilLengkap($user_id) {
        try {
            $sql = "SELECT id, username, email, namaLengkap, bio, nomorTelpon, tanggalLahir, 
                           fotoProfil, role, status, terakhirLogin, dibuat, diperbarui
                    FROM users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $user = $stmt->get_result()->fetch_assoc();
            
            if ($user) {
                // Format tanggal untuk display
                if ($user['tanggalLahir']) {
                    $user['tanggalLahir_formatted'] = date('d/m/Y', strtotime($user['tanggalLahir']));
                }
                
                if ($user['terakhirLogin']) {
                    $user['terakhirLogin_formatted'] = date('d/m/Y H:i', strtotime($user['terakhirLogin']));
                }
                
                // URL foto profil
                if ($user['fotoProfil']) {
                    // Check if fotoProfil already contains the full path
                    if (strpos($user['fotoProfil'], 'uploads/profile/') === 0) {
                        // Already has the correct path structure
                        $user['fotoProfil_url'] = '../../' . $user['fotoProfil'];
                    } else {
                        // Legacy format - just filename
                        $user['fotoProfil_url'] = '../../uploads/profile/' . $user['fotoProfil'];
                    }
                } else {
                    // Use inline SVG instead of external API
                    $initial = substr($user['namaLengkap'], 0, 1);
                    $user['fotoProfil_url'] = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='96' height='96' viewBox='0 0 96 96'%3E%3Crect width='96' height='96' fill='%23ff6347'/%3E%3Ctext x='48' y='56' text-anchor='middle' fill='white' font-size='32' font-family='Arial'%3E" . $initial . "%3C/text%3E%3C/svg%3E";
                }
            }
            
            return $user;
        } catch (Exception $e) {
            error_log("getProfilLengkap error: " . $e->getMessage());
            return null;
        }
    }
    
    // Mendapatkan statistik keamanan akun
    public function getStatistikKeamanan($user_id) {
        try {
            $stats = [];
            
            // Terakhir ganti password (asumsi ada log atau gunakan diperbarui)
            $sql = "SELECT diperbarui FROM users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result && $result['diperbarui']) {
                $stats['terakhir_update_password'] = date('d/m/Y', strtotime($result['diperbarui']));
            }
            
            // Total login dalam 30 hari terakhir (asumsi ada tabel login_log)
            $sql = "SELECT COUNT(*) as total_login FROM users WHERE id = ? AND terakhirLogin >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stats['login_30_hari'] = $result['total_login'] ?? 0;
            
            // Status 2FA (untuk fitur masa depan)
            $stats['two_factor_enabled'] = false;
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Validasi session dan autorisasi
    public function validateUserAccess($user_id) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user']) || $_SESSION['user']['id'] != $user_id) {
            return false;
        }
        
        return true;
    }
    
    // Log aktivitas user (untuk audit trail)
    public function logActivity($user_id, $activity, $description = '') {
        try {
            $sql = "INSERT INTO user_activity_log (user_id, activity, description, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("issss", $user_id, $activity, $description, $ip, $userAgent);
            $stmt->execute();
        } catch (Exception $e) {
            // Log error but don't break the main functionality
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
    
    // Check username availability (exclude current user)
    public function checkUsernameAvailability($user_id, $username) {
        try {
            $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $username, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Return true if available (no other user has this username)
            return $result->num_rows === 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Check email availability (exclude current user)
    public function checkEmailAvailability($user_id, $email) {
        try {
            $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Return true if available (no other user has this email)
            return $result->num_rows === 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Update informasi username dan personal (untuk form terpisah)
    public function updateUsernameInfo($user_id, $data) {
        try {
            // Validasi input
            if (empty($data['namaLengkap']) || empty($data['username'])) {
                return ['success' => false, 'message' => 'Nama lengkap dan username tidak boleh kosong'];
            }
            
            // Cek apakah username sudah digunakan user lain
            $checkUsername = "SELECT id FROM users WHERE username = ? AND id != ?";
            $stmt = $this->conn->prepare($checkUsername);
            $stmt->bind_param("si", $data['username'], $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => 'Username sudah digunakan oleh user lain'];
            }
            
            // Update data username dan personal
            $sql = "UPDATE users SET 
                        namaLengkap = ?, 
                        username = ?, 
                        bio = ?,
                        diperbarui = NOW()
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssi", 
                $data['namaLengkap'],
                $data['username'],
                $data['bio'],
                $user_id
            );
            
            if ($stmt->execute()) {
                // Update session data jika berhasil
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['namaLengkap'] = $data['namaLengkap'];
                    $_SESSION['user']['username'] = $data['username'];
                }
                
                return ['success' => true, 'message' => 'Informasi pribadi berhasil diperbarui'];
            } else {
                return ['success' => false, 'message' => 'Gagal memperbarui informasi pribadi'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Update informasi kontak (untuk form terpisah)
    public function updateContactInfo($user_id, $data) {
        try {
            // Validasi input
            if (empty($data['email'])) {
                return ['success' => false, 'message' => 'Email tidak boleh kosong'];
            }
            
            // Validasi email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Format email tidak valid'];
            }
            
            // Cek apakah email sudah digunakan user lain
            $checkEmail = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = $this->conn->prepare($checkEmail);
            $stmt->bind_param("si", $data['email'], $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => 'Email sudah digunakan oleh user lain'];
            }
            
            // Update data kontak
            $sql = "UPDATE users SET 
                        email = ?, 
                        nomorTelpon = ?, 
                        tanggalLahir = ?,
                        diperbarui = NOW()
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssi", 
                $data['email'],
                $data['nomorTelpon'],
                $data['tanggalLahir'],
                $user_id
            );
            
            if ($stmt->execute()) {
                // Update session data jika berhasil
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['email'] = $data['email'];
                }
                
                return ['success' => true, 'message' => 'Informasi kontak berhasil diperbarui'];
            } else {
                return ['success' => false, 'message' => 'Gagal memperbarui informasi kontak'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
