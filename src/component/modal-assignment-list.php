<el-dialog>
    <dialog id="assignment-list-modal" aria-labelledby="assignment-dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent hidden">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-3 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative w-full max-w-lg sm:max-w-4xl transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                
                <!-- Header -->
                <div class="bg-white px-4 pt-4 pb-3 sm:px-6 sm:pt-6 sm:pb-4 border-b border-gray-200 sticky top-0 z-10">
                    <div class="flex items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center min-w-0">
                            <div class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 bg-purple-100 rounded-full flex-shrink-0">
                                <span class="ti ti-clipboard-list text-lg sm:text-xl text-purple-600"></span>
                            </div>
                            <div class="ml-3 min-w-0">
                                <h3 id="assignment-dialog-title" class="text-base sm:text-xl font-semibold text-gray-900 leading-tight truncate">Semua Tugas</h3>
                                <p class="text-[11px] sm:text-sm text-gray-500 hidden sm:block">Tap tugas untuk menuju postingan</p>
                                <p class="text-[11px] text-gray-500 sm:hidden mt-0.5">Tap tugas untuk buka</p>
                            </div>
                        </div>
                        <button id="close-assignment-modal" class="text-gray-400 hover:text-gray-600 focus:outline-none -mr-1 sm:mr-0">
                            <span class="ti ti-x text-xl sm:text-2xl"></span>
                        </button>
                    </div>
                    <!-- Search + Sort (mobile stacked) -->
                    <div class="mt-3 flex flex-col sm:flex-row gap-2 sm:gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="ti ti-search text-gray-400 text-sm"></span>
                                </div>
                                <input type="text" id="assignment-search" placeholder="Cari tugas..." 
                                    class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-xs sm:text-sm" />
                            </div>
                        </div>
                        <div class="flex sm:w-48">
                            <select id="assignment-sort" class="block w-full px-2.5 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-xs sm:text-sm">
                                <option value="created_desc">Terbaru</option>
                                <option value="created_asc">Terlama</option>
                                <option value="name_asc">A-Z</option>
                                <option value="name_desc">Z-A</option>
                                <option value="deadline_asc">Deadline Dekat</option>
                                <option value="deadline_desc">Deadline Jauh</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Assignment List -->
                <div class="bg-white px-4 sm:px-6 py-3 sm:py-4 max-h-[70vh] overflow-y-auto">
                    <div id="assignment-list-container">
                        <!-- Loading state -->
                        <div id="assignment-loading" class="text-center py-6 sm:py-8">
                            <div class="animate-spin inline-block w-5 h-5 sm:w-6 sm:h-6 border-2 border-current border-t-transparent text-purple-600 rounded-full mb-2"></div>
                            <p class="text-xs sm:text-sm text-gray-500">Memuat tugas...</p>
                        </div>
                        
                        <!-- Assignment items will be populated here -->
                        <div id="assignment-items" class="space-y-2 sm:space-y-3 hidden">
                            <!-- Template will be filled by JavaScript -->
                        </div>
                        
                        <!-- No results state -->
                        <div id="no-assignments" class="text-center py-8 hidden">
                            <div class="text-gray-300 mb-2">
                                <span class="ti ti-clipboard-off text-3xl sm:text-4xl"></span>
                            </div>
                            <p class="text-xs sm:text-sm text-gray-500">Tidak ada tugas ditemukan</p>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-4 sm:px-6 py-3 sm:py-4 text-center border-t border-gray-200">
                    <p class="text-[11px] sm:text-sm text-gray-500">Tap tugas untuk buka • Tap area gelap untuk tutup</p>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

<script>
// Global variables for assignments modal
let assignmentsModal = null;
let allAssignments = [];
let filteredAssignments = [];

// Determine API base path based on current location
function getAssignmentApiBasePath() {
    const currentPath = window.location.pathname;
    
    // If we're in front/ directory, API is in ../logic/
    if (currentPath.includes('/front/')) {
        return '../logic/';
    }
    // If we're in root or other directory, try src/logic/
    else if (currentPath.includes('/lms/')) {
        return 'src/logic/';
    }
    // Default fallback
    return '../logic/';
}

// Initialize assignment modal when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeAssignmentModal();
});

