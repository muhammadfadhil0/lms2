// Settings JavaScript Handler
class SettingsManager {
    constructor() {
        this.apiUrl = '../logic/settings-api.php';
        this.currentTab = 'profile';
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupTabNavigation();
        this.setupPhotoUpload();
        
        // Load profile data immediately when page loads
        this.loadProfileData();
        
        // Add real-time validation
        this.setupRealTimeValidation();
        
        // Add keyboard shortcuts
        this.setupKeyboardShortcuts();
    }

    setupEventListeners() {
        // Form submit handlers
        const profileForm = document.querySelector('#profile-form');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => this.handleProfileUpdate(e));
        }

        const passwordForm = document.querySelector('#password-form');
        if (passwordForm) {
            passwordForm.addEventListener('submit', (e) => this.handlePasswordChange(e));
        }

        // Photo upload handler
        const photoInput = document.querySelector('#profile-photo');
        if (photoInput) {
            photoInput.addEventListener('change', (e) => this.handlePhotoUpload(e));
        }
    }

    setupTabNavigation() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');
                
                // Remove active class from all buttons and contents
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.add('hidden'));
                
                // Add active class to clicked button and show corresponding content
                button.classList.add('active');
                document.querySelector(`#${tabId}-tab`).classList.remove('hidden');
                
                this.currentTab = tabId;
                
                // Load security stats if switching to security tab
                if (tabId === 'security') {
                    this.loadSecurityStats();
                }
            });
        });
    }

    setupPhotoUpload() {
        const photoInput = document.querySelector('#profile-photo');
        const preview = document.querySelector('#profile-preview');
        
        if (photoInput && preview) {
            photoInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    // Preview image
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                    
                    // Auto upload
                    this.uploadPhoto(file);
                }
            });
        }
    }

    async loadProfileData() {
        try {
            this.showLoading(true);
            
            const response = await fetch(`${this.apiUrl}?action=get_profile`);
            const result = await response.json();
            
            if (result.success) {
                this.populateProfileForm(result.data);
                console.log('Profile data loaded:', result.data);
                
                // Show success indicator briefly
                this.showDataLoadedIndicator();
            } else {
                this.showAlert('error', result.message || 'Gagal memuat data profil');
                console.error('Failed to load profile:', result);
            }
        } catch (error) {
            console.error('Error loading profile:', error);
            this.showAlert('error', 'Gagal memuat data profil. Periksa koneksi internet.');
        } finally {
            this.showLoading(false);
        }
    }

    showDataLoadedIndicator() {
        // Tampilkan indikator bahwa data sudah dimuat
        const indicator = document.createElement('div');
        indicator.className = 'data-loaded-indicator fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-4 py-2 rounded-lg text-sm z-50';
        indicator.textContent = '✓ Data profil dimuat';
        document.body.appendChild(indicator);
        
        // Auto remove after 2 seconds
        setTimeout(() => {
            if (indicator.parentNode) {
                indicator.remove();
            }
        }, 2000);
    }

    populateProfileForm(data) {
        // Populate form fields
        const fields = ['namaLengkap', 'username', 'email', 'bio', 'nomorTelpon', 'tanggalLahir'];
        
        fields.forEach(field => {
            const input = document.querySelector(`input[name="${field}"], textarea[name="${field}"]`);
            if (input) {
                // Set value bahkan jika data[field] null atau undefined
                input.value = data[field] || '';
                
                // Add visual feedback untuk field yang sudah terisi
                if (data[field] && data[field].trim() !== '') {
                    input.classList.add('has-value');
                }
            }
        });

        // Update profile photo
        const profilePreview = document.querySelector('#profile-preview');
        if (profilePreview) {
            if (data.fotoProfil_url && data.fotoProfil_url !== '') {
                profilePreview.src = data.fotoProfil_url;
            } else {
                // Fallback ke avatar default jika tidak ada foto
                const defaultAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(data.namaLengkap || 'User')}&background=ff6347&color=fff&size=96`;
                profilePreview.src = defaultAvatar;
            }
        }
        
        // Update profile info di header jika ada
        this.updateProfileInfo(data);
    }

    updateProfileInfo(data) {
        // Update informasi profil di bagian lain halaman jika diperlukan
        // Misalnya di header atau sidebar
        const profileElements = {
            '.profile-name': data.namaLengkap,
            '.profile-username': data.username,
            '.profile-email': data.email
        };
        
        Object.entries(profileElements).forEach(([selector, value]) => {
            const element = document.querySelector(selector);
            if (element && value) {
                element.textContent = value;
            }
        });
    }

    async handleProfileUpdate(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'update_profile');
        
        try {
            this.showLoading(true);
            form.classList.add('form-loading');
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('success', result.message);
                // Reload profile data to reflect changes and update session
                setTimeout(() => {
                    this.loadProfileData();
                    // Add visual feedback untuk fields yang diupdate
                    this.highlightUpdatedFields(form);
                }, 1000);
            } else {
                this.showAlert('error', result.message);
            }
        } catch (error) {
            console.error('Error updating profile:', error);
            this.showAlert('error', 'Gagal memperbarui profil. Periksa koneksi internet.');
        } finally {
            this.showLoading(false);
            form.classList.remove('form-loading');
        }
    }

    highlightUpdatedFields(form) {
        // Berikan highlight pada field yang baru diupdate
        const inputs = form.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            if (input.value && input.value.trim() !== '') {
                input.classList.add('has-value');
                // Add temporary glow effect
                input.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
                setTimeout(() => {
                    input.style.boxShadow = '';
                }, 2000);
            }
        });
    }

    async uploadPhoto(file) {
        const formData = new FormData();
        formData.append('profile_photo', file);
        formData.append('action', 'upload_photo');
        
        try {
            this.showLoading(true);
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('success', result.message);
            } else {
                this.showAlert('error', result.message);
                // Reset preview to original
                this.loadProfileData();
            }
        } catch (error) {
            console.error('Error uploading photo:', error);
            this.showAlert('error', 'Gagal mengupload foto');
            this.loadProfileData();
        } finally {
            this.showLoading(false);
        }
    }

    async handlePasswordChange(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'change_password');
        
        // Client-side validation
        const passwordBaru = formData.get('password_baru');
        const konfirmasiPassword = formData.get('konfirmasi_password');
        
        if (passwordBaru !== konfirmasiPassword) {
            this.showAlert('error', 'Password baru dan konfirmasi tidak cocok');
            return;
        }
        
        try {
            this.showLoading(true);
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('success', result.message);
                form.reset();
            } else {
                this.showAlert('error', result.message);
            }
        } catch (error) {
            console.error('Error changing password:', error);
            this.showAlert('error', 'Gagal mengubah password');
        } finally {
            this.showLoading(false);
        }
    }

    async loadSecurityStats() {
        try {
            const response = await fetch(`${this.apiUrl}?action=get_security_stats`);
            const result = await response.json();
            
            if (result.success) {
                this.displaySecurityStats(result.data);
            }
        } catch (error) {
            console.error('Error loading security stats:', error);
        }
    }

    displaySecurityStats(stats) {
        // Update security information in the UI
        // This can be expanded based on your UI design
        console.log('Security stats:', stats);
    }

    showAlert(type, message) {
        // Remove existing alerts
        const existingAlert = document.querySelector('.alert-message');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert-message fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        }`;
        alert.textContent = message;
        
        document.body.appendChild(alert);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }

    showLoading(show) {
        // Create or remove loading overlay
        let loadingOverlay = document.querySelector('.loading-overlay');
        
        if (show) {
            if (!loadingOverlay) {
                loadingOverlay = document.createElement('div');
                loadingOverlay.className = 'loading-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                loadingOverlay.innerHTML = `
                    <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-orange"></div>
                        <span class="text-gray-700">Memproses...</span>
                    </div>
                `;
                document.body.appendChild(loadingOverlay);
            }
        } else {
            if (loadingOverlay) {
                loadingOverlay.remove();
            }
        }
    }

    setupRealTimeValidation() {
        // Real-time validation untuk username dan email
        const usernameInput = document.querySelector('input[name="username"]');
        const emailInput = document.querySelector('input[name="email"]');
        
        // Visual feedback saat user mengetik
        const allInputs = document.querySelectorAll('input, textarea');
        allInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                if (e.target.value.trim() !== '') {
                    e.target.classList.add('has-value');
                } else {
                    e.target.classList.remove('has-value');
                }
            });
        });
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+S untuk save profile
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const profileForm = document.querySelector('#profile-form');
                if (profileForm) {
                    profileForm.dispatchEvent(new Event('submit'));
                }
            }
            
            // Tab switching dengan keyboard
            if (e.altKey && e.key >= '1' && e.key <= '2') {
                e.preventDefault();
                const tabIndex = parseInt(e.key) - 1;
                const tabs = ['profile', 'security'];
                if (tabs[tabIndex]) {
                    const tabBtn = document.querySelector(`[data-tab="${tabs[tabIndex]}"]`);
                    if (tabBtn) tabBtn.click();
                }
            }
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SettingsManager();
});

// Add CSS styles for better UX
const style = document.createElement('style');
style.textContent = `
    .tab-btn {
        border-bottom-color: transparent;
        color: #6b7280;
        transition: all 0.2s ease;
    }
    
    .tab-btn.active {
        border-bottom-color: #ff6347;
        color: #ff6347;
    }
    
    .tab-btn:hover {
        color: #ff6347;
    }
    
    .loading-overlay {
        backdrop-filter: blur(2px);
    }
    
    .alert-message {
        animation: slideIn 0.3s ease-out;
    }
    
    .data-loaded-indicator {
        animation: slideDown 0.3s ease-out;
    }
    
    /* Visual feedback untuk field yang sudah terisi */
    .has-value {
        border-color: #10b981;
        background-color: #f0fdf4;
    }
    
    .has-value:focus {
        border-color: #059669;
        ring-color: #10b981;
    }
    
    /* Loading state untuk form */
    .form-loading {
        opacity: 0.6;
        pointer-events: none;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideDown {
        from {
            transform: translate(-50%, -100%);
            opacity: 0;
        }
        to {
            transform: translate(-50%, 0);
            opacity: 1;
        }
    }
    
    .orange {
        background-color: #ff6347;
    }
    
    .orange-tipis {
        background-color: #fff5f3;
    }
    
    /* Profile photo hover effect */
    .profile-photo-container {
        position: relative;
        transition: all 0.3s ease;
    }
    
    .profile-photo-container:hover {
        transform: scale(1.05);
    }
    
    .profile-photo-container:hover .photo-overlay {
        opacity: 1;
    }
    
    .photo-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        cursor: pointer;
    }
`;
document.head.appendChild(style);
