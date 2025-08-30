-- ====================================
-- Add edit tracking columns to postingan_kelas table
-- ====================================

-- Add is_edited column to track if post has been edited
ALTER TABLE `postingan_kelas` 
ADD COLUMN `is_edited` TINYINT(1) DEFAULT 0 COMMENT 'Flag to indicate if post has been edited';

-- Add diupdate column to track last update time
ALTER TABLE `postingan_kelas` 
ADD COLUMN `diupdate` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp';

-- Add index for better query performance
ALTER TABLE `postingan_kelas` 
ADD INDEX `idx_is_edited` (`is_edited`);
