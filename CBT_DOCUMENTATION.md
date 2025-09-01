# CBT (Computer Based Test) - Dokumentasi

## Fitur Halaman CBT Siswa

### Halaman yang Dibuat
- **File**: `src/front/kerjakan-ujian.php`
- **URL**: `http://localhost/lms/src/front/kerjakan-ujian.php?ujian_id=6`

### Fitur Utama

#### 1. **Halaman Awal Ujian**
- Menampilkan informasi ujian (nama, deskripsi, total soal, durasi, total poin)
- Petunjuk ujian yang jelas
- Konfirmasi sebelum memulai ujian
- Statistik ujian (total soal, durasi, poin)

#### 2. **Interface Ujian**
- **Timer**: Countdown real-time dengan peringatan 5 menit sebelum selesai
- **Navigasi Soal**: 
  - Grid navigasi dengan status visual (dijawab/belum dijawab/sedang dikerjakan)
  - Progress bar menunjukkan persentase soal yang telah dijawab
  - Navigasi dengan tombol Previous/Next
- **Auto-save**: Jawaban tersimpan otomatis setiap 30 detik
- **Manual Save**: Tombol simpan untuk menyimpan jawaban secara manual

#### 3. **Responsif Design**
- Desktop: Navigasi dan timer di sidebar kanan
- Mobile: Navigasi dan timer di atas konten
- Mobile menu toggle untuk sidebar

#### 4. **Tipe Soal yang Didukung**
- **Pilihan Ganda**: Radio button dengan pilihan A, B, C, D
- **Essay/Jawaban Singkat**: Textarea untuk jawaban panjang

#### 5. **Keamanan dan Validasi**
- Konfirmasi sebelum keluar halaman (mencegah kehilangan data)
- Auto-submit saat waktu habis
- Validasi session siswa
- CSRF protection melalui session

### Database yang Dibutuhkan

#### Tabel Baru: `jawaban_siswa`
```sql
CREATE TABLE `jawaban_siswa` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ujian_siswa_id` int(11) NOT NULL,
    `soal_id` int(11) NOT NULL,
    `jawaban` text NOT NULL,
    `waktu_jawab` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_jawaban` (`ujian_siswa_id`, `soal_id`)
);
```

#### Method Baru di `UjianLogic`
- `getUjianById($ujian_id)` - Mendapatkan data ujian berdasarkan ID
- `getUjianSiswaById($ujian_siswa_id)` - Mendapatkan data ujian_siswa
- `simpanJawaban($ujian_siswa_id, $soal_id, $jawaban)` - Menyimpan jawaban siswa
- `getJawabanSiswa($ujian_siswa_id)` - Mengambil jawaban yang sudah disimpan

### Alur Kerja CBT

1. **Akses Ujian**: Siswa mengklik "Mulai" dari halaman ujian-user.php
2. **Redirect**: Sistem redirect ke `kerjakan-ujian.php?ujian_id=6`
3. **Halaman Awal**: Tampilkan info ujian dan petunjuk
4. **Konfirmasi**: Siswa mengkonfirmasi untuk memulai ujian
5. **Mulai Ujian**: Sistem membuat record di `ujian_siswa` dan redirect ke `kerjakan-ujian.php?us_id=X`
6. **Interface Ujian**: Siswa mengerjakan soal dengan fitur lengkap
7. **Auto-save**: Jawaban tersimpan otomatis dan manual
8. **Selesai**: Siswa klik "Selesai" atau waktu habis → redirect ke ujian-user.php

### Fitur JavaScript

#### Timer
- Countdown real-time
- Peringatan 5 menit sebelum habis
- Auto-submit saat waktu habis
- Format: HH:MM:SS

#### Navigation
- Klik nomor soal untuk navigasi langsung
- Tombol Previous/Next
- Visual indicator: current (biru), answered (hijau), unanswered (abu-abu)

#### Auto-save
- Interval 30 detik
- Hanya save jika ada jawaban
- Indikator visual "Tersimpan otomatis"
- Error handling jika gagal save

#### Progress Tracking
- Progress bar visual
- Counter "X/Y" soal dijawab
- Update real-time saat jawaban berubah

### Catatan Teknis

1. **Session Management**: Menggunakan PHP session untuk validasi user
2. **AJAX**: Untuk auto-save tanpa refresh halaman
3. **Responsive**: CSS dengan breakpoint mobile/desktop
4. **Error Handling**: Try-catch untuk semua operasi database
5. **SQL Injection Prevention**: Menggunakan prepared statements
6. **XSS Prevention**: htmlspecialchars() untuk output data

### Testing

Untuk testing CBT:
1. Login sebagai siswa
2. Akses ujian dengan ID 6: `http://localhost/lms/src/front/kerjakan-ujian.php?ujian_id=6`
3. Klik "Mulai Ujian"
4. Test semua fitur (navigasi, save, timer, dll)

### Dependencies

- **CSS Framework**: Tailwind CSS (via CDN)
- **Icons**: Tabler Icons
- **Font**: Inter (Google Fonts)
- **Backend**: PHP 7.4+, MySQL 5.7+
- **Frontend**: Vanilla JavaScript (ES6+)
