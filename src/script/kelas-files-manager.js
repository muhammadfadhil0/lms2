class KelasFilesManager {
    constructor(kelasId, userRole) {
        this.kelasId = kelasId;
        this.userRole = userRole;
        this.init();
    }

    init() {
        // Initialize upload forms
        this.initScheduleForm();
        this.initMaterialForm();
    }

    initScheduleForm() {
        const form = document.getElementById('upload-schedule-form');
        if (form) {
            form.addEventListener('submit', (e) => this.handleScheduleUpload(e));
        }
    }

    initMaterialForm() {
        const form = document.getElementById('upload-material-form');
        if (form) {
            form.addEventListener('submit', (e) => this.handleMaterialUpload(e));
        }
    }

    async handleScheduleUpload(e) {
        e.preventDefault();
        
        // Check if file is selected
        const fileInput = document.getElementById('schedule-file');
        if (!fileInput.files.length) {
            this.showAlert('error', 'Silakan pilih file jadwal terlebih dahulu');
            return;
        }
        
        const formData = new FormData(e.target);
        formData.append('kelas_id', this.kelasId);
        formData.append('file_type', 'schedule');
        
        try {
            const response = await fetch('../logic/upload-kelas-file.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            console.log('Upload result:', result);
            
            if (result.success) {
                this.showAlert('success', 'Jadwal berhasil diupload!');
                e.target.reset();
                await this.loadExistingSchedules(); // Make sure this waits
                
                // Also refresh student view if this is called from kelas-user.php
                if (typeof loadClassSchedules === 'function') {
                    loadClassSchedules();
                }
                // Refresh unified list modal if open
                if (window.listModalsManager) {
                    window.listModalsManager.loadSchedules(true);
                }
            } else {
                this.showAlert('error', result.message || 'Gagal mengupload jadwal');
                if (result.debug) {
                    console.error('Debug info:', result.debug);
                }
            }
        } catch (error) {
            console.error('Upload error:', error);
            this.showAlert('error', 'Terjadi kesalahan saat mengupload: ' + error.message);
        }
    }

    async handleMaterialUpload(e) {
        e.preventDefault();
        
        // Check if file is selected
        const fileInput = document.getElementById('material-file');
        if (!fileInput.files.length) {
            this.showAlert('error', 'Silakan pilih file materi terlebih dahulu');
            return;
        }
        
        const formData = new FormData(e.target);
        formData.append('kelas_id', this.kelasId);
        formData.append('file_type', 'material');
        
        try {
            const response = await fetch('../logic/upload-kelas-file.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            console.log('Upload result:', result);
            
            if (result.success) {
                this.showAlert('success', 'Materi berhasil diupload!');
                e.target.reset();
                await this.loadExistingMaterials(); // Make sure this waits
                
                // Also refresh student view if this is called from kelas-user.php
                if (typeof loadLearningMaterials === 'function') {
                    loadLearningMaterials();
                }
                if (window.listModalsManager) {
                    window.listModalsManager.loadMaterials(true);
                }
            } else {
                this.showAlert('error', result.message || 'Gagal mengupload materi');
                if (result.debug) {
                    console.error('Debug info:', result.debug);
                }
            }
        } catch (error) {
            console.error('Upload error:', error);
            this.showAlert('error', 'Terjadi kesalahan saat mengupload: ' + error.message);
        }
    }

    async loadExistingSchedules() {
        try {
            const response = await fetch(`../logic/get-kelas-files.php?kelas_id=${this.kelasId}&file_type=schedule`);
            const data = await response.json();
            
            // Handle both array response and object with files property
            const files = Array.isArray(data) ? data : (data.files || []);
            
            if (data.error) {
                console.error('API Error:', data.error);
            }
            
            const container = document.getElementById('existing-schedules');
            if (!container) return;
            
            if (files.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="ti ti-calendar-off text-4xl mb-2"></i>
                        <p class="text-sm">Tidak ada jadwal yang Anda upload</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = files.map(file => this.renderFileItem(file)).join('');
        } catch (error) {
            console.error('Error loading schedules:', error);
            const container = document.getElementById('existing-schedules');
            if (container) {
                container.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="ti ti-exclamation-triangle text-4xl mb-2"></i>
                        <p class="text-sm">Error loading schedules</p>
                    </div>
                `;
            }
        }
    }

    async loadExistingMaterials() {
        try {
            const response = await fetch(`../logic/get-kelas-files.php?kelas_id=${this.kelasId}&file_type=material`);
            const data = await response.json();
            
            // Handle both array response and object with files property
            const files = Array.isArray(data) ? data : (data.files || []);
            
            if (data.error) {
                console.error('API Error:', data.error);
            }
            
            const container = document.getElementById('existing-materials');
            if (!container) return;
            
            if (files.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="ti ti-book-off text-4xl mb-2"></i>
                        <p class="text-sm">Tidak ada materi yang Anda upload</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = files.map(file => this.renderFileItem(file)).join('');
        } catch (error) {
            console.error('Error loading materials:', error);
            const container = document.getElementById('existing-materials');
            if (container) {
                container.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="ti ti-exclamation-triangle text-4xl mb-2"></i>
                        <p class="text-sm">Error loading materials</p>
                    </div>
                `;
            }
        }
    }

    renderFileItem(file) {
        const fileSize = this.formatFileSize(file.file_size);
        const uploadDate = new Date(file.created_at).toLocaleDateString('id-ID');
        const fileIcon = this.getFileIcon(file.file_extension);
        
        return `
            <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="${fileIcon} text-orange-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h5 class="text-sm font-medium text-gray-900 truncate">${file.title}</h5>
                    <p class="text-xs text-gray-500">${file.file_name} • ${fileSize} • ${uploadDate}</p>
                </div>
                <div class="flex items-center space-x-2 ml-3">
                    <button onclick="downloadFile(${file.id})" class="text-blue-600 hover:text-blue-800" title="Download">
                        <i class="ti ti-download text-sm"></i>
                    </button>
                    ${this.userRole === 'guru' ? `
                        <button onclick="deleteFile(${file.id}, '${file.file_type}')" class="text-red-600 hover:text-red-800" title="Hapus">
                            <i class="ti ti-trash text-sm"></i>
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }

    getFileIcon(extension) {
        const iconMap = {
            'pdf': 'ti ti-file-type-pdf',
            'doc': 'ti ti-file-type-doc',
            'docx': 'ti ti-file-type-docx',
            'ppt': 'ti ti-presentation',
            'pptx': 'ti ti-presentation',
            'jpg': 'ti ti-photo',
            'jpeg': 'ti ti-photo',
            'png': 'ti ti-photo'
        };
        return iconMap[extension.toLowerCase()] || 'ti ti-file';
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    showAlert(type, message) {
        // Create alert element
        const alertClass = type === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700';
        const iconClass = type === 'success' ? 'ti ti-check' : 'ti ti-exclamation-triangle';
        
        const alert = document.createElement('div');
        alert.className = `fixed top-4 right-4 z-50 ${alertClass} border-l-4 p-4 rounded shadow-lg max-w-sm`;
        alert.innerHTML = `
            <div class="flex items-center">
                <i class="${iconClass} mr-2"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(alert);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            alert.remove();
        }, 3000);
    }

    async deleteFile(fileId, fileType) {
        if (!confirm('Apakah Anda yakin ingin menghapus file ini?')) {
            return;
        }
        
        try {
            const response = await fetch('../logic/delete-kelas-file.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    file_id: fileId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('success', 'File berhasil dihapus!');
                if (fileType === 'schedule') {
                    this.loadExistingSchedules();
                    if (window.listModalsManager) window.listModalsManager.loadSchedules(true);
                } else {
                    this.loadExistingMaterials();
                    if (window.listModalsManager) window.listModalsManager.loadMaterials(true);
                }
            } else {
                this.showAlert('error', result.message || 'Gagal menghapus file');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showAlert('error', 'Terjadi kesalahan saat menghapus file');
        }
    }
}

// Global functions for modal management
function openScheduleModal() {
    const modal = document.getElementById('upload-schedule-modal');
    if (modal && window.kelasFilesManager) {
        modal.showModal();
        window.kelasFilesManager.loadExistingSchedules();
    }
}

function closeScheduleModal() {
    const modal = document.getElementById('upload-schedule-modal');
    if (modal) {
        modal.close();
    }
}

function openMaterialModal() {
    const modal = document.getElementById('upload-material-modal');
    if (modal && window.kelasFilesManager) {
        modal.showModal();
        window.kelasFilesManager.loadExistingMaterials();
    }
}

function closeMaterialModal() {
    const modal = document.getElementById('upload-material-modal');
    if (modal) {
        modal.close();
    }
}

function downloadFile(fileId) {
    window.open(`../logic/download-kelas-file.php?file_id=${fileId}`, '_blank');
}

function deleteFile(fileId, fileType) {
    if (window.kelasFilesManager) {
        window.kelasFilesManager.deleteFile(fileId, fileType);
    }
}
