# INSTRUKSI INSTALASI AUTO SAVE FEATURE

## 📋 Ringkasan Fitur
Fitur auto-save untuk ujian online yang akan:
- ✅ Menyimpan jawaban siswa otomatis setiap klik/ketik
- ✅ Menampilkan indikator visual (centang hijau/merah) pada box soal
- ✅ Mengatasi masalah session dengan direct database save
- ✅ Aman dari refresh browser

## 📁 File yang Telah Dibuat/Dimodifikasi

### File Baru:
1. `src/logic/auto-save-logic.php` - Core logic untuk auto save
2. `src/logic/auto-save-api.php` - API endpoints untuk auto save
3. `src/script/auto-save-manager.js` - JavaScript class untuk manage auto save
4. `test-auto-save.html` - File test untuk development
5. `setup-auto-save-test.php` - File test setup dan connection
6. `AUTO_SAVE_DOCUMENTATION.md` - Dokumentasi lengkap

### File yang Dimodifikasi:
1. `src/front/kerjakan-ujian.php` - Menambahkan script auto-save-manager
2. `src/script/kerjakan-ujian.js` - Integrasi dengan AutoSaveManager
3. `src/css/kerjakan-soal.css` - Styling untuk indikator baru

## 🚀 Langkah Instalasi

### 1. Verifikasi File Structure
Pastikan semua file sudah ada di lokasi yang tepat:

```bash
# Check dari root directory lms
ls -la src/logic/auto-save-*
ls -la src/script/auto-save-manager.js
ls -la AUTO_SAVE_DOCUMENTATION.md
```

### 2. Test Database Connection
```bash
# Buka di browser
http://localhost/lms/setup-auto-save-test.php
```

Pastikan muncul:
- ✅ Auto Save Logic berhasil diinisialisasi
- ✅ Database connection OK

### 3. Test API Endpoint
Di halaman setup test, klik:
- "Test Auto Save API" - harus return JSON response
- "Test AutoSaveManager Class" - harus berhasil create instance

### 4. Test Integrasi dengan Ujian Real

1. **Login sebagai siswa**
2. **Mulai ujian yang ada**
3. **Test functionality:**
   - Klik jawaban pilihan ganda → harus auto save
   - Ketik di textarea → harus auto save setelah 1 detik
   - Refresh browser → jawaban harus tetap ada
   - Check indikator di question map → harus ada centang hijau

## 🔧 Troubleshooting

### Problem 1: Auto Save tidak berfungsi
```bash
# Check PHP error log
tail -f /var/log/apache2/error.log

# Check browser console untuk JavaScript errors
# F12 → Console tab
```

### Problem 2: Database connection error
```bash
# Pastikan database server running
sudo systemctl status mysql

# Check koneksi database di src/logic/koneksi.php
```

### Problem 3: Session tidak valid
```bash
# Pastikan session_start() ada di kerjakan-ujian.php
# Check di browser: inspect element → Application → Cookies
```

### Problem 4: JavaScript not loading
```html
<!-- Pastikan script dimuat dalam urutan yang benar -->
<script src="../script/auto-save-manager.js"></script>
<script src="../script/kerjakan-ujian.js"></script>
```

## 🎯 Verifikasi Fungsionalitas

### Checklist Testing:
- [ ] Login sebagai siswa berhasil
- [ ] Bisa masuk ke halaman ujian
- [ ] Klik radio button → auto save (check console log)
- [ ] Ketik di textarea → auto save setelah 1 detik
- [ ] Question map menampilkan indikator yang benar:
  - Kuning dengan ⟳ saat saving
  - Hijau dengan ✓ saat saved
  - Merah dengan ⚠ saat error
- [ ] Global status indicator update yang benar
- [ ] Refresh browser → jawaban tetap ada
- [ ] Finish ujian berfungsi normal

### Test Visual Indicators:

1. **Question Map Box:**
   ```css
   .q-btn.saved    /* Hijau dengan ✓ */
   .q-btn.saving   /* Kuning dengan ⟳ */
   .q-btn.error    /* Merah dengan ⚠ */
   ```

2. **Global Status:**
   ```html
   <div id="save-status">Tersimpan</div>          <!-- Hijau -->
   <div id="save-status-unsaved">Belum Tersimpan</div> <!-- Kuning -->
   <div id="save-status-error">Gagal Disimpan</div>    <!-- Merah -->
   ```

## 📊 Monitoring

### Log Files untuk Monitor:
```bash
# PHP Error Log
tail -f /var/log/apache2/error.log | grep "AutoSave"

# JavaScript Console Log
# Browser → F12 → Console
# Cari log: "Auto-saved answer for soal X"
```

### Database Query untuk Check:
```sql
-- Check jawaban yang tersimpan
SELECT js.*, s.nomorSoal, s.pertanyaan 
FROM jawaban_siswa js 
JOIN soal s ON js.soal_id = s.id 
WHERE js.ujian_siswa_id = 1 
ORDER BY js.waktuDijawab DESC;

-- Check status ujian siswa
SELECT * FROM ujian_siswa WHERE id = 1;
```

## 🔒 Security Checklist

- [ ] Validasi session di auto-save-api.php
- [ ] Validasi ownership ujian_siswa_id
- [ ] Validasi soal_id milik ujian yang sedang dikerjakan
- [ ] Validasi waktu ujian masih berlaku
- [ ] SQL injection protection dengan prepared statements
- [ ] CSRF protection dengan session validation

## 📞 Support

### Error yang Sering Terjadi:

1. **"Unauthorized" response**
   - Solution: Check session, pastikan login sebagai siswa

2. **"Ujian tidak valid"**
   - Solution: Check ujian_siswa_id, pastikan milik user yang login

3. **"Waktu ujian sudah habis"**
   - Solution: Check waktu ujian di database, extend jika perlu untuk testing

4. **JavaScript errors**
   - Solution: Check browser compatibility, pastikan script loading order

### Quick Debug Commands:
```php
// Di auto-save-api.php, tambahkan untuk debug
error_log("AutoSave Debug: " . print_r($_POST, true));
error_log("Session User: " . print_r($_SESSION['user'], true));
```

```javascript
// Di browser console
window.autoSaveManager.getQuestionStatus('1');
console.log(window.examData);
```

---

## 🎉 Selesai!

Jika semua checklist ✅, maka fitur auto-save sudah berfungsi dengan sempurna. Siswa sekarang bisa:

1. ✅ **Klik jawaban → langsung tersimpan ke database**
2. ✅ **Lihat indikator hijau di box soal yang sudah dijawab**
3. ✅ **Lihat indikator merah jika gagal simpan**
4. ✅ **Aman dari refresh browser**
5. ✅ **Session protection dengan database trigger**

**Happy Testing! 🚀**
