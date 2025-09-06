# Fitur Review Hasil Ujian untuk Siswa

## 📋 Deskripsi
Fitur ini memungkinkan siswa untuk melihat hasil ujian yang telah mereka kerjakan beserta review jawaban dan koreksi dari guru. Guru dapat mengontrol apakah siswa diizinkan melihat hasil atau tidak melalui pengaturan saat membuat ujian.

## ✨ Fitur Utama

### 1. **Kontrol Izin Guru**
- Guru dapat mengatur apakah siswa diizinkan melihat hasil ujian melalui switch "Siswa Dapat Melihat Hasil" di halaman pembuatan ujian
- Switch ini tersimpan di kolom `showScore` dalam tabel `ujian`
- Default: Siswa diizinkan melihat hasil (showScore = 1)

### 2. **Halaman Review Hasil** (`review-ujian.php`)
- **URL**: `http://localhost/lms/src/front/review-ujian.php?ujian_id=[ID_UJIAN]`
- **Akses**: Hanya siswa yang sudah menyelesaikan ujian
- **UI**: Mirip dengan `detail-ujian-guru.php` namun disesuaikan untuk perspektif siswa

### 3. **Konten Review**
- **Identitas Ujian**: Nama, kelas, mata pelajaran, durasi, tanggal, waktu
- **Ringkasan Hasil**: Nilai total, jumlah benar/salah, waktu pengerjaan
- **Review Per Soal**:
  - Pertanyaan dan jawaban siswa
  - Status jawaban (Benar/Salah/Belum Dikoreksi/Tidak Dijawab)
  - Untuk pilihan ganda: Menampilkan pilihan dengan highlight kunci jawaban dan pilihan siswa
  - Untuk essay: Jawaban siswa dan kunci jawaban (jika ada)
  - Catatan koreksi dari guru (jika ada)
  - Nilai per soal

### 4. **Sistem Perlindungan**
- Jika guru tidak mengizinkan (`showScore = 0`), siswa akan diarahkan kembali ke halaman ujian dengan notifikasi toast
- Hanya siswa yang sudah menyelesaikan ujian yang dapat mengakses review
- Validasi kepemilikan ujian (hanya siswa yang mengerjakan ujian tersebut)

## 🔗 Integrasi dengan Sistem

### 1. **Link Akses**
Di halaman `ujian-user.php`, untuk ujian dengan status "selesai":
```html
<a href="review-ujian.php?ujian_id=<?= (int)$u['id'] ?>" class="flex-1 bg-orange text-white rounded-lg px-3 py-2 text-xs text-center hover:bg-orange-600 transition">
    Lihat Nilai
</a>
```

### 2. **Database Schema**
**Tabel `ujian`**:
- `showScore` TINYINT(1) DEFAULT 1 - Kontrol izin lihat hasil

**Tabel yang digunakan**:
- `ujian` - Data ujian dan pengaturan
- `ujian_siswa` - Data pengerjaan siswa
- `soal` - Data soal
- `jawaban_siswa` - Jawaban siswa

### 3. **Logic Backend**
**File**: `src/logic/ujian-logic.php`
**Method baru**: `getReviewUjianSiswa($ujian_id, $siswa_id)`
- Menggabungkan data ujian, soal, dan jawaban siswa
- Memvalidasi izin akses (`showScore`)
- Memproses pilihan ganda untuk tampilan yang lebih baik

## 🚀 Cara Penggunaan

### Untuk Guru:
1. Buat ujian baru atau edit ujian existing
2. Pada bagian "Pengaturan Tambahan", toggle switch "Siswa Dapat Melihat Hasil"
3. Jika dimatikan, siswa tidak akan bisa melihat hasil ujian

### Untuk Siswa:
1. Selesaikan ujian terlebih dahulu
2. Di halaman daftar ujian, klik tombol "Lihat Nilai" pada ujian yang sudah selesai
3. Jika guru mengizinkan, halaman review akan tampil dengan detail jawaban

## 📱 UI/UX Features

### Responsive Design
- Layout grid yang responsive (desktop: 3 kolom utama + 1 sidebar, mobile: full width)
- Tombol dan elemen yang mobile-friendly

### Visual Indicators
- Status badge untuk setiap jawaban (Benar/Salah/Belum Dikoreksi/Tidak Dijawab)
- Color coding:
  - Hijau: Jawaban benar
  - Merah: Jawaban salah
  - Biru: Belum dikoreksi
  - Abu-abu: Tidak dijawab
- Highlight pilihan jawaban siswa vs kunci jawaban

### Toast Notification
- Notifikasi yang user-friendly ketika akses ditolak
- Auto-close setelah 5 detik
- Clean URL (parameter error dihapus setelah ditampilkan)

## 🔧 File yang Terlibat

### File Baru:
- `src/front/review-ujian.php` - Halaman review hasil ujian untuk siswa

### File yang Dimodifikasi:
- `src/logic/ujian-logic.php` - Menambah method `getReviewUjianSiswa()`
- `src/front/ujian-user.php` - Menambah toast notification system dan handling error
- `src/logic/create-exam.php` - Sudah mendukung penyimpanan `showScore`

### Database Migration:
- `src/database/migration_add_exam_settings.sql` - Menambah kolom `showScore`

## 🐛 Error Handling

1. **Ujian tidak ditemukan**: Redirect ke ujian-user.php dengan error
2. **Akses tidak diizinkan**: Redirect dengan toast notification
3. **Siswa belum menyelesaikan ujian**: Validasi di backend
4. **Ujian bukan milik siswa**: Validasi kepemilikan

## 🔒 Security

- Session validation untuk role siswa
- Validasi ujian_id (integer casting)
- Sanitasi output dengan `htmlspecialchars()`
- Validasi ownership (siswa hanya bisa melihat ujian yang sudah dikerjakan)

## 📊 Status Implementasi

✅ **Selesai**:
- Switch kontrol guru di halaman buat ujian
- Halaman review hasil ujian untuk siswa
- Sistem validasi dan error handling
- Toast notification system
- Responsive UI design
- Database migration

✅ **Telah ditest**:
- Kontrol izin guru (showScore = 0/1)
- Tampilan review hasil ujian
- Error handling untuk akses ditolak
- Link integrasi dari ujian-user.php

## 🎯 Fitur Tambahan yang Bisa Dikembangkan

1. **Export/Print hasil ujian**
2. **Grafik performa siswa**
3. **Riwayat ujian siswa**
4. **Notifikasi real-time ketika hasil sudah tersedia**
5. **Diskusi/komentar pada hasil ujian**
