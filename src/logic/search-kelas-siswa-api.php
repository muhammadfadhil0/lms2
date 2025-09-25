<?php
session_start();
header('Content-Type: application/json');

// Cek apakah user sudah login dan adalah siswa
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'siswa') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../logic/koneksi.php';

class SearchKelasSiswaLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function searchKelas($query, $siswa_id) {
        try {
            // Sanitize query
            $searchQuery = trim($query);
            
            if (empty($searchQuery)) {
                // Jika query kosong, return semua kelas yang diikuti
                return $this->getAllKelas($siswa_id);
            }
            
            // Query dengan LIKE untuk pencarian kelas yang diikuti siswa
            $sql = "SELECT k.*, g.namaLengkap as namaGuru,
                          COUNT(DISTINCT ks2.siswa_id) as jumlahSiswa,
                          ks.tanggal_bergabung
                   FROM kelas k 
                   INNER JOIN kelas_siswa ks ON k.id = ks.kelas_id 
                   LEFT JOIN users g ON k.guru_id = g.id
                   LEFT JOIN kelas_siswa ks2 ON k.id = ks2.kelas_id AND ks2.status = 'aktif'
                   WHERE ks.siswa_id = ? 
                   AND ks.status = 'aktif'
                   AND k.status = 'aktif'
                   AND (k.namaKelas LIKE ? OR k.mataPelajaran LIKE ? OR k.deskripsi LIKE ? OR g.namaLengkap LIKE ?)
                   GROUP BY k.id 
                   ORDER BY ks.tanggal_bergabung DESC";
            
            $stmt = $this->conn->prepare($sql);
            $searchPattern = "%{$searchQuery}%";
            $stmt->bind_param("issss", $siswa_id, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $kelas = [];
            while ($row = $result->fetch_assoc()) {
                // Highlight matching text
                $row['namaKelas_highlighted'] = $this->highlightMatch($row['namaKelas'], $searchQuery);
                $row['mataPelajaran_highlighted'] = $this->highlightMatch($row['mataPelajaran'], $searchQuery);
                $row['deskripsi_highlighted'] = $this->highlightMatch($row['deskripsi'], $searchQuery);
                $row['namaGuru_highlighted'] = $this->highlightMatch($row['namaGuru'], $searchQuery);
                
                $kelas[] = $row;
            }
            
            return [
                'success' => true,
                'data' => $kelas,
                'total' => count($kelas),
                'query' => $searchQuery
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    private function getAllKelas($siswa_id) {
        try {
            $sql = "SELECT k.*, g.namaLengkap as namaGuru,
                          COUNT(DISTINCT ks2.siswa_id) as jumlahSiswa,
                          ks.tanggal_bergabung
                   FROM kelas k 
                   INNER JOIN kelas_siswa ks ON k.id = ks.kelas_id 
                   LEFT JOIN users g ON k.guru_id = g.id
                   LEFT JOIN kelas_siswa ks2 ON k.id = ks2.kelas_id AND ks2.status = 'aktif'
                   WHERE ks.siswa_id = ? 
                   AND ks.status = 'aktif'
                   AND k.status = 'aktif'
                   GROUP BY k.id 
                   ORDER BY ks.tanggal_bergabung DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $siswa_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $kelas = [];
            while ($row = $result->fetch_assoc()) {
                $kelas[] = $row;
            }
            
            return [
                'success' => true,
                'data' => $kelas,
                'total' => count($kelas),
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
    $searchLogic = new SearchKelasSiswaLogic();
    $siswa_id = $_SESSION['user']['id'];
    $query = $_GET['q'];
    
    // Add artificial delay untuk simulasi loading (sesuai request user)
    usleep(800000); // 0.8 detik delay
    
    $result = $searchLogic->searchKelas($query, $siswa_id);
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>