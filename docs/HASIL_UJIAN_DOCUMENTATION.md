# Dokumentasi Hasil Ujian - LMS

## Overview
File `hasil-ujian.php` adalah halaman untuk guru melihat dan mengelola hasil ujian siswa dengan berbagai mode koreksi dan penilaian.

## Fitur Utama

### 1. Tabel Hasil Ujian (Mode Default)
- Menampilkan daftar siswa dengan statistik lengkap:
  - Nama siswa
  - Jumlah jawaban benar
  - Jumlah jawaban salah  
  - Jumlah tidak dijawab
  - Nilai akhir
- Button aksi berbeda berdasarkan mode scoring:
  - **AutoScore aktif**: Button "Rincian" untuk melihat detail
  - **Manual scoring**: Button "Nilai" untuk koreksi manual

### 2. Mode Penilaian

#### A. Nilai Otomatis (AutoScore)
- Untuk ujian dengan `autoScore = 1` di database
- Pilihan ganda dinilai otomatis berdasarkan kunci jawaban
- Nilai langsung tampil di tabel tanpa perlu koreksi manual
- Button "Rincian" menampilkan detail jawaban dan perhitungan

#### B. Nilai Manual  
- Untuk ujian dengan `autoScore = 0` di database
- Nilai awalnya kosong/belum dinilai
- Button "Nilai" mengantarkan ke halaman koreksi
- Mendukung semua jenis soal (pilihan ganda, jawaban singkat, essay)

### 3. Mode Koreksi

#### A. Mode Swipe (Seperti Tinder)
- Interface card yang bisa di-swipe untuk koreksi cepat
- Navigasi:
  - **Swipe kanan / tombol ✓**: Jawaban benar
  - **Swipe kiri / tombol ✗**: Jawaban salah  
  - **Swipe bawah / tombol 📝**: Input nilai manual
- Keyboard shortcuts:
  - `Arrow Right`: Benar
  - `Arrow Left`: Salah
  - `Arrow Down`: Input nilai
  - `Escape`: Tutup modal
- Progress bar menampilkan kemajuan koreksi
- Auto-progression ke siswa berikutnya setelah soal habis

#### B. Mode Formulir
- Interface list tradisional dengan semua soal dan siswa
- Radio button untuk benar/salah
- Input field untuk nilai per soal
- Batch saving untuk semua nilai sekaligus

### 4. Fitur Khusus Essay/Jawaban Panjang
Untuk soal essay, tampilan dibagi 3 bagian:
- **Atas**: Pertanyaan soal
- **Tengah**: Jawaban siswa
- **Bawah**: Kunci jawaban/rubrik

### 5. Button Periksa Otomatis Pilihan Ganda
- Memproses semua jawaban pilihan ganda secara batch
- Membandingkan dengan kunci jawaban
- Update skor dan statistik otomatis
- Konfirmasi sebelum eksekusi

## Struktur Database

### Tabel yang Digunakan
1. **ujian**: Data ujian dan pengaturan
2. **ujian_siswa**: Record pengerjaan ujian per siswa
3. **soal**: Daftar soal ujian
4. **jawaban_siswa**: Jawaban dan nilai per soal per siswa
5. **users**: Data siswa dan guru

### Kolom Penting
- `ujian.autoScore`: Mode penilaian (0=manual, 1=otomatis)
- `jawaban_siswa.benar`: Status benar/salah (1/0)
- `jawaban_siswa.poin`: Poin yang diperoleh
- `jawaban_siswa.pilihanJawaban`: Pilihan untuk multiple choice

## API Endpoints

### `src/logic/hasil-ujian-api.php`

#### Actions Available:
1. **get_detail_jawaban**
   - Parameter: `ujian_siswa_id`
   - Return: Detail jawaban siswa per soal

2. **get_swipe_data** 
   - Parameter: `ujian_id`
   - Return: Data untuk mode swipe koreksi

3. **periksa_otomatis_pg**
   - Parameter: `ujian_id`
   - Function: Auto-check semua pilihan ganda

4. **save_manual_score**
   - Parameter: `ujian_siswa_id`, `soal_id`, `benar`, `poin`
   - Function: Simpan nilai manual per soal

5. **batch_save_scores**
   - Parameter: `scores` (JSON array)
   - Function: Simpan banyak nilai sekaligus

## Penggunaan

### URL Parameters
- `ujian_id`: ID ujian yang akan dilihat hasilnya
- `mode`: Mode tampilan (`tabel`, `swipe`, `formulir`)
- `koreksi_id`: ID ujian_siswa untuk koreksi spesifik

### Contoh URL:
```
hasil-ujian.php?ujian_id=123                    # Mode tabel
hasil-ujian.php?ujian_id=123&mode=swipe         # Mode swipe
hasil-ujian.php?ujian_id=123&mode=formulir      # Mode formulir
```

## Keamanan
- Session validation untuk guru
- Ownership validation ujian vs guru
- Parameter sanitization
- SQL injection protection
- CSRF protection via session

## Responsive Design
- Mobile-friendly interface
- Touch gestures untuk mode swipe
- Adaptive button sizes
- Collapsible table pada mobile

## Browser Compatibility
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile browsers (iOS Safari, Chrome Mobile)
- JavaScript ES6+ features required

## Troubleshooting

### Common Issues:
1. **Nilai tidak muncul**: Cek kolom `autoScore` di tabel ujian
2. **API error**: Pastikan file `hasil-ujian-api.php` accessible
3. **Swipe tidak bekerja**: Cek JavaScript console untuk errors
4. **Database error**: Validasi struktur tabel jawaban_siswa

### Debug Mode:
Gunakan `test-hasil-ujian.php` untuk mengecek:
- Struktur database
- Sample data
- API endpoints
- Kolom yang required

## Future Enhancements
- Export hasil ke PDF/Excel
- Grafik statistik ujian
- Analisis per soal
- Bulk operations
- Email notifikasi hasil
- Integration dengan sistem penilaian sekolah
