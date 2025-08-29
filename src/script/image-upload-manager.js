/**
 * Image Upload Manager
 * Handles image selection, preview, validation, and upload for posting system
 */
class ImageUploadManager {
    constructor(options = {}) {
        this.maxFiles = options.maxFiles || 4;
        this.maxFileSize = options.maxFileSize || 5 * 1024 * 1024; // 5MB
        this.allowedTypes = options.allowedTypes || ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        this.selectedFiles = [];
        this.previewContainer = null;
        
        this.init();
    }
    
    init() {
        this.setupImageInput();
        // setupImageViewer removed - now handled by PhotoSwipe
    }
    
    setupImageInput() {
        const imageInput = document.getElementById('imageInput');
        
        if (imageInput) {
            imageInput.addEventListener('change', (e) => {
                console.log('Files selected:', e.target.files.length);
                this.handleFileSelection(e.target.files);
            });
        } else {
            console.warn('Image input not found');
        }
    }
    
    handleFileSelection(files) {
        if (files.length === 0) return;
        
        // Validate number of files
        if (this.selectedFiles.length + files.length > this.maxFiles) {
            this.showMessage(`Maksimal ${this.maxFiles} gambar yang dapat dipilih`, 'error');
            return;
        }
        
        // Process each file
        Array.from(files).forEach(file => {
            if (this.validateFile(file)) {
                this.addFile(file);
            }
        });
        
        this.updatePreview();
        this.clearFileInput();
    }
    
    validateFile(file) {
        // Check file size
        if (file.size > this.maxFileSize) {
            this.showMessage(`File ${file.name} terlalu besar. Maksimal ${this.formatFileSize(this.maxFileSize)}`, 'error');
            return false;
        }
        
        // Check file type
        if (!this.allowedTypes.includes(file.type)) {
            this.showMessage(`File ${file.name} tidak didukung. Gunakan format JPG, PNG, atau GIF`, 'error');
            return false;
        }
        
        return true;
    }
    
    addFile(file) {
        const fileId = Date.now() + Math.random();
        this.selectedFiles.push({
            id: fileId,
            file: file,
            preview: URL.createObjectURL(file)
        });
    }
    
    removeFile(fileId) {
        const index = this.selectedFiles.findIndex(f => f.id === fileId);
        if (index !== -1) {
            URL.revokeObjectURL(this.selectedFiles[index].preview);
            this.selectedFiles.splice(index, 1);
            this.updatePreview();
        }
    }
    
    updatePreview() {
        const container = this.getPreviewContainer();
        
        if (this.selectedFiles.length === 0) {
            container.classList.add('hidden');
            return;
        }
        
        container.classList.remove('hidden');
        const grid = container.querySelector('.image-preview-grid');
        
        // Set grid class based on number of images
        grid.className = `image-preview-grid grid-${this.selectedFiles.length}`;
        
        // Clear existing previews
        grid.innerHTML = '';
        
        // Add preview items
        this.selectedFiles.forEach(fileData => {
            const item = this.createPreviewItem(fileData);
            grid.appendChild(item);
        });
        
        console.log('Preview updated with', this.selectedFiles.length, 'images');
    }
    
    createPreviewItem(fileData) {
        const item = document.createElement('div');
        item.className = 'preview-item';
        
        item.innerHTML = `
            <img src="${fileData.preview}" alt="Preview" class="preview-image" />
            <button type="button" class="remove-image" onclick="window.imageUpload.removeFile(${fileData.id})">
                <i class="ti ti-x"></i>
            </button>
        `;
        
        return item;
    }
    
    getPreviewContainer() {
        if (!this.previewContainer) {
            this.previewContainer = document.querySelector('.image-preview-container');
            if (!this.previewContainer) {
                this.createPreviewContainer();
            }
        }
        return this.previewContainer;
    }
    
    createPreviewContainer() {
        const postForm = document.getElementById('postForm');
        const container = document.createElement('div');
        container.className = 'image-preview-container hidden';
        container.innerHTML = `
            <div class="image-preview-grid"></div>
            <div class="upload-message-container"></div>
        `;
        
        // Insert before the submit button area
        const textareaContainer = postForm.querySelector('textarea').closest('.flex-1');
        textareaContainer.appendChild(container);
        this.previewContainer = container;
    }
    
    getSelectedFiles() {
        return this.selectedFiles.map(fileData => fileData.file);
    }
    
    clearSelection() {
        this.selectedFiles.forEach(fileData => {
            URL.revokeObjectURL(fileData.preview);
        });
        this.selectedFiles = [];
        this.updatePreview();
    }
    
    clearFileInput() {
        const imageInput = document.getElementById('imageInput');
        if (imageInput) {
            imageInput.value = '';
        }
    }
    
    showMessage(message, type) {
        const container = this.getPreviewContainer();
        const messageContainer = container.querySelector('.upload-message-container');
        
        messageContainer.innerHTML = `
            <div class="upload-message ${type}">
                ${message}
            </div>
        `;
        
        setTimeout(() => {
            messageContainer.innerHTML = '';
        }, 5000);
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Image viewer functionality has been moved to PhotoSwipe
    // No need to handle image clicks here anymore
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing image upload manager...');
    window.imageUpload = new ImageUploadManager();
    // ImageViewer removed - now using PhotoSwipe
    console.log('Image upload manager initialized');
});
