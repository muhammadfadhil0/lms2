-- ====================================
-- Create table for postingan images
-- ====================================

CREATE TABLE IF NOT EXISTS `postingan_gambar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `postingan_id` int(11) NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `path_gambar` varchar(500) NOT NULL,
  `ukuran_file` int(11) DEFAULT NULL,
  `tipe_file` varchar(50) DEFAULT NULL,
  `urutan` tinyint(4) DEFAULT 1,
  `dibuat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `postingan_id` (`postingan_id`),
  KEY `idx_postingan_gambar_urutan` (`postingan_id`, `urutan`),
  FOREIGN KEY (`postingan_id`) REFERENCES `postingan_kelas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
