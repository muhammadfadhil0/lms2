-- ====================================
-- Fix VIEW hasil ujian siswa
-- ====================================

-- Drop existing view first
DROP VIEW IF EXISTS `view_hasil_ujian`;

-- Create corrected view dengan alias yang benar
CREATE OR REPLACE VIEW `view_hasil_ujian` AS
SELECT 
    us.id,
    uj.namaUjian,
    usr.namaLengkap as namaSiswa,
    us.totalNilai,
    us.jumlahBenar,
    us.jumlahSalah,
    us.status,
    us.waktuMulai,
    us.waktuSelesai,
    uj.totalSoal,
    k.namaKelas
FROM ujian_siswa us
JOIN ujian uj ON us.ujian_id = uj.id
JOIN users usr ON us.siswa_id = usr.id
JOIN kelas k ON uj.kelas_id = k.id;
