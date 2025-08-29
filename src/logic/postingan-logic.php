<?php
require_once 'koneksi.php';

class PostinganLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Membuat postingan baru
    public function buatPostingan($kelas_id, $user_id, $konten, $tipePost = 'umum', $deadline = null, $images = []) {
        try {
            // Start transaction
            $this->conn->begin_transaction();
            
            $sql = "INSERT INTO postingan_kelas (kelas_id, user_id, konten, tipePost, deadline) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iisss", $kelas_id, $user_id, $konten, $tipePost, $deadline);
            
            if ($stmt->execute()) {
                $postingan_id = $this->conn->insert_id;
                
                // Save images if any
                if (!empty($images)) {
                    $this->simpanGambarPostingan($postingan_id, $images);
                }
                
                $this->conn->commit();
                return [
                    'success' => true, 
                    'message' => 'Postingan berhasil dibuat',
                    'postingan_id' => $postingan_id
                ];
            } else {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Gagal membuat postingan'];
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Mendapatkan postingan berdasarkan kelas
    public function getPostinganByKelas($kelas_id, $limit = 20, $offset = 0) {
        try {
            $sql = "SELECT p.*, u.namaLengkap as namaPenulis, u.role as rolePenulis,
                           COUNT(DISTINCT l.id) as jumlahLike,
                           COUNT(DISTINCT k.id) as jumlahKomentar
                    FROM postingan_kelas p
                    JOIN users u ON p.user_id = u.id
                    LEFT JOIN like_postingan l ON p.id = l.postingan_id
                    LEFT JOIN komentar_postingan k ON p.id = k.postingan_id
                    WHERE p.kelas_id = ?
                    GROUP BY p.id
                    ORDER BY p.dibuat DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $kelas_id, $limit, $offset);
            $stmt->execute();
            
            $postingan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get images for each post
            foreach ($postingan as &$post) {
                $post['gambar'] = $this->getGambarPostingan($post['id']);
            }
            
            return $postingan;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Like/Unlike postingan
    public function toggleLike($postingan_id, $user_id) {
        try {
            // Cek apakah sudah like
            $sql = "SELECT id FROM like_postingan WHERE postingan_id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $postingan_id, $user_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                // Unlike
                $sql = "DELETE FROM like_postingan WHERE postingan_id = ? AND user_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ii", $postingan_id, $user_id);
                
                if ($stmt->execute()) {
                    return ['success' => true, 'action' => 'unliked', 'message' => 'Unlike berhasil'];
                } else {
                    return ['success' => false, 'message' => 'Gagal menghapus like'];
                }
            } else {
                // Like
                $sql = "INSERT INTO like_postingan (postingan_id, user_id) VALUES (?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ii", $postingan_id, $user_id);
                
                if ($stmt->execute()) {
                    return ['success' => true, 'action' => 'liked', 'message' => 'Like berhasil'];
                } else {
                    return ['success' => false, 'message' => 'Gagal menambah like'];
                }
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Tambah komentar
    public function tambahKomentar($postingan_id, $user_id, $komentar) {
        try {
            $sql = "INSERT INTO komentar_postingan (postingan_id, user_id, komentar) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iis", $postingan_id, $user_id, $komentar);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Komentar berhasil ditambahkan',
                    'komentar_id' => $this->conn->insert_id
                ];
            } else {
                return ['success' => false, 'message' => 'Gagal menambah komentar'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Mendapatkan komentar postingan
    public function getKomentarPostingan($postingan_id) {
        try {
            $sql = "SELECT k.*, u.namaLengkap as namaKomentator, u.role
                    FROM komentar_postingan k
                    JOIN users u ON k.user_id = u.id
                    WHERE k.postingan_id = ?
                    ORDER BY k.dibuat ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Update postingan
    public function updatePostingan($postingan_id, $konten, $user_id) {
        try {
            // Cek ownership
            $sql = "SELECT user_id FROM postingan_kelas WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['user_id'] != $user_id) {
                return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk mengedit postingan ini'];
            }
            
            $sql = "UPDATE postingan_kelas SET konten = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $konten, $postingan_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Postingan berhasil diupdate'];
            } else {
                return ['success' => false, 'message' => 'Gagal mengupdate postingan'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Mendapatkan postingan berdasarkan ID
    public function getPostinganById($postingan_id) {
        try {
            $sql = "SELECT p.*, u.namaLengkap as namaPenulis, u.role as rolePenulis
                    FROM postingan_kelas p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Hapus postingan
    public function hapusPostingan($postingan_id, $user_id) {
        try {
            // Cek ownership
            $sql = "SELECT user_id FROM postingan_kelas WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['user_id'] != $user_id) {
                return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus postingan ini'];
            }
            
            $this->conn->begin_transaction();
            
            // Hapus gambar postingan
            $this->hapusGambarPostingan($postingan_id);
            
            // Hapus like dan komentar
            $sql = "DELETE FROM like_postingan WHERE postingan_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();

            $sql = "DELETE FROM komentar_postingan WHERE postingan_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();

            // Hapus postingan
            $sql = "DELETE FROM postingan_kelas WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Postingan berhasil dihapus'];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Mendapatkan komentar berdasarkan ID
    public function getKomentarById($komentar_id) {
        try {
            $sql = "SELECT k.*, u.namaLengkap as namaKomentator, u.role
                    FROM komentar_postingan k
                    JOIN users u ON k.user_id = u.id
                    WHERE k.id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $komentar_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Hapus komentar
    public function hapusKomentar($komentar_id, $user_id) {
        try {
            // Cek ownership
            $sql = "SELECT user_id FROM komentar_postingan WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $komentar_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!$result) {
                return ['success' => false, 'message' => 'Komentar tidak ditemukan'];
            }
            
            if ($result['user_id'] != $user_id) {
                return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus komentar ini'];
            }
            
            $sql = "DELETE FROM komentar_postingan WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $komentar_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Komentar berhasil dihapus'];
            } else {
                return ['success' => false, 'message' => 'Gagal menghapus komentar'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Simpan gambar postingan
    private function simpanGambarPostingan($postingan_id, $images) {
        try {
            foreach ($images as $image) {
                $sql = "INSERT INTO postingan_gambar (postingan_id, nama_file, path_gambar, ukuran_file, tipe_file, urutan) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("issisi", 
                    $postingan_id, 
                    $image['nama_file'], 
                    $image['path_gambar'], 
                    $image['ukuran_file'], 
                    $image['tipe_file'], 
                    $image['urutan']
                );
                $stmt->execute();
            }
            return true;
        } catch (Exception $e) {
            throw new Exception('Gagal menyimpan gambar: ' . $e->getMessage());
        }
    }
    
    // Mendapatkan gambar postingan
    public function getGambarPostingan($postingan_id) {
        try {
            $sql = "SELECT * FROM postingan_gambar WHERE postingan_id = ? ORDER BY urutan";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Hapus gambar postingan
    public function hapusGambarPostingan($postingan_id) {
        try {
            // Get image paths first for file deletion
            $images = $this->getGambarPostingan($postingan_id);
            
            // Delete from database
            $sql = "DELETE FROM postingan_gambar WHERE postingan_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();
            
            // Delete physical files
            foreach ($images as $image) {
                $filePath = '../../' . $image['path_gambar'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getStatistikPostingan($kelas_id) {
        try {
            $sql = "SELECT 
                        COUNT(*) as totalPostingan,
                        COUNT(CASE WHEN tipePost = 'tugas' THEN 1 END) as totalTugas,
                        COUNT(CASE WHEN tipePost = 'pengumuman' THEN 1 END) as totalPengumuman,
                        COUNT(CASE WHEN tipePost = 'materi' THEN 1 END) as totalMateri
                    FROM postingan_kelas 
                    WHERE kelas_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $kelas_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }
}
?>
