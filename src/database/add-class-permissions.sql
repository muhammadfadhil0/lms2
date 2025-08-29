-- SQL Script untuk Update Fitur Class Settings
-- Jalankan script ini untuk menambahkan/update kolom permissions ke tabel kelas

-- Cek dan tambah kolom restrict_posting
SET @sql = CONCAT('ALTER TABLE kelas ADD COLUMN restrict_posting TINYINT(1) DEFAULT 0');
SET @exist = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'kelas' 
              AND COLUMN_NAME = 'restrict_posting');
SET @sql = IF(@exist = 0, @sql, 'SELECT "Column restrict_posting already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cek dan tambah kolom restrict_comments
SET @sql = CONCAT('ALTER TABLE kelas ADD COLUMN restrict_comments TINYINT(1) DEFAULT 0');
SET @exist = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'kelas' 
              AND COLUMN_NAME = 'restrict_comments');
SET @sql = IF(@exist = 0, @sql, 'SELECT "Column restrict_comments already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cek dan tambah kolom lock_class (mengganti private_class)
SET @sql = CONCAT('ALTER TABLE kelas ADD COLUMN lock_class TINYINT(1) DEFAULT 0');
SET @exist = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'kelas' 
              AND COLUMN_NAME = 'lock_class');
SET @sql = IF(@exist = 0, @sql, 'SELECT "Column lock_class already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Hapus kolom yang tidak terpakai (jika ada)
SET @sql = CONCAT('ALTER TABLE kelas DROP COLUMN hide_students');
SET @exist = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'kelas' 
              AND COLUMN_NAME = 'hide_students');
SET @sql = IF(@exist > 0, @sql, 'SELECT "Column hide_students does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('ALTER TABLE kelas DROP COLUMN private_class');
SET @exist = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'kelas' 
              AND COLUMN_NAME = 'private_class');
SET @sql = IF(@exist > 0, @sql, 'SELECT "Column private_class does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Reset semua permissions ke default (0)
UPDATE kelas SET 
    restrict_posting = 0, 
    restrict_comments = 0, 
    lock_class = 0;

-- Tampilkan struktur tabel setelah perubahan
DESCRIBE kelas;
