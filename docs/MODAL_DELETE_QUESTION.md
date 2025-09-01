# Modal Delete Question Implementation

## Files Created
1. `src/component/modal-delete-question.php` - Modal component
2. `src/script/modal-delete-question.js` - Modal JavaScript handler

## Integration Steps

### 1. Include Modal Component in Your Page
Add this line in your main page (e.g., `buat-soal.php` or wherever you use the question builder):

```php
<?php include '../src/component/modal-delete-question.php'; ?>
```

### 2. Include JavaScript Files
Make sure to include both JavaScript files in your page:

```html
<!-- Include the modal script before buat-soal.js -->
<script src="../src/script/modal-delete-question.js"></script>
<script src="../src/script/buat-soal.js"></script>
```

### 3. CSS Dependencies
The modal uses Tailwind CSS classes. Make sure Tailwind CSS is loaded in your page.

## Features

### Modal Features
- ✅ Elegant confirmation dialog
- ✅ Loading state with spinner
- ✅ Keyboard navigation (ESC to close)
- ✅ Click outside to close
- ✅ Accessibility attributes
- ✅ Smooth animations
- ✅ Dynamic question title in confirmation text

### JavaScript Features
- ✅ Graceful fallback to `confirm()` if modal is not available
- ✅ Integration with existing question management functions
- ✅ Server-side deletion for saved questions
- ✅ DOM-only deletion for unsaved questions
- ✅ Error handling with user feedback

## Usage

The modal will automatically be used when:
1. Modal component is included in the page
2. Modal JavaScript is loaded
3. User clicks the delete button on any question

If the modal is not available, it will fallback to the original `confirm()` dialog.

## Customization

### Styling
You can customize the modal appearance by modifying the CSS classes in `modal-delete-question.php`.

### Behavior
You can modify the modal behavior by editing the `DeleteQuestionModal` class in `modal-delete-question.js`.

### Messages
The modal automatically shows dynamic messages based on the question being deleted (e.g., "Hapus Soal 1", "Hapus Soal 2", etc.).
