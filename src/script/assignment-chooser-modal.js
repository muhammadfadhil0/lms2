/**
 * Assignment Chooser Modal Script
 * JavaScript untuk handling modal pemilihan tugas untuk AI analysis
 */

class AssignmentChooserModal {
    constructor() {
        this.modal = null;
        this.selectedAssignmentId = null;
        this.assignments = [];
        this.classes = [];
        this.currentFilter = '';
        
        this.initializeModal();
        this.bindEvents();
    }
    
    initializeModal() {
        // Get modal element
        this.modal = document.getElementById('chooseAssignmentModal');
        if (!this.modal) {
            console.error('Assignment chooser modal not found');
            return;
        }
        
        // Initialize modal elements
        this.loadingState = document.getElementById('assignmentLoadingState');
        this.errorState = document.getElementById('assignmentErrorState');
        this.errorMessage = document.getElementById('assignmentErrorMessage');
        this.listContainer = document.getElementById('assignmentListContainer');
        this.itemsContainer = document.getElementById('assignmentItems');
        this.emptyState = document.getElementById('assignmentEmptyState');
        this.classFilter = document.getElementById('classFilter');
        this.selectBtn = document.getElementById('selectAssignmentBtn');
        this.cancelBtn = document.getElementById('cancelChooseAssignmentBtn');
    }
    
