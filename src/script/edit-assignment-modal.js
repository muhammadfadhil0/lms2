// Modal Edit Assignment Functions

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
    fetch(`../logic/handle-edit-assignment.php?action=get_assignment_detail&assignment_id=${assignmentId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                populateAssignmentForm(data.assignment);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching assignment data:', error);
            alert('Terjadi kesalahan saat memuat data tugas');
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
            
            fetch('../logic/handle-edit-assignment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Tugas berhasil diperbarui!');
                    closeEditAssignmentModal();
                    
                    // Refresh the post display
                    if (typeof loadPosts === 'function') {
                        loadPosts();
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan perubahan');
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