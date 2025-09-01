# PingoAI - AI Question Generator

PingoAI adalah sistem AI yang menggunakan Groq API untuk menghasilkan soal ujian secara otomatis. Sistem ini terintegrasi dengan LMS untuk membantu guru membuat soal dengan cepat dan efisien.

## Fitur

- **Generate Soal Otomatis**: Membuat soal pilihan ganda dan essay menggunakan AI
- **Kustomisasi**: Atur jumlah soal, tingkat kesulitan, dan jumlah pilihan jawaban
- **Konteks Aware**: AI mempertimbangkan nama ujian, mata pelajaran, dan soal yang sudah ada
- **Integration**: Terintegrasi langsung dengan sistem LMS yang ada
- **Real-time Preview**: Melihat prompt yang akan dikirim ke AI sebelum generate

## Konfigurasi

### 1. Setup API Key Groq

Edit file `src/pingo/config.php` dan ganti API key Groq Anda:

```php
define('GROQ_API_KEY', 'your_groq_api_key_here'); // Ganti dengan API Key Groq Anda
```

### 2. Mendapatkan API Key Groq

1. Kunjungi [Groq Console](https://console.groq.com/)
2. Daftar atau login ke akun Anda
3. Buat API key baru
4. Copy API key dan masukkan ke `config.php`

### 3. Konfigurasi Model

Anda dapat mengubah model AI yang digunakan di `config.php`:

```php
define('GROQ_MODEL', 'llama-3.1-70b-versatile'); // Model yang akan digunakan
```

Model yang tersedia:
- `llama-3.1-70b-versatile`
- `llama-3.1-8b-instant`
- `mixtral-8x7b-32768`

## Cara Penggunaan

### 1. Akses PingoAI

1. Login sebagai guru
2. Buat ujian baru atau edit ujian yang ada
3. Di halaman "Buat Soal", klik tombol **"🤖 Bantuan PingoAI"**

### 2. Konfigurasi Soal

Di modal PingoAI, atur:

- **Jumlah Soal**: 1-20 soal per request
- **Tipe Soal**: Pilihan Ganda atau Essay
- **Pilihan Jawaban**: 4-6 pilihan (untuk pilihan ganda)
- **Tingkat Kesulitan**: Mudah, Sedang, atau Sulit

### 3. Generate Soal

1. Review prompt yang akan dikirim ke AI
2. Klik **"Generate Soal"**
3. Tunggu proses generate (biasanya 10-30 detik)
4. Soal akan ditambahkan otomatis ke ujian

## Struktur File

```
src/pingo/
├── config.php           # Konfigurasi API key dan pengaturan
├── pingo-ai.php         # Class utama PingoAI
├── generate-questions.php # Endpoint untuk generate soal
├── pingo-modal.js       # JavaScript untuk modal UI
└── README.md           # Dokumentasi ini
```

## API Endpoint

### POST `/src/pingo/generate-questions.php`

Generate soal menggunakan AI.

**Request Body (JSON):**
```json
{
    "ujian_id": 123,
    "question_count": 5,
    "question_type": "multiple_choice",
    "answer_options": 4,
    "difficulty": "sedang"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Berhasil generate 5 soal menggunakan PingoAI",
    "questions": [...],
    "total": 5
}
```

## Troubleshooting

### Error: "API Key Groq belum dikonfigurasi"

1. Pastikan Anda sudah mengisi API key di `config.php`
2. Pastikan API key valid dan tidak expired

### Error: "API Error (401): Invalid API key"

1. Periksa kembali API key Anda
2. Pastikan API key memiliki akses yang sesuai
3. Coba generate API key baru

### Error: "Request timeout"

1. Cek koneksi internet
2. Coba kurangi jumlah soal yang di-generate
3. Coba lagi setelah beberapa menit

### Soal yang dihasilkan tidak sesuai

1. Pastikan deskripsi ujian dan mata pelajaran sudah jelas
2. Coba ubah tingkat kesulitan
3. Review existing questions yang mungkin mempengaruhi AI

## Konfigurasi Lanjutan

### Mengubah Timeout

Edit `config.php`:
```php
define('AI_TIMEOUT', 60); // Timeout dalam detik
```

### Mengubah Maksimal Token

Edit `config.php`:
```php
define('AI_MAX_TOKENS', 4000); // Maksimal token untuk response
```

### Mengubah Temperature (Kreativitas)

Edit `config.php`:
```php
define('AI_TEMPERATURE', 0.7); // 0.0 = konservatif, 1.0 = kreatif
```

## Keamanan

1. **Jangan commit API key** ke repository
2. **Validasi input** - semua input user sudah divalidasi
3. **Session check** - hanya guru yang bisa mengakses
4. **Rate limiting** - pertimbangkan menambah rate limiting jika diperlukan

## Batasan

- Maksimal 20 soal per request
- Hanya mendukung bahasa Indonesia
- Bergantung pada koneksi internet
- Tergantung pada ketersediaan layanan Groq

## Dukungan

Jika mengalami masalah:

1. Periksa log PHP untuk error details
2. Pastikan semua dependency terpenuhi
3. Cek dokumentasi Groq API
4. Test dengan prompt sederhana terlebih dahulu

## Pengembangan

Untuk menambah fitur atau model AI baru:

1. Edit `PingoAI` class di `pingo-ai.php`
2. Tambah tipe soal baru di `$SUPPORTED_QUESTION_TYPES`
3. Update prompt building logic
4. Test dengan berbagai skenario

---

**Catatan**: PingoAI masih dalam tahap pengembangan. Fitur dan kemampuan akan terus ditingkatkan seiring waktu.
