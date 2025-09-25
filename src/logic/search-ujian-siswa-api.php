<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'siswa') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['q'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$searchQuery = trim($_GET['q']);
$siswa_id = $_SESSION['user']['id'];

// Function to highlight matching text
function highlightMatch($text, $search) {
    if (empty($search)) return $text;
    return preg_replace('/(' . preg_quote($search, '/') . ')/i', '<span style="background-color: yellow; color: black;">$1</span>', $text);
}

try {
    // Use the same connection as ujian-user.php
    
    // Add artificial delay for loading animation
    usleep(800000); // 0.8 seconds
    
    // Search query for student exams - based on getUjianBySiswa structure
    $sql = "SELECT u.*, k.namaKelas, k.gambar_kelas, us.status as statusPengerjaan, us.totalNilai, us.waktuMulai, us.waktuSelesai, us.id as ujian_siswa_id,
               CASE 
                   WHEN us.id IS NULL THEN 
                       CASE 
                           WHEN u.waktuSelesai < u.waktuMulai THEN 
                               -- Ujian melewati tengah malam
                               CASE 
                                   WHEN TIME(NOW()) >= u.waktuMulai OR TIME(NOW()) <= u.waktuSelesai THEN 
                                       CASE 
                                           WHEN DATE(NOW()) > u.tanggalUjian AND TIME(NOW()) > u.waktuSelesai THEN 'terlambat'
                                           ELSE 'dapat_dikerjakan'
                                       END
                                   ELSE 'belum_dimulai'
                               END
                           ELSE 
                               -- Ujian dalam hari yang sama
                               CASE 
                                   WHEN CONCAT(u.tanggalUjian, ' ', u.waktuSelesai) < NOW() THEN 'terlambat'
                                   WHEN CONCAT(u.tanggalUjian, ' ', u.waktuMulai) <= NOW() AND CONCAT(u.tanggalUjian, ' ', u.waktuSelesai) >= NOW() THEN 'dapat_dikerjakan'
                                   WHEN CONCAT(u.tanggalUjian, ' ', u.waktuMulai) > NOW() THEN 'belum_dimulai'
                                   ELSE 'belum_dikerjakan'
                               END
                       END
                   WHEN us.status = 'selesai' THEN 'selesai'
                   WHEN us.status = 'sedang_mengerjakan' THEN 
                       CASE 
                           WHEN u.waktuSelesai < u.waktuMulai THEN 
                               -- Ujian melewati tengah malam
                               CASE 
                                   WHEN DATE(NOW()) > u.tanggalUjian AND TIME(NOW()) > u.waktuSelesai THEN 'waktu_habis'
                                   ELSE 'sedang_mengerjakan'
                               END
                           ELSE 
                               -- Ujian dalam hari yang same
                               CASE 
                                   WHEN CONCAT(u.tanggalUjian, ' ', u.waktuSelesai) < NOW() THEN 'waktu_habis'
                                   ELSE 'sedang_mengerjakan'
                               END
                       END
                   ELSE 'belum_dikerjakan'
               END as status_ujian
        FROM ujian u 
        JOIN kelas k ON u.kelas_id = k.id
        JOIN kelas_siswa ks ON k.id = ks.kelas_id
        LEFT JOIN ujian_siswa us ON u.id = us.ujian_id AND us.siswa_id = ?
        WHERE ks.siswa_id = ? AND ks.status = 'aktif' AND u.status = 'aktif'
        AND (
            u.namaUjian LIKE ? OR 
            u.deskripsi LIKE ? OR 
            u.mataPelajaran LIKE ? OR 
            k.namaKelas LIKE ? OR
            u.topik LIKE ?
        )
        ORDER BY u.tanggalUjian ASC, u.waktuMulai ASC";
    
    $searchParam = '%' . $searchQuery . '%';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$siswa_id, $siswa_id, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    
    $exams = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Store both original and highlighted text
        $row['namaUjian_highlighted'] = highlightMatch($row['namaUjian'], $searchQuery);
        $row['deskripsi_highlighted'] = highlightMatch($row['deskripsi'], $searchQuery);
        $row['mataPelajaran_highlighted'] = highlightMatch($row['mataPelajaran'], $searchQuery);
        $row['namaKelas_highlighted'] = highlightMatch($row['namaKelas'], $searchQuery);
        $row['topik_highlighted'] = highlightMatch($row['topik'], $searchQuery);
        
        $exams[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $exams,
        'count' => count($exams)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>