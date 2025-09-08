// Kelas Posting System - Stable Version
class KelasPosting {
    constructor(kelasId, permissions = null) {
        this.kelasId = kelasId;
        this.permissions = permissions || {
            canPost: true,
            canComment: true
        };
        this.currentOffset = 0;
        this.limit = 5; // Reduced from 10 to 5 for better performance
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
        
        // Add selected media if any
        if (window.mediaUpload && window.mediaUpload.selectedFiles.length > 0) {
            const selectedFiles = window.mediaUpload.getSelectedFiles();
            console.log('Adding', selectedFiles.length, 'media files to form data');
            selectedFiles.forEach((file, index) => {
                console.log('Adding media:', file.name, 'size:', file.size);
                formData.append('media[]', file);
            });
        } else if (window.imageUpload && window.imageUpload.selectedFiles.length > 0) {
            // Backward compatibility with old image upload system
            const selectedFiles = window.imageUpload.getSelectedFiles();
            console.log('Adding', selectedFiles.length, 'images to form data (legacy)');
            selectedFiles.forEach((file, index) => {
                console.log('Adding image:', file.name, 'size:', file.size);
                formData.append('images[]', file);
            });
        } else {
            console.log('No media selected for upload');
        }
        
        // Add selected files if any
        if (window.fileUploadManager && window.fileUploadManager.selectedFiles.length > 0) {
            const selectedFiles = window.fileUploadManager.getSelectedFiles();
            console.log('Adding', selectedFiles.length, 'files to form data');
            selectedFiles.forEach((file, index) => {
                console.log('Adding file:', file.name, 'size:', file.size);
                formData.append('files[]', file);
            });
        } else {
            console.log('No files selected for upload');
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
                
                // Clear selected media
                if (window.mediaUpload) {
                    window.mediaUpload.clearSelection();
                } else if (window.imageUpload) {
                    window.imageUpload.clearSelection();
                }
                
                // Clear selected files
                if (window.fileUploadManager) {
                    window.fileUploadManager.clearFiles();
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
                    
                    // Remove scroll loading indicator
                    this.hideLoadingIndicator();
                    
                    // Add posts with optimized rendering
                    result.data.forEach((post, index) => {
                        const timeoutId = setTimeout(() => {
                            const postElement = this.createPostElement(post, result.user_id, result.user_role);
                            if (postsContainer && postElement) {
                                postsContainer.appendChild(postElement);
                                
                                // Lazy load comments preview for better performance
                                if (this.permissions.canComment) {
                                    setTimeout(() => {
                                        this.loadCommentsPreview(post.id);
                                    }, 200 * (index + 1)); // Staggered comment loading
                                }
                            }
                            // Remove this timeout from pending list
                            const timeoutIndex = this.pendingTimeouts.indexOf(timeoutId);
                            if (timeoutIndex > -1) {
                                this.pendingTimeouts.splice(timeoutIndex, 1);
                            }
                        }, index * 50); // Reduced delay for faster rendering
                        
                        // Track this timeout
                        this.pendingTimeouts.push(timeoutId);
                    });
                    
                    this.currentOffset += result.data.length;
                    
                    if (result.data.length < this.limit) {
                        this.hasMorePosts = false;
                    }
                    
                    // Add load more indicator with improved styling
                    if (this.hasMorePosts) {
                        const loadMoreTimeoutId = setTimeout(() => {
                            const loadMoreElement = document.createElement('div');
                            loadMoreElement.id = 'loadMoreIndicator';
                            loadMoreElement.className = 'text-center py-4 text-gray-400 border-t border-gray-100 mt-4';
                            loadMoreElement.innerHTML = `
                                <div class="flex items-center justify-center space-x-2">
                                    <i class="ti ti-chevron-down text-sm animate-bounce"></i>
                                    <span class="text-sm">Scroll untuk memuat ${this.limit} postingan lagi</span>
                                    <i class="ti ti-chevron-down text-sm animate-bounce"></i>
                                </div>
                            `;
                            if (postsContainer) {
                                postsContainer.appendChild(loadMoreElement);
                            }
                            // Remove this timeout from pending list
                            const timeoutIndex = this.pendingTimeouts.indexOf(loadMoreTimeoutId);
                            if (timeoutIndex > -1) {
                                this.pendingTimeouts.splice(timeoutIndex, 1);
                            }
                        }, result.data.length * 50 + 100); // Reduced delay
                        
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
        
        // Debug: Log post data untuk troubleshooting
        console.log('🔍 Creating post element:', {
            id: post.id,
            konten: post.konten,
            tipePost: post.tipePost,
            namaPenulis: post.namaPenulis
        });
        
        // Build profile photo HTML for post author
        let profilePhotoHtml = '';
        if (post.fotoProfil && post.fotoProfil.trim() !== '') {
            // Check if it already contains the full path
            let photoPath = '';
            if (post.fotoProfil.indexOf('uploads/profile/') === 0) {
                photoPath = '../../' + post.fotoProfil;
            } else {
                photoPath = '../../uploads/profile/' + post.fotoProfil;
            }
            
            profilePhotoHtml = `
                <img src="${photoPath}" 
                     alt="Profile Photo" 
                     class="w-full h-full object-cover"
                     onerror="this.parentElement.innerHTML='<i class=\\'ti ti-user text-white\\'></i>'">
            `;
        } else {
            // Fallback with role-based colors
            let bgColorClass = 'bg-orange-500';
            switch(post.rolePenulis) {
                case 'admin':
                    bgColorClass = 'bg-red-500';
                    break;
                case 'guru':
                    bgColorClass = 'bg-blue-500';
                    break;
                case 'siswa':
                    bgColorClass = 'bg-green-500';
                    break;
                default:
                    bgColorClass = 'bg-orange-500';
            }
            
            profilePhotoHtml = `<i class="ti ti-user text-white"></i>`;
        }
        
        const postElement = document.createElement('div');
        postElement.className = 'bg-white rounded-lg shadow-sm mb-6';
        postElement.setAttribute('data-post-id', post.id);
        postElement.setAttribute('data-user-id', post.user_id);
        
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
                    <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-full ${post.fotoProfil ? 'overflow-hidden' : 'bg-orange-500'} flex items-center justify-center">
                        ${profilePhotoHtml}
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
                    <!-- Post Content -->
                    ${post.konten ? `
                        <div class="text-gray-900 text-sm lg:text-base mb-3" style="line-height: 1.6; white-space: pre-wrap;">${this.escapeHtml(post.konten)}</div>
                    ` : ''}
                    ${post.deadline ? `
                        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center">
                                <i class="ti ti-clock text-yellow-600 mr-2"></i>
                                <span class="text-sm text-yellow-800">Deadline: ${new Date(post.deadline).toLocaleDateString('id-ID')}</span>
                            </div>
                        </div>
                    ` : ''}
                    ${this.renderAssignmentContent(post)}
                    ${this.renderPostMedia(post.gambar)}
                    ${this.renderPostFiles(post.files)}
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div class="flex items-center space-x-4 lg:space-x-6">
                        <button class="like-btn flex items-center space-x-2 ${post.userLiked ? 'text-red-600' : 'text-gray-600'} hover:text-red-600 transition-colors text-sm lg:text-base" 
                                data-post-id="${post.id}" 
                                data-liked="${post.userLiked ? 'true' : 'false'}">
                            <i class="ti ti-heart${post.userLiked ? '-filled text-red-600' : ''}"></i>
                            <span class="like-count">${post.jumlahLike || 0}</span>
                        </button>
                        ${this.permissions.canComment ? `
                        <button class="comment-btn flex items-center space-x-2 text-gray-600 hover:text-blue-600 transition-colors text-sm lg:text-base" data-post-id="${post.id}">
                            <i class="ti ti-message-circle"></i>
                            <span class="comment-count">${post.jumlahKomentar || 0}</span>
                        </button>
                        ` : ''}
                        <button class="flex items-center space-x-2 text-gray-600 hover:text-gray-800 transition-colors text-sm lg:text-base">
                            <i class="ti ti-share"></i>
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
                    const heartIcon = likeBtn.querySelector('i');
                    const currentCount = parseInt(likeCount.textContent) || 0;
                    
                    if (result.action === 'liked') {
                        likeCount.textContent = currentCount + 1;
                        likeBtn.classList.remove('text-gray-600');
                        likeBtn.classList.add('text-red-600');
                        likeBtn.setAttribute('data-liked', 'true');
                        if (heartIcon) {
                            heartIcon.className = 'ti ti-heart-filled text-red-600';
                        }
                    } else {
                        likeCount.textContent = Math.max(0, currentCount - 1);
                        likeBtn.classList.remove('text-red-600');
                        likeBtn.classList.add('text-gray-600');
                        likeBtn.setAttribute('data-liked', 'false');
                        if (heartIcon) {
                            heartIcon.className = 'ti ti-heart';
                        }
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
        
        // Improved scroll detection for better lazy loading
        const scrollPosition = window.innerHeight + window.scrollY;
        const threshold = document.body.offsetHeight - 500; // Reduced threshold for earlier loading
        
        if (scrollPosition >= threshold) {
            // Remove existing load more indicator
            const loadMoreIndicator = document.getElementById('loadMoreIndicator');
            if (loadMoreIndicator) {
                loadMoreIndicator.remove();
            }
            
            // Show loading indicator immediately
            this.showLoadingIndicator();
            this.loadPostingan();
        }
    }
    
    // Add loading indicator for better UX
    showLoadingIndicator() {
        const postsContainer = document.getElementById('postsContainer');
        if (!postsContainer) return;
        
        // Remove existing indicator first
        const existingIndicator = document.getElementById('scrollLoadingIndicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }
        
        const loadingElement = document.createElement('div');
        loadingElement.id = 'scrollLoadingIndicator';
        loadingElement.className = 'text-center py-6 text-gray-500';
        loadingElement.innerHTML = `
            <div class="flex items-center justify-center space-x-2">
                <i class="ti ti-loader animate-spin text-xl"></i>
                <span class="text-sm">Memuat postingan...</span>
            </div>
        `;
        postsContainer.appendChild(loadingElement);
    }
    
    // Remove loading indicator
    hideLoadingIndicator() {
        const indicator = document.getElementById('scrollLoadingIndicator');
        if (indicator) {
            indicator.remove();
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
        if (unsafe === null || unsafe === undefined) {
            return '';
        }
        return String(unsafe)
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
            console.log('Comments data for post', postId, ':', result);
            
            if (result.success) {
                this.displayCommentsPreview(postId, result.comments);
            } else {
                console.error('Failed to load comments:', result.message);
            }
        } catch (error) {
            console.error('Error loading comments preview:', error);
        }
    }
    
    displayCommentsPreview(postId, comments) {
        console.log('Displaying comments preview for post', postId, comments);
        const previewDiv = document.getElementById(`comments-preview-${postId}`);
        const viewAllBtn = document.querySelector(`[data-post-id="${postId}"].view-all-comments`);
        
        if (!previewDiv) {
            console.error('Preview div not found for post', postId);
            return;
        }
        
        if (comments.length === 0) {
            previewDiv.style.display = 'none';
        } else {
            // Show max 3 comments
            const displayComments = comments.slice(0, 3);
            console.log('Creating comment elements for', displayComments);
            const commentsHtml = displayComments.map(comment => this.createCommentElement(comment, true)).join('');
            previewDiv.innerHTML = commentsHtml;
            previewDiv.style.display = 'block';
            
            // Show "view all" button if there are more than 3 comments
            if (comments.length > 3) {
                if (viewAllBtn) {
                    viewAllBtn.style.display = 'block';
                    viewAllBtn.textContent = `Lihat ${comments.length - 3} komentar lainnya`;
                }
            } else {
                if (viewAllBtn) {
                    viewAllBtn.style.display = 'none';
                }
            }
        }
        
        previewDiv.setAttribute('data-loaded', 'true');
    }
    
    createCommentElement(comment, isPreview = false) {
        const commentDate = comment.dibuat ? new Date(comment.dibuat) : new Date();
        const timeAgo = this.getTimeAgo(commentDate);
        
        // Build profile photo HTML
        let profilePhotoHtml = '';
        if (comment.fotoProfil && comment.fotoProfil.trim() !== '') {
            // Check if it already contains the full path
            let photoPath = '';
            if (comment.fotoProfil.indexOf('uploads/profile/') === 0) {
                photoPath = '../../' + comment.fotoProfil;
            } else {
                photoPath = '../../uploads/profile/' + comment.fotoProfil;
            }
            
            profilePhotoHtml = `
                <img src="${photoPath}" 
                     alt="Profile Photo" 
                     class="w-full h-full object-cover"
                     onerror="this.parentElement.innerHTML='<i class=\\'ti ti-user text-white text-xs\\'></i>'">
            `;
        } else {
            // Fallback with role-based colors
            let bgColorClass = 'bg-orange-500';
            switch(comment.role) {
                case 'admin':
                    bgColorClass = 'bg-red-500';
                    break;
                case 'guru':
                    bgColorClass = 'bg-blue-500';
                    break;
                case 'siswa':
                    bgColorClass = 'bg-green-500';
                    break;
                default:
                    bgColorClass = 'bg-orange-500';
            }
            
            profilePhotoHtml = `<i class="ti ti-user text-white text-xs"></i>`;
        }
        
        return `
            <div class="flex space-x-3 ${isPreview ? 'py-2' : 'py-3'}" data-user-id="${comment.user_id || ''}">
                <div class="w-6 h-6 rounded-full ${comment.fotoProfil ? 'overflow-hidden' : 'bg-orange-500'} flex items-center justify-center flex-shrink-0">
                    ${profilePhotoHtml}
                </div>
                <div class="flex-1 ${isPreview ? 'text-sm' : ''}">
                    <div class="bg-gray-100 rounded-lg px-3 py-2">
                        <p class="font-medium text-gray-900 text-xs">${this.escapeHtml(comment.nama_penulis || comment.namaKomentator)}</p>
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
    
    renderPostMedia(mediaFiles) {
        if (!mediaFiles || mediaFiles.length === 0) {
            return '';
        }
        
        const mediaCount = mediaFiles.length;
        const gridClass = `post-media-grid grid-${mediaCount}`;
        const mediaClass = mediaCount === 1 ? 'single' : 'multiple';
        
        let mediaHtml = mediaFiles.map((media, index) => {
            const isVideo = media.media_type === 'video' || media.tipe_file.startsWith('video/');
            const mediaPath = `../../${media.path_gambar}`;
            
            if (isVideo) {
                return `
                    <div class="post-media-item ${mediaClass}">
                        <video controls 
                               class="post-media" 
                               data-media-index="${index}"
                               preload="metadata">
                            <source src="${mediaPath}" type="${media.tipe_file}">
                            Your browser does not support the video tag.
                        </video>
                        <div class="post-media-type-badge video">
                            <i class="ti ti-video"></i> Video
                        </div>
                        <button class="media-download-btn" onclick="downloadMedia('${mediaPath}', '${this.escapeHtml(media.nama_file)}')" title="Download Video">
                            <i class="ti ti-download"></i>
                        </button>
                    </div>
                `;
            } else {
                return `
                    <div class="post-media-item ${mediaClass}">
                        <img src="${mediaPath}" 
                             alt="${this.escapeHtml(media.nama_file)}" 
                             class="post-media" 
                             data-media-index="${index}"
                             style="cursor: pointer;"
                             onerror="this.style.display='none'">
                        <div class="post-media-type-badge image">
                            <i class="ti ti-photo"></i> Gambar
                        </div>
                        <button class="media-download-btn" onclick="downloadMedia('${mediaPath}', '${this.escapeHtml(media.nama_file)}')" title="Download Gambar">
                            <i class="ti ti-download"></i>
                        </button>
                    </div>
                `;
            }
        }).join('');
        
        return `
            <div class="post-media-container mt-3">
                <div class="${gridClass}">
                    ${mediaHtml}
                </div>
            </div>
        `;
    }
}

// Global function for media download
window.downloadMedia = function(mediaPath, fileName) {
    const link = document.createElement('a');
    link.href = mediaPath;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

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
    
    // Add backdrop click to close modal
    const commentsModal = document.getElementById('comments-modal');
    if (commentsModal) {
        commentsModal.addEventListener('click', function(e) {
            if (e.target === commentsModal) {
                closeCommentsModal();
            }
        });
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

// Assignment Navigation and Highlight System
class AssignmentNavigator {
    constructor() {
        this.init();
    }

    init() {
        // Add click handlers to assignment items in sidebar
        this.addSidebarClickHandlers();
    }

    // Public method to refresh handlers (useful if DOM changes)
    refreshHandlers() {
        console.log('🔄 Refreshing assignment handlers...');
        this.addSidebarClickHandlers();
    }

    addSidebarClickHandlers() {
        // Use a more reliable method to wait for assignments to be rendered
        const checkAssignments = () => {
            const assignmentCards = document.querySelectorAll('.assignment-card');
            console.log('🎯 Found assignment cards:', assignmentCards.length);
            
            if (assignmentCards.length > 0) {
                assignmentCards.forEach((card, index) => {
                    const assignmentId = card.getAttribute('data-assignment-id');
                    console.log(`📋 Assignment card ${index + 1}:`, { element: card, assignmentId });
                    
                    // Remove existing listeners to prevent duplicates
                    card.removeEventListener('click', this.handleAssignmentClick);
                    
                    // Add new listener
                    card.addEventListener('click', (e) => {
                        e.preventDefault();
                        console.log('🖱️ Assignment card clicked:', assignmentId);
                        if (assignmentId) {
                            this.scrollToAssignment(assignmentId);
                        }
                    });
                });
            } else {
                // Retry if no cards found yet
                setTimeout(checkAssignments, 500);
            }
        };
        
        // Start checking immediately and retry if needed
        checkAssignments();
    }

    async scrollToAssignment(assignmentId) {
        console.log('🎯 Scrolling to assignment:', assignmentId);
        
        // First, try to find the assignment post in current loaded posts
        let assignmentPost = this.findAssignmentPost(assignmentId);
        
        if (!assignmentPost) {
            // If not found, load more posts until we find it or reach the end
            assignmentPost = await this.loadUntilAssignmentFound(assignmentId);
        }
        
        if (assignmentPost) {
            console.log('✅ Assignment post found, scrolling...');
            this.smoothScrollToPost(assignmentPost);
        } else {
            console.warn('❌ Assignment post not found:', assignmentId);
            // Show message to user
            this.showNotFoundMessage();
        }
    }

    findAssignmentPost(assignmentId) {
        // Try to find by data-assignment-id attribute within post content
        const assignmentElement = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
        if (assignmentElement) {
            // Find the parent post element
            const postElement = assignmentElement.closest('[data-post-id]');
            if (postElement) {
                return postElement;
            }
        }
        
        // Fallback: search through all posts for assignment content
        const posts = document.querySelectorAll('[data-post-id]');
        for (let post of posts) {
            const assignmentContent = post.querySelector(`[data-assignment-id="${assignmentId}"]`);
            if (assignmentContent) {
                return post;
            }
        }
        
        return null;
    }

    async loadUntilAssignmentFound(assignmentId, maxAttempts = 10) {
        let attempts = 0;
        
        while (attempts < maxAttempts && window.kelasPosting.hasMorePosts) {
            console.log(`🔍 Attempt ${attempts + 1}: Loading more posts to find assignment ${assignmentId}`);
            
            // Load more posts
            await window.kelasPosting.loadPostingan(false);
            
            // Check if assignment is now loaded
            const assignmentPost = this.findAssignmentPost(assignmentId);
            if (assignmentPost) {
                console.log('✅ Assignment found after loading more posts');
                return assignmentPost;
            }
            
            attempts++;
            // Small delay between attempts
            await new Promise(resolve => setTimeout(resolve, 300));
        }
        
        return null;
    }

    smoothScrollToPost(postElement) {
        // Calculate position to center the post in viewport
        const elementRect = postElement.getBoundingClientRect();
        const offsetTop = window.pageYOffset + elementRect.top;
        const windowHeight = window.innerHeight;
        const elementHeight = elementRect.height;
        
        // Center the element in the viewport with some padding from top
        const scrollToPosition = offsetTop - (windowHeight / 2) + (elementHeight / 2);
        
        console.log('📏 Scroll calculation:', {
            elementRect,
            offsetTop,
            windowHeight,
            elementHeight,
            scrollToPosition: Math.max(0, scrollToPosition)
        });
        
        // Smooth scroll
        window.scrollTo({
            top: Math.max(0, scrollToPosition),
            behavior: 'smooth'
        });
        
        // Wait for scroll to complete, then highlight
        setTimeout(() => {
            this.highlightPost(postElement);
        }, 800);
    }

    highlightPost(postElement) {
        console.log('✨ Highlighting post:', postElement);
        
        // Force remove any existing highlight first
        postElement.classList.remove('assignment-highlight');
        
        // Force a reflow to ensure the class removal takes effect
        postElement.offsetHeight;
        
        // Store original styles
        const originalBorder = postElement.style.border;
        const originalBoxShadow = postElement.style.boxShadow;
        const originalBackground = postElement.style.backgroundColor;
        const originalTransition = postElement.style.transition;
        const originalTransform = postElement.style.transform;
        
        // Add highlight class
        postElement.classList.add('assignment-highlight');
        
        // Also add inline styles as fallback to ensure visibility
        postElement.style.setProperty('border', '4px solid #f97316', 'important');
        postElement.style.setProperty('box-shadow', '0 0 0 8px rgba(249, 115, 22, 0.4)', 'important');
        postElement.style.setProperty('background-color', 'rgba(249, 115, 22, 0.05)', 'important');
        postElement.style.setProperty('transform', 'scale(1.02)', 'important');
        postElement.style.setProperty('transition', 'all 0.3s ease-in-out', 'important');
        postElement.style.setProperty('border-radius', '8px', 'important');
        postElement.style.setProperty('z-index', '999', 'important');
        postElement.style.setProperty('position', 'relative', 'important');
        
        // Verify the styles were applied
        console.log('🎨 Class list after adding highlight:', postElement.classList.toString());
        console.log('🎨 Final computed styles:', {
            border: window.getComputedStyle(postElement).border,
            borderColor: window.getComputedStyle(postElement).borderColor,
            boxShadow: window.getComputedStyle(postElement).boxShadow,
            backgroundColor: window.getComputedStyle(postElement).backgroundColor,
            transform: window.getComputedStyle(postElement).transform
        });
        
        // Add a visual indicator in console
        console.log('🎨 Highlight styles applied, will remove after 5 seconds');
        
        // Remove highlight after 5 seconds
        setTimeout(() => {
            postElement.classList.remove('assignment-highlight');
            
            // Restore original styles
            postElement.style.border = originalBorder;
            postElement.style.boxShadow = originalBoxShadow;
            postElement.style.backgroundColor = originalBackground;
            postElement.style.transition = originalTransition;
            postElement.style.transform = originalTransform;
            
            // Remove other properties
            postElement.style.removeProperty('border-radius');
            postElement.style.removeProperty('z-index');
            postElement.style.removeProperty('position');
            
            console.log('🎨 Highlight class and styles removed');
        }, 5000);
    }

    showNotFoundMessage() {
        // Create and show a temporary notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-orange-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity duration-300';
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="ti ti-info-circle mr-2"></i>
                <span>Postingan tugas tidak ditemukan atau belum dimuat</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Fade out and remove after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
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
