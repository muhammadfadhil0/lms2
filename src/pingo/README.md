# Pingo Chat Backend Implementation

## Overview
Backend implementasi untuk Pingo AI Chat menggunakan Groq API dengan format yang sesuai dengan dokumentasi Groq yang Anda berikan.

## Files Created

### Backend Files
1. **`chat-api.php`** - Main API endpoint untuk chat
2. **`chat-handler.php`** - Class untuk handle chat logic dan Groq API calls
3. **`clear-chat.php`** - API endpoint untuk clear chat history
4. **`chat.js`** - Frontend JavaScript untuk handle chat interface
5. **`chat.css`** - Styling untuk chat interface (updated)

### Database
Tabel `pingo_chat_history` akan dibuat otomatis saat pertama kali digunakan dengan struktur:
```sql
CREATE TABLE pingo_chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    role ENUM('user', 'assistant') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_id VARCHAR(255) NOT NULL,
    INDEX idx_user_session (user_id, session_id),
    INDEX idx_timestamp (timestamp)
)
```

## Configuration

### 1. Groq API Key
Edit file `src/pingo/config.php` dan ganti API key:
```php
define('GROQ_API_KEY', 'gsk_your_actual_groq_api_key_here');
```

### 2. Database Configuration
Database sudah dikonfigurasi untuk menggunakan database "lms" yang sudah ada.

## Features

### Chat Features
- ✅ Real-time chat dengan Groq AI
- ✅ Chat history tersimpan per user per hari
- ✅ Session-based chat management
- ✅ Message formatting (bold, italic, code)
- ✅ Typing indicator
- ✅ Error handling
- ✅ Clear chat functionality

### Technical Features
- ✅ Session validation
- ✅ CORS headers
- ✅ JSON responses
- ✅ SQL injection protection
- ✅ Auto-resize textarea
- ✅ Responsive design
- ✅ Keyboard shortcuts (Enter to send)

## API Endpoints

### Chat API (`chat-api.php`)
- **GET**: Retrieve chat history
- **POST**: Send message and get AI response

Request format:
```json
{
    "message": "Halo, bagaimana cara belajar matematika?"
}
```

Response format:
```json
{
    "success": true,
    "message": "AI response here...",
    "user_message": "User message",
    "timestamp": "2025-09-05 10:30:00"
}
```

### Clear Chat API (`clear-chat.php`)
- **POST**: Clear user's chat history

Response format:
```json
{
    "success": true,
    "message": "Chat history cleared"
}
```

## Groq API Implementation

Backend menggunakan format yang persis sesuai dengan dokumentasi Groq:

```php
$data = [
    'messages' => $messages,
    'model' => 'openai/gpt-oss-120b',
    'temperature' => 1,
    'max_completion_tokens' => 8192,
    'top_p' => 1,
    'stream' => false,
    'reasoning_effort' => 'medium',
    'stop' => null
];
```

## Frontend Integration

Chat interface sudah terintegrasi dengan:
- Empty state untuk first-time users
- Chat history loading saat page load
- Auto-scroll ke message terbaru
- Message formatting dan timestamps
- Loading states dan error handling

## Security

- Session validation untuk semua API calls
- SQL prepared statements
- Input sanitization
- XSS protection dengan escape HTML
- CSRF protection via session validation

## Testing

Untuk test functionality:
1. Pastikan Groq API key sudah dikonfigurasi
2. Login ke aplikasi sebagai user
3. Akses halaman Pingo (`src/front/pingo.php`)
4. Kirim message untuk test

## Troubleshooting

### Common Issues:
1. **API Key Error**: Update `GROQ_API_KEY` di `config.php`
2. **Database Error**: Pastikan database "lms" accessible
3. **Session Error**: User harus login terlebih dahulu
4. **CORS Error**: Pastikan server mendukung CORS headers

### Debug Mode:
Check browser console untuk JavaScript errors dan network tab untuk API responses.

## Chat System Architecture

```
Frontend (pingo.php)
    ↓
JavaScript (chat.js)
    ↓
API Endpoint (chat-api.php)
    ↓
Chat Handler (chat-handler.php)
    ↓
Groq API + Database
```

## System Requirements

- PHP 7.4+
- MySQL/MariaDB
- cURL extension
- PDO extension
- Valid Groq API key
- Modern browser dengan JavaScript enabled

## Future Enhancements

Potential improvements:
- [ ] Message attachments support
- [ ] Voice messages
- [ ] Chat export functionality
- [ ] Multiple chat sessions per user
- [ ] Admin chat monitoring
- [ ] Chat analytics
- [ ] Streaming responses
- [ ] Message reactions
- [ ] Chat search functionality