    bindEvents() {
        if (!this.modal) return;
        
        // Filter change event
        if (this.classFilter) {
            this.classFilter.addEventListener('change', () => {
                this.currentFilter = this.classFilter.value;
                this.filterAndDisplayAssignments();
            });
        }
        
        // Button events
        if (this.selectBtn) {
            this.selectBtn.addEventListener('click', () => this.handleSelectAssignment());
        }
        
        if (this.cancelBtn) {
            this.cancelBtn.addEventListener('click', () => this.closeModal());
        }
        
        // Close modal on backdrop click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.closeModal();
            }
        });
        
        // ESC key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isModalOpen()) {
                this.closeModal();
            }
        });
    }
    
    /**
     * Open modal dan load assignments
     */
    async openModal() {
        if (!this.modal) return;
        
        // Show modal
        this.modal.showModal();
        this.modal.classList.remove('data-closed');
        
        // Reset state
        this.selectedAssignmentId = null;
        this.updateSelectButton();
        
        // Load data
        await this.loadData();
    }
    
    /**
     * Close modal
     */
    closeModal() {
        if (!this.modal) return;
        
        this.modal.classList.add('data-closed');
        setTimeout(() => {
            this.modal.close();
        }, 200);
    }
    
    /**
     * Check if modal is open
     */
    isModalOpen() {
        return this.modal && this.modal.open;
    }
    
    /**
     * Load assignments dan classes dari API
     */
    async loadData() {
        this.showLoading(true);
        this.showError(false);
        
        try {
            // Load classes dan assignments secara paralel
            const [classesResponse, assignmentsResponse] = await Promise.all([
                fetch('src/api/get-assignments.php?action=get_classes'),
                fetch('src/api/get-assignments.php?action=get_assignments')
            ]);
            
            if (!classesResponse.ok || !assignmentsResponse.ok) {
                throw new Error('Failed to fetch data');
            }
            
            const classesData = await classesResponse.json();
            const assignmentsData = await assignmentsResponse.json();
            
            if (!classesData.success) {
                throw new Error(classesData.message || 'Failed to load classes');
            }
            
            if (!assignmentsData.success) {
                throw new Error(assignmentsData.message || 'Failed to load assignments');
            }
            
            this.classes = classesData.classes || [];
            this.assignments = assignmentsData.assignments || [];
            
            this.populateClassFilter();
            this.displayAssignments();
            this.showLoading(false);
            
        } catch (error) {
            console.error('Error loading assignments:', error);
            this.showError(true, error.message);
            this.showLoading(false);
        }
    }
    
    /**
     * Populate class filter dropdown
     */
    populateClassFilter() {
        if (!this.classFilter) return;
        
        // Clear existing options (except "Semua Kelas")
        this.classFilter.innerHTML = '<option value="">Semua Kelas</option>';
        
        // Add class options
        this.classes.forEach(kelas => {
            const option = document.createElement('option');
            option.value = kelas.id;
            option.textContent = `${kelas.namaKelas}`;
            this.classFilter.appendChild(option);
        });
    }
    
    /**
     * Filter dan display assignments berdasarkan filter kelas
     */
    filterAndDisplayAssignments() {
        let filteredAssignments = this.assignments;
        
        if (this.currentFilter) {
            filteredAssignments = this.assignments.filter(assignment => 
                assignment.kelas.id == this.currentFilter
            );
        }
        
        this.displayAssignments(filteredAssignments);
    }
    
    /**
     * Display assignments dalam modal
     */
    displayAssignments(assignmentsToShow = null) {
        if (!this.itemsContainer) return;
        
        const assignments = assignmentsToShow || this.assignments;
        
        // Clear container
        this.itemsContainer.innerHTML = '';
        
        if (assignments.length === 0) {
            this.showEmptyState(true);
            return;
        }
        
        this.showEmptyState(false);
        
        // Create assignment items
        assignments.forEach(assignment => {
            const item = this.createAssignmentItem(assignment);
            this.itemsContainer.appendChild(item);
        });
    }
    
    /**
     * Create assignment item element
     */
    createAssignmentItem(assignment) {
        const item = document.createElement('div');
        item.className = 'assignment-item';
        item.dataset.assignmentId = assignment.id;
        
        // Determine deadline class
        let deadlineClass = 'assignment-deadline-normal';
        if (assignment.deadline_status === 'urgent') {
            deadlineClass = 'assignment-deadline-urgent';
        } else if (assignment.deadline_status === 'soon') {
            deadlineClass = 'assignment-deadline-soon';
        } else if (assignment.deadline_status === 'overdue') {
            deadlineClass = 'assignment-deadline-urgent';
        }
        
        // Create submission info for students
        let submissionInfo = '';
        if (assignment.submission) {
            if (assignment.submission.is_submitted) {
                const statusText = assignment.submission.status === 'dinilai' ? 'Sudah Dinilai' : 'Sudah Dikumpulkan';
                const statusClass = assignment.submission.status === 'dinilai' ? 'text-green-600' : 'text-blue-600';
                submissionInfo = `
                    <div class="flex items-center text-xs mt-2">
                        <i class="ti ti-check-circle ${statusClass} mr-1"></i>
                        <span class="${statusClass}">${statusText}</span>
                        ${assignment.submission.nilai ? `<span class="ml-2 text-gray-500">Nilai: ${assignment.submission.nilai}</span>` : ''}
                    </div>
                `;
            } else {
                submissionInfo = `
                    <div class="flex items-center text-xs mt-2">
                        <i class="ti ti-clock text-orange-500 mr-1"></i>
                        <span class="text-orange-600">Belum Dikumpulkan</span>
                    </div>
                `;
            }
        } else if (assignment.submissions) {
            // Teacher view - show submission count
            submissionInfo = `
                <div class="flex items-center text-xs mt-2 text-gray-600">
                    <i class="ti ti-users mr-1"></i>
                    <span>${assignment.submissions.total} pengumpulan, ${assignment.submissions.graded} dinilai</span>
                </div>
            `;
        }
        
        item.innerHTML = `
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center mb-2">
                        <h4 class="font-semibold text-gray-900 text-sm">${this.escapeHtml(assignment.judul)}</h4>
                        <span class="${deadlineClass} ml-2">${assignment.deadline_formatted}</span>
                    </div>
                    <p class="text-xs text-gray-600 mb-2">${this.escapeHtml(assignment.kelas.nama)} - ${this.escapeHtml(assignment.kelas.mata_pelajaran)}</p>
                    <p class="text-xs text-gray-500 line-clamp-2">${this.escapeHtml(assignment.deskripsi)}</p>
                    ${submissionInfo}
                </div>
                <div class="ml-4 flex-shrink-0">
                    <i class="ti ti-chevron-right text-gray-400"></i>
                </div>
            </div>
        `;
        
        // Add click event
        item.addEventListener('click', () => {
            this.selectAssignment(assignment.id);
        });
        
        return item;
    }
    
    /**
     * Select assignment
     */
    selectAssignment(assignmentId) {
        // Remove previous selection
        this.itemsContainer.querySelectorAll('.assignment-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        // Add selection to clicked item
        const selectedItem = this.itemsContainer.querySelector(`[data-assignment-id="${assignmentId}"]`);
        if (selectedItem) {
            selectedItem.classList.add('selected');
            this.selectedAssignmentId = assignmentId;
            this.updateSelectButton();
        }
    }
    
    /**
     * Update select button state
     */
    updateSelectButton() {
        if (!this.selectBtn) return;
        
        if (this.selectedAssignmentId) {
            this.selectBtn.disabled = false;
        } else {
            this.selectBtn.disabled = true;
        }
    }
    
    /**
     * Handle select assignment button click
     */
    async handleSelectAssignment() {
        if (!this.selectedAssignmentId) return;
        
        const selectedAssignment = this.assignments.find(a => a.id == this.selectedAssignmentId);
        if (!selectedAssignment) return;
        
        // Show loading state
        this.setButtonLoading(true);
        
        try {
            // Trigger custom event dengan assignment data
            const event = new CustomEvent('assignmentSelected', {
                detail: {
                    assignment: selectedAssignment
                }
            });
            document.dispatchEvent(event);
            
            // Close modal
            this.closeModal();
            
        } catch (error) {
            console.error('Error handling assignment selection:', error);
            alert('Terjadi kesalahan saat memproses tugas yang dipilih.');
        } finally {
            this.setButtonLoading(false);
        }
    }
    
    /**
     * Set button loading state
     */
    setButtonLoading(loading) {
        if (!this.selectBtn) return;
        
        const btnText = this.selectBtn.querySelector('.select-assignment-btn-text');
        const btnLoader = this.selectBtn.querySelector('.select-assignment-btn-loading');
        
        if (loading) {
            this.selectBtn.disabled = true;
            if (btnText) btnText.textContent = 'Memproses...';
            if (btnLoader) btnLoader.classList.remove('hidden');
        } else {
            this.selectBtn.disabled = !this.selectedAssignmentId;
            if (btnText) btnText.textContent = 'Pilih Tugas';
            if (btnLoader) btnLoader.classList.add('hidden');
        }
    }
    
    /**
     * Show/hide loading state
     */
    showLoading(show) {
        if (this.loadingState) {
            this.loadingState.classList.toggle('hidden', !show);
        }
        if (this.listContainer) {
            this.listContainer.classList.toggle('hidden', show);
        }
    }
    
    /**
     * Show/hide error state
     */
    showError(show, message = '') {
        if (this.errorState) {
            this.errorState.classList.toggle('hidden', !show);
        }
        if (this.errorMessage && message) {
            this.errorMessage.textContent = message;
        }
        if (this.listContainer) {
            this.listContainer.classList.toggle('hidden', show);
        }
    }
    
    /**
     * Show/hide empty state
     */
    showEmptyState(show) {
        if (this.emptyState) {
            this.emptyState.classList.toggle('hidden', !show);
        }
    }
    
    /**
     * Escape HTML untuk prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize modal when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.assignmentChooserModal = new AssignmentChooserModal();
});

// Function to open modal dari external code
function openAssignmentChooser() {
    if (window.assignmentChooserModal) {
        window.assignmentChooserModal.openModal();
    }
}

// Listen for assignment selection events
document.addEventListener('assignmentSelected', (event) => {
    const assignment = event.detail.assignment;
    console.log('Assignment selected:', assignment);
    
    // You can add custom logic here untuk handle assignment yang dipilih
    // Contoh: kirim ke AI untuk analysis, dll
    
    // Example: Show notification
    if (typeof showNotification === 'function') {
        showNotification(`Tugas "${assignment.judul}" dipilih untuk analisis AI`, 'success');
    } else {
        alert(`Tugas "${assignment.judul}" berhasil dipilih!`);
    }
});