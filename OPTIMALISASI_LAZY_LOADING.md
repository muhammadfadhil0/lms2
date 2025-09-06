# Optimalisasi Lazy Loading untuk Postingan LMS

## Perubahan yang Dilakukan

### 1. Frontend (JavaScript) - kelas-posting-stable.js
- **Reduce limit**: Mengubah limit posting dari 10 menjadi 5 untuk loading yang lebih cepat
- **Improved scroll detection**: Mengurangi threshold dari 1000px menjadi 500px untuk loading yang lebih responsif
- **Loading indicators**: Menambahkan indikator loading yang lebih baik saat scroll
- **Staggered rendering**: Mengurangi delay rendering dari 100ms menjadi 50ms per item
- **Lazy comment loading**: Komentar di-load secara bertahap setelah postingan

### 2. Backend (PHP)
#### get-postingan.php
- **Default limit**: Mengubah default limit dari 10 menjadi 5

#### postingan-logic.php  
- **Method signature**: Update `getPostinganByKelas()` default limit dari 20 menjadi 5

#### dashboard-logic.php
- **Pagination support**: Menambahkan parameter `$offset` pada `getPostinganTerbaruSiswa()`
- **SQL optimization**: Update query untuk mendukung LIMIT dan OFFSET

#### get-beranda-posts.php (Baru)
- **API endpoint baru**: Untuk lazy loading postingan di beranda
- **Parameter**: Mendukung limit dan offset untuk pagination

### 3. Frontend Pages

#### beranda-user.php
- **Reduce initial load**: Mengurangi postingan awal dari 15 menjadi 5
- **Dynamic container**: Menambahkan container dinamis untuk lazy loading
- **Load more button**: Tombol "Muat Postingan Lainnya" 
- **Scroll detection**: Auto-load saat scroll mendekati bawah
- **JavaScript functions**:
  - `initializeBerandaLazyLoading()`
  - `loadMoreBerandaPosts()`
  - `createBerandaPostElement()`

#### kelas-guru.php & kelas-user.php
- **Sudah optimal**: Kedua file sudah menggunakan sistem posting dinamis via JavaScript
- **Otomatis memanfaatkan**: Perubahan limit dan optimalisasi scroll di kelas-posting-stable.js

## Manfaat Optimalisasi

### 1. **Performa Loading**
- ✅ Loading awal 50% lebih cepat (5 vs 10 postingan)
- ✅ Waktu First Paint lebih cepat
- ✅ Mengurangi beban server per request

### 2. **User Experience**
- ✅ Smooth scrolling dengan lazy loading
- ✅ Loading indicator yang jelas
- ✅ Progressive loading (5 postingan per batch)
- ✅ Auto-load saat scroll (opsional)

### 3. **Network Optimization**
- ✅ Mengurangi transfer data awal
- ✅ Request yang lebih kecil dan terdistribusi
- ✅ Caching yang lebih efektif

### 4. **Memory Efficiency**
- ✅ DOM tidak overload dengan banyak element
- ✅ Rendering yang bertahap
- ✅ Cleanup timeout yang proper

## Cara Kerja

### 1. **Initial Load**
```
User masuk → Load 5 postingan pertama → Show content
```

### 2. **Lazy Loading**
```
User scroll → Deteksi 500px dari bawah → Load 5 postingan berikutnya
```

### 3. **Manual Load (Beranda)**
```
User klik "Muat Lainnya" → Request 5 postingan → Append ke container
```

## File yang Dimodifikasi

1. `/src/script/kelas-posting-stable.js` - Core optimization
2. `/src/logic/get-postingan.php` - Backend limit  
3. `/src/logic/postingan-logic.php` - Method update
4. `/src/logic/dashboard-logic.php` - Pagination support
5. `/src/logic/get-beranda-posts.php` - New endpoint
6. `/src/front/beranda-user.php` - Lazy loading UI
7. `/src/front/kelas-guru.php` - Sudah optimal
8. `/src/front/kelas-user.php` - Sudah optimal

## Testing

### Test Cases yang Direkomendasikan:
1. **Load Test**: Cek loading time halaman dengan banyak postingan
2. **Scroll Test**: Test smooth scrolling dan auto-load
3. **Network Test**: Monitor network requests dan data transfer
4. **Mobile Test**: Pastikan responsive di berbagai device
5. **Edge Cases**: Test dengan 0 postingan, koneksi lambat, dll

### Metrics yang Dipantau:
- Time to First Byte (TTFB)
- First Contentful Paint (FCP) 
- Network requests count
- Memory usage
- User scroll behavior

## Konfigurasi

Jika ingin mengubah jumlah postingan per batch, edit di:
- `kelas-posting-stable.js` → `this.limit = 5`
- `get-postingan.php` → `$limit = intval($_GET['limit'] ?? 5)`
- `get-beranda-posts.php` → `$limit = intval($_GET['limit'] ?? 5)`
