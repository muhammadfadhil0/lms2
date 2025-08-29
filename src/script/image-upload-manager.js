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
        this.setupImageViewer();
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
    
    setupImageViewer() {
        // Image viewer will be handled by a separate class
        document.addEventListener('click', (e) => {
            if (e.target.matches('.post-image')) {
                const postElement = e.target.closest('[data-post-id]');
                if (postElement) {
                    const postId = postElement.dataset.postId;
                    const imageIndex = parseInt(e.target.dataset.imageIndex) || 0;
                    this.openImageViewer(postId, imageIndex);
                } else {
                    console.warn('Post element not found for image viewer');
                }
            }
        });
    }
    
    openImageViewer(postId, imageIndex) {
        if (window.imageViewer) {
            window.imageViewer.open(postId, imageIndex);
        }
    }
}

/**
 * Image Viewer Modal
 * Handles fullscreen image viewing with navigation
 */
class ImageViewer {
    constructor() {
        this.currentImages = [];
        this.currentIndex = 0;
        this.postData = null;
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Close button
        document.getElementById('closeImageViewer')?.addEventListener('click', () => {
            this.close();
        });
        
        // Navigation buttons
        document.getElementById('prevImage')?.addEventListener('click', () => {
            this.navigate(-1);
        });
        
        document.getElementById('nextImage')?.addEventListener('click', () => {
            this.navigate(1);
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!this.isOpen()) return;
            
            switch (e.key) {
                case 'Escape':
                    this.close();
                    break;
                case 'ArrowLeft':
                    this.navigate(-1);
                    break;
                case 'ArrowRight':
                    this.navigate(1);
                    break;
            }
        });
        
        // Close on background click
        document.getElementById('imageViewerModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'imageViewerModal') {
                this.close();
            }
        });
    }
    
    open(postId, imageIndex = 0) {
        const postElement = document.querySelector(`[data-post-id="${postId}"]`);
        if (!postElement) return;
        
        const images = postElement.querySelectorAll('.post-image');
        if (images.length === 0) return;
        
        this.currentImages = Array.from(images).map(img => ({
            src: img.src,
            alt: img.alt
        }));
        
        this.currentIndex = Math.max(0, Math.min(imageIndex, this.currentImages.length - 1));
        
        // Get post data
        const authorElement = postElement.querySelector('h3.font-semibold');
        const dateElement = postElement.querySelector('p.text-xs');
        
        this.postData = {
            author: authorElement?.textContent || 'Unknown',
            date: this.extractTimeFromText(dateElement?.textContent || '')
        };
        
        this.updateViewer();
        this.show();
    }
    
    close() {
        const modal = document.getElementById('imageViewerModal');
        if (modal) {
            modal.classList.add('hidden');
        }
        document.body.style.overflow = '';
    }
    
    show() {
        const modal = document.getElementById('imageViewerModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
        document.body.style.overflow = 'hidden';
    }
    
    navigate(direction) {
        if (this.currentImages.length <= 1) return;
        
        this.currentIndex += direction;
        
        if (this.currentIndex < 0) {
            this.currentIndex = this.currentImages.length - 1;
        } else if (this.currentIndex >= this.currentImages.length) {
            this.currentIndex = 0;
        }
        
        this.updateViewer();
    }
    
    updateViewer() {
        const image = document.getElementById('viewerImage');
        const counter = document.getElementById('imageCounter');
        const authorName = document.getElementById('viewerAuthorName');
        const postDate = document.getElementById('viewerPostDate');
        const prevBtn = document.getElementById('prevImage');
        const nextBtn = document.getElementById('nextImage');
        
        if (image && this.currentImages[this.currentIndex]) {
            image.src = this.currentImages[this.currentIndex].src;
            image.alt = this.currentImages[this.currentIndex].alt;
        }
        
        if (counter) {
            counter.textContent = `${this.currentIndex + 1} / ${this.currentImages.length}`;
        }
        
        if (authorName && this.postData) {
            authorName.textContent = this.postData.author;
        }
        
        if (postDate && this.postData) {
            postDate.textContent = this.postData.date;
        }
        
        // Show/hide navigation buttons
        const showNav = this.currentImages.length > 1;
        if (prevBtn) prevBtn.classList.toggle('hidden', !showNav);
        if (nextBtn) nextBtn.classList.toggle('hidden', !showNav);
    }
    
    isOpen() {
        const modal = document.getElementById('imageViewerModal');
        return modal && !modal.classList.contains('hidden');
    }
    
    extractTimeFromText(text) {
        // Extract time info from text like "Guru • 5 menit yang lalu"
        const matches = text.match(/•\s*(.+?)(?:\s*•|$)/);
        return matches ? matches[1].trim() : text;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing image upload manager...');
    window.imageUpload = new ImageUploadManager();
    window.imageViewer = new ImageViewer();
    console.log('Image upload manager initialized');
});
