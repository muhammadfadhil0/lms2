// Modal Edit Assignment Functions

// Local showToast function if not available globally
function showToastLocal(message, type = 'success') {
    // Remove existing toast if any
    const existingToast = document.querySelector('.toast-edit-assignment');
    if (existingToast) {
        existingToast.remove();
    }

    // Create new toast
    const toast = document.createElement('div');
    toast.className = 'toast-edit-assignment fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transform translate-x-full opacity-0 transition-all duration-300 ease-in-out flex items-center gap-2';
    
    if (type === 'success') {
        toast.classList.add('bg-green-500', 'text-white');
    } else if (type === 'error') {
        toast.classList.add('bg-red-500', 'text-white');
    } else {
        toast.classList.add('bg-blue-500', 'text-white');
    }
    
    toast.innerHTML = `
        <i class="ti ti-${type === 'success' ? 'check' : type === 'error' ? 'x' : 'info-circle'} text-lg"></i>
        <span class="font-medium">${message}</span>
    `;

    // Add to body
    document.body.appendChild(toast);

    // Show toast with animation
    setTimeout(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
    }, 100);

    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// Open Edit Assignment Modal
function openEditAssignmentModal(assignmentId) {
    console.log('Opening edit assignment modal for ID:', assignmentId);
    
    // Set assignment ID
    document.getElementById('editAssignmentId').value = assignmentId;
    
    // Fetch assignment data
    fetchAssignmentData(assignmentId);
    
    // Show modal
    const modal = document.getElementById('modalEditAssignment');
    modal.showModal();
    
    // Add backdrop click handler
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeEditAssignmentModal();
        }
    });
}

// Close Edit Assignment Modal
function closeEditAssignmentModal() {
    const modal = document.getElementById('modalEditAssignment');
    modal.close();
    
    // Reset form
    document.getElementById('edit-assignment-form').reset();
    document.getElementById('currentAssignmentFile').innerHTML = '';
    
    // Reset file manager
    if (typeof resetEditAssignmentFileManager === 'function') {
        resetEditAssignmentFileManager();
    }
}

// Fetch Assignment Data
function fetchAssignmentData(assignmentId) {
    console.log('Fetching assignment data for ID:', assignmentId);
    
    fetch(`../logic/handle-edit-assignment.php?action=get_assignment_detail&assignment_id=${assignmentId}`)
        .then(response => {
            console.log('Response status:', response.status);
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text.substring(0, 500));
                    throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
                });
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Assignment data response:', data);
            
            if (data.success) {
                populateAssignmentForm(data.assignment);
            } else {
                alert('Error: ' + data.message);
                closeEditAssignmentModal();
            }
        })
        .catch(error => {
            console.error('Error fetching assignment data:', error);
            alert('Terjadi kesalahan saat memuat data tugas: ' + error.message);
            closeEditAssignmentModal();
        });
}

// Populate Assignment Form
function populateAssignmentForm(assignment) {
    document.getElementById('editAssignmentTitle').value = assignment.judul || '';
    document.getElementById('editAssignmentDescription').value = assignment.deskripsi || '';
    document.getElementById('editMaxScore').value = assignment.nilai_maksimal || 100;
    
    // Format deadline for datetime-local input
    if (assignment.deadline) {
        const deadline = new Date(assignment.deadline);
        const formattedDeadline = deadline.toISOString().slice(0, 16);
        document.getElementById('editAssignmentDeadline').value = formattedDeadline;
    }
    
    // Display current files if exist
    displayCurrentAssignmentFiles(assignment.files || []);
    
    // Update file manager with current files
    if (typeof showCurrentAssignmentFiles === 'function') {
        showCurrentAssignmentFiles(assignment.files || []);
    }
}

