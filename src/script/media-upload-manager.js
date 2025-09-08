/**
 * Media Upload Manager
 * Handles image and video selection, preview, validation, and upload for posting system
 */
class MediaUploadManager {
    constructor(options = {}) {
        this.maxFiles = options.maxFiles || 4;
        this.maxImageSize = options.maxImageSize || 5 * 1024 * 1024; // 5MB for images
        this.maxVideoSize = options.maxVideoSize || 50 * 1024 * 1024; // 50MB for videos
        this.allowedImageTypes = options.allowedImageTypes || ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        this.allowedVideoTypes = options.allowedVideoTypes || ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm'];
        this.selectedFiles = [];
        this.previewContainer = null;
        
        this.init();
    }
    
    init() {
        console.log('Initializing media upload manager...');
        
        // Check if we're in a page that supports media upload
        const isPostingPage = document.getElementById('mediaInput') || document.getElementById('postForm');
        
        if (isPostingPage) {
            console.log('📝 Page supports media upload - setting up input handler');
            this.setupMediaInput();
        } else {
            console.log('👀 View-only page - media upload not needed');
            // In view-only pages, we still want the class available for other functions
            return;
        }
    }
    
    setupMediaInput() {
        const mediaInput = document.getElementById('mediaInput');
        
        if (mediaInput) {
            console.log('✅ Media input found - adding event listeners');
            mediaInput.addEventListener('change', (e) => {
                console.log('Media files selected:', e.target.files.length);
                this.handleFileSelection(e.target.files);
            });
        } else {
            console.log('ℹ️ Media input not found (normal for view-only pages)');
        }
    }
    
    handleFileSelection(files) {
        if (files.length === 0) return;
        
        // Validate number of files
        if (this.selectedFiles.length + files.length > this.maxFiles) {
            this.showMessage(`Maksimal ${this.maxFiles} file media yang dapat dipilih`, 'error');
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
        const isImage = this.allowedImageTypes.includes(file.type);
        const isVideo = this.allowedVideoTypes.includes(file.type);
        
        // Check if file type is supported
        if (!isImage && !isVideo) {
            this.showMessage(`File ${file.name} tidak didukung. Gunakan format gambar (JPG, PNG, GIF) atau video (MP4, AVI, MOV, WMV, WEBM)`, 'error');
            return false;
        }
        
        // Check file size based on type
        const maxSize = isVideo ? this.maxVideoSize : this.maxImageSize;
        if (file.size > maxSize) {
            const fileType = isVideo ? 'video' : 'gambar';
            this.showMessage(`File ${file.name} terlalu besar. Maksimal ${this.formatFileSize(maxSize)} untuk ${fileType}`, 'error');
            return false;
        }
        
        return true;
    }
    
    addFile(file) {
        const fileId = Date.now() + Math.random();
        const isVideo = this.allowedVideoTypes.includes(file.type);
        
        this.selectedFiles.push({
            id: fileId,
            file: file,
            type: isVideo ? 'video' : 'image',
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
        const grid = container.querySelector('.media-preview-grid');
        
        // Set grid class based on number of files
        grid.className = `media-preview-grid grid-${this.selectedFiles.length}`;
        
        // Clear existing previews
        grid.innerHTML = '';
        
        // Add preview items
        this.selectedFiles.forEach(fileData => {
            const item = this.createPreviewItem(fileData);
            grid.appendChild(item);
        });
        
        console.log('Media preview updated with', this.selectedFiles.length, 'files');
    }
    
    createPreviewItem(fileData) {
        const item = document.createElement('div');
        item.className = 'preview-item';
        
        if (fileData.type === 'video') {
            item.innerHTML = `
                <video src="${fileData.preview}" class="preview-media" controls muted>
                    Your browser does not support the video tag.
                </video>
                <div class="media-type-indicator video-indicator">
                    <i class="ti ti-video"></i>
                </div>
                <button type="button" class="remove-media" onclick="window.mediaUpload.removeFile(${fileData.id})">
                    <i class="ti ti-x"></i>
                </button>
            `;
        } else {
            item.innerHTML = `
                <img src="${fileData.preview}" alt="Preview" class="preview-media" />
                <div class="media-type-indicator image-indicator">
                    <i class="ti ti-photo"></i>
                </div>
                <button type="button" class="remove-media" onclick="window.mediaUpload.removeFile(${fileData.id})">
                    <i class="ti ti-x"></i>
                </button>
            `;
        }
        
        return item;
    }
    
    getPreviewContainer() {
        if (!this.previewContainer) {
            this.previewContainer = document.querySelector('.media-preview-container');
            if (!this.previewContainer) {
                this.createPreviewContainer();
            }
        }
        return this.previewContainer;
    }
    
    createPreviewContainer() {
        const postForm = document.getElementById('postForm');
        const container = document.createElement('div');
        container.className = 'media-preview-container hidden';
        container.innerHTML = `
            <div class="media-preview-grid"></div>
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
        const mediaInput = document.getElementById('mediaInput');
        if (mediaInput) {
            mediaInput.value = '';
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
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing media upload manager...');
    window.mediaUpload = new MediaUploadManager();
    console.log('Media upload manager initialized');
});
