<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Mock evaluation response for testing
echo json_encode([
    'success' => true,
    'evaluation' => [
        'deskripsi' => 'Siswa menunjukkan pemahaman yang baik pada materi ujian dengan tingkat keberhasilan 75%. Terdapat beberapa konsep yang perlu diperkuat.',
        'materi_perlu_evaluasi' => [
            'jumlah_salah' => 2,
            'total_soal' => 8,
            'topik' => [
                'Pemahaman konsep dasar matematika',
                'Penerapan rumus dalam soal cerita'
            ]
        ],
        'materi_sudah_dikuasai' => [
            'jumlah_benar' => 6,
            'total_soal' => 8,
            'topik' => [
                'Operasi hitung dasar',
                'Pemahaman membaca soal',
                'Penguasaan rumus sederhana'
            ]
        ],
        'saran_pembelajaran' => [
            'Perbanyak latihan soal dengan tingkat kesulitan bertahap',
            'Gunakan metode pembelajaran visual dengan diagram',
            'Berikan contoh aplikasi nyata dalam kehidupan sehari-hari',
            'Lakukan review berkala untuk memperkuat konsep dasar'
        ]
    ]
]);
?>