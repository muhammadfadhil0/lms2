<?php
require_once 'koneksi.php';

class UserLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Registrasi user baru
    public function register($username, $email, $password, $namaLengkap, $role = 'siswa') {
        try {
            // Cek apakah username sudah ada
            if ($this->isUsernameExists($username)) {
                return ['success' => false, 'message' => 'Username sudah digunakan'];
            }
            
            // Cek apakah email sudah ada
            if ($this->isEmailExists($email)) {
                return ['success' => false, 'message' => 'Email sudah digunakan'];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, email, password, namaLengkap, role, status) 
                    VALUES (?, ?, ?, ?, ?, 'pending')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssss", $username, $email, $hashedPassword, $namaLengkap, $role);
            
            if ($stmt->execute()) {
                $user_id = $this->conn->insert_id;
                
                // Buat pengaturan default
                $this->buatPengaturanDefault($user_id);
                
                return [
                    'success' => true, 
                    'message' => 'Registrasi berhasil',
                    'user_id' => $user_id
                ];
            } else {
                return ['success' => false, 'message' => 'Gagal melakukan registrasi'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Login user
    public function login($username, $password) {
        try {
            $sql = "SELECT id, username, email, password, namaLengkap, role, status FROM users 
                    WHERE username = ? OR email = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            
            $result = $stmt->get_result();
            if ($result->num_rows == 0) {
                return ['success' => false, 'message' => 'Username atau email tidak ditemukan'];
            }
            
            $user = $result->fetch_assoc();
            
            if ($user['status'] != 'aktif') {
                return ['success' => false, 'message' => 'Akun belum aktif atau diblokir'];
            }
            
            if (password_verify($password, $user['password'])) {
                // Update terakhir login
                $this->updateTerakhirLogin($user['id']);
                
                // Hapus password dari return data
                unset($user['password']);
                
                return [
                    'success' => true, 
                    'message' => 'Login berhasil',
                    'user' => $user
                ];
            } else {
                return ['success' => false, 'message' => 'Password salah'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Update terakhir login
    private function updateTerakhirLogin($user_id) {
        $sql = "UPDATE users SET terakhirLogin = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    // Cek username exists
    private function isUsernameExists($username) {
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    // Cek email exists
    private function isEmailExists($email) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    // Buat pengaturan default
    private function buatPengaturanDefault($user_id) {
        $sql = "INSERT INTO pengaturan_akun (user_id) VALUES (?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    // Update profil user
    public function updateProfil($user_id, $namaLengkap, $bio, $nomorTelpon, $tanggalLahir) {
        try {
            $sql = "UPDATE users SET namaLengkap = ?, bio = ?, nomorTelpon = ?, tanggalLahir = ? 
                    WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssi", $namaLengkap, $bio, $nomorTelpon, $tanggalLahir, $user_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Profil berhasil diupdate'];
            } else {
                return ['success' => false, 'message' => 'Gagal mengupdate profil'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Ganti password
    public function gantiPassword($user_id, $passwordLama, $passwordBaru) {
        try {
            // Cek password lama
            $sql = "SELECT password FROM users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!password_verify($passwordLama, $result['password'])) {
                return ['success' => false, 'message' => 'Password lama salah'];
            }
            
            // Update password baru
            $hashedPassword = password_hash($passwordBaru, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $hashedPassword, $user_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Password berhasil diubah'];
            } else {
                return ['success' => false, 'message' => 'Gagal mengubah password'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Mendapatkan data user
    public function getUserById($user_id) {
        try {
            $sql = "SELECT id, username, email, namaLengkap, bio, nomorTelpon, tanggalLahir, 
                           fotoProfil, role, status, terakhirLogin, dibuat
                    FROM users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Mendapatkan pengaturan user
    public function getPengaturan($user_id) {
        try {
            $sql = "SELECT * FROM pengaturan_akun WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Update pengaturan user
    public function updatePengaturan($user_id, $pengaturan) {
        try {
            $sql = "UPDATE pengaturan_akun SET 
                        notifikasi_email = ?, 
                        notifikasi_browser = ?, 
                        visibilitas_profil = ?, 
                        bahasa = ?, 
                        timezone = ?, 
                        tema = ?
                    WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iissssi", 
                $pengaturan['notifikasi_email'],
                $pengaturan['notifikasi_browser'],
                $pengaturan['visibilitas_profil'],
                $pengaturan['bahasa'],
                $pengaturan['timezone'],
                $pengaturan['tema'],
                $user_id
            );
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Pengaturan berhasil diupdate'];
            } else {
                return ['success' => false, 'message' => 'Gagal mengupdate pengaturan'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Cari user (untuk admin)
    public function cariUser($keyword, $role = null) {
        try {
            $sql = "SELECT id, username, email, namaLengkap, role, status, dibuat 
                    FROM users 
                    WHERE (username LIKE ? OR email LIKE ? OR namaLengkap LIKE ?)";
            
            $params = ["%$keyword%", "%$keyword%", "%$keyword%"];
            $types = "sss";
            
            if ($role) {
                $sql .= " AND role = ?";
                $params[] = $role;
                $types .= "s";
            }
            
            $sql .= " ORDER BY dibuat DESC LIMIT 50";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Update status user (untuk admin)
    public function updateStatus($user_id, $status) {
        try {
            $sql = "UPDATE users SET status = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $status, $user_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Status user berhasil diupdate'];
            } else {
                return ['success' => false, 'message' => 'Gagal mengupdate status user'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>
