-- Script untuk memastikan tabel komentar_postingan ada
-- Jalankan ini jika tabel belum ada

CREATE TABLE IF NOT EXISTS `komentar_postingan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `postingan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `komentar` text NOT NULL,
  `dibuat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`postingan_id`) REFERENCES `postingan_kelas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pastikan juga ada indeks untuk performa yang lebih baik
CREATE INDEX IF NOT EXISTS `idx_postingan_id` ON `komentar_postingan` (`postingan_id`);
CREATE INDEX IF NOT EXISTS `idx_user_id` ON `komentar_postingan` (`user_id`);
