-- Create tugas (assignments) table
CREATE TABLE IF NOT EXISTS tugas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id INT NOT NULL,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT NOT NULL,
    file_path VARCHAR(500) NULL,
    deadline DATETIME NOT NULL,
    nilai_maksimal INT NOT NULL DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE
);

-- Create pengumpulan_tugas (assignment submissions) table
CREATE TABLE IF NOT EXISTS pengumpulan_tugas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    siswa_id INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    catatan_pengumpulan TEXT NULL,
    tanggal_pengumpulan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tanggal_penilaian TIMESTAMP NULL,
    status ENUM('dikumpulkan', 'dinilai') DEFAULT 'dikumpulkan',
    nilai INT NULL,
    feedback TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES tugas(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_submission (assignment_id, siswa_id)
);

-- Add assignment_id column to postingan_kelas table if not exists
ALTER TABLE postingan_kelas 
ADD COLUMN IF NOT EXISTS assignment_id INT NULL,
ADD COLUMN IF NOT EXISTS tipe_postingan ENUM('regular', 'assignment') DEFAULT 'regular';

-- Add foreign key constraint
ALTER TABLE postingan_kelas 
ADD CONSTRAINT fk_postingan_assignment 
FOREIGN KEY (assignment_id) REFERENCES tugas(id) ON DELETE CASCADE;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_tugas_kelas_id ON tugas(kelas_id);
CREATE INDEX IF NOT EXISTS idx_tugas_deadline ON tugas(deadline);
CREATE INDEX IF NOT EXISTS idx_pengumpulan_assignment_id ON pengumpulan_tugas(assignment_id);
CREATE INDEX IF NOT EXISTS idx_pengumpulan_siswa_id ON pengumpulan_tugas(siswa_id);
CREATE INDEX IF NOT EXISTS idx_pengumpulan_status ON pengumpulan_tugas(status);
CREATE INDEX IF NOT EXISTS idx_postingan_assignment_id ON postingan_kelas(assignment_id);
CREATE INDEX IF NOT EXISTS idx_postingan_tipe ON postingan_kelas(tipe_postingan);