function initializeAssignmentModal() {
    assignmentsModal = document.getElementById('assignment-list-modal');
    const closeBtn = document.getElementById('close-assignment-modal');
    const searchInput = document.getElementById('assignment-search');
    const sortSelect = document.getElementById('assignment-sort');
    
    if (!assignmentsModal) return;
    
    // Close button handler
    if (closeBtn) {
        closeBtn.addEventListener('click', closeAssignmentsModal);
    }
    
    // Click outside to close
    assignmentsModal.addEventListener('click', function(e) {
        if (e.target === assignmentsModal) {
            closeAssignmentsModal();
        }
    });
    
    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterAndRenderAssignments();
        });
    }
    
    // Sort functionality
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            filterAndRenderAssignments();
        });
    }
}

// Open assignments modal
function openAssignmentsModal() {
    if (!assignmentsModal) return;
    
    assignmentsModal.classList.remove('hidden');
    assignmentsModal.showModal();
    loadAllAssignments();
    
    // Focus trap
    const focusableElements = assignmentsModal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (focusableElements.length > 0) {
        focusableElements[0].focus();
    }
}

// Close assignments modal
function closeAssignmentsModal() {
    if (assignmentsModal) {
        assignmentsModal.close();
        assignmentsModal.classList.add('hidden');
    }
}

