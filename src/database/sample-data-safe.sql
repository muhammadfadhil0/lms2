-- ====================================
-- Safe Sample Data Insert
-- Menggunakan INSERT IGNORE atau ON DUPLICATE KEY UPDATE
-- ====================================

-- Sample users (dengan INSERT IGNORE untuk menghindari duplikasi)
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `namaLengkap`, `bio`, `role`, `status`) VALUES
('admin', 'admin@lms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'Administrator sistem LMS', 'admin', 'aktif'),
('pak_ahmad', 'ahmad@lms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Ahmad Fulan, M.Kom', 'Dosen Pengampu Pemrograman Web', 'guru', 'aktif'),
('bu_sari', 'sari@lms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sari Indah, S.Pd', 'Guru Bahasa Indonesia', 'guru', 'aktif'),
('budi123', 'budi@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Santoso', 'Mahasiswa Teknik Informatika', 'siswa', 'aktif'),
('sari123', 'sari@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sari Indah', 'Mahasiswa Sistem Informasi', 'siswa', 'aktif'),
('andi456', 'andi@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Andi Pratama', 'Mahasiswa Teknik Komputer', 'siswa', 'aktif'),
('dina789', 'dina@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dina Maharani', 'Mahasiswa Desain Grafis', 'siswa', 'aktif');

-- Sample kelas (dengan INSERT IGNORE)
INSERT IGNORE INTO `kelas` (`namaKelas`, `deskripsi`, `mataPelajaran`, `kodeKelas`, `guru_id`, `status`) VALUES
('Pemrograman Web', 'Kelas untuk mempelajari dasar-dasar pemrograman web menggunakan HTML, CSS, JavaScript, dan PHP', 'Informatika', 'PROGWEB001', 2, 'aktif'),
('Bahasa Indonesia', 'Kelas untuk mempelajari tata bahasa dan sastra Indonesia', 'Bahasa Indonesia', 'BIND001', 3, 'aktif'),
('Matematika Dasar', 'Kelas untuk mempelajari konsep dasar matematika', 'Matematika', 'MTK001', 2, 'aktif'),
('Database Design', 'Kelas untuk mempelajari perancangan dan implementasi database', 'Informatika', 'DB001', 2, 'aktif'),
('Algoritma Pemrograman', 'Kelas untuk mempelajari logika dan algoritma pemrograman', 'Informatika', 'ALGO001', 2, 'aktif');

-- Sample kelas_siswa (dengan INSERT IGNORE)
INSERT IGNORE INTO `kelas_siswa` (`kelas_id`, `siswa_id`, `status`) VALUES
(1, 4, 'aktif'),
(1, 5, 'aktif'),
(1, 6, 'aktif'),
(2, 4, 'aktif'),
(2, 5, 'aktif'),
(2, 7, 'aktif'),
(3, 4, 'aktif'),
(3, 6, 'aktif'),
(4, 5, 'aktif'),
(4, 6, 'aktif'),
(5, 4, 'aktif'),
(5, 5, 'aktif'),
(5, 6, 'aktif');

-- Sample ujian (dengan INSERT IGNORE)
INSERT IGNORE INTO `ujian` (`namaUjian`, `deskripsi`, `kelas_id`, `guru_id`, `mataPelajaran`, `tanggalUjian`, `waktuMulai`, `waktuSelesai`, `durasi`, `status`, `totalSoal`) VALUES
('UTS Pemrograman Web', 'Ujian Tengah Semester untuk mata kuliah Pemrograman Web', 1, 2, 'Informatika', '2025-09-15', '08:00:00', '10:00:00', 120, 'aktif', 20),
('Quiz HTML/CSS', 'Quiz singkat tentang HTML dan CSS', 1, 2, 'Informatika', '2025-09-10', '14:00:00', '15:00:00', 60, 'aktif', 10),
('UAS Bahasa Indonesia', 'Ujian Akhir Semester Bahasa Indonesia', 2, 3, 'Bahasa Indonesia', '2025-12-20', '09:00:00', '11:30:00', 150, 'draft', 25),
('Quiz Matematika', 'Quiz harian matematika dasar', 3, 2, 'Matematika', '2025-09-05', '10:00:00', '11:00:00', 60, 'selesai', 15);

-- Sample pengaturan_akun (dengan INSERT IGNORE)
INSERT IGNORE INTO `pengaturan_akun` (`user_id`, `notifikasi_email`, `notifikasi_browser`, `visibilitas_profil`) VALUES
(1, 1, 1, 'publik'),
(2, 1, 1, 'terbatas'),
(3, 1, 1, 'terbatas'),
(4, 1, 0, 'terbatas'),
(5, 0, 1, 'pribadi'),
(6, 1, 1, 'terbatas'),
(7, 1, 0, 'pribadi');

-- Sample postingan_kelas (dengan INSERT IGNORE)
INSERT IGNORE INTO `postingan_kelas` (`kelas_id`, `user_id`, `konten`, `tipePost`) VALUES
(1, 2, 'Selamat datang di kelas Pemrograman Web! Semoga kalian semua semangat belajar.', 'pengumuman'),
(1, 2, 'Silakan download materi HTML dan CSS dari link berikut...', 'materi'),
(2, 3, 'Untuk tugas minggu ini, silakan buat essay tentang sastra Indonesia modern.', 'tugas'),
(1, 4, 'Terima kasih pak atas materinya, sangat membantu!', 'umum');
