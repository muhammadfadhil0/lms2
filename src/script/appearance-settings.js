// Appearance Settings JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeAppearanceSettings();
});

function initializeAppearanceSettings() {
    // Initialize theme selection
    initializeThemeSelection();
    
    // Initialize font size selection
    initializeFontSizeSelection();
    
    // Load saved settings
    loadSavedAppearanceSettings();
    
    // Save appearance settings
    setupSaveAppearanceSettings();
}

function initializeThemeSelection() {
    const themeOptions = document.querySelectorAll('.theme-option');
    
    themeOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove active class from all options
            themeOptions.forEach(opt => opt.classList.remove('active'));
            
            // Add active class to clicked option
            this.classList.add('active');
            
            // Apply theme immediately for preview
            const theme = this.dataset.theme;
            applyThemePreview(theme);
        });
        
        // Add keyboard support
        option.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
        
        // Make it focusable
        option.setAttribute('tabindex', '0');
    });
}

function initializeFontSizeSelection() {
    const fontSizeRadios = document.querySelectorAll('input[name="fontSize"]');
    
    fontSizeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                // Apply font size immediately for preview
                const fontSize = this.value;
                applyFontSizePreview(fontSize);
            }
        });
    });
}

function applyThemePreview(theme) {
    const body = document.body;
    
    // Remove existing theme classes
    body.classList.remove('theme-light', 'theme-dark', 'theme-system');
    
    // Add new theme class
    body.classList.add(`theme-${theme}`);
    
    // Store in temporary storage for preview
    sessionStorage.setItem('previewTheme', theme);
}

function applyFontSizePreview(fontSize) {
    const root = document.documentElement;
    
    // Calculate the font size percentage
    const fontSizePercentage = fontSize / 100;
    
    // Apply to root element
    root.style.fontSize = `${fontSizePercentage}rem`;
    
    // Store in temporary storage for preview
    sessionStorage.setItem('previewFontSize', fontSize);
}

function loadSavedAppearanceSettings() {
    // Load saved theme
    const savedTheme = localStorage.getItem('userTheme') || 'light';
    const themeOption = document.querySelector(`[data-theme="${savedTheme}"]`);
    if (themeOption) {
        themeOption.classList.add('active');
        applyThemePreview(savedTheme);
    }
    
    // Load saved font size
    const savedFontSize = localStorage.getItem('userFontSize') || '100';
    const fontSizeRadio = document.querySelector(`input[name="fontSize"][value="${savedFontSize}"]`);
    if (fontSizeRadio) {
        fontSizeRadio.checked = true;
        applyFontSizePreview(savedFontSize);
    }
}

function setupSaveAppearanceSettings() {
    const saveButton = document.getElementById('save-appearance');
    
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            saveAppearanceSettings();
        });
    }
    
    // Reset button
    const resetButton = document.querySelector('#appearance-tab button[type="button"]:not(#save-appearance)');
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            resetAppearanceSettings();
        });
    }
}

function saveAppearanceSettings() {
    // Get selected theme
    const selectedTheme = document.querySelector('.theme-option.active');
    const theme = selectedTheme ? selectedTheme.dataset.theme : 'light';
    
    // Get selected font size
    const selectedFontSize = document.querySelector('input[name="fontSize"]:checked');
    const fontSize = selectedFontSize ? selectedFontSize.value : '100';
    
    // Save to localStorage
    localStorage.setItem('userTheme', theme);
    localStorage.setItem('userFontSize', fontSize);
    
    // Apply settings permanently
    applyTheme(theme);
    applyFontSize(fontSize);
    
    // Show success message
    showSuccessMessage('Pengaturan tampilan berhasil disimpan!');
    
    // Clear preview storage
    sessionStorage.removeItem('previewTheme');
    sessionStorage.removeItem('previewFontSize');
}

function resetAppearanceSettings() {
    // Reset to default values
    const defaultTheme = 'light';
    const defaultFontSize = '100';
    
    // Reset theme selection
    document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('active'));
    const defaultThemeOption = document.querySelector(`[data-theme="${defaultTheme}"]`);
    if (defaultThemeOption) {
        defaultThemeOption.classList.add('active');
    }
    
    // Reset font size selection
    document.querySelectorAll('input[name="fontSize"]').forEach(radio => radio.checked = false);
    const defaultFontSizeRadio = document.querySelector(`input[name="fontSize"][value="${defaultFontSize}"]`);
    if (defaultFontSizeRadio) {
        defaultFontSizeRadio.checked = true;
    }
    
    // Apply default settings
    applyThemePreview(defaultTheme);
    applyFontSizePreview(defaultFontSize);
    
    // Clear saved settings
    localStorage.removeItem('userTheme');
    localStorage.removeItem('userFontSize');
    
    showSuccessMessage('Pengaturan tampilan telah direset ke default!');
}

function applyTheme(theme) {
    const body = document.body;
    
    // Remove existing theme classes
    body.classList.remove('theme-light', 'theme-dark', 'theme-system');
    
    // Add new theme class
    body.classList.add(`theme-${theme}`);
    
    // Handle system theme
    if (theme === 'system') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        body.classList.add(prefersDark ? 'theme-dark' : 'theme-light');
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (localStorage.getItem('userTheme') === 'system') {
                body.classList.remove('theme-light', 'theme-dark');
                body.classList.add(e.matches ? 'theme-dark' : 'theme-light');
            }
        });
    }
}

function applyFontSize(fontSize) {
    const root = document.documentElement;
    const fontSizePercentage = fontSize / 100;
    root.style.fontSize = `${fontSizePercentage}rem`;
}

function showSuccessMessage(message) {
    // Create success notification
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full';
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="ti ti-check mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Animate out and remove
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Apply saved settings on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('userTheme');
    const savedFontSize = localStorage.getItem('userFontSize');
    
    if (savedTheme) {
        applyTheme(savedTheme);
    }
    
    if (savedFontSize) {
        applyFontSize(savedFontSize);
    }
});
