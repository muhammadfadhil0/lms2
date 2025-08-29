/**
 * Class Settings Management
 * Mengelola modal-modal pengaturan kelas dengan navigasi bertingkat
 */

class ClassSettingsManager {
    constructor(kelasId) {
        this.kelasId = kelasId;
        this.currentStudentToRemove = null;
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Search functionality for students
        const searchInput = document.getElementById('search-students');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.searchStudents(e.target.value));
        }

        // File preview functionality
        const backgroundInput = document.getElementById('new-background');
        if (backgroundInput) {
            backgroundInput.addEventListener('change', (e) => this.previewBackground(e.target));
        }
    }

    // Modal Management Functions
    openMainSettings() {
        const modal = document.getElementById('class-settings-modal');
        if (modal) {
            modal.showModal();
        }
    }

    closeMainSettings() {
        const modal = document.getElementById('class-settings-modal');
        if (modal) {
            modal.close();
        }
    }

    openBackgroundModal() {
        this.closeMainSettings();
        const modal = document.getElementById('class-background-modal');
        if (modal) {
            modal.showModal();
        }
    }

    openEditClassModal() {
        this.closeMainSettings();
        const modal = document.getElementById('edit-class-modal');
        if (modal) {
            modal.showModal();
        }
    }

    openManageStudentsModal() {
        this.closeMainSettings();
        const modal = document.getElementById('manage-students-modal');
        if (modal) {
            modal.showModal();
        }
    }

    openPermissionsModal() {
        this.closeMainSettings();
        const modal = document.getElementById('class-permissions-modal');
        if (modal) {
            modal.showModal();
            
            // Debug: Log checkbox states when modal opens
            setTimeout(() => {
                const restrictPosting = document.getElementById('restrict-posting');
                const restrictComments = document.getElementById('restrict-comments');
                const lockClass = document.getElementById('lock-class');
                
                console.log('=== PERMISSIONS MODAL OPENED ===');
                console.log('Checkbox initial states:', {
                    restrict_posting: restrictPosting ? restrictPosting.checked : 'NOT FOUND',
                    restrict_comments: restrictComments ? restrictComments.checked : 'NOT FOUND',
                    lock_class: lockClass ? lockClass.checked : 'NOT FOUND'
                });
                
                // Log HTML content to see if PHP values are rendered correctly
                if (restrictPosting) {
                    console.log('restrict-posting HTML:', restrictPosting.outerHTML);
                }
                
                // Show debug info
                const debugDiv = document.getElementById('debug-permissions');
                if (debugDiv) {
                    debugDiv.style.display = 'block';
                }
            }, 100);
        }
    }

    backToSettings() {
        // Close all sub-modals
        const modals = [
            'class-background-modal',
            'edit-class-modal', 
            'manage-students-modal',
            'class-permissions-modal'
        ];
        
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.close();
            }
        });

        // Reopen main settings
        setTimeout(() => {
            this.openMainSettings();
        }, 200);
    }

    // Background Functions
    previewBackground(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            const previewContainer = document.getElementById('new-preview-container');
            const previewImg = document.getElementById('new-background-preview');
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.classList.remove('hidden');
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    async updateBackground(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'update_background');
        formData.append('kelas_id', this.kelasId);

        try {
            const response = await fetch('../logic/kelas-logic.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                this.showModalAlert('success', 'Berhasil!', 'Latar belakang berhasil diperbarui!');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                this.showModalAlert('error', 'Gagal!', result.message || 'Gagal memperbarui latar belakang');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showModalAlert('error', 'Kesalahan!', 'Terjadi kesalahan saat memperbarui latar belakang');
        }
    }

    // Class Edit Functions
    async updateClass(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'update_class');
        formData.append('kelas_id', this.kelasId);

        try {
            const response = await fetch('../logic/kelas-logic.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                this.showModalAlert('success', 'Berhasil!', 'Kelas berhasil diperbarui!');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                this.showModalAlert('error', 'Gagal!', result.message || 'Gagal memperbarui kelas');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showModalAlert('error', 'Kesalahan!', 'Terjadi kesalahan saat memperbarui kelas');
        }
    }

    // Student Management Functions
    searchStudents(query) {
        const studentItems = document.querySelectorAll('.student-item');
        const searchTerm = query.toLowerCase();

        studentItems.forEach(item => {
            const studentName = item.querySelector('h4').textContent.toLowerCase();
            const studentEmail = item.querySelector('p').textContent.toLowerCase();
            
            if (studentName.includes(searchTerm) || studentEmail.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    confirmRemoveStudent(studentId, studentName) {
        this.currentStudentToRemove = studentId;
        document.getElementById('student-name-confirm').textContent = studentName;
        document.getElementById('confirm-remove-student-modal').showModal();
    }

    closeConfirmRemoveModal() {
        document.getElementById('confirm-remove-student-modal').close();
        this.currentStudentToRemove = null;
    }

    async removeStudent() {
        if (!this.currentStudentToRemove) return;

        try {
            const formData = new FormData();
            formData.append('action', 'remove_student');
            formData.append('kelas_id', this.kelasId);
            formData.append('student_id', this.currentStudentToRemove);

            const response = await fetch('../logic/kelas-logic.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Siswa berhasil dikeluarkan dari kelas', 'success');
                
                // Remove student from UI
                const studentItem = document.querySelector(`[data-student-id="${this.currentStudentToRemove}"]`);
                if (studentItem) {
                    studentItem.remove();
                }
                
                // Update student count
                const countElement = document.getElementById('student-count');
                if (countElement) {
                    const currentCount = parseInt(countElement.textContent);
                    countElement.textContent = currentCount - 1;
                }
                
                this.closeConfirmRemoveModal();
            } else {
                this.showNotification(result.message || 'Gagal mengeluarkan siswa', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Terjadi kesalahan saat mengeluarkan siswa', 'error');
        }
    }

    copyClassCode() {
        const codeElement = document.querySelector('code');
        if (codeElement) {
            navigator.clipboard.writeText(codeElement.textContent.trim()).then(() => {
                this.showNotification('Kode kelas berhasil disalin!', 'success');
            }).catch(() => {
                this.showNotification('Gagal menyalin kode kelas', 'error');
            });
        }
    }

    // Permissions Functions
    async updatePermissions(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData();
        
        // Add action and kelas_id
        formData.append('action', 'update_permissions');
        formData.append('kelas_id', this.kelasId);
        
        // Add checkbox values explicitly
        const restrictPosting = document.getElementById('restrict-posting').checked;
        const restrictComments = document.getElementById('restrict-comments').checked;
        const lockClass = document.getElementById('lock-class').checked;
        
        if (restrictPosting) formData.append('restrict_posting', 'on');
        if (restrictComments) formData.append('restrict_comments', 'on');
        if (lockClass) formData.append('lock_class', 'on');
        
        // Debug logging - enhanced
        console.log('=== PERMISSIONS UPDATE DEBUG ===');
        console.log('Checkbox states:', {
            restrict_posting: restrictPosting,
            restrict_comments: restrictComments,
            lock_class: lockClass
        });
        
        // Log FormData contents
        const formDataEntries = {};
        for (let [key, value] of formData.entries()) {
            formDataEntries[key] = value;
        }
        console.log('FormData contents:', formDataEntries);

        try {
            const response = await fetch('../logic/kelas-logic.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                // Show different messages based on what was changed
                const restrictPosting = document.getElementById('restrict-posting').checked;
                const lockClass = document.getElementById('lock-class').checked;
                
                let message = 'Pengaturan perizinan berhasil diperbarui!';
                if (lockClass) {
                    message = 'Kelas telah dikunci dan tidak menerima mahasiswa baru';
                }
                
                this.showModalAlert('success', 'Berhasil!', message);
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                this.showModalAlert('error', 'Gagal!', result.message || 'Gagal memperbarui pengaturan');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showModalAlert('error', 'Kesalahan!', 'Terjadi kesalahan saat memperbarui pengaturan');
        }
    }

    // Utility Functions
    showModalAlert(type, title, message, autoHide = 5000) {
        // Hide all alerts first
        this.hideAllModalAlerts();
        
        // Get the appropriate alert element
        const alertElement = document.getElementById(`modal-alert-${type}`);
        const titleElement = document.getElementById(`modal-alert-${type}-title`);
        const messageElement = document.getElementById(`modal-alert-${type}-message`);
        
        if (alertElement && titleElement && messageElement) {
            // Set content
            titleElement.textContent = title;
            messageElement.textContent = message;
            
            // Show alert
            alertElement.classList.remove('hidden');
            
            // Scroll to top of modal to ensure alert is visible
            const modalContent = alertElement.closest('.bg-white');
            if (modalContent) {
                modalContent.scrollTop = 0;
            }
            
            // Auto hide if set
            if (autoHide > 0) {
                setTimeout(() => {
                    this.hideModalAlert(type);
                }, autoHide);
            }
        }
    }

    hideModalAlert(type) {
        const alertElement = document.getElementById(`modal-alert-${type}`);
        if (alertElement) {
            alertElement.classList.add('hidden');
        }
    }

    hideAllModalAlerts() {
        const types = ['success', 'error', 'warning', 'info'];
        types.forEach(type => {
            this.hideModalAlert(type);
        });
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
        
        // Set very high z-index to appear above modals and backdrops
        notification.style.zIndex = '9999';
        
        if (type === 'success') {
            notification.className += ' bg-green-500 text-white';
        } else if (type === 'error') {
            notification.className += ' bg-red-500 text-white';
        } else {
            notification.className += ' bg-blue-500 text-white';
        }
        
        notification.innerHTML = `
            <div class="flex items-center">
                <span class="mr-2">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        `;
        
        // Add additional styling for better visibility above modals
        notification.style.boxShadow = '0 25px 50px -12px rgba(0, 0, 0, 0.5)';
        notification.style.border = '2px solid rgba(255, 255, 255, 0.2)';
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Auto remove after 4 seconds (increased for better visibility)
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, 4000);
    }
}

// Global functions for onclick handlers
function openClassSettings() {
    if (window.classSettings) {
        window.classSettings.openMainSettings();
    }
}

function openBackgroundModal() {
    if (window.classSettings) {
        window.classSettings.openBackgroundModal();
    }
}

function openEditClassModal() {
    if (window.classSettings) {
        window.classSettings.openEditClassModal();
    }
}

function openManageStudentsModal() {
    if (window.classSettings) {
        window.classSettings.openManageStudentsModal();
    }
}

function openPermissionsModal() {
    if (window.classSettings) {
        window.classSettings.openPermissionsModal();
    }
}

function backToSettings() {
    if (window.classSettings) {
        window.classSettings.backToSettings();
    }
}

function previewBackground(input) {
    if (window.classSettings) {
        window.classSettings.previewBackground(input);
    }
}

function updateBackground(event) {
    if (window.classSettings) {
        window.classSettings.updateBackground(event);
    }
}

function updateClass(event) {
    if (window.classSettings) {
        window.classSettings.updateClass(event);
    }
}

function confirmRemoveStudent(studentId, studentName) {
    if (window.classSettings) {
        window.classSettings.confirmRemoveStudent(studentId, studentName);
    }
}

function closeConfirmRemoveModal() {
    if (window.classSettings) {
        window.classSettings.closeConfirmRemoveModal();
    }
}

function removeStudent() {
    if (window.classSettings) {
        window.classSettings.removeStudent();
    }
}

function copyClassCode() {
    if (window.classSettings) {
        window.classSettings.copyClassCode();
    }
}

function updatePermissions(event) {
    if (window.classSettings) {
        window.classSettings.updatePermissions(event);
    }
}
