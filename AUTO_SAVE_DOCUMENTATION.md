# Auto Save Feature Documentation

## Overview
Fitur auto-save untuk ujian online yang memungkinkan penyimpanan jawaban siswa secara otomatis ke database setiap kali siswa mengklik jawaban, dengan indikator visual status penyimpanan.

## Features

### 1. Real-time Auto Save
- **Trigger**: Setiap kali siswa mengklik jawaban (radio button atau mengetik di textarea)
- **Delay**: 1 detik untuk textarea (debounced), langsung untuk radio button
- **Retry**: 3x retry dengan exponential backoff jika gagal
- **Validation**: Validasi keamanan penuh di backend

### 2. Visual Indicators

#### Question Map Indicators
- **Hijau dengan ✓**: Jawaban tersimpan dengan sukses
- **Kuning dengan ⟳**: Sedang menyimpan (loading)
- **Merah dengan ⚠**: Gagal menyimpan
- **Biru**: Soal aktif/sedang dikerjakan
- **Abu-abu**: Belum dijawab

#### Global Status Indicators
- **"Tersimpan"** (hijau): Semua jawaban tersimpan
- **"Belum Tersimpan"** (kuning): Ada jawaban yang belum tersimpan
- **"Gagal Disimpan"** (merah): Ada jawaban yang gagal disimpan

### 3. Session Protection
- Jawaban langsung masuk database, tidak bergantung pada session
- Aman dari refresh browser
- Recovery otomatis jika koneksi terputus

## File Structure

### Backend (PHP)
```
src/logic/
├── auto-save-logic.php     # Core auto-save logic
└── auto-save-api.php       # API endpoints
```

### Frontend (JavaScript)
```
src/script/
├── auto-save-manager.js    # Auto-save manager class
└── kerjakan-ujian.js      # Main exam interface (updated)
```

### Styling (CSS)
```
src/css/
└── kerjakan-soal.css      # Updated with new indicators
```

## API Endpoints

### 1. Auto Save Answer
**URL**: `src/logic/auto-save-api.php`
**Method**: POST
**Action**: `auto_save`

**Parameters**:
- `ujian_siswa_id`: ID ujian siswa
- `soal_id`: ID soal
- `jawaban`: Jawaban siswa

**Response**:
```json
{
    "success": true,
    "message": "Jawaban berhasil disimpan",
    "auto_save": true,
    "timestamp": "2025-09-03 14:30:15"
}
```

### 2. Get Status
**URL**: `src/logic/auto-save-api.php`
**Method**: POST
**Action**: `get_status`

**Parameters**:
- `ujian_siswa_id`: ID ujian siswa

**Response**:
```json
{
    "success": true,
    "data": {
        "1": {
            "nomor_soal": 1,
            "is_answered": true,
            "jawaban": "A",
            "waktu_dijawab": "2025-09-03 14:30:15",
            "tipe_soal": "pilihan_ganda"
        }
    }
}
```

### 3. Delete Answer
**URL**: `src/logic/auto-save-api.php`
**Method**: POST
**Action**: `delete_answer`

**Parameters**:
- `ujian_siswa_id`: ID ujian siswa
- `soal_id`: ID soal

## Security Features

### 1. Authentication
- Session validation
- Role checking (hanya siswa)
- User ID validation

### 2. Authorization
- Validasi kepemilikan ujian_siswa_id
- Validasi soal_id milik ujian yang sedang dikerjakan
- Validasi status ujian (harus 'sedang_mengerjakan')

### 3. Time Validation
- Validasi waktu ujian masih berlaku
- Validasi durasi ujian belum habis

## Usage

### 1. Include Scripts
```html
<script src="../script/auto-save-manager.js"></script>
<script src="../script/kerjakan-ujian.js"></script>
```

### 2. Initialize Auto Save Manager
```javascript
if (window.examData && window.examData.isStarted) {
    autoSaveManager = new window.AutoSaveManager(window.examData.ujianSiswaId);
}
```

### 3. Listen to Status Changes
```javascript
document.addEventListener('questionStatusChanged', (e) => {
    const { soalId, status } = e.detail;
    console.log(`Question ${soalId} status: ${status}`);
});
```

## Configuration

### Auto Save Settings
```javascript
// Dalam AutoSaveManager constructor
this.saveDelay = 1000;        // 1 detik delay untuk textarea
this.maxRetries = 3;          // Maksimal 3x retry
```

### CSS Customization
```css
/* Custom colors untuk status indicators */
.q-btn.saved { background: linear-gradient(155deg, #22c55e, #16a34a); }
.q-btn.saving { background: linear-gradient(155deg, #f59e0b, #d97706); }
.q-btn.error { background: linear-gradient(155deg, #ef4444, #dc2626); }
```

## Error Handling

### 1. Network Errors
- Retry dengan exponential backoff
- Visual indicator error
- Queue untuk retry otomatis

### 2. Server Errors
- HTTP status code handling
- JSON parsing error handling
- Fallback ke method lama jika perlu

### 3. Validation Errors
- Parameter validation
- Security validation
- User feedback yang informatif

## Testing

### Manual Testing
1. Buka `test-auto-save.html` untuk testing API
2. Test berbagai skenario:
   - Pilihan ganda
   - Essay
   - Network error simulation
   - Multiple quick saves

### Integration Testing
1. Test dengan ujian real
2. Test dengan multiple users
3. Test dengan jaringan lambat

## Troubleshooting

### Common Issues

1. **Auto save tidak berfungsi**
   - Pastikan `auto-save-manager.js` dimuat
   - Check console untuk error JavaScript
   - Pastikan session valid

2. **Indikator tidak muncul**
   - Check CSS loading
   - Pastikan element HTML ada
   - Check event listener

3. **Jawaban tidak tersimpan**
   - Check API response di Network tab
   - Pastikan database connection
   - Check PHP error log

### Debug Mode
```javascript
// Enable debug logging
window.autoSaveDebug = true;

// Check status
console.log(autoSaveManager.getQuestionStatus('1'));
```

## Performance Considerations

### 1. Database Optimization
- Index pada `ujian_siswa_id` dan `soal_id`
- Efficient query untuk validation
- Proper connection handling

### 2. Frontend Optimization
- Debounced saves untuk textarea
- Queue management untuk multiple saves
- Efficient DOM updates

### 3. Network Optimization
- Minimal payload size
- Proper error retry logic
- Connection pooling di server

## Migration Guide

### From Old System
1. Backup database jawaban_siswa
2. Deploy new files
3. Test dengan data existing
4. Monitor untuk issues

### Database Changes
Tidak ada perubahan database required - menggunakan tabel existing `jawaban_siswa`.

## Maintenance

### 1. Monitoring
- Monitor error rates di PHP log
- Monitor save success rates
- Monitor network performance

### 2. Updates
- Regular testing dengan browser updates
- Performance monitoring
- Security updates

### 3. Backup Strategy
- Regular backup tabel jawaban_siswa
- Monitor storage usage
- Archive old exam data

## Support

### 1. Error Logs
- Check `/var/log/apache2/error.log` untuk PHP errors
- Check browser console untuk JavaScript errors
- Check Network tab untuk API errors

### 2. User Support
- Provide clear error messages
- Fallback untuk basic functionality
- User guide untuk troubleshooting

---

**Last Updated**: September 3, 2025
**Version**: 1.0
**Author**: Auto Save Development Team
