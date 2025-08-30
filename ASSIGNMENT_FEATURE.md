# Assignment Feature Implementation

Fitur tugas telah berhasil ditambahkan ke sistem LMS dengan fungsionalitas lengkap untuk guru dan siswa.

## 🚀 Cara Setup

1. Jalankan setup database:
```bash
php setup-assignments.php
```

2. Pastikan folder upload sudah memiliki permission yang benar:
```bash
chmod 755 uploads/
mkdir -p uploads/assignments uploads/submissions
chmod 755 uploads/assignments uploads/submissions
```

## 📋 Fitur yang Ditambahkan

### Untuk Guru (kelas-guru.php):

1. **Membuat Tugas Baru**
   - Icon tugas di samping upload foto
   - Modal form dengan field: judul, deskripsi, file tugas (opsional), deadline, nilai maksimal
   - File tugas mendukung format: PDF, DOC, DOCX, PPT, PPTX, TXT

2. **Melihat Laporan Tugas**
   - Button "Laporan Tugas Siswa" di quick actions
   - Halaman laporan lengkap (assignment-reports.php)
   - Statistik pengumpulan dan penilaian
   - Fitur grading dengan nilai dan feedback

3. **Postingan Tugas Otomatis**
   - Setiap tugas baru otomatis membuat postingan khusus
   - Tampilan berbeda dengan badge "assignment"
   - Statistik real-time (terkumpul/dinilai)

### Untuk Siswa (kelas-user.php):

1. **Mengumpulkan Tugas**
   - Modal pengumpulan dengan upload file dan catatan
   - Support berbagai format file
   - Tracking status pengumpulan

2. **Status Tracking**
   - Belum mengumpulkan → Tugas dikumpulkan → Tugas dinilai
   - Notifikasi visual untuk setiap tahap
   - Feedback dan nilai dari guru

3. **Fitur Kumpulkan Ulang**
   - Siswa dapat mengumpulkan ulang sebelum deadline
   - Replace file sebelumnya dengan yang baru

## 🗃️ Database Tables

### tugas
- Menyimpan data tugas yang dibuat guru
- Field: id, kelas_id, judul, deskripsi, file_path, deadline, nilai_maksimal

### pengumpulan_tugas
- Menyimpan data pengumpulan siswa
- Field: id, assignment_id, siswa_id, file_path, catatan_pengumpulan, status, nilai, feedback
- Status: 'dikumpulkan', 'dinilai'

### postingan (modified)
- Ditambahkan field: assignment_id, tipe_postingan
- Support untuk postingan khusus tugas

## 📁 File Structure

```
src/
├── component/
│   ├── modal-create-assignment.php     # Modal buat tugas (guru)
│   └── modal-submit-assignment.php     # Modal kumpul tugas (siswa)
├── front/
│   └── assignment-reports.php          # Halaman laporan tugas
├── logic/
│   ├── create-assignment.php           # Membuat tugas baru
│   ├── submit-assignment.php           # Mengumpulkan tugas
│   ├── get-assignments.php             # Daftar tugas
│   ├── get-assignment-report.php       # Laporan pengumpulan
│   ├── grade-submission.php            # Penilaian tugas
│   └── get-student-submission.php      # Status pengumpulan siswa
├── script/
│   └── assignment-manager.js           # JavaScript assignment handler
└── database/
    └── create-assignment-tables.sql    # SQL schema
```

## 🎯 Flow Kerja

1. **Guru membuat tugas** → Sistem membuat postingan khusus assignment
2. **Siswa melihat tugas** → Button "Kumpulkan Tugas" tersedia
3. **Siswa mengumpulkan** → Status berubah ke "menunggu penilaian"
4. **Guru menilai** → Siswa dapat melihat nilai dan feedback
5. **Tracking lengkap** → Semua tahap termonitor real-time

## 🔧 Teknologi yang Digunakan

- **Backend**: PHP dengan MySQLi
- **Frontend**: HTML5, JavaScript ES6, Tailwind CSS
- **File Upload**: Support multiple formats dengan validasi
- **Real-time Updates**: AJAX untuk update status dinamis
- **Responsive Design**: Mobile-friendly interface

## ⚠️ Catatan Penting

1. **File Size Limit**: Maksimal 10MB per file
2. **File Formats**: Sesuai spesifikasi (PDF, DOC, IMG, dll)
3. **Deadline Enforcement**: Sistem otomatis block pengumpulan setelah deadline
4. **Permission System**: Guru hanya bisa akses kelas sendiri
5. **Data Integrity**: Foreign key constraints untuk konsistensi data

## 🐛 Error Handling

- Validasi file format dan ukuran
- Error handling untuk upload gagal
- Fallback UI untuk koneksi bermasalah
- Rollback transaction untuk operasi critical

Fitur assignment ini terintegrasi penuh dengan sistem existing dan siap untuk production use!
