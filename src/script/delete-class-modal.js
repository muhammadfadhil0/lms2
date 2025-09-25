// Delete Class Modal Functionality
let currentClassToDelete = null;

// Function to show delete class modal
function showDeleteClassModal(kelasId, kelasName) {
    currentClassToDelete = kelasId;
    const modal = document.getElementById('deleteClassModal');
    const classNameSpan = document.getElementById('classNameToDelete');
    
    if (modal && classNameSpan) {
        classNameSpan.textContent = kelasName;
        modal.showModal();
        
        // Add show class to modal for animation
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }
}

// Function to close delete class modal
function closeDeleteClassModal() {
    const modal = document.getElementById('deleteClassModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.close();
            currentClassToDelete = null;
        }, 200);
    }
}

// Function to delete class
async function deleteClass() {
    if (!currentClassToDelete) return;
    
    const confirmBtn = document.getElementById('confirmDeleteClassBtn');
    const btnText = confirmBtn.querySelector('.delete-class-btn-text');
    const btnLoading = confirmBtn.querySelector('.delete-class-btn-loading');
    
    // Show loading state
    confirmBtn.disabled = true;
    btnText.textContent = 'Menghapus...';
    btnLoading.classList.remove('hidden');
    
    try {
        const response = await fetch('../logic/delete-class-process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                kelas_id: currentClassToDelete
            })
        });
        
        // Try to parse JSON only if response has content-type application/json
        let result = null;
        const contentType = response.headers.get('content-type') || '';
        if (contentType.indexOf('application/json') !== -1) {
            result = await response.json();
        } else {
            const text = await response.text();
            try {
                result = text ? JSON.parse(text) : null;
            } catch (e) {
                result = null;
            }
        }
        
        if (result.success) {
            // Show success message
            showNotification('Kelas berhasil dihapus', 'success');
            
            // Close modal
            closeDeleteClassModal();
            
            // Remove the class card from the page
            const classCard = document.querySelector(`[data-class-id="${currentClassToDelete}"]`) || 
                             document.querySelector(`button[onclick*="${currentClassToDelete}"]`)?.closest('.bg-white');
            
            if (classCard) {
                classCard.style.transition = 'all 0.3s ease-out';
                classCard.style.transform = 'scale(0.95)';
                classCard.style.opacity = '0';
                
                setTimeout(() => {
                    classCard.remove();
                    
                    // Check if there are no more classes
                    const remainingClasses = document.querySelectorAll('.grid .bg-white').length;
                    if (remainingClasses === 0) {
                        // Reload page to show empty state
                        window.location.reload();
                    }
                }, 300);
            } else {
                // If we can't find the specific card, reload the page
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
            
        } else {
            showNotification(result.message || 'Gagal menghapus kelas', 'error');
        }
        
    } catch (error) {
        console.error('Error deleting class:', error);
        showNotification('Terjadi kesalahan saat menghapus kelas', 'error');
    } finally {
        // Reset button state
        confirmBtn.disabled = false;
        btnText.textContent = 'Hapus Kelas';
        btnLoading.classList.add('hidden');
    }
}

// Function to show notification
function showNotification(message, type = 'info') {
    // Create notification element with explicit slide animation and correct transforms
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;

    notification.style.transition = 'transform 0.28s ease, opacity 0.28s ease';
    notification.style.transform = 'translateX(100%)';
    notification.style.opacity = '1';

    notification.innerHTML = `
        <div class="flex items-center">
            <i class="ti ti-${type === 'success' ? 'check' : type === 'error' ? 'x' : 'info-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(notification);

    // Slide in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 20);

    // Hide notification after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Confirm delete button
    const confirmDeleteBtn = document.getElementById('confirmDeleteClassBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', deleteClass);
    }
    
    // Cancel delete button
    const cancelDeleteBtn = document.getElementById('cancelDeleteClassBtn');
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', closeDeleteClassModal);
    }
    
    // Close modal when clicking backdrop
    const modal = document.getElementById('deleteClassModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeDeleteClassModal();
            }
        });
    }
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.open) {
            closeDeleteClassModal();
        }
    });
});