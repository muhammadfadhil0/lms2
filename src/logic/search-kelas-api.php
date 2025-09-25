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

class SearchKelasLogic {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function searchKelas($query, $guru_id) {
        try {
            // Sanitize query
            $searchQuery = trim($query);
            
            if (empty($searchQuery)) {
                // Jika query kosong, return semua kelas
                return $this->getAllKelas($guru_id);
            }
            
            // Persiapkan query dengan LIKE untuk pencarian
            $sql = "SELECT k.*, 
                          COUNT(DISTINCT ks.siswa_id) as jumlahSiswa,
                          COUNT(DISTINCT u.id) as jumlahUjian
                   FROM kelas k 
                   LEFT JOIN kelas_siswa ks ON k.id = ks.kelas_id 
                   LEFT JOIN ujian u ON k.id = u.kelas_id 
                   WHERE k.guru_id = ? 
                   AND k.status = 'aktif'
                   AND (k.namaKelas LIKE ? OR k.mataPelajaran LIKE ? OR k.deskripsi LIKE ?)
                   GROUP BY k.id 
                   ORDER BY k.dibuat DESC";
            
            $stmt = $this->conn->prepare($sql);
            $searchPattern = "%{$searchQuery}%";
            $stmt->bind_param("isss", $guru_id, $searchPattern, $searchPattern, $searchPattern);
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $kelas = [];
            while ($row = $result->fetch_assoc()) {
                // Highlight matching text
                $row['namaKelas_highlighted'] = $this->highlightMatch($row['namaKelas'], $searchQuery);
                $row['mataPelajaran_highlighted'] = $this->highlightMatch($row['mataPelajaran'], $searchQuery);
                $row['deskripsi_highlighted'] = $this->highlightMatch($row['deskripsi'], $searchQuery);
                
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
    
    private function getAllKelas($guru_id) {
        try {
            $sql = "SELECT k.*, 
                          COUNT(DISTINCT ks.siswa_id) as jumlahSiswa,
                          COUNT(DISTINCT u.id) as jumlahUjian
                   FROM kelas k 
                   LEFT JOIN kelas_siswa ks ON k.id = ks.kelas_id 
                   LEFT JOIN ujian u ON k.id = u.kelas_id 
                   WHERE k.guru_id = ? AND k.status = 'aktif'
                   GROUP BY k.id 
                   ORDER BY k.dibuat DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $guru_id);
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
        if (empty($query)) return $text;
        
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
    $searchLogic = new SearchKelasLogic();
    $guru_id = $_SESSION['user']['id'];
    $query = $_GET['q'];
    
    // Add artificial delay untuk simulasi loading (sesuai request user)
    usleep(800000); // 0.8 detik delay
    
    $result = $searchLogic->searchKelas($query, $guru_id);
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>