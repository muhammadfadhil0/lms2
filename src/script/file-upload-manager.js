/**
 * File Upload Manager
 * Handles file uploads with file type detection and preview
 */
class FileUploadManager {
    constructor() {
        this.selectedFiles = [];
        this.maxFileSize = 10 * 1024 * 1024; // 10MB
        this.allowedExtensions = [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 
            'txt', 'zip', 'rar', '7z'
        ];
        this.init();
    }

    init() {
        this.setupFileInput();
    }

    setupFileInput() {
        const fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
        }
    }

    handleFileSelect(event) {
        const files = Array.from(event.target.files);
        
        for (const file of files) {
            if (this.validateFile(file)) {
                this.selectedFiles.push(file);
            }
        }

        this.updatePreview();
        // Clear the input so the same file can be selected again if needed
        event.target.value = '';
    }

    validateFile(file) {
        // Check file size
        if (file.size > this.maxFileSize) {
            this.showError(`File "${file.name}" terlalu besar. Maksimal 10MB.`);
            return false;
        }

        // Check file extension
        const extension = this.getFileExtension(file.name);
        if (!this.allowedExtensions.includes(extension)) {
            this.showError(`File "${file.name}" tidak didukung. Format yang didukung: ${this.allowedExtensions.join(', ')}`);
            return false;
        }

        return true;
    }

    getFileExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }

    getFileIcon(extension) {
        const iconMap = {
            'pdf': { icon: 'ti-file-type-pdf', class: 'pdf' },
            'doc': { icon: 'ti-file-type-doc', class: 'word' },
            'docx': { icon: 'ti-file-type-doc', class: 'word' },
            'xls': { icon: 'ti-file-type-xls', class: 'excel' },
            'xlsx': { icon: 'ti-file-type-xls', class: 'excel' },
            'ppt': { icon: 'ti-presentation', class: 'powerpoint' },
            'pptx': { icon: 'ti-presentation', class: 'powerpoint' },
            'txt': { icon: 'ti-file-text', class: 'text' },
            'zip': { icon: 'ti-file-zip', class: 'archive' },
            'rar': { icon: 'ti-file-zip', class: 'archive' },
            '7z': { icon: 'ti-file-zip', class: 'archive' }
        };

        return iconMap[extension] || { icon: 'ti-file', class: 'default' };
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    updatePreview() {
        const container = document.querySelector('.file-preview-container');
        const listElement = document.querySelector('.file-preview-list');
        
        if (!container || !listElement) return;

        if (this.selectedFiles.length === 0) {
            container.classList.add('hidden');
            return;
        }

        container.classList.remove('hidden');
        listElement.innerHTML = '';

        this.selectedFiles.forEach((file, index) => {
            const fileItem = this.createFilePreviewItem(file, index);
            listElement.appendChild(fileItem);
        });
    }

    createFilePreviewItem(file, index) {
        const extension = this.getFileExtension(file.name);
        const iconInfo = this.getFileIcon(extension);
        
        const item = document.createElement('div');
        item.className = 'file-preview-item';
        
        item.innerHTML = `
            <div class="file-icon ${iconInfo.class}">
                <i class="ti ${iconInfo.icon}"></i>
            </div>
            <div class="file-info">
                <div class="file-name" title="${file.name}">${file.name}</div>
                <div class="file-size">${this.formatFileSize(file.size)}</div>
            </div>
            <button type="button" class="file-remove" onclick="window.fileUploadManager.removeFile(${index})" title="Hapus file">
                <i class="ti ti-x"></i>
            </button>
        `;

        return item;
    }

    removeFile(index) {
        this.selectedFiles.splice(index, 1);
        this.updatePreview();
    }

    getSelectedFiles() {
        return this.selectedFiles;
    }

    clearFiles() {
        this.selectedFiles = [];
        this.updatePreview();
    }

    showError(message) {
        // Create a simple toast notification
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity duration-300';
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    // Static method to render file attachment in posts
    static renderPostFileAttachment(fileData) {
        console.log('Rendering file attachment:', fileData); // Debug log
        
        // Handle different property names that might come from backend
        const filename = fileData.nama_file || fileData.filename || '';
        const fileSize = fileData.ukuran_file || fileData.file_size || 0;
        const filePath = fileData.path_file || fileData.file_path || '';
        const fileId = fileData.id || '';
        
        // Validate filename exists
        if (!filename) {
            console.error('Filename is missing from fileData:', fileData);
            return '';
        }
        
        const extension = filename.split('.').pop().toLowerCase();
        
        const iconMap = {
            'pdf': { icon: 'ti-file-type-pdf', class: 'pdf' },
            'doc': { icon: 'ti-file-type-doc', class: 'word' },
            'docx': { icon: 'ti-file-type-doc', class: 'word' },
            'xls': { icon: 'ti-file-type-xls', class: 'excel' },
            'xlsx': { icon: 'ti-file-type-xls', class: 'excel' },
            'ppt': { icon: 'ti-presentation', class: 'powerpoint' },
            'pptx': { icon: 'ti-presentation', class: 'powerpoint' },
            'txt': { icon: 'ti-file-text', class: 'text' },
            'zip': { icon: 'ti-file-zip', class: 'archive' },
            'rar': { icon: 'ti-file-zip', class: 'archive' },
            '7z': { icon: 'ti-file-zip', class: 'archive' }
        };

        const iconInfo = iconMap[extension] || { icon: 'ti-file', class: 'default' };
        
        const formatFileSize = (bytes) => {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        };

        return `
            <a href="../../${filePath}" class="post-file-attachment" target="_blank" rel="noopener noreferrer">
                <div class="post-file-icon ${iconInfo.class}">
                    <i class="ti ${iconInfo.icon}"></i>
                </div>
                <div class="post-file-info">
                    <div class="post-file-name" title="${filename}">${filename}</div>
                    <div class="post-file-size">${formatFileSize(fileSize)}</div>
                </div>
                <div class="post-file-download">
                    <i class="ti ti-download"></i>
                </div>
            </a>
        `;
    }
}

// Initialize file upload manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (!window.fileUploadManager) {
        window.fileUploadManager = new FileUploadManager();
    }
});

// Export for global access
window.FileUploadManager = FileUploadManager;
