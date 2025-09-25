<?php
// Contoh penggunaan semua logic class
require_once 'user-logic.php';
require_once 'kelas-logic.php';
require_once 'ujian-logic.php';
require_once 'soal-logic.php';
require_once 'postingan-logic.php';
require_once 'dashboard-logic.php';

// Inisialisasi logic classes
$userLogic = new UserLogic();
$kelasLogic = new KelasLogic();
$ujianLogic = new UjianLogic();
$soalLogic = new SoalLogic();
$postinganLogic = new PostinganLogic();
$dashboardLogic = new DashboardLogic();

// ========== CONTOH PENGGUNAAN ==========

// 1. REGISTRASI DAN LOGIN
/*
$register = $userLogic->register('siswa_baru', 'siswa@email.com', 'password123', 'Nama Siswa', 'siswa');
if ($register['success']) {
    echo "Registrasi berhasil!\n";
}

$login = $userLogic->login('siswa_baru', 'password123');
if ($login['success']) {
    $_SESSION['user'] = $login['user'];
    echo "Login berhasil!\n";
}
*/

// 2. MEMBUAT KELAS (GURU)
/*
$guru_id = 2; // ID guru
$kelas = $kelasLogic->buatKelas(
    'Pemrograman PHP', 
    'Kelas untuk belajar PHP dari dasar', 
    'Informatika', 
    $guru_id, 
    25
);
if ($kelas['success']) {
    echo "Kelas berhasil dibuat dengan kode: " . $kelas['kode_kelas'] . "\n";
}
*/

// 3. JOIN KELAS (SISWA)
/*
$siswa_id = 4; // ID siswa
$join = $kelasLogic->joinKelas($siswa_id, 'INF001');
if ($join['success']) {
    echo "Berhasil join kelas!\n";
}
*/

// 4. MEMBUAT UJIAN
/*
$ujian = $ujianLogic->buatUjian(
    'Quiz PHP Basic',
    'Quiz untuk menguji pemahaman dasar PHP',
    1, // kelas_id
    2, // guru_id
    'Informatika',
    '2025-09-15',
    '14:00:00',
    '15:30:00',
    90 // durasi dalam menit
);
if ($ujian['success']) {
    echo "Ujian berhasil dibuat!\n";
}
*/

// 5. MEMBUAT SOAL PILIHAN GANDA
/*
$pilihan = [
    'A' => 'echo',
    'B' => 'print',
    'C' => 'printf',
    'D' => 'display'
];
$soal = $soalLogic->buatSoalPilihanGanda(
    1, // ujian_id
    1, // nomor soal
    'Fungsi mana yang digunakan untuk menampilkan output di PHP?',
    $pilihan,
    'A', // kunci jawaban
    10 // poin
);
*/

// 6. DASHBOARD DATA
/*
// Dashboard Guru
$dashboardGuru = $dashboardLogic->getDashboardGuru(2);
echo "Total kelas guru: " . $dashboardGuru['totalKelas'] . "\n";

// Dashboard Siswa
$dashboardSiswa = $dashboardLogic->getDashboardSiswa(4);
echo "Total kelas siswa: " . $dashboardSiswa['totalKelas'] . "\n";
*/

// 7. POSTINGAN KELAS
/*
$postingan = $postinganLogic->buatPostingan(
    1, // kelas_id
    2, // user_id (guru)
    'Selamat datang di kelas Pemrograman PHP! Silakan download materi dari link berikut...',
    'pengumuman'
);
*/

// 8. MENDAPATKAN DATA
/*
// Kelas berdasarkan guru
$kelasGuru = $kelasLogic->getKelasByGuru(2);
foreach ($kelasGuru as $kelas) {
    echo "Kelas: " . $kelas['namaKelas'] . " - Siswa: " . $kelas['jumlahSiswa'] . "\n";
}

// Ujian berdasarkan siswa
$ujianSiswa = $ujianLogic->getUjianBySiswa(4);
foreach ($ujianSiswa as $ujian) {
    echo "Ujian: " . $ujian['namaUjian'] . " - Status: " . $ujian['status_ujian'] . "\n";
}
*/

// 9. RESPONSE FORMAT
/*
Semua function mengembalikan format:
Array(
    'success' => true/false,
    'message' => 'pesan',
    'data' => [...] // optional
)

Contoh penggunaan dengan error handling:
$result = $kelasLogic->buatKelas(...);
if ($result['success']) {
    // Berhasil
    echo $result['message'];
} else {
    // Error
    echo "Error: " . $result['message'];
}
*/

echo "Logic classes siap digunakan!\n";
echo "Gunakan file ini sebagai referensi untuk implementasi di front-end.\n";
?>
