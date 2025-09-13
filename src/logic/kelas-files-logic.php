<?php
class KelasFilesLogic {
    private $pdo;
    
    public function __construct() {
        require_once 'koneksi.php';
        $this->pdo = $pdo;
    }
    
    public function uploadFile($kelas_id, $guru_id, $file_type, $title, $description, $file_data) {
        try {
            // Validate file type
            if (!in_array($file_type, ['schedule', 'material'])) {
                throw new Exception('Invalid file type: ' . $file_type);
            }
            
            // Create upload directory structure
            $upload_dir = "../../uploads/kelas_files/" . $kelas_id . "/" . $file_type . "/";
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception('Failed to create upload directory: ' . $upload_dir);
                }
            }
            
            // Validate file
            $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            if ($file_type === 'material') {
                $allowed_extensions[] = 'ppt';
                $allowed_extensions[] = 'pptx';
            }
            
            $file_extension = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception('File type not allowed: ' . $file_extension . '. Allowed: ' . implode(', ', $allowed_extensions));
            }
            
            // Check file size (10MB for schedule, 20MB for material)
            $max_size = ($file_type === 'schedule') ? 10 * 1024 * 1024 : 20 * 1024 * 1024;
            if ($file_data['size'] > $max_size) {
                throw new Exception('File size too large: ' . $file_data['size'] . ' bytes. Max: ' . $max_size . ' bytes');
            }
            
            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file_data['tmp_name'], $file_path)) {
                throw new Exception('Failed to move uploaded file from ' . $file_data['tmp_name'] . ' to ' . $file_path);
            }
            
            // Save to database
            $sql = "INSERT INTO kelas_files (kelas_id, guru_id, file_type, title, description, file_name, file_path, file_size, file_extension) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                $kelas_id, 
                $guru_id, 
                $file_type, 
                $title, 
                $description, 
                $file_data['name'], 
                $file_path, 
                $file_data['size'], 
                $file_extension
            ]);
            
            if (!$success) {
                throw new Exception('Failed to save file info to database');
            }
            
            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'file_id' => $this->pdo->lastInsertId()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getFilesByType($kelas_id, $file_type) {
        try {
            $sql = "SELECT kf.*, u.namaLengkap as guru_nama 
                    FROM kelas_files kf
                    JOIN users u ON kf.guru_id = u.id
                    WHERE kf.kelas_id = ? AND kf.file_type = ?
                    ORDER BY kf.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$kelas_id, $file_type]);
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug logging
            error_log("getFilesByType - kelas_id: $kelas_id, file_type: $file_type, count: " . count($result));
            
            return $result;
            
        } catch (Exception $e) {
            error_log("getFilesByType error: " . $e->getMessage());
            return [];
        }
    }
    
    public function deleteFile($file_id, $guru_id) {
        try {
            // Get file info
            $sql = "SELECT * FROM kelas_files WHERE id = ? AND guru_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$file_id, $guru_id]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                throw new Exception('File not found or not authorized');
            }
            
            // Delete physical file
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }
            
            // Delete from database
            $sql = "DELETE FROM kelas_files WHERE id = ? AND guru_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$file_id, $guru_id]);
            
            return [
                'success' => true,
                'message' => 'File deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getFileForDownload($file_id, $kelas_id = null) {
        try {
            if ($kelas_id) {
                $sql = "SELECT * FROM kelas_files WHERE id = ? AND kelas_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$file_id, $kelas_id]);
            } else {
                $sql = "SELECT * FROM kelas_files WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$file_id]);
            }
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return null;
        }
    }
}
?>
