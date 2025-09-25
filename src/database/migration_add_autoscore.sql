-- Migration: add autoScore column to ujian table if not exists
ALTER TABLE ujian ADD COLUMN IF NOT EXISTS autoScore TINYINT(1) NOT NULL DEFAULT 0 AFTER showScore;