// Load all assignments
async function loadAllAssignments() {
    const loadingEl = document.getElementById('assignment-loading');
    const itemsEl = document.getElementById('assignment-items');
    const noAssignmentsEl = document.getElementById('no-assignments');
    
    // Show loading
    if (loadingEl) loadingEl.classList.remove('hidden');
    if (itemsEl) itemsEl.classList.add('hidden');
    if (noAssignmentsEl) noAssignmentsEl.classList.add('hidden');
    
    try {
        const response = await fetch(getAssignmentApiBasePath() + 'get-all-assignments.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.assignments) {
            allAssignments = data.assignments;
            filteredAssignments = [...allAssignments];
            
            if (allAssignments.length > 0) {
                filterAndRenderAssignments();
            } else {
                if (noAssignmentsEl) noAssignmentsEl.classList.remove('hidden');
            }
        } else {
            console.error('Error loading assignments:', data.message);
            if (noAssignmentsEl) noAssignmentsEl.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error loading assignments:', error);
        if (noAssignmentsEl) noAssignmentsEl.classList.remove('hidden');
    } finally {
        if (loadingEl) loadingEl.classList.add('hidden');
    }
}

// Filter and render assignments
function filterAndRenderAssignments() {
    const searchInput = document.getElementById('assignment-search');
    const sortSelect = document.getElementById('assignment-sort');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const sortValue = sortSelect ? sortSelect.value : 'created_desc';
    
    // Filter assignments
    filteredAssignments = allAssignments.filter(assignment => {
        if (!searchTerm) return true;
        
        return assignment.title.toLowerCase().includes(searchTerm) ||
               assignment.class_name.toLowerCase().includes(searchTerm) ||
               (assignment.description && assignment.description.toLowerCase().includes(searchTerm));
    });
    
    // Sort assignments
    switch (sortValue) {
        case 'created_asc':
            filteredAssignments.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            break;
        case 'name_asc':
            filteredAssignments.sort((a, b) => a.title.localeCompare(b.title));
            break;
        case 'name_desc':
            filteredAssignments.sort((a, b) => b.title.localeCompare(a.title));
            break;
        case 'deadline_asc':
            filteredAssignments.sort((a, b) => {
                if (!a.deadline && !b.deadline) return 0;
                if (!a.deadline) return 1;
                if (!b.deadline) return -1;
                return new Date(a.deadline) - new Date(b.deadline);
            });
            break;
        case 'deadline_desc':
            filteredAssignments.sort((a, b) => {
                if (!a.deadline && !b.deadline) return 0;
                if (!a.deadline) return -1;
                if (!b.deadline) return 1;
                return new Date(b.deadline) - new Date(a.deadline);
            });
            break;
        default: // created_desc
            filteredAssignments.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            break;
    }
    
    renderAssignments();
}

// Render assignments to the list
function renderAssignments() {
    const itemsEl = document.getElementById('assignment-items');
    const noAssignmentsEl = document.getElementById('no-assignments');
    
    if (!itemsEl) return;
    
    itemsEl.innerHTML = '';
    
    if (filteredAssignments.length === 0) {
        itemsEl.classList.add('hidden');
        if (noAssignmentsEl) noAssignmentsEl.classList.remove('hidden');
        return;
    }
    
    if (noAssignmentsEl) noAssignmentsEl.classList.add('hidden');
    itemsEl.classList.remove('hidden');
    
    filteredAssignments.forEach(assignment => {
        const assignmentEl = createAssignmentElement(assignment);
        itemsEl.appendChild(assignmentEl);
    });
}

// Create assignment element
function createAssignmentElement(assignment) {
    const div = document.createElement('div');
    div.className = 'assignment-item p-3 sm:p-4 bg-gray-50 hover:bg-gray-100 rounded-lg cursor-pointer transition-colors';
    div.setAttribute('data-assignment-id', assignment.id);
    div.setAttribute('data-class-id', assignment.class_id);
    
    // Determine status
    const status = getAssignmentStatus(assignment);
    const statusInfo = getAssignmentStatusInfo(status);
    
    // Format deadline
    let deadlineText = 'Tidak ada deadline';
    if (assignment.deadline) {
        const deadline = new Date(assignment.deadline);
        deadlineText = deadline.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    div.innerHTML = `
        <div class="flex items-start space-x-3">
            <!-- Assignment Icon -->
            <div class="flex-shrink-0 mt-0.5">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="ti ti-clipboard-text text-blue-600 text-lg sm:text-xl"></i>
                </div>
            </div>
            
            <!-- Assignment Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2 mb-1">
                            <h4 class="text-sm sm:text-base font-medium text-gray-900 truncate">${assignment.title}</h4>
                            <span class="text-xs px-2 py-1 rounded-full font-medium ${statusInfo.badgeClass}">${statusInfo.text}</span>
                        </div>
                        <p class="text-xs sm:text-sm text-gray-600 mb-2 line-clamp-2">${assignment.description || 'Tidak ada deskripsi'}</p>
                        <div class="flex flex-col sm:flex-row sm:items-center text-xs text-gray-500 space-y-1 sm:space-y-0 sm:space-x-4">
                            <span class="flex items-center">
                                <i class="ti ti-book mr-1"></i>
                                ${assignment.class_name}
                            </span>
                            <span class="flex items-center">
                                <i class="ti ti-clock mr-1"></i>
                                ${deadlineText}
                            </span>
                            ${assignment.score !== null && assignment.score !== undefined ? `
                            <span class="flex items-center">
                                <i class="ti ti-star mr-1"></i>
                                ${assignment.score}/${assignment.max_score || 100}
                            </span>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Status Indicator -->
                    <div class="flex items-center space-x-2 ml-3">
                        <div class="w-3 h-3 rounded-full ${statusInfo.indicatorClass}"></div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add click handler
    div.onclick = function() {
        handleAssignmentClick(assignment);
    };
    
    return div;
}

// Get assignment status based on data
function getAssignmentStatus(assignment) {
    const now = new Date();
    const deadline = assignment.deadline ? new Date(assignment.deadline) : null;
    
    if (assignment.score !== null && assignment.score !== undefined) {
        return 'graded';
    } else if (assignment.submitted_at) {
        return 'submitted';
    } else if (deadline && deadline < now) {
        return 'expired';
    } else {
        return 'not_submitted';
    }
}

// Get status information for styling
function getAssignmentStatusInfo(status) {
    switch (status) {
        case 'graded':
            return {
                text: 'Dinilai',
                badgeClass: 'bg-green-100 text-green-800',
                indicatorClass: 'bg-green-500'
            };
        case 'submitted':
            return {
                text: 'Dikerjakan',
                badgeClass: 'bg-blue-100 text-blue-800',
                indicatorClass: 'bg-blue-500'
            };
        case 'expired':
            return {
                text: 'Expired',
                badgeClass: 'bg-red-100 text-red-800',
                indicatorClass: 'bg-red-500'
            };
        default: // not_submitted
            return {
                text: 'Belum Dikerjakan',
                badgeClass: 'bg-orange-100 text-orange-800',
                indicatorClass: 'bg-orange-500'
            };
    }
}

// Handle assignment click
function handleAssignmentClick(assignment) {
    console.log('🎯 Assignment clicked:', assignment);
    // Close modal first
    closeAssignmentsModal();
    // Redirect to class page with assignment post highlighted
    const redirectUrl = `./kelas-user.php?id=${assignment.class_id}&tab=assignments#post-assignment-${assignment.id}`;
    setTimeout(() => {
        window.location.href = redirectUrl;
    }, 300);
}
</script>
