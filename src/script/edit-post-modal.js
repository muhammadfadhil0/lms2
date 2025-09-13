// Edit Post Modal Manager
class EditPostModal {
    constructor() {
        this.modal = document.getElementById('modalEditPost');
        this.form = document.getElementById('editPostForm');
        this.currentPostId = null;
        this.imagesToDelete = [];
        this.newImages = [];
        
        this.initializeEventListeners();
    }
    
    initializeEventListeners() {
        // Check if modal exists
        if (!this.modal) {
            console.error('Edit modal not found');
            return;
        }
        
        // Close modal events
        document.querySelectorAll('.close-modal-edit-post').forEach(btn => {
            btn.addEventListener('click', () => this.closeModal());
        });
        
        // Add image button
        const addImageBtn = document.getElementById('addImageBtn');
        const imageInput = document.getElementById('editPostImages');
        
        if (addImageBtn && imageInput) {
            addImageBtn.addEventListener('click', () => {
                imageInput.click();
            });
            
            // Handle new image selection
            imageInput.addEventListener('change', (e) => {
                this.handleNewImageSelection(e);
            });
        }
        
        // Form submit
        if (this.form) {
            this.form.addEventListener('submit', (e) => {
                this.handleFormSubmit(e);
            });
        }
        
        // Auto resize textarea
        const textarea = document.getElementById('editPostContent');
        if (textarea) {
            textarea.addEventListener('input', () => {
                this.autoResizeTextarea(textarea);
            });
        }
    }
    
    async openModal(postId) {
        this.currentPostId = postId;
        this.imagesToDelete = [];
        this.newImages = [];
        
        try {
            // Show modal using dialog API
            this.modal.showModal();
            
            // Load post data
            const response = await fetch(`../logic/handle-edit-post.php?action=get_post_detail&postingan_id=${postId}`);
            const data = await response.json();
            
            if (data.success) {
                this.populateForm(data.data);
            } else {
                this.showError(data.message);
                this.closeModal();
            }
        } catch (error) {
            console.error('Error loading post data:', error);
            this.showError('Gagal memuat data postingan');
            this.closeModal();
        }
    }
    
    populateForm(postData) {
        // Set form data
        document.getElementById('editPostId').value = postData.id;
        document.getElementById('editPostContent').value = postData.konten;
        
        // Auto resize textarea
        const textarea = document.getElementById('editPostContent');
        this.autoResizeTextarea(textarea);
        
        // Display current images
        this.displayCurrentImages(postData.gambar || []);
    }
    
    displayCurrentImages(images) {
        const container = document.getElementById('currentImagesList');
        const parentContainer = document.getElementById('currentImagesContainer');
        
        if (images.length === 0) {
            parentContainer.style.display = 'none';
            return;
        }
        
        parentContainer.style.display = 'block';
        container.innerHTML = '';
        
        images.forEach(image => {
            const imageDiv = document.createElement('div');
            imageDiv.className = 'relative group';
            
            // Fix path issue - remove duplicate uploads if exists
            let imagePath = image.path_gambar;
            if (imagePath.startsWith('uploads/')) {
                imagePath = imagePath.substring(8); // Remove 'uploads/' prefix
            }
            
            imageDiv.innerHTML = `
                <img src="../../uploads/${imagePath}" 
                     alt="${image.nama_file}"
                     class="w-full h-24 object-cover rounded-lg border"
                     onerror="console.error('Image not found:', this.src)">
                <button type="button" 
                        class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                        onclick="editPostModal.removeCurrentImage(${image.id}, this)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(imageDiv);
        });
    }
    
    removeCurrentImage(imageId, buttonElement) {
        // Add to delete list
        this.imagesToDelete.push(imageId);
        
        // Remove from UI
        buttonElement.closest('.relative').remove();
        
        // Hide container if no images left
        const container = document.getElementById('currentImagesList');
        if (container.children.length === 0) {
            document.getElementById('currentImagesContainer').style.display = 'none';
        }
    }
    
    handleNewImageSelection(event) {
        const files = event.target.files;
        const preview = document.getElementById('newImagesPreview');
        
        // Clear previous previews
        preview.innerHTML = '';
        this.newImages = [];
        
        Array.from(files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                this.newImages.push(file);
                
                const reader = new FileReader();
                reader.onload = (e) => {
                    const imageDiv = document.createElement('div');
                    imageDiv.className = 'relative group';
                    imageDiv.innerHTML = `
                        <img src="${e.target.result}" 
                             alt="${file.name}"
                             class="w-20 h-20 object-cover rounded-lg border">
                        <button type="button" 
                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-xs"
                                onclick="editPostModal.removeNewImage(${index}, this)">
                            ×
                        </button>
                    `;
                    preview.appendChild(imageDiv);
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    removeNewImage(index, buttonElement) {
        // Remove from array
        this.newImages.splice(index, 1);
        
        // Remove from UI
        buttonElement.closest('.relative').remove();
        
        // Update file input
        const input = document.getElementById('editPostImages');
        const dt = new DataTransfer();
        this.newImages.forEach(file => dt.items.add(file));
        input.files = dt.files;
    }
    
    async handleFormSubmit(event) {
        event.preventDefault();
        
        const konten = document.getElementById('editPostContent').value.trim();
        if (!konten) {
            this.showError('Konten postingan tidak boleh kosong');
            return;
        }
        
        // Disable submit button during processing
        const submitBtn = event.target.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Menyimpan...';
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'update_post');
            formData.append('postingan_id', this.currentPostId);
            formData.append('konten', konten);
            formData.append('images_to_delete', JSON.stringify(this.imagesToDelete));
            
            // Add new images
            this.newImages.forEach((file, index) => {
                formData.append(`new_images[${index}]`, file);
            });
            
            const response = await fetch('../logic/handle-edit-post.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess(data.message || 'Postingan berhasil di update');
                this.closeModal();
                
                // Reload posts if kelasPosting exists
                if (window.kelasPosting) {
                    window.kelasPosting.reloadPosts();
                }
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error('Error updating post:', error);
            this.showError('Gagal mengupdate postingan');
        } finally {
            // Re-enable submit button
            const submitBtn = document.querySelector('#editPostForm button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Simpan Perubahan';
            }
        }
    }
    
    closeModal() {
        if (this.modal) {
            this.modal.close();
        }
        
        // Reset form
        this.form.reset();
        this.currentPostId = null;
        this.imagesToDelete = [];
        this.newImages = [];
        
        // Clear previews
        document.getElementById('currentImagesList').innerHTML = '';
        document.getElementById('newImagesPreview').innerHTML = '';
        document.getElementById('currentImagesContainer').style.display = 'none';
    }
    
    autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }
    
    showError(message) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, 'error');
        } else {
            alert(message);
        }
    }

    showSuccess(message) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, 'success');
        } else {
            alert(message);
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing EditPostModal...');
    window.editPostModal = new EditPostModal();
    console.log('EditPostModal initialized successfully');
});

// Function to open edit modal (called from post items)
function openEditPostModal(postId) {
    console.log('openEditPostModal called with postId:', postId);
    if (window.editPostModal) {
        console.log('EditPostModal instance found, opening modal...');
        window.editPostModal.openModal(postId);
    } else {
        console.error('EditPostModal instance not found!');
    }
}
