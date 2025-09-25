<?php
/**
 * PingoAI Configuration Template
 * File template konfigurasi untuk API Keys dan pengaturan AI
 * 
 * INSTRUKSI:
 * 1. Copy file ini menjadi config.php
 * 2. Isi API key dan konfigurasi yang sesuai
 * 3. File config.php tidak akan di-commit ke git (sudah ada di .gitignore)
 */

// Groq AI Configuration
define('GROQ_API_KEY', 'YOUR_GROQ_API_KEY_HERE'); // Ganti dengan API Key Groq Anda
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_MODEL', 'openai/gpt-oss-120b'); // Model yang akan digunakan

// AI Settings
define('AI_TIMEOUT', 30); // Timeout dalam detik
define('AI_MAX_TOKENS', 4000); // Maksimal token untuk response
define('AI_TEMPERATURE', 0.7); // Kreativitas AI (0.0 - 1.0)

// Question Generation Limits
define('MAX_QUESTIONS_PER_REQUEST', 20); // Maksimal soal per request
define('MIN_QUESTIONS_PER_REQUEST', 1); // Minimal soal per request

// Database Configuration (jika diperlukan)
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'lms_database');
// define('DB_USER', 'your_username');
// define('DB_PASS', 'your_password');

// Error Reporting (set false untuk production)
define('AI_DEBUG_MODE', true);

// Default Language
define('DEFAULT_LANGUAGE', 'id'); // Indonesian

// Cache Settings
define('ENABLE_CACHE', false);
define('CACHE_DURATION', 3600); // 1 hour in seconds
?>
