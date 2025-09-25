<?php
require_once 'koneksi.php';
require_once 'notification-logic.php';

class PostinganLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Membuat postingan baru
    public function buatPostingan($kelas_id, $user_id, $konten, $tipePost = 'umum', $deadline = null, $images = [], $files = []) {
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
                
                // Save files if any
                if (!empty($files)) {
                    $this->simpanFilePostingan($postingan_id, $files);
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
    public function getPostinganByKelas($kelas_id, $limit = 5, $offset = 0, $user_id = null) {
        try {
            $sql = "SELECT p.*, u.namaLengkap as namaPenulis, u.role as rolePenulis, u.fotoProfil,
                           COUNT(DISTINCT l.id) as jumlahLike,
                           COUNT(DISTINCT k.id) as jumlahKomentar,
                           MAX(CASE WHEN l.user_id = ? THEN 1 ELSE 0 END) as userLiked,
                           t.id as assignment_id, t.judul as assignment_title, t.deskripsi as assignment_description,
                           t.deadline as assignment_deadline, t.nilai_maksimal as assignment_max_score,
                           t.file_path as assignment_file_path,
                           COALESCE(p.tipe_postingan, 'regular') as tipe_postingan
                    FROM postingan_kelas p
                    JOIN users u ON p.user_id = u.id
                    LEFT JOIN like_postingan l ON p.id = l.postingan_id
                    LEFT JOIN komentar_postingan k ON p.id = k.postingan_id
                    LEFT JOIN tugas t ON p.assignment_id = t.id
                    WHERE p.kelas_id = ?
                    GROUP BY p.id
                    ORDER BY p.dibuat DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiii", $user_id, $kelas_id, $limit, $offset);
            $stmt->execute();
            
            $postingan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get images for each post and assignment submission status if needed
            foreach ($postingan as &$post) {
                error_log("✏️ [DEBUG] Processing post ID: " . $post['id'] . ", Type: " . $post['tipe_postingan']);
                $post['gambar'] = $this->getGambarPostingan($post['id']);
                
                // Get assignment files if this is an assignment post
                if ($post['tipe_postingan'] === 'assignment' && $post['assignment_id']) {
                    error_log("✏️ [DEBUG] Getting assignment files for assignment ID: " . $post['assignment_id']);
                    $post['assignment_files'] = $this->getAssignmentFiles($post['assignment_id']);
                    error_log("✏️ [DEBUG] Found " . count($post['assignment_files']) . " assignment files");
                }
                $post['files'] = $this->getFilePostingan($post['id']); // Add file attachments
                
                // Convert assignment file path to URL if exists
                if ($post['assignment_file_path']) {
                    // Convert absolute path to relative URL for web access
                    $webRoot = '/opt/lampp/htdocs';
                    $post['assignment_file_path'] = str_replace($webRoot, '', $post['assignment_file_path']);
                }
                
                // If this is an assignment post, get submission status for current user
                if ($post['tipe_postingan'] === 'assignment' && $post['assignment_id']) {
                    $post = $this->addAssignmentSubmissionStatus($post, $kelas_id);
                }
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
                    // Send notification to post owner (only if not liking own post)
                    $postOwner = $this->getPostOwner($postingan_id);
                    if ($postOwner && $postOwner['user_id'] != $user_id) {
                        $notificationLogic = new NotificationLogic();
                        
                        // Get user info who liked the post
                        $likerSql = "SELECT namaLengkap, username FROM users WHERE id = ?";
                        $likerStmt = $this->conn->prepare($likerSql);
                        $likerStmt->bind_param("i", $user_id);
                        $likerStmt->execute();
                        $liker = $likerStmt->get_result()->fetch_assoc();
                        
                        $notificationLogic->createNotification(
                            $postOwner['user_id'],
                            'like_postingan',
                            'Postingan Disukai',
                            ($liker['namaLengkap'] ?? $liker['username']) . ' menyukai postingan Anda',
                            $postingan_id,
                            $postOwner['kelas_id']
                        );
                    }
                    
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
                $komentar_id = $this->conn->insert_id;
                
                // Send notification to post owner (only if not commenting on own post)
                $postOwner = $this->getPostOwner($postingan_id);
                if ($postOwner && $postOwner['user_id'] != $user_id) {
                    $notificationLogic = new NotificationLogic();
                    
                    // Get user info who commented
                    $commenterSql = "SELECT namaLengkap, username FROM users WHERE id = ?";
                    $commenterStmt = $this->conn->prepare($commenterSql);
                    $commenterStmt->bind_param("i", $user_id);
                    $commenterStmt->execute();
                    $commenter = $commenterStmt->get_result()->fetch_assoc();
                    
                    $notificationLogic->createNotification(
                        $postOwner['user_id'],
                        'komentar_postingan',
                        'Komentar Baru',
                        ($commenter['namaLengkap'] ?? $commenter['username']) . ' mengomentari postingan Anda',
                        $postingan_id,
                        $postOwner['kelas_id']
                    );
                }
                
                return [
                    'success' => true, 
                    'message' => 'Komentar berhasil ditambahkan',
                    'komentar_id' => $komentar_id
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
            $sql = "SELECT k.*, u.namaLengkap as nama_penulis, u.role, u.fotoProfil
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
                return ['success' => true, 'message' => 'Postingan berhasil di update'];
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
            // Cek ownership dan assignment_id
            $sql = "SELECT user_id, assignment_id FROM postingan_kelas WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['user_id'] != $user_id) {
                return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus postingan ini'];
            }
            
            $this->conn->begin_transaction();
            
            // Jika ini postingan assignment, hapus data assignment terlebih dahulu
            if ($result['assignment_id']) {
                $this->deleteAssignmentData($result['assignment_id']);
            }
            
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
            $sql = "SELECT k.*, u.namaLengkap as namaKomentator, u.role, u.fotoProfil
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
    
    // Simpan gambar/media postingan
    private function simpanGambarPostingan($postingan_id, $mediaFiles) {
        try {
            foreach ($mediaFiles as $media) {
                $sql = "INSERT INTO postingan_gambar (postingan_id, nama_file, path_gambar, ukuran_file, tipe_file, media_type, urutan) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $mediaType = isset($media['media_type']) ? $media['media_type'] : 'image';
                $stmt->bind_param("ississi", 
                    $postingan_id, 
                    $media['nama_file'], 
                    $media['path_gambar'], 
                    $media['ukuran_file'], 
                    $media['tipe_file'], 
                    $mediaType,
                    $media['urutan']
                );
                $stmt->execute();
            }
            return true;
        } catch (Exception $e) {
            throw new Exception('Gagal menyimpan media: ' . $e->getMessage());
        }
    }
    
    // Simpan file postingan
    private function simpanFilePostingan($postingan_id, $files) {
        try {
            foreach ($files as $file) {
                $sql = "INSERT INTO postingan_files (postingan_id, nama_file, path_file, ukuran_file, tipe_file, ekstensi_file, urutan) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ississi", 
                    $postingan_id, 
                    $file['nama_file'], 
                    $file['path_file'], 
                    $file['ukuran_file'], 
                    $file['tipe_file'],
                    $file['ekstensi_file'], 
                    $file['urutan']
                );
                $stmt->execute();
            }
            return true;
        } catch (Exception $e) {
            throw new Exception('Gagal menyimpan file: ' . $e->getMessage());
        }
    }
    
    // Mendapatkan gambar/media postingan
    public function getGambarPostingan($postingan_id) {
        try {
            $sql = "SELECT *, COALESCE(media_type, 'image') as media_type FROM postingan_gambar WHERE postingan_id = ? ORDER BY urutan";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Mendapatkan file postingan
    public function getFilePostingan($postingan_id) {
        try {
            $sql = "SELECT * FROM postingan_files WHERE postingan_id = ? ORDER BY urutan";
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

    // Edit postingan
    public function editPostingan($postingan_id, $user_id, $konten, $images_to_delete = [], $new_images = []) {
        try {
            // Cek ownership
            $sql = "SELECT user_id, kelas_id FROM postingan_kelas WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!$result || $result['user_id'] != $user_id) {
                return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk mengedit postingan ini'];
            }
            
            $this->conn->begin_transaction();
            
            // Update konten postingan dan tandai sebagai edited
            $sql = "UPDATE postingan_kelas SET konten = ?, diubah = CURRENT_TIMESTAMP, is_edited = 1 WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $konten, $postingan_id);
            
            if (!$stmt->execute()) {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Gagal mengupdate postingan'];
            }
            
            // Hapus gambar yang dipilih untuk dihapus
            if (!empty($images_to_delete)) {
                foreach ($images_to_delete as $image_id) {
                    $this->hapusGambarPostinganById($image_id);
                }
            }
            
            // Tambah gambar baru
            if (!empty($new_images)) {
                $this->simpanGambarPostingan($postingan_id, $new_images);
            }
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Postingan berhasil di update'];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Mendapatkan detail postingan untuk edit
    public function getDetailPostinganForEdit($postingan_id, $user_id) {
        try {
            // Cek ownership
            $sql = "SELECT p.*, u.namaLengkap as namaPenulis, u.role as rolePenulis
                    FROM postingan_kelas p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.id = ? AND p.user_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $postingan_id, $user_id);
            $stmt->execute();
            
            $postingan = $stmt->get_result()->fetch_assoc();
            
            if ($postingan) {
                // Get images for this post
                $postingan['gambar'] = $this->getGambarPostingan($postingan['id']);
            }
            
            return $postingan;
        } catch (Exception $e) {
            return null;
        }
    }

    // Hapus gambar postingan berdasarkan ID
    private function hapusGambarPostinganById($image_id) {
        try {
            // Get image info first
            $sql = "SELECT * FROM postingan_gambar WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $image_id);
            $stmt->execute();
            $image = $stmt->get_result()->fetch_assoc();
            
            if ($image) {
                // Delete from database
                $sql = "DELETE FROM postingan_gambar WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $image_id);
                $stmt->execute();
                
                // Delete physical file
                $fullPath = "../../uploads/" . $image['path_gambar'];
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
        } catch (Exception $e) {
            // Log error if needed
        }
    }

    private function addAssignmentSubmissionStatus($post, $kelas_id) {
        try {
            $assignment_id = $post['assignment_id'];
            
            // If user is a student, get their submission status
            if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'siswa') {
                $siswa_id = $_SESSION['user']['id'];
                
                $sql = "SELECT status, nilai, feedback, tanggal_pengumpulan 
                        FROM pengumpulan_tugas 
                        WHERE assignment_id = ? AND siswa_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ii", $assignment_id, $siswa_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result) {
                    $post['student_submission_status'] = $result['status'];
                    $post['student_score'] = $result['nilai'];
                    $post['student_feedback'] = $result['feedback'];
                    $post['student_submission_date'] = $result['tanggal_pengumpulan'];
                } else {
                    $post['student_submission_status'] = 'belum_mengumpulkan';
                }
            }
            
            // If user is a teacher, get submission statistics
            if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'guru') {
                // Get total students in class
                $sql = "SELECT COUNT(*) as total_students FROM kelas_siswa WHERE kelas_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $kelas_id);
                $stmt->execute();
                $total_students = $stmt->get_result()->fetch_assoc()['total_students'];
                
                // Get submitted count
                $sql = "SELECT COUNT(*) as submitted_count FROM pengumpulan_tugas WHERE assignment_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $assignment_id);
                $stmt->execute();
                $submitted_count = $stmt->get_result()->fetch_assoc()['submitted_count'];
                
                // Get graded count
                $sql = "SELECT COUNT(*) as graded_count FROM pengumpulan_tugas WHERE assignment_id = ? AND status = 'dinilai'";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $assignment_id);
                $stmt->execute();
                $graded_count = $stmt->get_result()->fetch_assoc()['graded_count'];
                
                $post['assignment_total_students'] = $total_students;
                $post['assignment_submitted_count'] = $submitted_count;
                $post['assignment_graded_count'] = $graded_count;
            }
            
            return $post;
        } catch (Exception $e) {
            return $post;
        }
    }
    
    // Hapus data assignment dan file terkait
    private function deleteAssignmentData($assignment_id) {
        try {
            // Get assignment file path first
            $sql = "SELECT file_path FROM tugas WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            // Delete assignment file if exists
            if ($result && $result['file_path'] && file_exists($result['file_path'])) {
                unlink($result['file_path']);
            }
            
            // Get all submission files
            $sql = "SELECT file_path FROM pengumpulan_tugas WHERE assignment_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            $submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Delete submission files
            foreach ($submissions as $submission) {
                if ($submission['file_path'] && file_exists($submission['file_path'])) {
                    unlink($submission['file_path']);
                }
            }
            
            // Delete submission records
            $sql = "DELETE FROM pengumpulan_tugas WHERE assignment_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            
            // Delete assignment record
            $sql = "DELETE FROM tugas WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Error deleting assignment data: " . $e->getMessage());
        }
    }
    
    public function getAssignmentFiles($assignment_id) {
        error_log("✏️ [DEBUG] getAssignmentFiles called for assignment_id: $assignment_id");
        $sql = "SELECT id, file_name, file_path, file_size, file_type, upload_order 
                FROM tugas_files 
                WHERE tugas_id = ? 
                ORDER BY upload_order ASC, id ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        
        $files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        error_log("✏️ [DEBUG] Found " . count($files) . " files in database for assignment $assignment_id");
        
        // Convert file paths to proper URLs
        foreach ($files as &$file) {
            if ($file['file_path']) {
                // Ensure consistent path format
                if (!str_starts_with($file['file_path'], '/')) {
                    $file['file_path'] = '/' . $file['file_path'];
                }
            }
        }
        
        return $files;
    }

    // Helper function to get post owner info
    private function getPostOwner($postingan_id) {
        try {
            $sql = "SELECT p.user_id, p.kelas_id, u.namaLengkap, u.username, u.role 
                    FROM postingan_kelas p 
                    JOIN users u ON p.user_id = u.id 
                    WHERE p.id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $postingan_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}
?>
