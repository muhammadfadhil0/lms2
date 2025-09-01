# Fitur Modal Validasi Soal & Points System

## Deskripsi
Fitur ini menambahkan validasi pada halaman pembuatan soal yang akan menampilkan modal peringatan jika ada soal yang belum lengkap saat pengguna mencoba menyimpan draft. Ditambah dengan sistem poin yang dapat diatur per soal.

## Fitur yang Ditambahkan

### 1. Modal Peringatan Soal Belum Lengkap
- **File**: `src/component/modal-incomplete-questions.php`
- **Fungsi**: Menampilkan daftar soal yang belum lengkap dengan detail masalah
- **Interaksi**: 
  - Klik pada card soal untuk langsung menuju ke soal tersebut
  - Tombol "Ke Soal Pertama" untuk menuju soal pertama yang bermasalah
  - Tombol "Tutup" untuk menutup modal

### 2. Sistem Poin per Soal
- **Input Poin**: Setiap soal memiliki input number untuk mengatur poin (1-100)
- **Live Update**: Total poin di sidebar statistik terupdate real-time saat poin diubah
- **Auto-hide**: Input poin tersembunyi ketika mode auto score aktif
- **Default Value**: Soal baru default 10 poin

### 3. Validasi Soal
- **Lokasi**: JavaScript di `src/script/buat-soal.js`
- **Kriteria Validasi**:
  - **Soal Pilihan Ganda**: 
    - Pertanyaan harus diisi
    - Minimal 2 pilihan jawaban harus diisi
    - Jawaban benar harus dipilih
  - **Soal Jawaban Singkat/Panjang**:
    - Pertanyaan harus diisi
    - Kunci jawaban harus diisi
  - **Mode Auto-Score**: Hanya soal pilihan ganda yang divalidasi

### 4. Toast Notification
- **Fungsi**: Menampilkan notifikasi success di pojok kanan atas
- **Kapan Muncul**: 
  - Berhasil menyimpan soal
  - Berhasil mempublikasi ujian
  - Peringatan jika ada soal yang gagal disimpan

### 5. Navigasi ke Soal Bermasalah
- **Fungsi**: Otomatis scroll dan focus ke soal yang bermasalah
- **Fitur**: 
  - Highlight soal yang aktif
  - Auto-focus pada field yang kosong
  - Smooth scrolling ke posisi soal

## Styling dan Animasi

### 1. CSS Modal
- **File**: `src/css/modal-styles.css`
- **Fitur**: 
  - Animasi enter/leave modal
  - Hover effects pada card soal
  - Responsive design
  - Scrollbar styling

### 2. Animasi
- Modal fade in/out
- Card hover transformation
- Toast slide in animation
- Smooth scrolling navigation

## Cara Kerja

### 1. Sistem Poin
- **Normal Mode**: Input poin tampil, total dihitung dari sum semua poin
- **Auto Score Mode**: Input poin tersembunyi, total tetap 100
- **Live Update**: Event listener pada input poin (`input` & `change`) trigger `updateStats()`
- **Save**: Poin dari input disimpan ke database

### 2. Saat Klik "Simpan Draft"
- JavaScript validasi semua soal aktif
- Jika ada soal belum lengkap → tampilkan modal
- Jika semua lengkap → simpan ke database + tampilkan toast success

### 3. Modal Interaksi
- List soal bermasalah dengan detail error
- Klik card soal → tutup modal + navigate ke soal
- Tombol action untuk navigasi cepat

### 4. Navigasi Soal
- Auto-scroll ke soal target
- Highlight soal aktif
- Focus pada field yang kosong

## Conditional Display Logic

### Auto Score Mode (dari buat-ujian-guru.php)
```php
<?php $autoScoreFlag = (isset($ujian['autoScore']) && $ujian['autoScore']) || (isset($_GET['autoscore']) && $_GET['autoscore']=='1'); ?>
```

- **Jika aktif**: Input poin hidden via CSS class `hidden`
- **Jika tidak aktif**: Input poin tampil normal

### JavaScript Detection
```javascript
const autoScoreFlag = document.getElementById('ujian_id')?.dataset.autoscore === '1';
if (autoScoreFlag) {
    pointsSection.classList.add('hidden');
}
```

## Live Statistics Update

### Event Listeners
- Input `input` event → immediate update
- Input `change` event → final update
- Triggered pada `setupPointsHandler()`

### Update Logic
```javascript
function updateStats() {
    if (autoScoreFlag) {
        totalPoints = 100; // Fixed for auto score
    } else {
        // Sum all points from inputs
        document.querySelectorAll('.question-points').forEach(inp => { 
            totalPoints += parseInt(inp.value) || 0;
        });
    }
    document.getElementById('total-points').textContent = totalPoints;
}
```

## Browser Support
- Modern browsers dengan support untuk:
  - Dialog element
  - CSS animations
  - ES6+ JavaScript features
  - Flexbox dan Grid layout
  - Input number type

## Integrasi
Sistem terintegrasi dengan:
- Auto score detection dari PHP
- Conditional CSS classes
- Live DOM updates
- Database save with actual point values
- Responsive design untuk mobile dan desktop
