# PANDUAN MENGAKTIFKAN PHP EXTENSIONS UNTUK IMPORT WORD

## Masalah
Fitur import Word memerlukan PHP extensions berikut yang belum aktif:
- ZIP extension (untuk membaca file .docx)  
- GD extension (untuk memproses gambar jika ada)

## Solusi untuk XAMPP Windows

### Langkah 1: Edit php.ini
1. Buka XAMPP Control Panel
2. Klik "Config" pada Apache
3. Pilih "PHP (php.ini)"

### Langkah 2: Aktifkan Extensions
Cari baris berikut dan hapus tanda `;` di awalnya:

```
;extension=gd
;extension=zip
```

Ubah menjadi:
```
extension=gd
extension=zip
```

### Langkah 3: Restart Apache
1. Stop Apache di XAMPP Control Panel
2. Start Apache kembali

### Langkah 4: Verifikasi
Buka terminal dan jalankan:
```bash
php -m | grep zip
php -m | grep gd
```

Jika berhasil, kedua extension akan muncul dalam daftar.

## Alternatif Sementara
Jika tidak bisa mengaktifkan extensions, gunakan metode input manual untuk menambahkan soal satu per satu.

## Test File
Setelah mengaktifkan extensions, coba lagi fitur import Word dengan file template yang sudah disediakan.