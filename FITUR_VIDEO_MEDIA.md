# Fitur Upload Video dan Media - LMS

## Fitur yang Telah Diimplementasikan

### 1. Upload Media (Gambar dan Video)
- **Lokasi**: `kelas-guru.php`, `kelas-user.php`
- **Input Field**: Berubah dari `imageInput` menjadi `mediaInput`
- **Icon**: Berubah dari `ti-photo` (Foto) menjadi `ti-device-camera` (Media)
- **Format Didukung**: 
  - Gambar: JPG, PNG, GIF (maksimal 5MB)
  - Video: MP4, AVI, MOV, WMV, WEBM (maksimal 50MB)
- **Maksimal File**: 4 file media per postingan

### 2. Database Update
- **Tabel**: `postingan_gambar`
- **Kolom Baru**: `media_type` ENUM('image', 'video') DEFAULT 'image'
- **File SQL**: `src/database/add-media-type-column.sql`

### 3. Backend Processing
- **File**: `src/logic/handle-posting.php`
- **Fungsi Baru**: `handleMediaUploads()` menggantikan `handleImageUploads()`
- **Validasi**: Berbeda berdasarkan tipe media (gambar vs video)
- **Upload Path**: `uploads/postingan/{kelas_id}/`

### 4. Frontend JavaScript
- **File Baru**: `src/script/media-upload-manager.js`
- **File Update**: `src/script/kelas-posting-stable.js`
- **Fungsi**:
  - Preview media (gambar dan video)
  - Validasi ukuran dan tipe file
  - Tampilan postingan dengan video player
  - Download media

### 5. CSS Styling
- **File Baru**: `src/css/media-upload.css`
- **Fitur**:
  - Grid layout responsif untuk media
  - Video player styling
  - Media type indicators
  - Download buttons
  - Mobile-responsive design

### 6. Tampilan Postingan
- **Video Player**: 
  - HTML5 video controls
  - Preload metadata
  - Fallback message untuk browser yang tidak mendukung
- **Download Button**: Tersedia untuk semua media
- **Media Type Badge**: Menunjukkan apakah file adalah gambar atau video
- **Grid Layout**: Mendukung 1-4 file media dengan layout yang responsif

## Penggunaan

### Upload Media
1. Buka halaman kelas (guru atau siswa)
2. Klik icon "Media" di form posting
3. Pilih file gambar dan/atau video (maksimal 4 file)
4. Preview akan muncul dengan indikator tipe media
5. Klik "Posting" untuk mengupload

### Melihat Media di Postingan
1. Gambar: Dapat diklik untuk view fullscreen
2. Video: Memiliki controls untuk play/pause, volume, dll
3. Download: Klik tombol download di pojok kanan atas media

## File yang Diubah

### Backend
- `src/logic/handle-posting.php` - Upload processing
- `src/logic/postingan-logic.php` - Database operations
- `src/database/add-media-type-column.sql` - Database schema

### Frontend PHP
- `src/front/kelas-guru.php` - UI update
- `src/front/kelas-user.php` - UI update
- (beranda-user.php sudah otomatis mendukung karena menggunakan kelas-posting-stable.js)

### JavaScript
- `src/script/media-upload-manager.js` - New file
- `src/script/kelas-posting-stable.js` - Updated for video support

### CSS
- `src/css/media-upload.css` - New file

## Backward Compatibility

Sistem masih mendukung upload gambar lama melalui `images[]` form field untuk kompatibilitas dengan kode yang ada.

## Testing

Untuk menguji fitur:
1. Buka halaman kelas sebagai guru atau siswa
2. Coba upload kombinasi gambar dan video
3. Verifikasi preview, posting, dan tampilan di feed
4. Test download functionality
5. Test responsive design di mobile

## Troubleshooting

1. **Video tidak muncul**: Pastikan format video didukung browser
2. **Upload gagal**: Cek ukuran file (gambar max 5MB, video max 50MB)
3. **Preview tidak muncul**: Pastikan `media-upload-manager.js` ter-load
4. **Database error**: Pastikan kolom `media_type` sudah ditambahkan ke tabel `postingan_gambar`
