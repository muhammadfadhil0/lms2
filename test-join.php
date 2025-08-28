<?php
session_start();

echo "<h3>Debug Join Kelas</h3>";

// Check session
echo "<p><strong>Session Status:</strong></p>";
if (isset($_SESSION['user'])) {
    echo "<p>User ID: " . $_SESSION['user']['id'] . "</p>";
    echo "<p>Role: " . $_SESSION['user']['role'] . "</p>";
    echo "<p>Name: " . $_SESSION['user']['namaLengkap'] . "</p>";
} else {
    echo "<p>No session found</p>";
}

echo "<hr>";

// Test join kelas functionality
if ($_POST) {
    echo "<p><strong>POST Data:</strong></p>";
    var_dump($_POST);
    
    if (isset($_POST['kodeKelas'])) {
        require_once 'src/logic/kelas-logic.php';
        
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'siswa') {
            echo "<p style='color: red;'>Error: User not logged in as siswa</p>";
        } else {
            $kelasLogic = new KelasLogic();
            $siswa_id = $_SESSION['user']['id'];
            $kodeKelas = trim($_POST['kodeKelas']);
            
            echo "<p>Attempting to join class with code: " . htmlspecialchars($kodeKelas) . "</p>";
            
            $result = $kelasLogic->joinKelas($siswa_id, $kodeKelas);
            
            echo "<p><strong>Result:</strong></p>";
            echo "<pre>";
            print_r($result);
            echo "</pre>";
        }
    }
} else {
    echo "<form method='POST'>";
    echo "<p>Test Join Kelas:</p>";
    echo "<input type='text' name='kodeKelas' placeholder='Kode Kelas' value='MAT693'>";
    echo "<button type='submit'>Test Join</button>";
    echo "</form>";
}

echo "<hr>";
echo "<p><a href='src/front/beranda-user.php'>Back to Beranda User</a></p>";
?>
