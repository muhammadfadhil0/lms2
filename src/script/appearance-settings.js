// Appearance Settings JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeAppearanceSettings();
});

function initializeAppearanceSettings() {
    initializeFontSizeSelection();
    loadSavedAppearanceSettings();
    setupSaveAppearanceSettings();
}

function initializeFontSizeSelection() {
    const fontSizeRadios = document.querySelectorAll('input[name="fontSize"]');
    
    fontSizeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const fontSize = this.value;
                applyFontSize(fontSize);
                sessionStorage.setItem('previewFontSize', fontSize);
            }
        });
    });
}

function loadSavedAppearanceSettings() {
    // Load saved font size
    const savedFontSize = localStorage.getItem('userFontSize') || '100';
    const fontSizeRadio = document.querySelector(`input[name="fontSize"][value="${savedFontSize}"]`);
    if (fontSizeRadio) {
        fontSizeRadio.checked = true;
    }
    applyFontSize(savedFontSize);
}

function setupSaveAppearanceSettings() {
    const saveButton = document.getElementById('save-appearance');
    
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            saveAppearanceSettings();
        });
    }
    
    // Reset button (the first button in the appearance tab that's not the save button)
    const resetButton = document.querySelector('#appearance-tab button[type="button"]:not(#save-appearance)');
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            resetAppearanceSettings();
        });
    }
}

function saveAppearanceSettings() {
    const selectedFontSize = document.querySelector('input[name="fontSize"]:checked');
    const fontSize = selectedFontSize ? selectedFontSize.value : '100';
    localStorage.setItem('userFontSize', fontSize);
    applyFontSize(fontSize);
    showSuccessMessage('Pengaturan tampilan berhasil disimpan!');
    sessionStorage.removeItem('previewFontSize');
}

function resetAppearanceSettings() {
    const defaultFontSize = '100';
    document.querySelectorAll('input[name="fontSize"]').forEach(radio => radio.checked = false);
    const defaultFontSizeRadio = document.querySelector(`input[name="fontSize"][value="${defaultFontSize}"]`);
    if (defaultFontSizeRadio) defaultFontSizeRadio.checked = true;
    localStorage.removeItem('userFontSize');
    applyFontSize(defaultFontSize);
    showSuccessMessage('Pengaturan tampilan telah direset ke default!');
}

function applyFontSize(fontSize) {
    const root = document.documentElement;
    const fontSizePercentage = fontSize / 100;
    root.style.fontSize = `${fontSizePercentage}rem`;
}

function showSuccessMessage(message) {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full';
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="ti ti-check mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.classList.remove('translate-x-full'), 100);
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => { if (notification.parentNode) notification.parentNode.removeChild(notification); }, 300);
    }, 3000);
}