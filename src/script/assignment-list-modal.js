// Assignment List Modal Manager
class AssignmentListModal {
    constructor(kelasId) {
        this.kelasId = kelasId;
        this.modal = null;
        this.isOpen = false;
        this.assignments = [];
        this.filteredAssignments = [];
        this.currentSearch = '';
        this.currentSort = 'created_desc';
        this.searchTimeout = null;
        
        this.init();
    }

    init() {
        // Get modal element
        this.modal = document.getElementById('assignment-list-modal');
        if (!this.modal) {
            console.error('Assignment list modal not found');
            return;
        }

        this.bindEvents();
    }

    bindEvents() {
        // Close modal events
        const closeBtn = document.getElementById('close-assignment-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }

        // Close on backdrop click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });

        // Search functionality with debounce
        const searchInput = document.getElementById('assignment-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.trim();
                
                // Clear previous timeout
                if (this.searchTimeout) {
                    clearTimeout(this.searchTimeout);
                }
                
                // Set new timeout for 1 second delay
                this.searchTimeout = setTimeout(() => {
                    this.currentSearch = query;
                    this.loadAssignments();
                }, 1000);
            });
        }

        // Sort functionality
        const sortSelect = document.getElementById('assignment-sort');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.currentSort = e.target.value;
                this.loadAssignments();
            });
        }
    }

    async open() {
        console.log('🎯 Opening assignment list modal for class:', this.kelasId);
        
        if (!this.modal) return;
        
        this.isOpen = true;
        this.modal.classList.remove('hidden');
        
        // Use a small delay to ensure the modal is visible before showing
        setTimeout(() => {
            this.modal.showModal();
        }, 10);
        
        // Reset search and sort
        const searchInput = document.getElementById('assignment-search');
        const sortSelect = document.getElementById('assignment-sort');
        
        if (searchInput) searchInput.value = '';
        if (sortSelect) sortSelect.value = 'created_desc';
        
        this.currentSearch = '';
        this.currentSort = 'created_desc';
        
        // Load assignments
        await this.loadAssignments();
    }

    close() {
        if (!this.modal || !this.isOpen) return;
        
        console.log('🎯 Closing assignment list modal');
        
        this.isOpen = false;
        this.modal.close();
        
        // Use a small delay to hide the modal after closing
        setTimeout(() => {
            this.modal.classList.add('hidden');
        }, 100);
        
        // Clear search timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = null;
        }
    }

    async loadAssignments() {
        console.log('📋 Loading assignments...', {
            search: this.currentSearch,
            sort: this.currentSort
        });

        this.showLoading();

        try {
            const params = new URLSearchParams({
                kelas_id: this.kelasId,
                search: this.currentSearch,
                sort: this.currentSort
            });

            // Switch back to main endpoint now that scoring is fixed
            const response = await fetch(`../logic/get-assignments.php?${params}`);
            const text = await response.text();
            
            console.log('📋 Raw response length:', text.length);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('❌ JSON Parse Error:', parseError);
                console.log('📋 Raw response text:', text.substring(0, 1000));
                throw new Error('Invalid JSON response from server');
            }

            if (data.success) {
                this.assignments = data.assignments;
                this.renderAssignments();
                console.log('✅ Assignments loaded:', this.assignments.length);
                
                // Log first assignment for debugging
                if (this.assignments.length > 0) {
                    console.log('🔍 First assignment data:', this.assignments[0]);
                }
            } else {
                console.error('❌ Failed to load assignments:', data.message);
                if (data.debug) console.log('🐛 Debug info:', data.debug);
                this.showError(data.message);
            }
        } catch (error) {
            console.error('❌ Error loading assignments:', error);
            this.showError('Gagal memuat tugas. Silakan coba lagi.');
        }
    }

    showLoading() {
        const loadingEl = document.getElementById('assignment-loading');
        const itemsEl = document.getElementById('assignment-items');
        const noResultsEl = document.getElementById('no-assignments');

        if (loadingEl) loadingEl.classList.remove('hidden');
        if (itemsEl) itemsEl.classList.add('hidden');
        if (noResultsEl) noResultsEl.classList.add('hidden');
    }

    showError(message) {
        const loadingEl = document.getElementById('assignment-loading');
        const itemsEl = document.getElementById('assignment-items');
        const noResultsEl = document.getElementById('no-assignments');

        if (loadingEl) loadingEl.classList.add('hidden');
        if (itemsEl) itemsEl.classList.add('hidden');
        if (noResultsEl) {
            noResultsEl.classList.remove('hidden');
            const messageEl = noResultsEl.querySelector('p');
            if (messageEl) messageEl.textContent = message;
        }
    }

    renderAssignments() {
        const loadingEl = document.getElementById('assignment-loading');
        const itemsEl = document.getElementById('assignment-items');
        const noResultsEl = document.getElementById('no-assignments');

        if (loadingEl) loadingEl.classList.add('hidden');

        if (this.assignments.length === 0) {
            if (itemsEl) itemsEl.classList.add('hidden');
            if (noResultsEl) {
                noResultsEl.classList.remove('hidden');
                const messageEl = noResultsEl.querySelector('p');
                if (messageEl) {
                    messageEl.textContent = this.currentSearch 
                        ? `Tidak ada tugas yang cocok dengan "${this.currentSearch}"`
                        : 'Tidak ada tugas ditemukan';
                }
            }
            return;
        }

        if (noResultsEl) noResultsEl.classList.add('hidden');
        if (itemsEl) {
            itemsEl.classList.remove('hidden');
            itemsEl.innerHTML = this.assignments.map(assignment => this.renderAssignmentItem(assignment)).join('');
            
            // Add click handlers
            this.addAssignmentClickHandlers();
        }
    }

    renderAssignmentItem(assignment) {
        // Format dates safely
        let timeAgo = assignment.time_ago || 'Tidak diketahui';
        let deadlineFormatted = assignment.deadline_formatted || '';
        let isExpired = assignment.is_deadline_passed || false;
        let isSoon = assignment.is_deadline_soon && !isExpired;
        
        // Determine status styling based on submission_status
        let statusClass = 'bg-gray-50 border-gray-200';
        let statusIcon = 'ti ti-clock';
        let statusText = 'Belum Dikumpulkan';
        
        if (assignment.submission_status) {
            switch (assignment.submission_status) {
                case 'expired':
                    statusClass = 'bg-red-50 border-red-200';
                    statusIcon = 'ti ti-x-circle text-red-600';
                    statusText = 'Terlewat';
                    break;
                case 'graded':
                    statusClass = 'bg-green-50 border-green-200';
                    statusIcon = 'ti ti-check text-green-600';
                    statusText = `Dinilai (${assignment.student_score || 0}/${assignment.nilai_maksimal || 0})`;
                    break;
                case 'submitted':
                    statusClass = 'bg-yellow-50 border-yellow-200';
                    statusIcon = 'ti ti-clock text-yellow-600';
                    statusText = 'Menunggu Penilaian';
                    break;
                case 'pending':
                default:
                    if (isExpired) {
                        statusClass = 'bg-red-50 border-red-200';
                        statusIcon = 'ti ti-x-circle text-red-600';
                        statusText = 'Terlewat';
                    } else {
                        statusClass = 'bg-orange-50 border-orange-200';
                        statusIcon = 'ti ti-exclamation-circle text-orange-600';
                        statusText = 'Belum Dikumpulkan';
                    }
                    break;
            }
        }

        console.log('🎯 Rendering assignment:', {
            id: assignment.id,
            judul: assignment.judul,
            submission_status: assignment.submission_status,
            student_status: assignment.student_status,
            statusText: statusText
        });

        return `
            <div class="assignment-item p-4 border border-gray-200 rounded-lg hover:border-purple-300 hover:bg-purple-50 transition-all cursor-pointer ${statusClass}" 
                 data-assignment-id="${assignment.id}">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <i class="ti ti-clipboard-text text-purple-600"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-semibold text-gray-900 truncate">${this.escapeHtml(assignment.judul || 'Tanpa Judul')}</h4>
                                ${assignment.deskripsi ? `
                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2">${this.escapeHtml(assignment.deskripsi.substring(0, 100))}${assignment.deskripsi.length > 100 ? '...' : ''}</p>
                                ` : ''}
                                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                    <span class="flex items-center">
                                        <i class="ti ti-calendar mr-1"></i>
                                        ${timeAgo}
                                    </span>
                                    ${deadlineFormatted ? `
                                        <span class="flex items-center ${isSoon ? 'text-orange-600 font-medium' : isExpired ? 'text-red-600 font-medium' : ''}">
                                            <i class="ti ti-clock mr-1"></i>
                                            ${deadlineFormatted}
                                        </span>
                                    ` : ''}
                                    ${assignment.nilai_maksimal ? `
                                        <span class="flex items-center">
                                            <i class="ti ti-trophy mr-1"></i>
                                            ${assignment.nilai_maksimal} poin
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end ml-3">
                        <i class="${statusIcon} text-sm mb-1"></i>
                        <span class="text-xs font-medium text-center">${statusText}</span>
                    </div>
                </div>
            </div>
        `;
    }

    addAssignmentClickHandlers() {
        const assignmentItems = document.querySelectorAll('.assignment-item');
        console.log('🎯 Adding click handlers to', assignmentItems.length, 'assignment items');
        
        assignmentItems.forEach(item => {
            item.addEventListener('click', (e) => {
                const assignmentId = item.getAttribute('data-assignment-id');
                if (assignmentId) {
                    console.log('🎯 Assignment clicked from modal:', assignmentId);
                    
                    // Close modal first
                    this.close();
                    
                    // Then scroll to assignment using the navigator
                    if (window.assignmentNavigator) {
                        setTimeout(() => {
                            window.assignmentNavigator.scrollToAssignment(assignmentId);
                        }, 300); // Small delay to ensure modal is closed
                    } else {
                        console.warn('⚠️ AssignmentNavigator not available');
                    }
                }
            });
        });
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
}
