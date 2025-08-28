# Role-Based Sidebar Documentation

## Overview
Sidebar telah diperbarui untuk mendukung 3 role yang berbeda: **Admin**, **Guru**, dan **Siswa**. Setiap role memiliki navigasi dan tampilan yang disesuaikan dengan kebutuhan mereka.

## Fitur Baru

### 1. Role-Based Navigation
- **Admin**: Dashboard, Manajemen User, Manajemen Kelas, Manajemen Ujian, Laporan, Pengaturan Sistem
- **Guru**: Beranda, Kelas Saya, Buat Soal, Ujian, Buat Ujian, Pingo AI
- **Siswa**: Beranda, Kelas Saya, Ujian, Pingo AI

### 2. Enhanced Profile Section
- Menampilkan nama dan email user dari session
- Avatar dengan warna berdasarkan role:
  - Admin: Merah (Shield icon)
  - Guru: Biru (School icon)
  - Siswa: Hijau (User icon)
- Role badge dengan warna yang sesuai
- Dropdown menu yang lebih informatif

### 3. Security Improvements
- Session check di setiap halaman
- Role-based access control
- Redirect ke login jika tidak memiliki akses

## File yang Dimodifikasi

### Core Files
1. `/src/component/sidebar.php` - Sidebar utama dengan logic role-based
2. `/assets/head.php` - Menambahkan CSS role-based

### Frontend Pages
1. `/src/front/admin-dashboard.php` - Dashboard khusus admin (baru)
2. `/src/front/beranda-guru.php` - Sudah ada session check
3. `/src/front/beranda-user.php` - Sudah ada session check
4. `/src/front/kelas-guru.php` - Ditambahkan session check
5. `/src/front/kelas-user.php` - Ditambahkan session check
6. `/src/front/ujian-guru.php` - Ditambahkan session check
7. `/src/front/ujian-user.php` - Ditambahkan session check
8. `/src/front/buat-soal-guru.php` - Ditambahkan session check & page identifier
9. `/src/front/buat-ujian-guru.php` - Ditambahkan session check & page identifier
10. `/src/front/pingo.php` - Ditambahkan session check
11. `/src/front/settings.php` - Ditambahkan session check

### Assets
1. `/src/css/sidebar-roles.css` - Styling khusus untuk role-based sidebar
2. `/src/script/sidebar-roles.js` - JavaScript enhancements untuk sidebar

## Cara Penggunaan

### Untuk Developer
1. Setiap halaman sudah memiliki session check otomatis
2. Variable `$userRole`, `$userName`, dan `$userEmail` tersedia di sidebar
3. Navigasi akan muncul sesuai dengan role user yang login

### Role Assignment
Role diatur saat registrasi dan login melalui:
- `/src/logic/back-register.php`
- `/src/logic/login.php`

### Color Scheme
- **Admin**: Red (#dc2626)
- **Guru**: Blue (#2563eb)  
- **Siswa**: Green (#16a34a)

## Functions Tersedia

### PHP Functions
- `getRoleDisplayName($role)` - Mengkonversi role ke display name
- `getNavigationItems($role)` - Mendapatkan menu navigasi berdasarkan role

### JavaScript Functions
- `initRoleBasedFeatures()` - Inisialisasi fitur role-based
- `toggleProfileDropdown()` - Toggle dropdown profile
- `updateProfileInfo(userData)` - Update info profile secara dinamis

## Security Notes
- Semua halaman memiliki role-based access control
- Session validation di setiap halaman
- Redirect otomatis jika akses tidak diizinkan
- Logout modal untuk keamanan tambahan

## Future Enhancements
1. Avatar upload untuk user
2. Role permissions yang lebih granular
3. Audit log untuk admin actions
4. Dark mode support untuk sidebar
5. Notifikasi real-time berdasarkan role

## Troubleshooting
- Pastikan session PHP aktif
- Pastikan file CSS dan JS ter-load dengan benar
- Check browser console untuk error JavaScript
- Verifikasi path file CSS/JS sesuai struktur folder
