<?php
require_once 'koneksi.php';

class UserLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Registrasi user baru dengan email verification
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
            
            // Set status 'unverified' untuk user baru, akan berubah jadi 'aktif' setelah verifikasi reCAPTCHA
            $sql = "INSERT INTO users (username, email, password, namaLengkap, role, status, email_verified) 
                    VALUES (?, ?, ?, ?, ?, 'unverified', 0)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssss", $username, $email, $hashedPassword, $namaLengkap, $role);
            
            if ($stmt->execute()) {
                $user_id = $this->conn->insert_id;
                
                // Buat pengaturan default
                $this->buatPengaturanDefault($user_id);
                
                return [
                    'success' => true, 
                    'message' => 'Registrasi berhasil',
                    'user_id' => $user_id,
                    'email' => $email,
                    'requires_verification' => true
                ];
            } else {
                return ['success' => false, 'message' => 'Gagal melakukan registrasi'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Create verified user (untuk reCAPTCHA flow)
    public function createVerifiedUser($email, $username, $namaLengkap, $password, $role = 'siswa') {
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
            
            // Set status 'aktif' langsung karena sudah lolos reCAPTCHA
            $sql = "INSERT INTO users (username, email, password, namaLengkap, role, status, email_verified) 
                    VALUES (?, ?, ?, ?, ?, 'aktif', 1)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssss", $username, $email, $hashedPassword, $namaLengkap, $role);
            
            if ($stmt->execute()) {
                $user_id = $this->conn->insert_id;
                
                // Buat pengaturan default
                $this->buatPengaturanDefault($user_id);
                
                // Track pendaftaran untuk statistik admin
                $this->trackRegistration($role);
                
                return [
                    'success' => true, 
                    'message' => 'Akun berhasil dibuat',
                    'user_id' => $user_id,
                    'temp_password' => $password
                ];
            } else {
                return ['success' => false, 'message' => 'Gagal membuat akun'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Login user dengan email verification check
    public function login($username, $password) {
        try {
            $sql = "SELECT id, username, email, password, namaLengkap, role, status, email_verified FROM users 
                    WHERE username = ? OR email = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            
            $result = $stmt->get_result();
            if ($result->num_rows == 0) {
                return ['success' => false, 'message' => 'Username atau email tidak ditemukan'];
            }
            
            $user = $result->fetch_assoc();
            
            // Cek status akun
            if ($user['status'] == 'unverified') {
                return [
                    'success' => false, 
                    'message' => 'Email belum diverifikasi',
                    'requires_verification' => true,
                    'email' => $user['email']
                ];
            }
            
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
    public function isUsernameExists($username) {
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    // Cek email exists
    public function isEmailExists($email) {
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
    
    // Track registrasi untuk statistik admin dashboard
    private function trackRegistration($role) {
        try {
            $today = date('Y-m-d');
            $month = date('Y-m');
            
            // Cek apakah sudah ada record untuk tanggal dan role ini
            $sql = "SELECT id, jumlah_pendaftar FROM registration_stats WHERE tanggal = ? AND role = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $today, $role);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing record
                $row = $result->fetch_assoc();
                $newCount = $row['jumlah_pendaftar'] + 1;
                $updateSql = "UPDATE registration_stats SET jumlah_pendaftar = ? WHERE id = ?";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->bind_param("ii", $newCount, $row['id']);
                $updateStmt->execute();
            } else {
                // Insert new record
                $insertSql = "INSERT INTO registration_stats (tanggal, bulan, role, jumlah_pendaftar) VALUES (?, ?, ?, 1)";
                $insertStmt = $this->conn->prepare($insertSql);
                $insertStmt->bind_param("sss", $today, $month, $role);
                $insertStmt->execute();
            }
            
            return true;
        } catch (Exception $e) {
            // Log error tapi jangan stop proses registrasi
            error_log("Error tracking registration: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get users with filters, sorting, and pagination for admin panel
     */
    public function getUsers($search = '', $role_filter = '', $status_filter = '', $sort_by = 'id', $sort_order = 'DESC', $page = 1, $per_page = 20) {
        $offset = ($page - 1) * $per_page;
        
        // Query yang aman sesuai struktur tabel yang sebenarnya
        $sql = "SELECT u.id, 
                       u.username,
                       u.email,
                       COALESCE(u.namaLengkap, u.nama, u.username) as nama,
                       u.role,
                       COALESCE(u.status, 'active') as status,
                       u.fotoProfil,
                       COALESCE(u.created_at, NOW()) as tanggal_registrasi
                FROM users u 
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Search filter
        if (!empty($search)) {
            $sql .= " AND (u.namaLengkap LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR u.id LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, "%$search%"]);
            $types .= "ssss";
        }
        
        // Role filter
        if (!empty($role_filter)) {
            $sql .= " AND u.role = ?";
            $params[] = $role_filter;
            $types .= "s";
        }
        
        // Status filter
        if (!empty($status_filter)) {
            if ($status_filter === 'active') {
                $sql .= " AND (u.status = 'active' OR u.status IS NULL OR u.status = 'aktif')";
            } else {
                $sql .= " AND u.status = ?";
                $params[] = $status_filter;
                $types .= "s";
            }
        }
        
        // Date range filter
        if (!empty($_GET['date_from'])) {
            $sql .= " AND DATE(u.tanggal_registrasi) >= ?";
            $params[] = $_GET['date_from'];
            $types .= "s";
        }
        
        if (!empty($_GET['date_to'])) {
            $sql .= " AND DATE(u.tanggal_registrasi) <= ?";
            $params[] = $_GET['date_to'];
            $types .= "s";
        }
        
        // Sorting
        $allowed_sort_fields = ['id', 'namaLengkap', 'email', 'role', 'status', 'tanggal_registrasi'];
        if (!in_array($sort_by, $allowed_sort_fields)) {
            $sort_by = 'id';
        }
        
        // Map nama to namaLengkap for sorting
        if ($sort_by === 'nama') {
            $sort_by = 'namaLengkap';
        }
        
        $allowed_sort_orders = ['ASC', 'DESC'];
        if (!in_array(strtoupper($sort_order), $allowed_sort_orders)) {
            $sort_order = 'DESC';
        }
        
        if ($sort_by === 'status') {
            $sql .= " ORDER BY COALESCE(u.status, 'active') $sort_order, u.id DESC";
        } else {
            $sql .= " ORDER BY u.$sort_by $sort_order";
        }
        
        // Pagination
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $types .= "ii";
        
        try {
            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getUsers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count total users with filters for admin panel
     */
    public function countUsers($search = '', $role_filter = '', $status_filter = '') {
        $sql = "SELECT COUNT(*) as total FROM users u WHERE 1=1";
        $params = [];
        $types = "";
        
        // Search filter
        if (!empty($search)) {
            $sql .= " AND (u.namaLengkap LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR u.id LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, "%$search%"]);
            $types .= "ssss";
        }
        
        // Role filter
        if (!empty($role_filter)) {
            $sql .= " AND u.role = ?";
            $params[] = $role_filter;
            $types .= "s";
        }
        
        // Status filter
        if (!empty($status_filter)) {
            if ($status_filter === 'active') {
                $sql .= " AND (u.status = 'active' OR u.status IS NULL OR u.status = 'aktif')";
            } else {
                $sql .= " AND u.status = ?";
                $params[] = $status_filter;
                $types .= "s";
            }
        }
        
        // Date range filter
        if (!empty($_GET['date_from'])) {
            $sql .= " AND DATE(u.tanggal_registrasi) >= ?";
            $params[] = $_GET['date_from'];
            $types .= "s";
        }
        
        if (!empty($_GET['date_to'])) {
            $sql .= " AND DATE(u.tanggal_registrasi) <= ?";
            $params[] = $_GET['date_to'];
            $types .= "s";
        }
        
        try {
            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error in countUsers: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get user statistics for admin panel
     */
    public function getUserStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN role = 'guru' THEN 1 ELSE 0 END) as guru,
                        SUM(CASE WHEN role = 'siswa' THEN 1 ELSE 0 END) as siswa,
                        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin,
                        SUM(CASE WHEN COALESCE(status, 'active') IN ('active', 'aktif') THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                    FROM users";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error in getUserStats: " . $e->getMessage());
            return [
                'total' => 0,
                'guru' => 0,
                'siswa' => 0,
                'admin' => 0,
                'active' => 0,
                'inactive' => 0,
                'pending' => 0
            ];
        }
    }
    
    /**
     * Get user by ID for admin panel with nama field mapping
     */
    public function getUserForAdmin($userId) {
        try {
            $sql = "SELECT *, namaLengkap as nama FROM users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error in getUserForAdmin: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create new user for admin panel
     */
    public function createUser($data) {
        try {
            // Check if email already exists
            if ($this->isEmailExists($data['email'])) {
                return [
                    'success' => false,
                    'message' => 'Email sudah terdaftar'
                ];
            }
            
            // Check if username already exists (if provided)
            if (!empty($data['username']) && $this->isUsernameExists($data['username'])) {
                return [
                    'success' => false,
                    'message' => 'Username sudah digunakan'
                ];
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Prepare variables for binding
            $nama = $data['nama'];
            $email = $data['email'];
            $username = !empty($data['username']) ? $data['username'] : null;
            $role = $data['role'];
            $status = !empty($data['status']) ? $data['status'] : 'active';
            
            $sql = "INSERT INTO users (namaLengkap, email, username, password, role, status, tanggal_registrasi, email_verified) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssss", $nama, $email, $username, $hashedPassword, $role, $status);
            
            if ($stmt->execute()) {
                $user_id = $this->conn->insert_id;
                $this->buatPengaturanDefault($user_id);
                
                return [
                    'success' => true,
                    'message' => 'User berhasil ditambahkan'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal menambahkan user'
                ];
            }
        } catch (Exception $e) {
            error_log("Error in createUser: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal menambahkan user: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update user for admin panel
     */
    public function updateUser($userId, $data) {
        try {
            // Check if email already exists for other users
            $checkSql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->bind_param("si", $data['email'], $userId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                return [
                    'success' => false,
                    'message' => 'Email sudah digunakan oleh user lain'
                ];
            }
            
            // Check if username already exists for other users (if provided)
            if (!empty($data['username'])) {
                $checkSql = "SELECT id FROM users WHERE username = ? AND id != ?";
                $checkStmt = $this->conn->prepare($checkSql);
                $checkStmt->bind_param("si", $data['username'], $userId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    return [
                        'success' => false,
                        'message' => 'Username sudah digunakan oleh user lain'
                    ];
                }
            }
            
            // Build update SQL
            $nama = $data['nama'];
            $email = $data['email'];
            $username = !empty($data['username']) ? $data['username'] : null;
            $role = $data['role'];
            $status = !empty($data['status']) ? $data['status'] : 'active';
            
            if (!empty($data['password'])) {
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $sql = "UPDATE users SET namaLengkap = ?, email = ?, username = ?, password = ?, role = ?, status = ? WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ssssssi", $nama, $email, $username, $hashedPassword, $role, $status, $userId);
            } else {
                $sql = "UPDATE users SET namaLengkap = ?, email = ?, username = ?, role = ?, status = ? WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("sssssi", $nama, $email, $username, $role, $status, $userId);
            }
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'User berhasil diupdate'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal mengupdate user'
                ];
            }
        } catch (Exception $e) {
            error_log("Error in updateUser: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal mengupdate user: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Toggle user status for admin panel
     */
    public function toggleUserStatus($userId, $status) {
        try {
            $sql = "UPDATE users SET status = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $status, $userId);
            
            if ($stmt->execute()) {
                $statusText = $status === 'active' ? 'diaktifkan' : 'dinonaktifkan';
                return [
                    'success' => true,
                    'message' => "User berhasil $statusText"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal mengubah status user'
                ];
            }
        } catch (Exception $e) {
            error_log("Error in toggleUserStatus: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal mengubah status user'
            ];
        }
    }
    
    /**
     * Delete user for admin panel
     */
    public function deleteUser($userId) {
        try {
            // Get user info for logging
            $user = $this->getUserForAdmin($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User tidak ditemukan'
                ];
            }
            
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'User berhasil dihapus'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal menghapus user'
                ];
            }
        } catch (Exception $e) {
            error_log("Error in deleteUser: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal menghapus user: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Export users to array for Excel export
     */
    public function exportUsers($search = '', $role_filter = '', $status_filter = '') {
        $sql = "SELECT u.id, u.namaLengkap as nama, u.email, u.username, u.role, 
                       COALESCE(u.status, 'active') as status,
                       u.tanggal_registrasi
                FROM users u 
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Apply same filters as getUsers method
        if (!empty($search)) {
            $sql .= " AND (u.namaLengkap LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR u.id LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, "%$search%"]);
            $types .= "ssss";
        }
        
        if (!empty($role_filter)) {
            $sql .= " AND u.role = ?";
            $params[] = $role_filter;
            $types .= "s";
        }
        
        if (!empty($status_filter)) {
            if ($status_filter === 'active') {
                $sql .= " AND (u.status = 'active' OR u.status IS NULL OR u.status = 'aktif')";
            } else {
                $sql .= " AND u.status = ?";
                $params[] = $status_filter;
                $types .= "s";
            }
        }
        
        if (!empty($_GET['date_from'])) {
            $sql .= " AND DATE(u.tanggal_registrasi) >= ?";
            $params[] = $_GET['date_from'];
            $types .= "s";
        }
        
        if (!empty($_GET['date_to'])) {
            $sql .= " AND DATE(u.tanggal_registrasi) <= ?";
            $params[] = $_GET['date_to'];
            $types .= "s";
        }
        
        $sql .= " ORDER BY u.id DESC";
        
        try {
            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error in exportUsers: " . $e->getMessage());
            return [];
        }
    }
}
?>
