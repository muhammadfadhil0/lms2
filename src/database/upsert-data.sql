-- ====================================
-- Alternative: UPDATE existing data or INSERT new
-- Menggunakan ON DUPLICATE KEY UPDATE
-- ====================================

INSERT INTO `users` (`username`, `email`, `password`, `namaLengkap`, `bio`, `role`, `status`) VALUES
('admin', 'admin@lms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator System', 'Administrator sistem LMS terbaru', 'admin', 'aktif')
ON DUPLICATE KEY UPDATE 
    namaLengkap = VALUES(namaLengkap),
    bio = VALUES(bio),
    diperbarui = CURRENT_TIMESTAMP;

-- Cara ini akan:
-- 1. INSERT data baru jika username belum ada
-- 2. UPDATE data yang sudah ada jika username sudah ada
