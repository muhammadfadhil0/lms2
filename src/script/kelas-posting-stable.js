// Kelas Posting System - Stable Version
class KelasPosting {
    constructor(kelasId, permissions = null) {
        this.kelasId = kelasId;
        this.permissions = permissions || {
            canPost: true,
            canComment: true
        };
        this.currentOffset = 0;
        this.limit = 10;
        this.isLoading = false;
        this.hasMorePosts = true;
        this.submitInProgress = false;
        this.initialized = false;
        this.pendingTimeouts = []; // Track pending timeouts
        this.postToDelete = null; // Track post ID to delete
        
        this.initializeEventListeners();
        this.initializeDeleteModal();
        // Add delay before initial load to ensure page is ready
        setTimeout(() => {
            if (!this.initialized) {
                this.initialized = true;
                this.loadPostingan(true);
            }
        }, 300);
    }
    
    initializeEventListeners() {
        // Submit post form
        const postForm = document.getElementById('postForm');
        if (postForm) {
            postForm.addEventListener('submit', (e) => this.handleSubmitPost(e));
        }
        
        // Auto-resize textarea
        const postTextarea = document.getElementById('postTextarea');
        if (postTextarea) {
            postTextarea.addEventListener('input', this.autoResizeTextarea);
        }
        
        // Load more posts on scroll
        window.addEventListener('scroll', () => this.handleScroll());
        
        // Like button handler
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('like-btn') || e.target.closest('.like-btn')) {
                e.preventDefault();
                const btn = e.target.closest('.like-btn');
                const postId = btn.getAttribute('data-post-id');
                this.toggleLike(postId);
            }
        });
        
        // Comment button handler
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('comment-btn') || e.target.closest('.comment-btn')) {
                if (!this.permissions.canComment) return;
                e.preventDefault();
                const btn = e.target.closest('.comment-btn');
                const postId = btn.getAttribute('data-post-id');
                this.toggleQuickComment(postId);
            }
        });
        
        // View all comments button handler
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('view-all-comments') || e.target.closest('.view-all-comments')) {
                if (!this.permissions.canComment) return;
                e.preventDefault();
                const btn = e.target.closest('.view-all-comments');
                const postId = btn.getAttribute('data-post-id');
                this.openCommentsModal(postId);
            }
        });
    }
    
    initializeDeleteModal() {
        const modal = document.getElementById('deletePostModal');
        const cancelBtn = document.getElementById('cancelDeleteBtn');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        
        if (modal && cancelBtn && confirmBtn) {
            // Cancel delete
            cancelBtn.addEventListener('click', () => {
                this.hideDeleteModal();
            });
            
            // Confirm delete
            confirmBtn.addEventListener('click', () => {
                this.confirmDeletePost();
            });
            
            // Close modal when clicking outside (on backdrop)
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.hideDeleteModal();
                }
            });
            
            // Close modal with ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.hasAttribute('open')) {
                    this.hideDeleteModal();
                }
            });
        }
    }
    
    async handleSubmitPost(e) {
        e.preventDefault();
        
        if (this.submitInProgress) {
            return;
        }
        
        const textarea = document.getElementById('postTextarea');
        const konten = textarea.value.trim();
        const submitBtn = document.querySelector('#postForm button[type="submit"]');
        
        if (!konten) {
            this.showAlert('Konten postingan tidak boleh kosong', 'error');
            return;
        }
        
        this.submitInProgress = true;
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="ti ti-loader animate-spin mr-2"></i>Posting...';
        
        const formData = new FormData();
        formData.append('kelas_id', this.kelasId);
        formData.append('konten', konten);
        formData.append('tipePost', 'umum');
        
        // Add selected images if any
        if (window.imageUpload && window.imageUpload.selectedFiles.length > 0) {
            const selectedFiles = window.imageUpload.getSelectedFiles();
            console.log('Adding', selectedFiles.length, 'files to form data');
            selectedFiles.forEach((file, index) => {
                console.log('Adding file:', file.name, 'size:', file.size);
                formData.append('images[]', file);
            });
        } else {
            console.log('No images selected for upload');
        }
        
        try {
            const response = await fetch('../logic/handle-posting.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                textarea.value = '';
                this.autoResizeTextarea.call(textarea);
                
                // Clear selected images
                if (window.imageUpload) {
                    window.imageUpload.clearSelection();
                }
                
                this.showAlert('Postingan berhasil dibuat!', 'success');
                
                // Wait longer then reload posts to avoid conflicts
                setTimeout(() => {
                    this.refreshPosts();
                }, 1000);
            } else {
                console.error('Post submission failed:', result);
                this.showAlert(result.message || 'Gagal membuat postingan', 'error');
            }
        } catch (error) {
            console.error('Error submitting post:', error);
            this.showAlert('Terjadi kesalahan saat membuat postingan', 'error');
        } finally {
            this.submitInProgress = false;
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
    
    async loadPostingan(refresh = false) {
        // Prevent multiple simultaneous loads
        if (this.isLoading || (!this.hasMorePosts && !refresh)) {
            return;
        }
        
        this.isLoading = true;
        
        if (refresh) {
            this.currentOffset = 0;
            this.hasMorePosts = true;
        }
        
        // Get container and ensure it exists
        const postsContainer = document.getElementById('postsContainer');
        if (!postsContainer) {
            console.error('Posts container not found');
            this.isLoading = false;
            return;
        }
        
        // Show loading only on refresh
        if (refresh) {
            postsContainer.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="ti ti-loader animate-spin text-4xl mb-2"></i>
                    <p>Memuat postingan...</p>
                </div>
            `;
        }
        
        try {
            const url = `../logic/get-postingan.php?kelas_id=${this.kelasId}&limit=${this.limit}&offset=${this.currentOffset}&_=${Date.now()}`;
            
            const response = await fetch(url, {
                cache: 'no-store',
                headers: {
                    'Cache-Control': 'no-cache'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                if (refresh) {
                    // Clear completely on refresh and reset timeouts
                    postsContainer.innerHTML = '';
                    if (this.pendingTimeouts) {
                        this.pendingTimeouts.forEach(timeout => clearTimeout(timeout));
                        this.pendingTimeouts = [];
                    }
                }
                
                if (result.data && result.data.length > 0) {
                    // Clear any existing pending timeouts to prevent double posting
                    if (this.pendingTimeouts) {
                        this.pendingTimeouts.forEach(timeout => clearTimeout(timeout));
                        this.pendingTimeouts = [];
                    }
                    
                    // Add posts one by one to avoid conflicts
                    result.data.forEach((post, index) => {
                        const timeoutId = setTimeout(() => {
                            const postElement = this.createPostElement(post, result.user_id, result.user_role);
                            if (postsContainer && postElement) {
                                postsContainer.appendChild(postElement);
                            }
                            // Remove this timeout from pending list
                            const timeoutIndex = this.pendingTimeouts.indexOf(timeoutId);
                            if (timeoutIndex > -1) {
                                this.pendingTimeouts.splice(timeoutIndex, 1);
                            }
                        }, index * 100); // Stagger the additions
                        
                        // Track this timeout
                        this.pendingTimeouts.push(timeoutId);
                    });
                    
                    this.currentOffset += result.data.length;
                    
                    if (result.data.length < this.limit) {
                        this.hasMorePosts = false;
                    }
                    
                    // Add load more indicator
                    if (this.hasMorePosts) {
                        const loadMoreTimeoutId = setTimeout(() => {
                            const loadMoreElement = document.createElement('div');
                            loadMoreElement.id = 'loadMoreIndicator';
                            loadMoreElement.className = 'text-center py-4 text-gray-400';
                            loadMoreElement.innerHTML = '<p class="text-sm">Scroll ke bawah untuk memuat lebih banyak...</p>';
                            if (postsContainer) {
                                postsContainer.appendChild(loadMoreElement);
                            }
                            // Remove this timeout from pending list
                            const timeoutIndex = this.pendingTimeouts.indexOf(loadMoreTimeoutId);
                            if (timeoutIndex > -1) {
                                this.pendingTimeouts.splice(timeoutIndex, 1);
                            }
                        }, result.data.length * 100 + 200);
                        
                        // Track this timeout too
                        this.pendingTimeouts.push(loadMoreTimeoutId);
                    }
                } else {
                    this.hasMorePosts = false;
                    
                    if (this.currentOffset === 0) {
                        // No posts at all
                        postsContainer.innerHTML = `
                            <div class="text-center py-12 text-gray-500">
                                <div class="mb-4">
                                    <i class="ti ti-message-circle text-6xl text-gray-300"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-700 mb-2">Belum Ada Postingan</h3>
                                <p class="text-sm text-gray-500 mb-4">Jadilah yang pertama untuk berbagi sesuatu di kelas ini!</p>
                                <div class="flex justify-center">
                                    <button onclick="document.getElementById('postTextarea').focus()" 
                                            class="inline-flex items-center px-4 py-2 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors">
                                        <i class="ti ti-plus mr-2"></i>
                                        Buat Postingan Pertama
                                    </button>
                                </div>
                            </div>
                        `;
                    }
                }
            } else {
                postsContainer.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="ti ti-alert-circle text-4xl mb-2"></i>
                        <p>${result.message || 'Gagal memuat postingan'}</p>
                        <button onclick="window.kelasPosting.refreshPosts()" 
                                class="mt-4 px-4 py-2 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors">
                            Coba Lagi
                        </button>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Load error:', error);
            postsContainer.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="ti ti-wifi-off text-4xl mb-2"></i>
                    <p>Terjadi kesalahan saat memuat postingan</p>
                    <button onclick="window.kelasPosting.refreshPosts()" 
                            class="mt-4 px-4 py-2 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors">
                        <i class="ti ti-refresh mr-2"></i>
                        Coba Lagi
                    </button>
                </div>
            `;
        } finally {
            this.isLoading = false;
        }
    }
    
    createPostElement(post, currentUserId, currentUserRole) {
        const isOwner = post.user_id == currentUserId;
        const postDate = new Date(post.dibuat);
        const timeAgo = this.getTimeAgo(postDate);
        
        const postElement = document.createElement('div');
        postElement.className = 'bg-white rounded-lg shadow-sm mb-6';
        postElement.setAttribute('data-post-id', post.id); // Add this line for easy identification
        
        // Add fade-in animation after element is created
        setTimeout(() => {
            postElement.classList.add('opacity-0');
            postElement.style.transform = 'translateY(20px)';
            postElement.style.transition = 'all 0.3s ease-out';
            
            // Trigger animation
            setTimeout(() => {
                postElement.classList.remove('opacity-0');
                postElement.style.opacity = '1';
                postElement.style.transform = 'translateY(0)';
            }, 50);
        }, 10);
        
        postElement.innerHTML = `
            <div class="p-4 lg:p-6">
                <div class="flex items-start space-x-3 lg:space-x-4 mb-4">
                    <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-full bg-orange-500 flex items-center justify-center">
                        <i class="ti ti-user text-white"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 text-sm lg:text-base">${this.escapeHtml(post.namaPenulis)}</h3>
                        <p class="text-xs lg:text-sm text-gray-600">
                            ${post.rolePenulis === 'guru' ? 'Guru' : 'Siswa'} • ${timeAgo}
                            ${post.is_edited == 1 ? ' • <span class="text-gray-500 italic">(telah diedit)</span>' : ''}
                            ${post.tipePost !== 'umum' ? ` • <span class="px-2 py-1 bg-orange-100 text-orange-600 rounded text-xs">${post.tipePost}</span>` : ''}
                        </p>
                    </div>
                    ${isOwner ? `
                        <div class="dropdown relative">
                            <button class="text-gray-400 hover:text-gray-600" onclick="toggleDropdown(this)">
                                <i class="ti ti-dots"></i>
                            </button>
                            <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 hidden">
                                <button onclick="openEditPostModal(${post.id})" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="ti ti-edit mr-2"></i>Edit
                                </button>
                                <button onclick="deletePost(${post.id})" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    <i class="ti ti-trash mr-2"></i>Hapus
                                </button>
                            </div>
                        </div>
                    ` : ''}
                </div>
                <div class="mb-4">
                    ${post.deadline ? `
                        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center">
                                <i class="ti ti-clock text-yellow-600 mr-2"></i>
                                <span class="text-sm text-yellow-800">Deadline: ${new Date(post.deadline).toLocaleDateString('id-ID')}</span>
                            </div>
                        </div>
                    ` : ''}
                    ${this.renderAssignmentContent(post)}
                    ${this.renderPostImages(post.gambar)}
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div class="flex items-center space-x-4 lg:space-x-6">
                        <button class="like-btn flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base" data-post-id="${post.id}">
                            <i class="ti ti-heart mr-1 lg:mr-2"></i>
                            <span class="like-count">${post.jumlahLike || 0}</span>
                        </button>
                        ${this.permissions.canComment ? `
                        <button class="comment-btn flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base" data-post-id="${post.id}">
                            <i class="ti ti-message-circle mr-1 lg:mr-2"></i>
                            <span class="comment-count">${post.jumlahKomentar || 0}</span>
                        </button>
                        ` : ''}
                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                            <i class="ti ti-share mr-1 lg:mr-2"></i>
                            <span class="hidden sm:inline">Bagikan</span>
                        </button>
                    </div>
                    ${this.permissions.canComment ? `
                    <button class="view-all-comments text-orange text-sm hover:text-orange-600 transition-colors" data-post-id="${post.id}" style="display: none;">
                        Lihat komentar lainnya
                    </button>
                    ` : ''}
                </div>
                ${this.permissions.canComment ? `
                <!-- Comments Preview - Always visible if there are comments -->
                <div id="comments-preview-${post.id}" class="mt-4 pt-4 border-t border-gray-100" style="display: none;">
                    <!-- Preview comments (max 3) will be loaded here -->
                </div>
                <!-- Quick Comment Input -->
                <div id="quick-comment-${post.id}" class="hidden mt-4 pt-4 border-t border-gray-100">
                    <form class="flex space-x-3" onsubmit="addQuickComment(event, ${post.id})">
                        <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center flex-shrink-0">
                            <i class="ti ti-user text-white text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <textarea placeholder="Tulis komentar... (tekan Enter untuk mengirim)" 
                                rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm"
                                onkeydown="handleCommentKeydown(event, ${post.id})"
                                required></textarea>
                            <div class="flex justify-end mt-2">
                                <button type="button" class="text-gray-500 text-sm mr-3" onclick="hideQuickComment(${post.id})">Batal</button>
                                <button type="submit" class="bg-orange-600 text-white px-4 py-1.5 rounded-lg hover:bg-orange-700 text-sm">Kirim</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div id="comments-${post.id}" class="hidden mt-4 pt-4 border-t border-gray-100">
                    <!-- Comments will be loaded here -->
                </div>
                ` : ''}
            </div>
        `;
        
        // Auto-load comments preview after element is created (only if comments are allowed)
        setTimeout(() => {
            if (this.permissions.canComment) {
                this.loadCommentsPreview(post.id);
            }
        }, 100);
        
        return postElement;
    }
    
    async toggleLike(postId) {
        try {
            const formData = new FormData();
            formData.append('postingan_id', postId);
            
            const response = await fetch('../logic/handle-like.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update like count in UI
                const likeBtn = document.querySelector(`.like-btn[data-post-id="${postId}"]`);
                if (likeBtn) {
                    const likeCount = likeBtn.querySelector('.like-count');
                    const currentCount = parseInt(likeCount.textContent) || 0;
                    
                    if (result.action === 'liked') {
                        likeCount.textContent = currentCount + 1;
                        likeBtn.classList.add('text-red-500', 'liked');
                    } else {
                        likeCount.textContent = Math.max(0, currentCount - 1);
                        likeBtn.classList.remove('text-red-500', 'liked');
                    }
                }
            } else {
                this.showAlert(result.message || 'Gagal mengubah like', 'error');
            }
        } catch (error) {
            console.error('Error toggling like:', error);
            this.showAlert('Terjadi kesalahan saat mengubah like', 'error');
        }
    }
    
    handleScroll() {
        if (this.isLoading || !this.hasMorePosts) return;
        
        const scrollPosition = window.innerHeight + window.scrollY;
        const threshold = document.body.offsetHeight - 1000;
        
        if (scrollPosition >= threshold) {
            // Remove existing load more indicator
            const loadMoreIndicator = document.getElementById('loadMoreIndicator');
            if (loadMoreIndicator) {
                loadMoreIndicator.remove();
            }
            
            this.loadPostingan();
        }
    }
    
    // Method untuk refresh manual
    refreshPosts() {
        // Stop any ongoing operations and clear any pending timeouts
        this.isLoading = false;
        this.submitInProgress = false;
        this.currentOffset = 0;
        this.hasMorePosts = true;
        
        // Clear any pending staggered timeouts
        if (this.pendingTimeouts) {
            this.pendingTimeouts.forEach(timeout => clearTimeout(timeout));
            this.pendingTimeouts = [];
        }
        
        // Clear container
        const postsContainer = document.getElementById('postsContainer');
        if (postsContainer) {
            postsContainer.innerHTML = '';
        }
        
        // Load fresh posts with delay
        setTimeout(() => {
            this.loadPostingan(true);
        }, 200);
    }
    
    autoResizeTextarea() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
    }
    
    getTimeAgo(date) {
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'Baru saja';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} menit yang lalu`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} jam yang lalu`;
        if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 86400)} hari yang lalu`;
        
        return date.toLocaleDateString('id-ID');
    }
    
    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    showDeleteModal(postId) {
        this.postToDelete = postId;
        const modal = document.getElementById('deletePostModal');
        if (modal) {
            modal.showModal();
        }
    }
    
    hideDeleteModal() {
        this.postToDelete = null;
        const modal = document.getElementById('deletePostModal');
        if (modal) {
            modal.close();
        }
        
        // Reset button state
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const btnText = confirmBtn?.querySelector('.delete-btn-text');
        const btnLoading = confirmBtn?.querySelector('.delete-btn-loading');
        
        if (btnText && btnLoading) {
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
            confirmBtn.disabled = false;
        }
    }
    
    async confirmDeletePost() {
        if (!this.postToDelete) return;
        
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const btnText = confirmBtn?.querySelector('.delete-btn-text');
        const btnLoading = confirmBtn?.querySelector('.delete-btn-loading');
        
        // Show loading state
        if (btnText && btnLoading && confirmBtn) {
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
            confirmBtn.disabled = true;
        }
        
        const formData = new FormData();
        formData.append('post_id', this.postToDelete);
        formData.append('action', 'delete');
        
        try {
            const response = await fetch('../logic/handle-posting.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Close modal first
                this.hideDeleteModal();
                
                // Show success message
                this.showAlert('Postingan berhasil dihapus!', 'success');
                
                // Remove post element from DOM immediately
                const postContainer = document.querySelector(`[data-post-id="${this.postToDelete}"]`);
                if (postContainer) {
                    // Add fade out animation
                    postContainer.style.transition = 'all 0.3s ease-out';
                    postContainer.style.opacity = '0';
                    postContainer.style.transform = 'translateY(-20px)';
                    
                    // Remove from DOM after animation
                    setTimeout(() => {
                        if (postContainer.parentNode) {
                            postContainer.remove();
                        }
                    }, 300);
                } else {
                    // If element not found, force refresh posts
                    console.log('Post element not found, refreshing posts...');
                    this.refreshPosts();
                }
            } else {
                this.showAlert(result.message || 'Gagal menghapus postingan', 'error');
                this.hideDeleteModal();
            }
        } catch (error) {
            console.error('Error deleting post:', error);
            this.showAlert('Terjadi kesalahan saat menghapus postingan', 'error');
            this.hideDeleteModal();
        }
    }
    
    showAlert(message, type = 'info') {
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        alert.innerHTML = `
            <div class="flex items-center">
                <i class="ti ti-${type === 'success' ? 'check' : type === 'error' ? 'x' : 'info-circle'} mr-2"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(alert);
        
        // Animate in
        setTimeout(() => {
            alert.classList.remove('translate-x-full');
        }, 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            alert.classList.add('translate-x-full');
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        }, 3000);
    }
    
    // Comment related methods
    toggleQuickComment(postId) {
        const quickCommentDiv = document.getElementById(`quick-comment-${postId}`);
        
        if (quickCommentDiv.classList.contains('hidden')) {
            // Show quick comment input
            quickCommentDiv.classList.remove('hidden');
            
            // Focus on textarea
            const textarea = quickCommentDiv.querySelector('textarea');
            setTimeout(() => textarea.focus(), 100);
        } else {
            // Hide quick comment input
            quickCommentDiv.classList.add('hidden');
        }
    }
    
    hideQuickComment(postId) {
        const quickCommentDiv = document.getElementById(`quick-comment-${postId}`);
        quickCommentDiv.classList.add('hidden');
    }
    
    async loadCommentsPreview(postId) {
        // Don't load comments if commenting is restricted
        if (!this.permissions.canComment) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'get_comments');
            formData.append('postingan_id', postId);
            
            const response = await fetch('../logic/handle-comment.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.displayCommentsPreview(postId, result.comments);
            }
        } catch (error) {
            console.error('Error loading comments preview:', error);
        }
    }
    
    displayCommentsPreview(postId, comments) {
        const previewDiv = document.getElementById(`comments-preview-${postId}`);
        const viewAllBtn = document.querySelector(`[data-post-id="${postId}"].view-all-comments`);
        
        if (comments.length === 0) {
            previewDiv.style.display = 'none';
        } else {
            // Show max 3 comments
            const displayComments = comments.slice(0, 3);
            const commentsHtml = displayComments.map(comment => this.createCommentElement(comment, true)).join('');
            previewDiv.innerHTML = commentsHtml;
            previewDiv.style.display = 'block';
            
            // Show "view all" button if there are more than 3 comments
            if (comments.length > 3) {
                viewAllBtn.style.display = 'block';
                viewAllBtn.textContent = `Lihat ${comments.length - 3} komentar lainnya`;
            } else {
                viewAllBtn.style.display = 'none';
            }
        }
        
        previewDiv.setAttribute('data-loaded', 'true');
    }
    
    createCommentElement(comment, isPreview = false) {
        const commentDate = new Date(comment.dibuat);
        const timeAgo = this.getTimeAgo(commentDate);
        
        return `
            <div class="flex space-x-3 ${isPreview ? 'py-2' : 'py-3'}">
                <div class="w-6 h-6 rounded-full bg-orange-500 flex items-center justify-center flex-shrink-0">
                    <i class="ti ti-user text-white text-xs"></i>
                </div>
                <div class="flex-1 ${isPreview ? 'text-sm' : ''}">
                    <div class="bg-gray-100 rounded-lg px-3 py-2">
                        <p class="font-medium text-gray-900 text-xs">${this.escapeHtml(comment.namaKomentator)}</p>
                        <p class="text-gray-800 ${isPreview ? 'text-xs' : 'text-sm'}">${this.escapeHtml(comment.komentar)}</p>
                    </div>
                    <div class="flex items-center mt-1 space-x-2 text-xs text-gray-500">
                        <span>${timeAgo}</span>
                        <span>•</span>
                        <span>${comment.role === 'guru' ? 'Guru' : 'Siswa'}</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    async openCommentsModal(postId) {
        const modal = document.getElementById('comments-modal');
        const postIdInput = document.getElementById('modal-post-id');
        const commentsList = document.getElementById('modal-comments-list');
        
        postIdInput.value = postId;
        
        // Show loading state
        commentsList.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="ti ti-loader animate-spin text-2xl mb-2"></i>
                <p>Memuat komentar...</p>
            </div>
        `;
        
        // Open modal
        modal.showModal();
        
        // Load all comments
        await this.loadAllComments(postId);
    }
    
    async loadAllComments(postId) {
        try {
            const formData = new FormData();
            formData.append('action', 'get_comments');
            formData.append('postingan_id', postId);
            
            const response = await fetch('../logic/handle-comment.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            const commentsList = document.getElementById('modal-comments-list');
            
            if (result.success) {
                if (result.comments.length === 0) {
                    commentsList.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <i class="ti ti-message-circle text-3xl mb-2"></i>
                            <p>Belum ada komentar</p>
                            <p class="text-sm mt-1">Jadilah yang pertama berkomentar!</p>
                        </div>
                    `;
                } else {
                    const commentsHtml = result.comments.map(comment => this.createCommentElement(comment, false)).join('');
                    commentsList.innerHTML = commentsHtml;
                }
            } else {
                commentsList.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="ti ti-alert-circle text-3xl mb-2"></i>
                        <p>Gagal memuat komentar</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading all comments:', error);
            const commentsList = document.getElementById('modal-comments-list');
            commentsList.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="ti ti-alert-circle text-3xl mb-2"></i>
                    <p>Terjadi kesalahan</p>
                </div>
            `;
        }
    }
    
    async addComment(postId, comment, isModal = false) {
        try {
            const formData = new FormData();
            formData.append('action', 'add_comment');
            formData.append('postingan_id', postId);
            formData.append('komentar', comment);
            
            const response = await fetch('../logic/handle-comment.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update comment count
                this.updateCommentCount(postId, 1);
                
                if (isModal) {
                    // Reload all comments in modal
                    await this.loadAllComments(postId);
                } else {
                    // Reload preview comments
                    const previewDiv = document.getElementById(`comments-preview-${postId}`);
                    previewDiv.removeAttribute('data-loaded');
                    await this.loadCommentsPreview(postId);
                }
                
                this.showAlert('Komentar berhasil ditambahkan', 'success');
                return true;
            } else {
                this.showAlert(result.message || 'Gagal menambahkan komentar', 'error');
                return false;
            }
        } catch (error) {
            console.error('Error adding comment:', error);
            this.showAlert('Terjadi kesalahan saat menambahkan komentar', 'error');
            return false;
        }
    }
    
    updateCommentCount(postId, delta) {
        const commentBtn = document.querySelector(`[data-post-id="${postId}"].comment-btn .comment-count`);
        if (commentBtn) {
            const currentCount = parseInt(commentBtn.textContent) || 0;
            commentBtn.textContent = Math.max(0, currentCount + delta);
        }
    }
    
    renderPostImages(images) {
        if (!images || images.length === 0) {
            return '';
        }
        
        const imageCount = images.length;
        const gridClass = `post-images-grid grid-${imageCount}`;
        const imageClass = imageCount === 1 ? 'single' : 'multiple';
        
        let imagesHtml = images.map((image, index) => `
            <div class="post-image-item ${imageClass}">
                <img src="../../${image.path_gambar}" 
                     alt="${this.escapeHtml(image.nama_file)}" 
                     class="post-image" 
                     data-image-index="${index}"
                     style="cursor: pointer;"
                     onerror="this.style.display='none'">
            </div>
        `).join('');
        
        return `
            <div class="post-images mt-3">
                <div class="${gridClass}">
                    ${imagesHtml}
                </div>
            </div>
        `;
    }
    
    // Method to reload all posts (useful after edit/delete)
    reloadPosts() {
        this.currentOffset = 0;
        this.hasMorePosts = true;
        this.loadPostingan(true);
    }
    
    // Method to refresh a single post
    async refreshPost(postId) {
        try {
            const response = await fetch(`../logic/get-postingan.php?kelas_id=${this.kelasId}&postingan_id=${postId}`);
            const data = await response.json();
            
            if (data.success && data.data && data.data.length > 0) {
                const post = data.data[0];
                const existingElement = document.querySelector(`[data-post-id="${postId}"]`);
                if (existingElement) {
                    const postContainer = existingElement.closest('.bg-white');
                    if (postContainer) {
                        const newElement = this.createPostElement(post, data.user_id, data.user_role);
                        postContainer.replaceWith(newElement);
                    }
                }
            }
        } catch (error) {
            console.error('Error refreshing post:', error);
        }
    }

    renderAssignmentContent(post) {
        if (post.tipe_postingan !== 'assignment' || !post.assignment_id) {
            return '';
        }

        const currentUserRole = window.currentUserRole;
        console.log('🎯 renderAssignmentContent:', {
            postId: post.id,
            assignmentId: post.assignment_id,
            currentUserRole,
            submissionStatus: post.student_submission_status
        });
        
        const isDeadlinePassed = post.assignment_deadline && new Date(post.assignment_deadline) < new Date();
        
        // Format deadline
        const formatDeadline = (deadline) => {
            if (!deadline) return '';
            const date = new Date(deadline);
            return date.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        };

        // Format deadline for mobile (shorter)
        const formatDeadlineMobile = (deadline) => {
            if (!deadline) return '';
            const date = new Date(deadline);
            return date.toLocaleDateString('id-ID', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        };

        // Get file icon based on extension
        const getFileIcon = (filename) => {
            if (!filename) return 'ti ti-file';
            const ext = filename.toLowerCase().split('.').pop();
            switch (ext) {
                case 'pdf': return 'ti ti-file-type-pdf';
                case 'doc':
                case 'docx': return 'ti ti-file-type-doc';
                case 'xls':
                case 'xlsx': return 'ti ti-file-type-xls';
                case 'ppt':
                case 'pptx': return 'ti ti-file-type-ppt';
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif': return 'ti ti-photo';
                case 'mp4':
                case 'avi':
                case 'mov': return 'ti ti-video';
                case 'mp3':
                case 'wav': return 'ti ti-music';
                case 'zip':
                case 'rar': return 'ti ti-file-zip';
                default: return 'ti ti-file';
            }
        };

        // Assignment header with info
        let assignmentHeader = `
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-lg p-4 mt-3">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="ti ti-clipboard-text text-purple-600 text-lg"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">${this.escapeHtml(post.assignment_title)}</h3>
                        
                        ${post.assignment_description ? `
                            <div class="text-sm text-gray-700 mb-3">
                                <i class="ti ti-align-left mr-2"></i>${this.escapeHtml(post.assignment_description)}
                            </div>
                        ` : ''}
                        
                        <div class="flex flex-wrap gap-4 text-sm">
                            ${post.assignment_deadline ? `
                                <div class="flex items-center text-gray-600 min-w-0">
                                    <i class="ti ti-calendar-due mr-2 text-red-500 flex-shrink-0"></i>
                                    <span class="${isDeadlinePassed ? 'text-red-600 font-medium' : ''} truncate">
                                        <span class="hidden sm:inline">${formatDeadline(post.assignment_deadline)}</span>
                                        <span class="sm:hidden">${formatDeadlineMobile(post.assignment_deadline)}</span>
                                    </span>
                                </div>
                            ` : ''}
                            
                            ${post.assignment_max_score ? `
                                <div class="flex items-center text-gray-600 flex-shrink-0">
                                    <i class="ti ti-trophy mr-2 text-yellow-500"></i>
                                    <span class="whitespace-nowrap">Nilai Maks: ${post.assignment_max_score}</span>
                                </div>
                            ` : ''}
                        </div>
                        
                        ${post.assignment_file_path ? `
                            <div class="mt-3 p-3 bg-white rounded-lg border border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <i class="${getFileIcon(post.assignment_file_path)} text-blue-600 text-lg flex-shrink-0"></i>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900">File Tugas</div>
                                        <div class="text-xs text-gray-500 truncate">${post.assignment_file_path.split('/').pop()}</div>
                                    </div>
                                    <a href="/lms${post.assignment_file_path.startsWith('/') ? '' : '/'}${post.assignment_file_path}" target="_blank" 
                                       class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center flex-shrink-0">
                                        <i class="ti ti-download"></i>
                                        <span class="ml-1 hidden sm:inline">Download</span>
                                    </a>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        let assignmentActions = '';
        
        if (currentUserRole === 'siswa') {
            if (isDeadlinePassed) {
                assignmentActions = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 mt-3">
                        <div class="flex items-center">
                            <i class="ti ti-clock-x text-red-600 mr-2"></i>
                            <span class="text-sm text-red-800">Deadline telah terlewat</span>
                        </div>
                    </div>
                `;
            } else {
                const submissionStatus = post.student_submission_status;
                if (submissionStatus === 'dinilai') {
                    assignmentActions = `
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mt-3">
                            <div class="space-y-3">
                                <!-- Header with status -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                            <i class="ti ti-check text-green-600"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-green-800">Tugas telah dinilai</div>
                                            <div class="text-sm text-green-600">Selamat! Tugas Anda sudah mendapat nilai</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-green-600">${post.student_score}</div>
                                        <div class="text-sm text-gray-500">dari ${post.assignment_max_score}</div>
                                    </div>
                                </div>
                                
                                <!-- Progress indicators -->
                                <div class="flex items-center space-x-4 text-sm">
                                    <div class="flex items-center text-green-600">
                                        <div class="w-4 h-4 bg-green-600 rounded-full mr-2"></div>
                                        <span>Tugas terkirim</span>
                                    </div>
                                    <div class="flex items-center text-green-600">
                                        <div class="w-4 h-4 bg-green-600 rounded-full mr-2"></div>
                                        <span>Telah dinilai guru</span>
                                    </div>
                                </div>
                                
                                ${post.student_feedback ? `
                                    <div class="bg-white border border-green-200 rounded-lg p-3">
                                        <div class="flex items-start space-x-2">
                                            <i class="ti ti-message-circle text-green-600 mt-1"></i>
                                            <div>
                                                <div class="font-medium text-green-800 mb-1">Komentar Guru:</div>
                                                <div class="text-sm text-gray-700">${this.escapeHtml(post.student_feedback)}</div>
                                            </div>
                                        </div>
                                    </div>
                                ` : ''}
                                
                                <button onclick="showSubmissionForm(${post.assignment_id})" 
                                        class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    <i class="ti ti-refresh mr-1"></i>Kumpulkan Ulang
                                </button>
                            </div>
                        </div>
                    `;
                } else if (submissionStatus === 'dikumpulkan') {
                    assignmentActions = `
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-3">
                            <div class="space-y-3">
                                <!-- Header with status -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center mr-3">
                                            <i class="ti ti-clock text-yellow-600"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-yellow-800">Tugas telah dikumpulkan</div>
                                            <div class="text-sm text-yellow-600">Menunggu penilaian dari guru</div>
                                        </div>
                                    </div>
                                    <div class="text-yellow-600">
                                        <i class="ti ti-hourglass text-2xl"></i>
                                    </div>
                                </div>
                                
                                <!-- Progress indicators -->
                                <div class="flex items-center space-x-4 text-sm">
                                    <div class="flex items-center text-green-600">
                                        <div class="w-4 h-4 bg-green-600 rounded-full mr-2"></div>
                                        <span>Tugas terkirim</span>
                                    </div>
                                    <div class="flex items-center text-gray-400">
                                        <div class="w-4 h-4 bg-gray-300 rounded-full mr-2"></div>
                                        <span>Menunggu penilaian</span>
                                    </div>
                                </div>
                                
                                <button onclick="showSubmissionForm(${post.assignment_id})" 
                                        class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    <i class="ti ti-refresh mr-1"></i>Kumpulkan Ulang
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    assignmentActions = `
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-3">
                            <div class="assignment-submission-area" id="submission-area-${post.assignment_id}">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center">
                                        <i class="ti ti-upload text-blue-600 mr-2"></i>
                                        <span class="text-sm text-blue-800">Belum mengumpulkan tugas</span>
                                    </div>
                                    <button onclick="showSubmissionForm(${post.assignment_id})" 
                                            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                                        Kumpulkan Tugas
                                    </button>
                                </div>
                                
                                <!-- Hidden submission form -->
                                <div id="submission-form-${post.assignment_id}" class="hidden mt-4 space-y-4">
                                    <div class="border-t border-blue-200 pt-4">
                                        <h4 class="font-medium text-gray-900 mb-3">Kumpulkan Tugas: ${this.escapeHtml(post.assignment_title)}</h4>
                                        
                                        <!-- File upload area -->
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File</label>
                                            <div class="flex items-center space-x-3">
                                                <input type="file" id="submission-file-${post.assignment_id}" 
                                                       accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif"
                                                       class="hidden" onchange="handleSubmissionFileSelect(${post.assignment_id}, this)">
                                                <button onclick="document.getElementById('submission-file-${post.assignment_id}').click()" 
                                                        class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-medium border border-gray-300">
                                                    <i class="ti ti-paperclip mr-2"></i>Pilih File
                                                </button>
                                                <span class="text-sm text-gray-500">Format: PDF, DOC, PPT, gambar (Max 10MB)</span>
                                            </div>
                                        </div>
                                        
                                        <!-- File preview area -->
                                        <div id="submission-preview-${post.assignment_id}" class="hidden mb-4">
                                            <div class="bg-white border border-gray-200 rounded-lg p-3">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-3">
                                                        <div id="file-icon-${post.assignment_id}" class="text-blue-600 text-lg">
                                                            <i class="ti ti-file"></i>
                                                        </div>
                                                        <div>
                                                            <div id="file-name-${post.assignment_id}" class="text-sm font-medium text-gray-900"></div>
                                                            <div id="file-size-${post.assignment_id}" class="text-xs text-gray-500"></div>
                                                        </div>
                                                    </div>
                                                    <button onclick="removeSubmissionFile(${post.assignment_id})" 
                                                            class="text-red-600 hover:text-red-800 p-1 rounded">
                                                        <i class="ti ti-x"></i>
                                                    </button>
                                                </div>
                                                <!-- Image preview for images -->
                                                <div id="image-preview-${post.assignment_id}" class="hidden mt-3">
                                                    <img class="max-w-full h-48 object-cover rounded-lg">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Notes -->
                                        <div class="mb-4">
                                            <label for="submission-notes-${post.assignment_id}" class="block text-sm font-medium text-gray-700 mb-2">
                                                Catatan (Opsional)
                                            </label>
                                            <textarea id="submission-notes-${post.assignment_id}" rows="3" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Tambahkan catatan untuk guru..."></textarea>
                                        </div>
                                        
                                        <!-- Action buttons -->
                                        <div class="flex justify-between items-center">
                                            <button onclick="hideSubmissionForm(${post.assignment_id})" 
                                                    class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                                                Batal
                                            </button>
                                            <button onclick="submitAssignment(${post.assignment_id})" 
                                                    id="submit-btn-${post.assignment_id}"
                                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                                <i class="ti ti-send mr-2"></i>Kumpulkan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            }
        } else if (currentUserRole === 'guru') {
            assignmentActions = `
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mt-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="text-center">
                                <div class="text-lg font-bold text-green-600">${post.assignment_submitted_count || 0}</div>
                                <div class="text-xs text-gray-600">Terkumpul</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-blue-600">${post.assignment_graded_count || 0}</div>
                                <div class="text-xs text-gray-600">Dinilai</div>
                            </div>
                        </div>
                        <button onclick="openAssignmentReports(${post.assignment_id})" class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors">
                            Lihat Laporan
                        </button>
                    </div>
                </div>
            `;
        }

        return assignmentHeader + assignmentActions;
    }
}

// Global functions for dropdown and actions
function toggleDropdown(button) {
    const dropdown = button.nextElementSibling;
    dropdown.classList.toggle('hidden');
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function closeDropdown(e) {
        if (!button.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
            document.removeEventListener('click', closeDropdown);
        }
    });
}

function editPost(postId) {
    console.log('Edit post:', postId);
    // TODO: Implement edit functionality
}

function deletePost(postId) {
    if (window.kelasPosting) {
        window.kelasPosting.showDeleteModal(postId);
    }
}

function toggleComments(postId) {
    const commentsDiv = document.getElementById(`comments-${postId}`);
    commentsDiv.classList.toggle('hidden');
    
    if (!commentsDiv.classList.contains('hidden') && !commentsDiv.hasAttribute('data-loaded')) {
        // TODO: Load comments
        commentsDiv.innerHTML = '<p class="text-gray-500 text-sm">Fitur komentar akan segera hadir...</p>';
        commentsDiv.setAttribute('data-loaded', 'true');
    }
}

// Comment related global functions
function handleCommentKeydown(event, postId) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        const form = event.target.closest('form');
        if (form) {
            form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }
    }
}

async function addQuickComment(event, postId) {
    event.preventDefault();
    
    const form = event.target;
    const textarea = form.querySelector('textarea');
    const comment = textarea.value.trim();
    
    if (!comment) {
        return;
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Mengirim...';
    submitBtn.disabled = true;
    
    const success = await window.kelasPosting.addComment(postId, comment, false);
    
    if (success) {
        textarea.value = '';
    }
    
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
}

function hideQuickComment(postId) {
    if (window.kelasPosting) {
        window.kelasPosting.hideQuickComment(postId);
    }
}

function closeCommentsModal() {
    const modal = document.getElementById('comments-modal');
    modal.close();
}

// Modal comment form handler
document.addEventListener('DOMContentLoaded', function() {
    const modalCommentForm = document.getElementById('modal-comment-form');
    if (modalCommentForm) {
        modalCommentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const postId = document.getElementById('modal-post-id').value;
            const textarea = document.getElementById('modal-comment-input');
            const comment = textarea.value.trim();
            
            if (!comment) {
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Mengirim...';
            submitBtn.disabled = true;
            
            const success = await window.kelasPosting.addComment(postId, comment, true);
            
            if (success) {
                textarea.value = '';
            }
            
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
        
        // Add Enter key support for modal textarea
        const modalTextarea = document.getElementById('modal-comment-input');
        if (modalTextarea) {
            modalTextarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    modalCommentForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                }
            });
        }
    }
});

// Global function to open assignment reports
function openAssignmentReports(assignmentId) {
    const currentUrl = new URL(window.location.href);
    const kelasId = currentUrl.searchParams.get('id');
    
    if (kelasId) {
        const reportUrl = `assignment-reports.php?id=${kelasId}${assignmentId ? `&assignment_id=${assignmentId}` : ''}`;
        window.location.href = reportUrl;
    } else {
        console.error('Kelas ID tidak ditemukan di URL');
        alert('Gagal membuka laporan tugas');
    }
}

// Assignment submission functions
function showSubmissionForm(assignmentId) {
    console.log('🎯 showSubmissionForm called with ID:', assignmentId);
    const form = document.getElementById(`submission-form-${assignmentId}`);
    const button = form?.previousElementSibling?.querySelector('button');
    
    if (form && button) {
        form.classList.remove('hidden');
        button.textContent = 'Batal';
        button.onclick = () => hideSubmissionForm(assignmentId);
        console.log('✅ Form expanded successfully');
    } else {
        console.error('❌ Form or button not found:', {
            form: !!form,
            button: !!button,
            assignmentId
        });
    }
}

function hideSubmissionForm(assignmentId) {
    const form = document.getElementById(`submission-form-${assignmentId}`);
    const button = form.previousElementSibling.querySelector('button');
    
    if (form && button) {
        form.classList.add('hidden');
        button.textContent = 'Kumpulkan Tugas';
        button.onclick = () => showSubmissionForm(assignmentId);
        
        // Reset form
        removeSubmissionFile(assignmentId);
        document.getElementById(`submission-notes-${assignmentId}`).value = '';
    }
}

function handleSubmissionFileSelect(assignmentId, input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validate file size (10MB)
    const maxSize = 10 * 1024 * 1024;
    if (file.size > maxSize) {
        alert('Ukuran file terlalu besar. Maksimal 10MB');
        input.value = '';
        return;
    }
    
    // Show preview
    const preview = document.getElementById(`submission-preview-${assignmentId}`);
    const fileIcon = document.getElementById(`file-icon-${assignmentId}`);
    const fileName = document.getElementById(`file-name-${assignmentId}`);
    const fileSize = document.getElementById(`file-size-${assignmentId}`);
    const imagePreview = document.getElementById(`image-preview-${assignmentId}`);
    
    // Set file info
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    
    // Set appropriate icon
    const ext = file.name.toLowerCase().split('.').pop();
    fileIcon.innerHTML = getFileIconHtml(ext);
    
    // Show image preview if it's an image
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.querySelector('img').src = e.target.result;
            imagePreview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        imagePreview.classList.add('hidden');
    }
    
    preview.classList.remove('hidden');
    
    // Enable submit button
    const submitBtn = document.getElementById(`submit-btn-${assignmentId}`);
    submitBtn.disabled = false;
}

function removeSubmissionFile(assignmentId) {
    const input = document.getElementById(`submission-file-${assignmentId}`);
    const preview = document.getElementById(`submission-preview-${assignmentId}`);
    const submitBtn = document.getElementById(`submit-btn-${assignmentId}`);
    const imagePreview = document.getElementById(`image-preview-${assignmentId}`);
    
    input.value = '';
    preview.classList.add('hidden');
    imagePreview.classList.add('hidden');
    submitBtn.disabled = true;
}

async function submitAssignment(assignmentId) {
    const fileInput = document.getElementById(`submission-file-${assignmentId}`);
    const notesInput = document.getElementById(`submission-notes-${assignmentId}`);
    const submitBtn = document.getElementById(`submit-btn-${assignmentId}`);
    
    if (!fileInput.files[0]) {
        alert('Silakan pilih file terlebih dahulu');
        return;
    }
    
    // Show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ti ti-loader animate-spin mr-2"></i>Mengirim...';
    
    try {
        const formData = new FormData();
        formData.append('assignment_id', assignmentId);
        formData.append('submission_file', fileInput.files[0]);
        formData.append('notes', notesInput.value);
        
        const response = await fetch('../logic/submit-assignment.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success and reload posts
            alert('Tugas berhasil dikumpulkan!');
            if (window.kelasPosting) {
                window.kelasPosting.loadPostingan(true);
            }
        } else {
            throw new Error(data.message || 'Gagal mengumpulkan tugas');
        }
        
    } catch (error) {
        console.error('Error submitting assignment:', error);
        alert('Terjadi kesalahan: ' + error.message);
        
        // Reset button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-send mr-2"></i>Kumpulkan';
    }
}

// Helper functions
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function getFileIconHtml(extension) {
    const iconMap = {
        'pdf': '<i class="ti ti-file-type-pdf text-red-600"></i>',
        'doc': '<i class="ti ti-file-type-doc text-blue-600"></i>',
        'docx': '<i class="ti ti-file-type-doc text-blue-600"></i>',
        'xls': '<i class="ti ti-file-type-xls text-green-600"></i>',
        'xlsx': '<i class="ti ti-file-type-xls text-green-600"></i>',
        'ppt': '<i class="ti ti-file-type-ppt text-orange-600"></i>',
        'pptx': '<i class="ti ti-file-type-ppt text-orange-600"></i>',
        'jpg': '<i class="ti ti-photo text-purple-600"></i>',
        'jpeg': '<i class="ti ti-photo text-purple-600"></i>',
        'png': '<i class="ti ti-photo text-purple-600"></i>',
        'gif': '<i class="ti ti-photo text-purple-600"></i>',
        'txt': '<i class="ti ti-file-text text-gray-600"></i>'
    };
    
    return iconMap[extension] || '<i class="ti ti-file text-gray-600"></i>';
}
