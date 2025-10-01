# Sistem Role Free dan Pro - LMS

## Deskripsi
Implementasi sistem berlangganan dengan role `free` dan `pro` untuk membatasi fitur berdasarkan paket yang dimiliki guru.

## Fitur yang Diimplementasikan

### 1. Struktur Database
- **Kolom baru di tabel `users`:**
  - `role_type`: ENUM('free', 'pro') - Jenis role guru
  - `subscription_plan`: VARCHAR(50) - Nama paket berlangganan 
  - `subscription_expires_at`: DATETIME - Waktu kedaluwarsa langganan
  - `max_classes`: INT - Batas maksimal kelas yang bisa dibuat

- **Tabel baru `subscription_plans`:**
  - Menyimpan detail paket berlangganan (free, pro)
  - Fitur-fitur yang tersedia untuk setiap paket
  - Harga dan durasi langganan

### 2. Pembatasan Kelas untuk Role Free
- **Guru Free**: Maksimal 5 kelas
- **Guru Pro**: Unlimited kelas

### 3. Interface dan UX
- **Modal Upgrade Pro**: Muncul ketika guru free mencapai limit 5 kelas
- **Tombol Dinamis**: 
  - Guru free yang masih bisa buat kelas: "Tambah Kelas" (hijau)
  - Guru free yang sudah limit: "Upgrade Pro" (orange dengan ikon crown)

## File yang Dimodifikasi/Ditambahkan

### Database
- `database/add-subscription-system.sql` - Migration script

### Backend Logic
- `src/logic/kelas-logic.php`:
  - `canCreateClass($guru_id)` - Cek apakah guru bisa buat kelas
  - `getGuruSubscriptionInfo($guru_id)` - Get info langganan guru

- `src/logic/create-kelas.php`:
  - Pengecekan limit sebelum membuat kelas
  - Response error dengan `limit_reached` flag

### Frontend Components
- `src/component/modal-upgrade-to-pro.php` - Modal upgrade ke pro
- `src/front/beranda-guru.php`:
  - Logik cek limit kelas
  - Tombol kondisional berdasarkan status guru
  - Include modal upgrade pro

### JavaScript
- `src/script/upgrade-to-pro-modal.js` - Handler untuk modal upgrade
- `src/script/kelas-management.js`:
  - Handle response `limit_reached`
  - Auto show modal upgrade jika limit tercapai

## Cara Kerja

1. **Saat Load Beranda Guru:**
   ```php
   $classLimitInfo = $kelasLogic->canCreateClass($guru_id);
   $canCreateClass = $classLimitInfo['success'] && $classLimitInfo['can_create'];
   ```

2. **Tombol Tambah Kelas:**
   - Jika `$canCreateClass = true`: Tampilkan tombol normal "Tambah Kelas"
   - Jika `$canCreateClass = false`: Tampilkan tombol "Upgrade Pro"

3. **Saat Submit Form Buat Kelas:**
   - Backend cek limit dulu dengan `canCreateClass()`
   - Jika limit tercapai: Return response `limit_reached = true`
   - Frontend detect `limit_reached` → tutup modal buat kelas → buka modal upgrade

4. **Modal Upgrade Pro:**
   - Tampilkan fitur-fitur yang didapat dengan upgrade
   - Simulasi proses upgrade (bisa diintegrasikan dengan payment gateway)
   - Success notification setelah "upgrade"

## Testing

### Cara Test Limit 5 Kelas:
1. Login sebagai guru
2. Buat 5 kelas sampai limit tercapai
3. Coba buat kelas ke-6 → Modal upgrade pro akan muncul
4. Tombol "Tambah Kelas" berubah jadi "Upgrade Pro"

### Cara Update Guru jadi Pro:
```sql
UPDATE users 
SET role_type = 'pro', 
    subscription_plan = 'pro', 
    max_classes = NULL 
WHERE id = [GURU_ID];
```

## Rencana Pengembangan Selanjutnya
1. **Payment Gateway Integration** (Midtrans, dll)
2. **Auto-expiry subscription** dengan cron job
3. **Analytics fitur pro** (advanced reporting)
4. **Fitur AI premium** untuk guru pro
5. **Backup otomatis** khusus guru pro

## Catatan Penting
- Sistem ini adalah simulasi untuk pembatasan
- Untuk production, perlu integrate dengan payment gateway
- Database migration otomatis set semua guru existing jadi role `free`
- Siswa tetap role `free` dengan `max_classes = 0` (tidak membuat kelas)