<!-- Modal Alert Component -->
<!-- Komponen alert yang dapat digunakan di dalam modal untuk notifikasi -->

<!-- Success Alert -->
<div id="modal-alert-success" class="hidden mb-4 p-4 bg-green-50 border border-green-200 rounded-lg" role="alert">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <i class="ti ti-check-circle text-green-600 text-xl"></i>
        </div>
        <div class="ml-3 flex-1">
            <h4 class="text-sm font-medium text-green-800" id="modal-alert-success-title">Berhasil!</h4>
            <p class="text-sm text-green-700 mt-1" id="modal-alert-success-message">Operasi berhasil dilakukan.</p>
        </div>
        <div class="ml-3">
            <button type="button" onclick="hideModalAlert('success')" class="inline-flex bg-green-50 rounded-md p-1.5 text-green-500 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 focus:ring-offset-green-50">
                <span class="sr-only">Tutup</span>
                <i class="ti ti-x text-sm"></i>
            </button>
        </div>
    </div>
</div>

<!-- Error Alert -->
<div id="modal-alert-error" class="hidden mb-4 p-4 bg-red-50 border border-red-200 rounded-lg" role="alert">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <i class="ti ti-alert-circle text-red-600 text-xl"></i>
        </div>
        <div class="ml-3 flex-1">
            <h4 class="text-sm font-medium text-red-800" id="modal-alert-error-title">Terjadi Kesalahan!</h4>
            <p class="text-sm text-red-700 mt-1" id="modal-alert-error-message">Operasi gagal dilakukan.</p>
        </div>
        <div class="ml-3">
            <button type="button" onclick="hideModalAlert('error')" class="inline-flex bg-red-50 rounded-md p-1.5 text-red-500 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus:ring-offset-red-50">
                <span class="sr-only">Tutup</span>
                <i class="ti ti-x text-sm"></i>
            </button>
        </div>
    </div>
</div>

<!-- Warning Alert -->
<div id="modal-alert-warning" class="hidden mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg" role="alert">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <i class="ti ti-alert-triangle text-yellow-600 text-xl"></i>
        </div>
        <div class="ml-3 flex-1">
            <h4 class="text-sm font-medium text-yellow-800" id="modal-alert-warning-title">Perhatian!</h4>
            <p class="text-sm text-yellow-700 mt-1" id="modal-alert-warning-message">Ada hal yang perlu diperhatikan.</p>
        </div>
        <div class="ml-3">
            <button type="button" onclick="hideModalAlert('warning')" class="inline-flex bg-yellow-50 rounded-md p-1.5 text-yellow-500 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:ring-offset-2 focus:ring-offset-yellow-50">
                <span class="sr-only">Tutup</span>
                <i class="ti ti-x text-sm"></i>
            </button>
        </div>
    </div>
</div>

<!-- Info Alert -->
<div id="modal-alert-info" class="hidden mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg" role="alert">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <i class="ti ti-info-circle text-blue-600 text-xl"></i>
        </div>
        <div class="ml-3 flex-1">
            <h4 class="text-sm font-medium text-blue-800" id="modal-alert-info-title">Informasi</h4>
            <p class="text-sm text-blue-700 mt-1" id="modal-alert-info-message">Informasi penting untuk Anda.</p>
        </div>
        <div class="ml-3">
            <button type="button" onclick="hideModalAlert('info')" class="inline-flex bg-blue-50 rounded-md p-1.5 text-blue-500 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:ring-offset-blue-50">
                <span class="sr-only">Tutup</span>
                <i class="ti ti-x text-sm"></i>
            </button>
        </div>
    </div>
</div>

<script>
/**
 * Fungsi untuk menampilkan alert di dalam modal
 * @param {string} type - Tipe alert: 'success', 'error', 'warning', 'info'
 * @param {string} title - Judul alert
 * @param {string} message - Pesan alert
 * @param {number} autoHide - Waktu auto hide dalam milidetik (0 = tidak auto hide)
 */
function showModalAlert(type, title, message, autoHide = 5000) {
    // Sembunyikan semua alert terlebih dahulu
    hideAllModalAlerts();
    
    // Ambil elemen alert yang sesuai
    const alertElement = document.getElementById(`modal-alert-${type}`);
    const titleElement = document.getElementById(`modal-alert-${type}-title`);
    const messageElement = document.getElementById(`modal-alert-${type}-message`);
    
    if (alertElement && titleElement && messageElement) {
        // Set konten
        titleElement.textContent = title;
        messageElement.textContent = message;
        
        // Tampilkan alert
        alertElement.classList.remove('hidden');
        
        // Scroll ke atas modal untuk memastikan alert terlihat
        const modalContent = alertElement.closest('.bg-white');
        if (modalContent) {
            modalContent.scrollTop = 0;
        }
        
        // Auto hide jika diset
        if (autoHide > 0) {
            setTimeout(() => {
                hideModalAlert(type);
            }, autoHide);
        }
    }
}

/**
 * Fungsi untuk menyembunyikan alert tertentu
 * @param {string} type - Tipe alert yang akan disembunyikan
 */
function hideModalAlert(type) {
    const alertElement = document.getElementById(`modal-alert-${type}`);
    if (alertElement) {
        alertElement.classList.add('hidden');
    }
}

/**
 * Fungsi untuk menyembunyikan semua alert
 */
function hideAllModalAlerts() {
    const types = ['success', 'error', 'warning', 'info'];
    types.forEach(type => {
        hideModalAlert(type);
    });
}

/**
 * Fungsi shortcut untuk alert sukses
 */
function showModalSuccess(title, message, autoHide = 5000) {
    showModalAlert('success', title, message, autoHide);
}

/**
 * Fungsi shortcut untuk alert error
 */
function showModalError(title, message, autoHide = 0) {
    showModalAlert('error', title, message, autoHide);
}

/**
 * Fungsi shortcut untuk alert warning
 */
function showModalWarning(title, message, autoHide = 7000) {
    showModalAlert('warning', title, message, autoHide);
}

/**
 * Fungsi shortcut untuk alert info
 */
function showModalInfo(title, message, autoHide = 5000) {
    showModalAlert('info', title, message, autoHide);
}
</script>
