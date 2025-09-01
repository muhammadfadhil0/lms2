# Setup Instructions - PingoAI

Ikuti langkah-langkah berikut untuk mengaktifkan PingoAI di sistem LMS Anda.

## 1. Dapatkan API Key Groq

1. Buka [Groq Console](https://console.groq.com/)
2. Daftar atau login ke akun Anda
3. Navigasi ke **API Keys** di sidebar
4. Klik **Create API Key**
5. Beri nama key (misal: "LMS PingoAI")
6. Copy API key yang dihasilkan

## 2. Konfigurasi API Key

1. Buka file `src/pingo/config.php`
2. Temukan baris:
   ```php
   define('GROQ_API_KEY', 'your_groq_api_key_here');
   ```
3. Ganti `your_groq_api_key_here` dengan API key Anda:
   ```php
   define('GROQ_API_KEY', 'gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxx');
   ```
4. Simpan file

## 3. Test Konfigurasi

1. Buka browser dan akses: `http://localhost/lms/src/pingo/test-config.php`
2. Jika berhasil, akan muncul response JSON dengan `"success": true`
3. Jika gagal, periksa:
   - API key sudah benar
   - Koneksi internet aktif
   - PHP curl extension enabled

## 4. Test Generate Soal

1. Login sebagai guru
2. Buat ujian baru atau edit ujian yang ada
3. Di halaman "Buat Soal", klik tombol **"🤖 Bantuan PingoAI"**
4. Atur konfigurasi soal:
   - Jumlah soal: 2-3 (untuk test)
   - Tipe: Pilihan Ganda
   - Pilihan jawaban: 4
   - Kesulitan: Mudah
5. Klik **"Generate Soal"**
6. Tunggu proses (15-30 detik)
7. Soal harus muncul otomatis

## 5. Troubleshooting

### Error: "API Key Groq belum dikonfigurasi"
- Pastikan API key sudah diisi di `config.php`
- Restart web server jika perlu

### Error: "API Error (401): Invalid API key"
- Periksa API key sudah benar
- Pastikan API key belum expired
- Generate API key baru jika perlu

### Error: "Request timeout"
- Periksa koneksi internet
- Coba kurangi jumlah soal
- Coba lagi setelah beberapa menit

### Soal tidak muncul di UI
- Periksa console browser untuk JavaScript errors
- Pastikan file `pingo-modal.js` sudah di-load
- Refresh halaman dan coba lagi

### Soal yang dihasilkan tidak relevan
- Pastikan nama ujian dan mata pelajaran sudah jelas
- Isi deskripsi ujian dengan detail materi
- Coba ubah tingkat kesulitan

## 6. Kustomisasi (Opsional)

### Mengubah Model AI
Edit `config.php`:
```php
define('GROQ_MODEL', 'llama-3.1-8b-instant'); // Model lebih cepat
// atau
define('GROQ_MODEL', 'mixtral-8x7b-32768'); // Model alternatif
```

### Mengubah Timeout
Edit `config.php`:
```php
define('AI_TIMEOUT', 60); // 60 detik
```

### Mengubah Kreativitas AI
Edit `config.php`:
```php
define('AI_TEMPERATURE', 0.5); // Lebih konservatif (0.0-1.0)
```

## 7. Keamanan

1. **Jangan commit API key** ke repository
2. Backup file `config.php` secara terpisah
3. Monitor penggunaan API di Groq Console
4. Set limit harian jika diperlukan

## 8. Monitoring

1. Periksa Groq Console untuk usage statistics
2. Monitor log PHP untuk errors
3. Feedback dari guru tentang kualitas soal

## Bantuan

Jika masih ada masalah:

1. Periksa file `README.md` untuk dokumentasi lengkap
2. Test dengan file `test-config.php`
3. Lihat contoh prompt di `example-prompts.php`
4. Periksa log error PHP
5. Pastikan semua dependency terpenuhi

---

**Selamat! PingoAI siap membantu generate soal otomatis.** 🎉
