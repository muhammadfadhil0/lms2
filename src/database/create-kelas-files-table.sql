-- Create table for class files (schedules and materials)
CREATE TABLE IF NOT EXISTS kelas_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id INT NOT NULL,
    guru_id INT NOT NULL,
    file_type ENUM('schedule', 'material') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    file_extension VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
    FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_kelas_files_kelas (kelas_id),
    INDEX idx_kelas_files_type (file_type)
);
