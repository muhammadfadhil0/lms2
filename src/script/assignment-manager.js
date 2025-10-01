class AssignmentManager {
    constructor(kelasId, userRole) {
        this.kelasId = kelasId;
        this.userRole = userRole;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadAssignmentsInPosts();
    }

    bindEvents() {
        // Create assignment modal events (for teachers)
        if (this.userRole === 'guru') {
            const createForm = document.getElementById('create-assignment-form');
            if (createForm) {
                createForm.addEventListener('submit', (e) => this.handleCreateAssignment(e));
            }
        }

        // Submit assignment modal events (for students)
        if (this.userRole === 'siswa') {
            const submitForm = document.getElementById('submit-assignment-form');
            if (submitForm) {
                submitForm.addEventListener('submit', (e) => this.handleSubmitAssignment(e));
            }
        }
    }

    async handleCreateAssignment(event) {
        event.preventDefault();
        
        console.log('✏️ [DEBUG] handleCreateAssignment started');
        console.log('✏️ [DEBUG] window.assignmentFilesArray exists:', !!window.assignmentFilesArray);
        console.log('✏️ [DEBUG] window.assignmentFilesArray content:', window.assignmentFilesArray);
        
        // Create new FormData from form
        const formData = new FormData(event.target);
        formData.append('kelas_id', this.kelasId);
        
        // Remove any existing assignment_file entries from the default form data
        formData.delete('assignment_file');
        formData.delete('assignment_files[]');
        
        // Add multiple files from file manager if any exist
        if (window.assignmentFilesArray && window.assignmentFilesArray.length > 0) {
            console.log('✏️ [DEBUG] Adding files from assignmentFilesArray to FormData');
            
            // Add all selected files
            console.log('Adding files to FormData:', window.assignmentFilesArray);
            window.assignmentFilesArray.forEach((file, index) => {
                console.log(`Adding file ${index}:`, file.name, file.size, file.type);
                formData.append('assignment_files[]', file);
            });
            
            // Debug: Check FormData content
            console.log('FormData entries:');
            for (let pair of formData.entries()) {
                console.log(pair[0], pair[1]);
            }
        } else {
            console.log('✏️ [DEBUG] No files in assignmentFilesArray or array is empty');
            console.log('✏️ [DEBUG] assignmentFilesArray length:', window.assignmentFilesArray ? window.assignmentFilesArray.length : 'undefined');
            console.log('✏️ [DEBUG] Proceeding without files (files are optional)');
        }
        
        // Debug logging
        console.log('✏️ [DEBUG] Final FormData contents:');
        for (let pair of formData.entries()) {
            if (pair[1] instanceof File) {
                console.log(`✏️ [DEBUG] FormData file: ${pair[0]} = ${pair[1].name} (${pair[1].size} bytes)`);
            } else {
                console.log(`✏️ [DEBUG] FormData field: ${pair[0]} = ${pair[1]}`);
            }
        }
        
        console.log('Creating assignment with data:', {
            kelas_id: this.kelasId,
            assignmentTitle: formData.get('assignmentTitle'),
            assignmentDescription: formData.get('assignmentDescription'),
            assignmentDeadline: formData.get('assignmentDeadline'),
            maxScore: formData.get('maxScore'),
            fileCount: window.assignmentFilesArray ? window.assignmentFilesArray.length : 0
        });
        
        try {
            const response = await fetch('../logic/create-assignment.php', {
                method: 'POST',
                body: formData
            });
            
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success) {
                this.closeCreateAssignmentModal();
                // Reload posts to show new assignment
                if (window.kelasPosting) {
                    window.kelasPosting.loadPostingan(true);
                }
                this.showNotification('Tugas berhasil dibuat', 'success');
            } else {
                this.showNotification('Gagal membuat tugas: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Terjadi kesalahan saat membuat tugas', 'error');
        }
    }

    async handleSubmitAssignment(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        
        try {
            const response = await fetch('../logic/submit-assignment.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.closeSubmitAssignmentModal();
                // Reload posts to update assignment status
                if (window.kelasPosting) {
                    window.kelasPosting.loadPostingan(true);
                }
                this.showNotification('Tugas berhasil dikumpulkan', 'success');
            } else {
                this.showNotification('Gagal mengumpulkan tugas: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Terjadi kesalahan saat mengumpulkan tugas', 'error');
        }
    }

    openCreateAssignmentModal() {
        const modal = document.getElementById('create-assignment-modal');
        if (modal) {
            modal.showModal();
            // Set minimum deadline to current time
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('assignmentDeadline').min = now.toISOString().slice(0, 16);
        }
    }

    closeCreateAssignmentModal() {
        const modal = document.getElementById('create-assignment-modal');
        if (modal) {
            modal.close();
            document.getElementById('create-assignment-form').reset();
            
            // Reset file manager
            if (typeof resetAssignmentFileManager === 'function') {
                resetAssignmentFileManager();
            }
        }
    }

    openSubmitAssignmentModal(assignmentId, assignmentData) {
        const modal = document.getElementById('submit-assignment-modal');
        if (modal) {
            // Populate assignment details
            document.getElementById('assignment_id').value = assignmentId;
            document.getElementById('assignment-title-display').textContent = assignmentData.judul;
            document.getElementById('assignment-description-display').textContent = assignmentData.deskripsi;
            document.getElementById('assignment-deadline-display').textContent = 
                new Date(assignmentData.deadline).toLocaleString('id-ID');
            document.getElementById('assignment-maxscore-display').textContent = assignmentData.nilai_maksimal;
            
            // Check if student has already submitted
            this.checkSubmissionStatus(assignmentId);
            
            modal.showModal();
        }
    }

    closeSubmitAssignmentModal() {
        const modal = document.getElementById('submit-assignment-modal');
        if (modal) {
            modal.close();
            document.getElementById('submit-assignment-form').reset();
            document.getElementById('current-submission-status').classList.add('hidden');
        }
    }

    async checkSubmissionStatus(assignmentId) {
        try {
            const response = await fetch(`../logic/get-student-submission.php?assignment_id=${assignmentId}`);
            const data = await response.json();
            
            if (data.success && data.submission) {
                this.showCurrentSubmissionStatus(data.submission);
            }
        } catch (error) {
            console.error('Error checking submission status:', error);
        }
    }

    showCurrentSubmissionStatus(submission) {
        const statusContainer = document.getElementById('current-submission-status');
        const statusContent = document.getElementById('submission-status-content');
        
        let statusText = '';
        let statusClass = '';
        
        switch(submission.status) {
            case 'dikumpulkan':
                statusText = 'Tugas sudah dikumpulkan, menunggu penilaian';
                statusClass = 'text-yellow-800';
                break;
            case 'dinilai':
                statusText = `Tugas sudah dinilai: ${submission.nilai}/${submission.nilai_maksimal}`;
                statusClass = 'text-green-800';
                if (submission.feedback) {
                    statusText += `<br><strong>Feedback:</strong> ${submission.feedback}`;
                }
                break;
        }
        
        statusContent.innerHTML = `
            <p class="${statusClass}">${statusText}</p>
            <p class="text-sm text-gray-600 mt-1">Dikumpulkan: ${new Date(submission.tanggal_pengumpulan).toLocaleString('id-ID')}</p>
            <p class="text-sm text-blue-600 mt-2">Anda dapat mengumpulkan ulang untuk mengganti file sebelumnya.</p>
        `;
        
        statusContainer.classList.remove('hidden');
        
        // Update button text for resubmission
        document.getElementById('submit-assignment-btn').textContent = 'Kumpulkan Ulang';
    }

    async loadAssignmentsInPosts() {
        // This will be called by the posts loading system to include assignment posts
        // The implementation will be integrated with the existing posting system
    }

    showNotification(message, type = 'info') {
        // Create a simple notification
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
            type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
            type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
            'bg-blue-100 text-blue-800 border border-blue-200'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
}

// Global functions for modal controls
function openCreateAssignmentModal() {
    if (window.assignmentManager) {
        window.assignmentManager.openCreateAssignmentModal();
    }
}

function closeCreateAssignmentModal() {
    if (window.assignmentManager) {
        window.assignmentManager.closeCreateAssignmentModal();
    }
}

function openSubmitAssignmentModal(assignmentId, assignmentData) {
    if (window.assignmentManager) {
        window.assignmentManager.openSubmitAssignmentModal(assignmentId, assignmentData);
    }
}

function closeSubmitAssignmentModal() {
    if (window.assignmentManager) {
        window.assignmentManager.closeSubmitAssignmentModal();
    }
}

function openAssignmentReports() {
    const kelasId = window.location.search.match(/id=(\d+)/);
    if (kelasId) {
        window.location.href = `assignment-reports.php?id=${kelasId[1]}`;
    }
}

// Global assignment functions for backward compatibility
window.createAssignment = function(event) {
    if (window.assignmentManager) {
        window.assignmentManager.handleCreateAssignment(event);
    }
};

window.submitAssignment = function(event) {
    if (window.assignmentManager) {
        window.assignmentManager.handleSubmitAssignment(event);
    }
};
