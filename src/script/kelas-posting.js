// Kelas Posting System
class KelasPosting {
    constructor(kelasId) {
        this.kelasId = kelasId;
        this.currentOffset = 0;
        this.limit = 10;
        this.isLoading = false;
        this.hasMorePosts = true;
        
        this.initializeEventListeners();
        this.loadPostingan();
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
    }
    
    async handleSubmitPost(e) {
        e.preventDefault();
        
        if (this.isLoading) {
            return;
        }
        
        const textarea = document.getElementById('postTextarea');
        const konten = textarea.value.trim();
        const submitBtn = document.querySelector('#postForm button[type="submit"]');
        
        if (!konten) {
            this.showAlert('Konten postingan tidak boleh kosong', 'error');
            return;
        }
        
        console.log('Submitting post:', konten);
        this.isLoading = true;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ti ti-loader animate-spin mr-2"></i>Posting...';
        
        const formData = new FormData();
        formData.append('kelas_id', this.kelasId);
        formData.append('konten', konten);
        formData.append('tipePost', 'umum');
        
        try {
            const response = await fetch('../logic/handle-posting.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            console.log('Post result:', result);
            
            if (result.success) {
                textarea.value = '';
                this.autoResizeTextarea.call(textarea);
                this.showAlert('Postingan berhasil dibuat!', 'success');
                
                // Wait a moment then reload posts
                setTimeout(() => {
                    this.refreshPosts();
                }, 500);
            } else {
                this.showAlert(result.message || 'Gagal membuat postingan', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('Terjadi kesalahan saat membuat postingan', 'error');
        } finally {
            this.isLoading = false;
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Posting';
        }
    }
    
    async loadPostingan(refresh = false) {
        // Prevent multiple simultaneous loads
        if (this.isLoading || (!this.hasMorePosts && !refresh)) {
            console.log('Load prevented:', { isLoading: this.isLoading, hasMorePosts: this.hasMorePosts, refresh });
            return;
        }
        
        console.log('Starting load:', { refresh, offset: this.currentOffset });
        this.isLoading = true;
        
        if (refresh) {
            this.currentOffset = 0;
            this.hasMorePosts = true;
        }
        
        // Show loading indicator only if refreshing
        const postsContainer = document.getElementById('postsContainer');
        if (refresh) {
            postsContainer.innerHTML = `
                <div id="loadingIndicator" class="text-center py-8 text-gray-500">
                    <i class="ti ti-loader animate-spin text-4xl mb-2"></i>
                    <p>Memuat postingan...</p>
                </div>
            `;
        }
        
        try {
            const url = `../logic/get-postingan.php?kelas_id=${this.kelasId}&limit=${this.limit}&offset=${this.currentOffset}`;
            
            // Add timeout to prevent infinite loading
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
            
            const response = await fetch(url, {
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                // Remove loading indicator
                const loadingIndicator = document.getElementById('loadingIndicator');
                if (loadingIndicator) {
                    loadingIndicator.remove();
                }
                
                if (refresh) {
                    postsContainer.innerHTML = '';
                }
                
                if (result.data && result.data.length > 0) {
                    console.log(`Adding ${result.data.length} posts`);
                    
                    result.data.forEach(post => {
                        const postElement = this.createPostElement(post, result.user_id, result.user_role);
                        postsContainer.appendChild(postElement);
                    });
                    
                    this.currentOffset += result.data.length;
                    
                    if (result.data.length < this.limit) {
                        this.hasMorePosts = false;
                        console.log('No more posts available');
                    }
                    
                    // Add "load more" indicator if there are more posts
                    if (this.hasMorePosts) {
                        const loadMoreElement = document.createElement('div');
                        loadMoreElement.id = 'loadMoreIndicator';
                        loadMoreElement.className = 'text-center py-4 text-gray-400';
                        loadMoreElement.innerHTML = '<p class="text-sm">Scroll ke bawah untuk memuat lebih banyak...</p>';
                        postsContainer.appendChild(loadMoreElement);
                    }
                } else {
                    this.hasMorePosts = false;
                    console.log('No posts found');
                    
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
                    } else {
                        // No more posts to load
                        const noMoreElement = document.createElement('div');
                        noMoreElement.className = 'text-center py-6 text-gray-400 border-t border-gray-100';
                        noMoreElement.innerHTML = `
                            <i class="ti ti-check text-2xl mb-2"></i>
                            <p class="text-sm">Semua postingan telah dimuat</p>
                        `;
                        postsContainer.appendChild(noMoreElement);
                    }
                }
            } else {
                console.error('API Error:', result.message);
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
            console.error('Network Error:', error);
            postsContainer.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="ti ti-wifi-off text-4xl mb-2"></i>
                    <p>Terjadi kesalahan saat memuat postingan</p>
                    <p class="text-sm text-gray-500 mt-1">Periksa koneksi internet Anda</p>
                    <button onclick="window.kelasPosting.refreshPosts()" 
                            class="mt-4 px-4 py-2 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors">
                        <i class="ti ti-refresh mr-2"></i>
                        Coba Lagi
                    </button>
                </div>
            `;
        } finally {
            this.isLoading = false;
            console.log('Load completed, isLoading set to false');
        }
    }
    
    createPostElement(post, currentUserId, currentUserRole) {
        const isOwner = post.user_id == currentUserId;
        const postDate = new Date(post.dibuat);
        const timeAgo = this.getTimeAgo(postDate);
        
        const postElement = document.createElement('div');
        postElement.className = 'bg-white rounded-lg shadow-sm mb-6 post-enter';
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
                            ${post.tipePost !== 'umum' ? ` • <span class="px-2 py-1 bg-orange-100 text-orange-600 rounded text-xs">${post.tipePost}</span>` : ''}
                        </p>
                    </div>
                    ${isOwner ? `
                        <div class="dropdown relative">
                            <button class="text-gray-400 hover:text-gray-600" onclick="toggleDropdown(this)">
                                <i class="ti ti-dots"></i>
                            </button>
                            <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 hidden">
                                <button onclick="editPost(${post.id})" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
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
                    <p class="text-gray-800 text-sm lg:text-base whitespace-pre-wrap">${this.escapeHtml(post.konten)}</p>
                    ${post.deadline ? `
                        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center">
                                <i class="ti ti-clock text-yellow-600 mr-2"></i>
                                <span class="text-sm text-yellow-800">Deadline: ${new Date(post.deadline).toLocaleDateString('id-ID')}</span>
                            </div>
                        </div>
                    ` : ''}
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div class="flex items-center space-x-4 lg:space-x-6">
                        <button class="like-btn flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base" data-post-id="${post.id}">
                            <i class="ti ti-heart mr-1 lg:mr-2"></i>
                            <span class="like-count">${post.jumlahLike || 0}</span>
                        </button>
                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base" onclick="toggleComments(${post.id})">
                            <i class="ti ti-message-circle mr-1 lg:mr-2"></i>
                            <span>${post.jumlahKomentar || 0}</span>
                        </button>
                        <button class="flex items-center text-gray-600 hover:text-orange transition-colors text-sm lg:text-base">
                            <i class="ti ti-share mr-1 lg:mr-2"></i>
                            <span class="hidden sm:inline">Bagikan</span>
                        </button>
                    </div>
                </div>
                <div id="comments-${post.id}" class="hidden mt-4 pt-4 border-t border-gray-100">
                    <!-- Comments will be loaded here -->
                </div>
            </div>
        `;
        
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
                        likeBtn.querySelector('i').classList.add('ti-heart-filled');
                        likeBtn.querySelector('i').classList.remove('ti-heart');
                    } else {
                        likeCount.textContent = Math.max(0, currentCount - 1);
                        likeBtn.classList.remove('text-red-500', 'liked');
                        likeBtn.querySelector('i').classList.add('ti-heart');
                        likeBtn.querySelector('i').classList.remove('ti-heart-filled');
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
    
    // Method untuk refresh manual
    refreshPosts() {
        console.log('Refreshing posts...');
        // Reset all states
        this.isLoading = false;
        this.currentOffset = 0;
        this.hasMorePosts = true;
        
        // Clear existing content
        const postsContainer = document.getElementById('postsContainer');
        postsContainer.innerHTML = '';
        
        // Load fresh posts
        this.loadPostingan(true);
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
    if (confirm('Apakah Anda yakin ingin menghapus postingan ini?')) {
        // TODO: Implement delete functionality
        console.log('Delete post:', postId);
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
