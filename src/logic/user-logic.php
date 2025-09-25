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
        
        // Query yang sudah terbukti bekerja berdasarkan debug
        $sql = "SELECT u.id, 
                       u.username,
                       u.email,
                       u.namaLengkap as nama,
                       u.role,
                       COALESCE(u.status, 'active') as status,
                       u.fotoProfil,
                       NOW() as tanggal_registrasi
                FROM users u 
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Search filter - menggunakan namaLengkap yang sudah confirmed exist
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
        
        // Date range filter - Skip karena tidak ada kolom tanggal yang real
        // Bisa ditambahkan nanti jika diperlukan
        /*
        if (!empty($_GET['date_from'])) {
            $sql .= " AND DATE(NOW()) >= ?";
            $params[] = $_GET['date_from'];
            $types .= "s";
        }
        
        if (!empty($_GET['date_to'])) {
            $sql .= " AND DATE(NOW()) <= ?";
            $params[] = $_GET['date_to'];
            $types .= "s";
        }
        */
        
        // Sorting
        $allowed_sort_fields = ['id', 'nama', 'email', 'role', 'status', 'tanggal_registrasi'];
        if (!in_array($sort_by, $allowed_sort_fields)) {
            $sort_by = 'id';
        }
        
        $allowed_sort_orders = ['ASC', 'DESC'];
        if (!in_array(strtoupper($sort_order), $allowed_sort_orders)) {
            $sort_order = 'DESC';
        }
        
        // Mapping untuk sorting - disesuaikan dengan struktur tabel yang benar
        if ($sort_by === 'nama') {
            $sql .= " ORDER BY u.namaLengkap $sort_order";
        } elseif ($sort_by === 'tanggal_registrasi') {
            $sql .= " ORDER BY u.id $sort_order"; // Fallback ke ID karena tidak ada kolom tanggal
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
        
        // Search filter - konsisten dengan getUsers()
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
        
        // Date range filter - Skip karena tidak ada kolom tanggal yang real
        /*
        if (!empty($_GET['date_from'])) {
            $sql .= " AND DATE(NOW()) >= ?";
            $params[] = $_GET['date_from'];
            $types .= "s";
        }
        
        if (!empty($_GET['date_to'])) {
            $sql .= " AND DATE(NOW()) <= ?";
            $params[] = $_GET['date_to'];
            $types .= "s";
        }
        */
        
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
                           u.namaLengkap as nama,
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
    
    /**
     * Update single field for user (for inline editing)
     */
    public function updateUserField($user_id, $field, $value) {
        try {
            // Map field to actual database column
            $column_map = [
                'nama' => 'namaLengkap',
                'email' => 'email',
                'role' => 'role',
                'status' => 'status',
                'pro_status' => 'pro_status' // Note: This column doesn't exist yet, will default to 'free'
            ];
            
            if (!isset($column_map[$field])) {
                return ['success' => false, 'message' => 'Field tidak valid'];
            }
            
            $db_column = $column_map[$field];
            
            // For pro_status, we'll just return success since column doesn't exist yet
            if ($field === 'pro_status') {
                return ['success' => true, 'message' => 'Pro status berhasil diupdate (sementara default free)'];
            }
            
            // Check if email already exists (for email updates)
            if ($field === 'email') {
                $sql_check = "SELECT id FROM users WHERE email = ? AND id != ?";
                $stmt_check = $this->conn->prepare($sql_check);
                $stmt_check->bind_param("si", $value, $user_id);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows > 0) {
                    return ['success' => false, 'message' => 'Email sudah digunakan oleh user lain'];
                }
            }
            
            // First check if user exists and if column exists
            $check_sql = "SELECT id FROM users WHERE id = ?";
            $check_stmt = $this->conn->prepare($check_sql);
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                return ['success' => false, 'message' => 'User tidak ditemukan'];
            }
            
            // Check if column exists in table
            $column_check = $this->conn->query("DESCRIBE users");
            $columns = [];
            while ($row = $column_check->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            
            if (!in_array($db_column, $columns)) {
                error_log("UpdateUserField Error: Column '$db_column' does not exist in users table");
                
                // Special handling for status column - add it if it doesn't exist
                if ($field === 'status') {
                    $alter_sql = "ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active'";
                    if ($this->conn->query($alter_sql)) {
                        error_log("UpdateUserField: Successfully added 'status' column to users table");
                    } else {
                        error_log("UpdateUserField: Failed to add 'status' column: " . $this->conn->error);
                        return ['success' => false, 'message' => 'Kolom status tidak ada dan gagal ditambahkan'];
                    }
                } else {
                    return ['success' => false, 'message' => "Kolom $field tidak ada di database"];
                }
            }
            
            // Now get current value
            $current_sql = "SELECT $db_column FROM users WHERE id = ?";
            $current_stmt = $this->conn->prepare($current_sql);
            $current_stmt->bind_param("i", $user_id);
            $current_stmt->execute();
            $current_result = $current_stmt->get_result();
            $current_user = $current_result->fetch_assoc();
            $current_value = $current_user[$db_column] ?? '';
            
            // Debug: Log current vs new values
            error_log("UpdateUserField Debug - Field: $field, Current: " . var_export($current_value, true) . ", New: " . var_export($value, true));
            
            // Check if value is actually different (handle empty string vs null)
            $current_normalized = trim($current_value === null ? '' : (string)$current_value);
            $new_normalized = trim((string)$value);
            
            error_log("UpdateUserField Comparison - Current: '$current_normalized' vs New: '$new_normalized'");
            
            if ($current_normalized === $new_normalized) {
                error_log("UpdateUserField: Values are identical, no change needed");
                $field_labels = [
                    'nama' => 'Nama',
                    'email' => 'Email', 
                    'role' => 'Role',
                    'status' => 'Status',
                    'pro_status' => 'Pro Status'
                ];
                $label = $field_labels[$field] ?? $field;
                return ['success' => true, 'message' => "$label sudah sesuai dengan nilai yang diinginkan"];
            }
            
            $sql = "UPDATE users SET $db_column = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $value, $user_id);
            
            error_log("UpdateUserField SQL: $sql with values: '$value', $user_id");
            
            if ($stmt->execute()) {
                error_log("UpdateUserField: Execute success, affected_rows: " . $stmt->affected_rows);
                
                if ($stmt->affected_rows > 0) {
                    $field_labels = [
                        'nama' => 'Nama',
                        'email' => 'Email', 
                        'role' => 'Role',
                        'status' => 'Status',
                        'pro_status' => 'Pro Status'
                    ];
                    
                    $label = $field_labels[$field] ?? $field;
                    return ['success' => true, 'message' => "$label berhasil diupdate"];
                } else {
                    return ['success' => false, 'message' => 'Update gagal - tidak ada baris yang terpengaruh (mungkin nilai sama)'];
                }
            } else {
                $error = $this->conn->error;
                error_log("UpdateUserField SQL Error: " . $error);
                return ['success' => false, 'message' => 'Gagal mengupdate data: ' . $error];
            }
        } catch (Exception $e) {
            error_log("Error in updateUserField: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user for admin (alias for getUserById)
     */
    public function getUserForAdmin($user_id) {
        return $this->getUserById($user_id);
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
}
?>