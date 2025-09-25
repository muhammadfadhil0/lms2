<?php
session_start();
header('Content-Type: application/json');

// Cek apakah user sudah login dan adalah guru
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../logic/koneksi.php';

class SearchUjianLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function searchUjian($query, $guru_id) {
        try {
            // Sanitize query
            $searchQuery = trim($query);
            
            if (empty($searchQuery)) {
                // Jika query kosong, return semua ujian
                return $this->getAllUjian($guru_id);
            }
            
            // Query dengan LIKE untuk pencarian ujian
            $sql = "SELECT u.*, k.namaKelas, k.gambar_kelas,
                          COUNT(DISTINCT us.siswa_id) as jumlahPeserta,
                          u.totalSoal as jumlahSoal
                   FROM ujian u 
                   LEFT JOIN kelas k ON u.kelas_id = k.id 
                   LEFT JOIN ujian_siswa us ON u.id = us.ujian_id
                   WHERE u.guru_id = ? 
                   AND u.status != 'arsip'
                   AND (u.namaUjian LIKE ? OR u.deskripsi LIKE ? OR u.mataPelajaran LIKE ? OR k.namaKelas LIKE ? OR u.topik LIKE ?)
                   GROUP BY u.id 
                   ORDER BY u.dibuat DESC";
            
            $stmt = $this->conn->prepare($sql);
            $searchPattern = "%{$searchQuery}%";
            $stmt->bind_param("isssss", $guru_id, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $ujian = [];
            while ($row = $result->fetch_assoc()) {
                // Highlight matching text
                $row['namaUjian_highlighted'] = $this->highlightMatch($row['namaUjian'], $searchQuery);
                $row['deskripsi_highlighted'] = $this->highlightMatch($row['deskripsi'], $searchQuery);
                $row['mataPelajaran_highlighted'] = $this->highlightMatch($row['mataPelajaran'], $searchQuery);
                $row['namaKelas_highlighted'] = $this->highlightMatch($row['namaKelas'], $searchQuery);
                $row['topik_highlighted'] = $this->highlightMatch($row['topik'], $searchQuery);
                
                $ujian[] = $row;
            }
            
            return [
                'success' => true,
                'data' => $ujian,
                'total' => count($ujian),
                'query' => $searchQuery
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    private function getAllUjian($guru_id) {
        try {
            $sql = "SELECT u.*, k.namaKelas, k.gambar_kelas,
                          COUNT(DISTINCT us.siswa_id) as jumlahPeserta,
                          u.totalSoal as jumlahSoal
                   FROM ujian u 
                   LEFT JOIN kelas k ON u.kelas_id = k.id 
                   LEFT JOIN ujian_siswa us ON u.id = us.ujian_id
                   WHERE u.guru_id = ? AND u.status != 'arsip'
                   GROUP BY u.id 
                   ORDER BY u.dibuat DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $guru_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $ujian = [];
            while ($row = $result->fetch_assoc()) {
                $ujian[] = $row;
            }
            
            return [
                'success' => true,
                'data' => $ujian,
                'total' => count($ujian),
                'query' => ''
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    private function highlightMatch($text, $query) {
        if (empty($query) || empty($text)) return htmlspecialchars($text);
        
        // Case-insensitive highlight
        return preg_replace(
            '/(' . preg_quote($query, '/') . ')/i',
            '<mark class="search-highlight">$1</mark>',
            htmlspecialchars($text)
        );
    }
}

// Handle request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['q'])) {
    $searchLogic = new SearchUjianLogic();
    $guru_id = $_SESSION['user']['id'];
    $query = $_GET['q'];
    
    // Add artificial delay untuk simulasi loading (sesuai request user)
    usleep(800000); // 0.8 detik delay
    
    $result = $searchLogic->searchUjian($query, $guru_id);
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>