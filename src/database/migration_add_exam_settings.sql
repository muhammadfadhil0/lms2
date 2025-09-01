-- Migration: add shuffleQuestions & showScore columns to ujian table
ALTER TABLE ujian 
    ADD COLUMN IF NOT EXISTS shuffleQuestions TINYINT(1) DEFAULT 0 AFTER durasi,
    ADD COLUMN IF NOT EXISTS showScore TINYINT(1) DEFAULT 1 AFTER shuffleQuestions;
