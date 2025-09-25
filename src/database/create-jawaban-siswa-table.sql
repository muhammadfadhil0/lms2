-- ====================================
-- Create Jawaban Siswa Table
-- Tabel untuk menyimpan jawaban siswa dalam ujian CBT
-- ====================================

USE `lms`;

CREATE TABLE IF NOT EXISTS `jawaban_siswa` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ujian_siswa_id` int(11) NOT NULL,
    `soal_id` int(11) NOT NULL,
    `jawaban` text NOT NULL,
    `pilihanJawaban` varchar(255) DEFAULT NULL,
    `benar` tinyint(1) DEFAULT NULL,
    `poin` double DEFAULT NULL,
    `waktuDijawab` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_jawaban` (`ujian_siswa_id`, `soal_id`),
    KEY `idx_ujian_siswa` (`ujian_siswa_id`),
    KEY `idx_soal` (`soal_id`),
    KEY `idx_ujian_siswa_soal` (`ujian_siswa_id`, `soal_id`),
    KEY `idx_waktu_dijawab` (`waktuDijawab`),
    CONSTRAINT `fk_jawaban_ujian_siswa` FOREIGN KEY (`ujian_siswa_id`) REFERENCES `ujian_siswa` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_jawaban_soal` FOREIGN KEY (`soal_id`) REFERENCES `soal` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
