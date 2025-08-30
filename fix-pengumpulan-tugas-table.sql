-- Add missing columns to pengumpulan_tugas table if they don't exist

-- Check if tanggal_penilaian column exists, if not add it
SET @sql = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE table_name = 'pengumpulan_tugas' 
    AND column_name = 'tanggal_penilaian' 
    AND table_schema = DATABASE());
SET @sql = IF(@sql = 0, 'ALTER TABLE pengumpulan_tugas ADD COLUMN tanggal_penilaian DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure the table has all required columns for assignment grading
-- This will create table if not exists with proper structure
CREATE TABLE IF NOT EXISTS pengumpulan_tugas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    siswa_id INT NOT NULL,
    file_path TEXT,
    catatan_pengumpulan TEXT,
    tanggal_pengumpulan DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('dikumpulkan', 'dinilai') DEFAULT 'dikumpulkan',
    nilai DECIMAL(5,2) NULL,
    feedback TEXT NULL,
    tanggal_penilaian DATETIME NULL,
    FOREIGN KEY (assignment_id) REFERENCES tugas(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_submission (assignment_id, siswa_id)
);
