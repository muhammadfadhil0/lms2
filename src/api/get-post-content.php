<?php
/**
 * API endpoint to get post content for AI explanation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit();
}

require_once '../logic/koneksi.php';

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['post_id'])) {
        throw new Exception('Post ID is required');
    }
    
    $post_id = intval($input['post_id']);
    $user_id = $_SESSION['user']['id'];
    
    // Get post data
    $stmt = $koneksi->prepare("
        SELECT 
            p.id,
            p.konten,
            p.dibuat as createdAt,
            p.user_id,
            p.kelas_id,
            p.tipePost,
            u.namaLengkap as authorName,
            k.namaKelas,
            (SELECT COUNT(*) FROM like_postingan lp WHERE lp.postingan_id = p.id) as jumlahLike,
            (SELECT COUNT(*) FROM komentar_postingan kp WHERE kp.postingan_id = p.id) as jumlahKomentar
        FROM postingan_kelas p 
        LEFT JOIN users u ON p.user_id = u.id 
        LEFT JOIN kelas k ON p.kelas_id = k.id
        WHERE p.id = ?
    ");
    
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Post not found');
    }
    
    $post = $result->fetch_assoc();
    
    // Debug log
    error_log("Post found: " . json_encode($post));
    
    // Check if user has access to this post (member of the class)
    $access_stmt = $koneksi->prepare("
        SELECT 1 FROM kelas_siswa ks 
        WHERE ks.kelas_id = ? AND ks.siswa_id = ?
        UNION 
        SELECT 1 FROM kelas k 
        WHERE k.id = ? AND k.guru_id = ?
    ");
    $access_stmt->bind_param("iiii", $post['kelas_id'], $user_id, $post['kelas_id'], $user_id);
    $access_stmt->execute();
    $access_result = $access_stmt->get_result();
    
    if ($access_result->num_rows === 0) {
        throw new Exception('Access denied to this post');
    }
    
    // Get media files from postingan_gambar table
    $media_stmt = $koneksi->prepare("
        SELECT nama_file, path_gambar, ukuran_file, tipe_file, media_type, urutan 
        FROM postingan_gambar 
        WHERE postingan_id = ? 
        ORDER BY urutan
    ");
    $media_stmt->bind_param("i", $post_id);
    $media_stmt->execute();
    $media_result = $media_stmt->get_result();
    
    $media = [];
    while ($img = $media_result->fetch_assoc()) {
        $media[] = [
            'filename' => $img['nama_file'],
            'path' => $img['path_gambar'],
            'size' => $img['ukuran_file'],
            'type' => $img['tipe_file'],
            'media_type' => $img['media_type'],
            'order' => $img['urutan']
        ];
    }
    
    // Get file attachments
    $files_stmt = $koneksi->prepare("
        SELECT nama_file, file_path, ukuran_file, mime_type, urutan 
        FROM postingan_files 
        WHERE postingan_id = ? 
        ORDER BY urutan
    ");
    $files_stmt->bind_param("i", $post_id);
    $files_stmt->execute();
    $files_result = $files_stmt->get_result();
    
    $files = [];
    while ($file = $files_result->fetch_assoc()) {
        $files[] = [
            'filename' => $file['nama_file'],
            'original_name' => $file['nama_file'],
            'file_size' => $file['ukuran_file'],
            'file_type' => $file['mime_type'],
            'path' => $file['file_path'],
            'order' => $file['urutan']
        ];
    }
    
    // Prepare response
    $post['gambar'] = $media;
    $post['files'] = $files;
    
    // Debug log
    error_log("Final post data: " . json_encode($post));
    error_log("Media count: " . count($media));
    error_log("Files count: " . count($files));
    
    echo json_encode([
        'success' => true,
        'post' => $post
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>