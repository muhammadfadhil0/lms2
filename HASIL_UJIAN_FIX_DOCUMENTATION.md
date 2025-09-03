# Perbaikan Masalah Hasil Ujian - Hanya Menampilkan 2 Soal

## Masalah yang Ditemukan

Ketika guru memeriksa hasil ujian siswa melalui `hasil-ujian.php`, baik di mode swipe maupun formulir, hanya 2 soal yang ditampilkan meskipun ujian memiliki 7 soal. 

## Akar Masalah

1. **Masalah pada penyimpanan jawaban**: 
   - Fungsi `simpanJawaban` tidak menangani kolom `pilihanJawaban` untuk soal pilihan ganda
   - Auto-save tidak cukup agresif sehingga beberapa jawaban tidak tersimpan
   - Proses finishing ujian hanya menyimpan jawaban terakhir yang aktif

2. **Masalah pada query database**:
   - `getDetailJawabanSiswa` menggunakan INNER JOIN yang hanya menampilkan soal yang memiliki jawaban
   - `getDataKoreksiSwipe` tidak menggunakan LEFT JOIN yang tepat

## Perbaikan yang Dilakukan

### 1. Perbaikan Fungsi `simpanJawaban` di `ujian-logic.php`

```php
// Sebelum: Tidak menangani pilihanJawaban
// Sesudah: Mendeteksi tipe soal dan menyimpan pilihanJawaban untuk pilihan ganda
```

**Perbaikan:**
- Menambah logic untuk mendeteksi tipe soal
- Menyimpan jawaban pilihan ganda ke kolom `pilihanJawaban`
- Menangani error dengan lebih baik

### 2. Perbaikan Fungsi `getDetailJawabanSiswa` di `ujian-logic.php`

```php
// Sebelum: INNER JOIN - hanya menampilkan soal yang dijawab
// Sesudah: LEFT JOIN - menampilkan semua soal, termasuk yang belum dijawab
```

**Perbaikan:**
- Menggunakan LEFT JOIN untuk menampilkan semua soal
- Mengurutkan berdasarkan nomor soal
- Menampilkan soal kosong sebagai belum dijawab

### 3. Perbaikan Fungsi `getDataKoreksiSwipe` di `ujian-logic.php`

```php
// Sebelum: CROSS JOIN dengan binding parameter ganda
// Sesudah: JOIN yang lebih efisien dengan LEFT JOIN
```

**Perbaikan:**
- Menggunakan JOIN yang lebih efisien
- Menambah filter `status = 'selesai'`
- Memperbaiki struktur query

### 4. Perbaikan Auto-Save di `kerjakan-ujian.php`

**Perbaikan:**
- Menambah fungsi `saveAllAnswers()` yang menyimpan semua jawaban sebelum finish
- Memperbaiki auto-save agar lebih agresif (setiap 10 detik)
- Menambah `saveUnsavedAnswers()` untuk menyimpan jawaban yang belum tersimpan

### 5. Script Perbaikan Data Retroaktif

Dibuat `fix-missing-answers.php` untuk:
- Menambahkan record `jawaban_siswa` kosong untuk soal yang tidak memiliki jawaban
- Memperbaiki data ujian yang sudah selesai namun memiliki jawaban tidak lengkap

## Hasil Perbaikan

**Sebelum:**
- Hanya 2 soal muncul di hasil ujian
- Jawaban pilihan ganda tidak tersimpan dengan benar
- Mode swipe dan formulir menampilkan data tidak lengkap

**Sesudah:**
- Semua 7 soal muncul di hasil ujian
- Jawaban pilihan ganda tersimpan dengan benar di kolom `pilihanJawaban`
- Mode swipe dan formulir menampilkan data lengkap
- Auto-save lebih reliable
- Data ujian lama sudah diperbaiki

## File yang Dimodifikasi

1. `/src/logic/ujian-logic.php` - Perbaikan fungsi core
2. `/src/front/kerjakan-ujian.php` - Perbaikan auto-save dan finish exam
3. `/fix-missing-answers.php` - Script perbaikan data (sekali pakai)

## Testing

Setelah perbaikan, hasil ujian menampilkan:
- ✅ Semua 7 soal ditampilkan di mode tabel, swipe, dan formulir
- ✅ Jawaban pilihan ganda tersimpan dengan benar
- ✅ Soal yang belum dijawab ditampilkan sebagai kosong
- ✅ Data ujian lama sudah diperbaiki

## Catatan

Script `fix-missing-answers.php` sudah dijalankan untuk memperbaiki data yang ada. Script ini bisa dihapus setelah konfirmasi semua data sudah benar.