// Display Current Assignment Files
function displayCurrentAssignmentFiles(files) {
    const container = document.getElementById('currentAssignmentFile');
    
    if (files && files.length > 0) {
        const filesHtml = files.map(file => {
            const icon = getFileIconClass(file.file_name);
            return `
                <div class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg mb-2 last:mb-0 file-item">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-100 rounded flex items-center justify-center">
                            <i class="${icon} text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-blue-900">${file.file_name}</p>
                            <p class="text-xs text-blue-600">File saat ini • ${formatFileSize(file.file_size)}</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="/lms${file.file_path}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Lihat
                        </a>
                        <button type="button" onclick="removeCurrentAssignmentFile(${file.id}, this)" class="text-red-600 hover:text-red-800 text-sm font-medium">
                            Hapus
                        </button>
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = `<div class="space-y-2">${filesHtml}</div>`;
    } else {
        container.innerHTML = `
            <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg text-center">
                <p class="text-sm text-gray-500">Tidak ada file tugas</p>
            </div>
        `;
    }
}

// This function is handled by assignment-file-manager.js
// No need to redefine it here since it's already globally available

// Handle Edit Assignment Form Submit
document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('edit-assignment-form');
    
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'edit_assignment');
            
            // Debug: Log form data being sent
            console.log('👍 Form data being sent:');
            console.log('👍 Assignment ID:', formData.get('assignment_id'));
            console.log('👍 Title:', formData.get('assignmentTitle'));
            console.log('👍 Description:', formData.get('assignmentDescription'));
            console.log('👍 Deadline:', formData.get('assignmentDeadline'));
            console.log('👍 Max Score:', formData.get('maxScore'));
            
            // Add files from file manager if available
            if (typeof prepareEditAssignmentFilesForSubmission === 'function') {
                const fileData = prepareEditAssignmentFilesForSubmission();
                // Merge file data into main form data
                for (let pair of fileData.entries()) {
                    formData.append(pair[0], pair[1]);
                }
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.textContent : 'Simpan Perubahan';
            if (submitBtn) {
                submitBtn.textContent = 'Menyimpan...';
                submitBtn.disabled = true;
            }
            
            console.log('👍 Sending request to server...');
            
            fetch('../logic/handle-edit-assignment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('👍 Server response received:', response.status);
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.log('👍 Non-JSON response:', text.substring(0, 200));
                        throw new Error('Server returned non-JSON response: ' + text.substring(0, 200) + '...');
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('👍 Server response data:', data);
                
                if (data.success) {
                    console.log('👍 Assignment update successful!');
                    
                    // Refresh the post display FIRST before closing modal
                    console.log('👍 Attempting to refresh posts...');
                    console.log('👍 window.kelasPosting exists:', !!window.kelasPosting);
                    console.log('👍 refreshPosts function exists:', !!(window.kelasPosting && window.kelasPosting.refreshPosts));
                    
                    if (window.kelasPosting && typeof window.kelasPosting.refreshPosts === 'function') {
                        console.log('👍 Using kelasPosting.refreshPosts()');
                        window.kelasPosting.refreshPosts();
                    } else if (typeof loadPosts === 'function') {
                        console.log('👍 Using loadPosts()');
                        loadPosts();
                    } else {
                        console.log('👍 Using fallback page reload');
                        // Fallback: reload the page if no refresh function is available
                        window.location.reload();
                        return; // Exit early since page will reload
                    }
                    
                    // Then close modal and show success message
                    if (typeof showToast === 'function') {
                        showToast('Tugas berhasil diperbarui!');
                    } else {
                        showToastLocal('Tugas berhasil diperbarui!', 'success');
                    }
                    closeEditAssignmentModal();
                } else {
                    console.log('👍 Assignment update failed:', data.message);
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('👍 Error occurred:', error);
                if (error.message.includes('Server returned non-JSON response')) {
                    console.log('👍 Server returned non-JSON response');
                    if (typeof showToast === 'function') {
                        showToast('Server error: Terjadi kesalahan pada server');
                    } else {
                        showToastLocal('Server error: Terjadi kesalahan pada server', 'error');
                    }
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Terjadi kesalahan saat menyimpan perubahan');
                    } else {
                        showToastLocal('Terjadi kesalahan saat menyimpan perubahan', 'error');
                    }
                }
            })
            .finally(() => {
                // Reset button state
                if (submitBtn) {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            });
        });
    }
});