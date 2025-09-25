<?php
require_once 'koneksi.php';

class UserLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Get users with filters, sorting, and pagination for admin panel
     * Fixed version that works with actual database structure
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
        
        // Search filter - menggunakan COALESCE untuk menghandle kolom nama yang bervariasi
        if (!empty($search)) {
            $sql .= " AND (COALESCE(u.namaLengkap, u.nama, u.username) LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR u.id LIKE ?)";
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
            $sql .= " AND DATE(COALESCE(u.created_at, NOW())) >= ?";
            $params[] = $_GET['date_from'];
            $types .= "s";
        }
        
        if (!empty($_GET['date_to'])) {
            $sql .= " AND DATE(COALESCE(u.created_at, NOW())) <= ?";
            $params[] = $_GET['date_to'];
            $types .= "s";
        }
        
        // Sorting
        $allowed_sort_fields = ['id', 'nama', 'email', 'role', 'status', 'tanggal_registrasi'];
        if (!in_array($sort_by, $allowed_sort_fields)) {
            $sort_by = 'id';
        }
        
        $allowed_sort_orders = ['ASC', 'DESC'];
        if (!in_array(strtoupper($sort_order), $allowed_sort_orders)) {
            $sort_order = 'DESC';
        }
        
        // Mapping untuk sorting
        if ($sort_by === 'nama') {
            $sql .= " ORDER BY COALESCE(u.namaLengkap, u.nama, u.username) $sort_order";
        } elseif ($sort_by === 'tanggal_registrasi') {
            $sql .= " ORDER BY COALESCE(u.created_at, NOW()) $sort_order";
        } elseif ($sort_by === 'status') {
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
            error_log("SQL: " . $sql);
            return [];
        }
    }
    
    /**
     * Count users with filters
     */
    public function countUsers($search = '', $role_filter = '', $status_filter = '') {
        $sql = "SELECT COUNT(*) as total FROM users u WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Search filter
        if (!empty($search)) {
            $sql .= " AND (COALESCE(u.namaLengkap, u.nama, u.username) LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR u.id LIKE ?)";
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
            $sql .= " AND DATE(COALESCE(u.created_at, NOW())) >= ?";
            $params[] = $_GET['date_from'];
            $types .= "s";
        }
        
        if (!empty($_GET['date_to'])) {
            $sql .= " AND DATE(COALESCE(u.created_at, NOW())) <= ?";
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
            return $row['total'];
        } catch (Exception $e) {
            error_log("Error in countUsers: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin,
                        SUM(CASE WHEN role = 'guru' THEN 1 ELSE 0 END) as guru,
                        SUM(CASE WHEN role = 'siswa' THEN 1 ELSE 0 END) as siswa
                    FROM users";
            
            $result = mysqli_query($this->conn, $sql);
            if ($result) {
                return mysqli_fetch_assoc($result);
            }
            return ['total' => 0, 'admin' => 0, 'guru' => 0, 'siswa' => 0];
        } catch (Exception $e) {
            error_log("Error in getUserStats: " . $e->getMessage());
            return ['total' => 0, 'admin' => 0, 'guru' => 0, 'siswa' => 0];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($user_id) {
        try {
            $sql = "SELECT u.id, 
                           u.username,
                           u.email,
                           COALESCE(u.namaLengkap, u.nama, u.username) as nama,
                           u.role,
                           COALESCE(u.status, 'active') as status,
                           u.fotoProfil
                    FROM users u 
                    WHERE u.id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error in getUserById: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new user
     */
    public function createUser($data) {
        try {
            // Validasi data
            if (empty($data['nama']) || empty($data['email']) || empty($data['role'])) {
                return ['success' => false, 'message' => 'Nama, email, dan role wajib diisi'];
            }
            
            if (empty($data['password'])) {
                return ['success' => false, 'message' => 'Password wajib diisi'];
            }
            
            // Cek email duplikat
            if ($this->isEmailExists($data['email'])) {
                return ['success' => false, 'message' => 'Email sudah digunakan'];
            }
            
            // Cek username duplikat (jika ada)
            if (!empty($data['username']) && $this->isUsernameExists($data['username'])) {
                return ['success' => false, 'message' => 'Username sudah digunakan'];
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert user baru
            $sql = "INSERT INTO users (username, email, password, namaLengkap, role, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $username = $data['username'] ?? '';
            $status = $data['status'] ?? 'active';
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssss", 
                $username, 
                $data['email'], 
                $hashedPassword, 
                $data['nama'], 
                $data['role'], 
                $status
            );
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'User berhasil ditambahkan'];
            } else {
                return ['success' => false, 'message' => 'Gagal menambahkan user'];
            }
        } catch (Exception $e) {
            error_log("Error in createUser: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update user
     */
    public function updateUser($data) {
        try {
            $user_id = $data['user_id'];
            
            // Validasi data
            if (empty($data['nama']) || empty($data['email']) || empty($data['role'])) {
                return ['success' => false, 'message' => 'Nama, email, dan role wajib diisi'];
            }
            
            // Cek email duplikat (exclude current user)
            $sql_check = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt_check = $this->conn->prepare($sql_check);
            $stmt_check->bind_param("si", $data['email'], $user_id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => 'Email sudah digunakan oleh user lain'];
            }
            
            // Cek username duplikat (jika ada)
            if (!empty($data['username'])) {
                $sql_check_username = "SELECT id FROM users WHERE username = ? AND id != ?";
                $stmt_check_username = $this->conn->prepare($sql_check_username);
                $stmt_check_username->bind_param("si", $data['username'], $user_id);
                $stmt_check_username->execute();
                if ($stmt_check_username->get_result()->num_rows > 0) {
                    return ['success' => false, 'message' => 'Username sudah digunakan oleh user lain'];
                }
            }
            
            // Buat query update
            if (!empty($data['password'])) {
                // Update dengan password baru
                $sql = "UPDATE users SET username = ?, email = ?, password = ?, namaLengkap = ?, role = ?, status = ? WHERE id = ?";
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ssssssi", 
                    $data['username'], 
                    $data['email'], 
                    $hashedPassword, 
                    $data['nama'], 
                    $data['role'], 
                    $data['status'], 
                    $user_id
                );
            } else {
                // Update tanpa mengubah password
                $sql = "UPDATE users SET username = ?, email = ?, namaLengkap = ?, role = ?, status = ? WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("sssssi", 
                    $data['username'], 
                    $data['email'], 
                    $data['nama'], 
                    $data['role'], 
                    $data['status'], 
                    $user_id
                );
            }
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'User berhasil diupdate'];
            } else {
                return ['success' => false, 'message' => 'Gagal mengupdate user'];
            }
        } catch (Exception $e) {
            error_log("Error in updateUser: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser($user_id) {
        try {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    return ['success' => true, 'message' => 'User berhasil dihapus'];
                } else {
                    return ['success' => false, 'message' => 'User tidak ditemukan'];
                }
            } else {
                return ['success' => false, 'message' => 'Gagal menghapus user'];
            }
        } catch (Exception $e) {
            error_log("Error in deleteUser: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Toggle user status
     */
    public function toggleUserStatus($user_id, $status) {
        try {
            $sql = "UPDATE users SET status = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $status, $user_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $statusText = $status === 'active' ? 'diaktifkan' : 'dinonaktifkan';
                    return ['success' => true, 'message' => "User berhasil $statusText"];
                } else {
                    return ['success' => false, 'message' => 'User tidak ditemukan'];
                }
            } else {
                return ['success' => false, 'message' => 'Gagal mengubah status user'];
            }
        } catch (Exception $e) {
            error_log("Error in toggleUserStatus: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Helper methods from original UserLogic
    public function isEmailExists($email) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    
    public function isUsernameExists($username) {
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}
?>