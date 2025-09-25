<?php
class AdvertisementLogic {
    private $pdo;
    
    public function __construct() {
        $this->initializeConnection();
    }
    
    private function initializeConnection() {
        require_once __DIR__ . '/koneksi.php';
        global $pdo;
        
        if (!$pdo) {
            // Create PDO connection if not exists
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "lms";
            
            try {
                $dsn = "mysql:host=$servername;port=3306;dbname=$dbname;charset=utf8mb4";
                $pdo = new PDO($dsn, $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        
        $this->pdo = $pdo;
    }
    
    public function getAllAdvertisements() {
        try {
            $sql = "SELECT a.*, u.namaLengkap as created_by_name 
                    FROM advertisements a 
                    LEFT JOIN users u ON a.created_by = u.id 
                    ORDER BY a.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting advertisements: " . $e->getMessage());
            return [];
        }
    }
    
    public function getActiveAdvertisements() {
        try {
            $sql = "SELECT a.*, u.namaLengkap as created_by_name 
                    FROM advertisements a 
                    LEFT JOIN users u ON a.created_by = u.id 
                    WHERE a.is_active = 1 
                    ORDER BY a.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting active advertisements: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAdvertisementById($id) {
        try {
            $sql = "SELECT a.*, u.namaLengkap as created_by_name 
                    FROM advertisements a 
                    LEFT JOIN users u ON a.created_by = u.id 
                    WHERE a.id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting advertisement by ID: " . $e->getMessage());
            return null;
        }
    }
    
    public function createAdvertisement($data) {
        try {
            $sql = "INSERT INTO advertisements (title, description, image_path, link_url, is_active, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['title'],
                $data['description'],
                $data['image_path'] ?? null,
                $data['link_url'] ?? '#',
                $data['is_active'] ?? 1,
                $data['created_by']
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'id' => $this->pdo->lastInsertId(),
                    'message' => 'Advertisement berhasil ditambahkan'
                ];
            }
            
            return ['success' => false, 'message' => 'Gagal menambahkan advertisement'];
        } catch (PDOException $e) {
            error_log("Error creating advertisement: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function updateAdvertisement($id, $data) {
        try {
            $sql = "UPDATE advertisements 
                    SET title = ?, description = ?, image_path = ?, link_url = ?, is_active = ? 
                    WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['title'],
                $data['description'],
                $data['image_path'] ?? null,
                $data['link_url'] ?? '#',
                $data['is_active'] ?? 1,
                $id
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Advertisement berhasil diupdate'];
            }
            
            return ['success' => false, 'message' => 'Gagal mengupdate advertisement'];
        } catch (PDOException $e) {
            error_log("Error updating advertisement: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function deleteAdvertisement($id) {
        try {
            $sql = "DELETE FROM advertisements WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Advertisement berhasil dihapus'];
            }
            
            return ['success' => false, 'message' => 'Gagal menghapus advertisement'];
        } catch (PDOException $e) {
            error_log("Error deleting advertisement: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function toggleStatus($id) {
        try {
            $sql = "UPDATE advertisements SET is_active = !is_active WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Status advertisement berhasil diubah'];
            }
            
            return ['success' => false, 'message' => 'Gagal mengubah status advertisement'];
        } catch (PDOException $e) {
            error_log("Error toggling advertisement status: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>