// Upgrade to Pro Modal JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const upgradeModal = document.getElementById('upgradeToProModal');
    const upgradeBtn = document.getElementById('upgradeToProBtn');
    const cancelBtn = document.getElementById('cancelUpgradeBtn');
    
    if (!upgradeModal) return;
    
    // Initialize modal event listeners
    initializeUpgradeModal();
    
    function initializeUpgradeModal() {
        // Cancel button - close modal
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                closeUpgradeToProModal();
            });
        }
        
        // Upgrade button - simulate upgrade process
        if (upgradeBtn) {
            upgradeBtn.addEventListener('click', function() {
                handleUpgradeProcess();
            });
        }
        
        // Close modal when clicking backdrop
        upgradeModal.addEventListener('click', function(e) {
            if (e.target === upgradeModal) {
                closeUpgradeToProModal();
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !upgradeModal.classList.contains('hidden')) {
                closeUpgradeToProModal();
            }
        });
    }
});

// Function to show upgrade to pro modal
function showUpgradeToProModal() {
    const upgradeModal = document.getElementById('upgradeToProModal');
    if (!upgradeModal) {
        console.error('Upgrade modal not found');
        return;
    }
    
    console.log('Showing upgrade modal'); // Debug log
    
    // Reset modal state
    resetUpgradeModalState();
    
    // Force show modal
    upgradeModal.style.cssText = 'display: block !important; z-index: 2147483647 !important; position: fixed !important; inset: 0 !important;';
    upgradeModal.classList.remove('hidden');
    
    // Add animation classes
    const panel = upgradeModal.querySelector('.modal-panel');
    if (panel) {
        panel.classList.add('modal-enter');
        setTimeout(() => {
            panel.classList.remove('modal-enter');
            panel.classList.add('modal-enter-active');
        }, 10);
    }
    
    // Ensure body doesn't scroll when modal is open
    document.body.style.overflow = 'hidden';
    
    // Focus management
    setTimeout(() => {
        const firstFocusable = upgradeModal.querySelector('button:not([disabled])');
        if (firstFocusable) {
            firstFocusable.focus();
        }
    }, 100);
}

// Function to close upgrade to pro modal
function closeUpgradeToProModal() {
    const upgradeModal = document.getElementById('upgradeToProModal');
    if (!upgradeModal) return;
    
    console.log('Closing upgrade modal'); // Debug log
    
    // Restore body scroll
    document.body.style.overflow = '';
    
    // Add closing animation classes
    const panel = upgradeModal.querySelector('.modal-panel');
    if (panel) {
        panel.classList.remove('modal-enter-active');
        panel.classList.add('modal-leave', 'modal-leave-active');
    }
    
    // Hide modal after animation
    setTimeout(() => {
        upgradeModal.style.display = 'none';
        upgradeModal.classList.add('hidden');
        
        // Reset animation classes
        if (panel) {
            panel.classList.remove('modal-leave', 'modal-leave-active', 'modal-enter');
        }
        
        // Clear inline styles
        upgradeModal.style.cssText = '';
    }, 150);
}

// Function to handle upgrade process (simulation)
function handleUpgradeProcess() {
    const upgradeBtn = document.getElementById('upgradeToProBtn');
    const btnText = upgradeBtn.querySelector('.upgrade-pro-btn-text');
    const btnLoading = upgradeBtn.querySelector('.upgrade-pro-btn-loading');
    
    // Show loading state
    setUpgradeButtonLoading(true);
    
    // Simulate upgrade process
    setTimeout(() => {
        // For now, just show a success message and close modal
        showUpgradeSuccessMessage();
        closeUpgradeToProModal();
        
        // Reset button state
        setTimeout(() => {
            setUpgradeButtonLoading(false);
        }, 500);
    }, 2000);
}

// Function to set loading state for upgrade button
function setUpgradeButtonLoading(isLoading) {
    const upgradeBtn = document.getElementById('upgradeToProBtn');
    const btnText = upgradeBtn.querySelector('.upgrade-pro-btn-text');
    const btnLoading = upgradeBtn.querySelector('.upgrade-pro-btn-loading');
    
    if (isLoading) {
        upgradeBtn.disabled = true;
        btnText.textContent = 'Memproses...';
        btnLoading.classList.remove('hidden');
    } else {
        upgradeBtn.disabled = false;
        btnText.textContent = 'Upgrade Sekarang';
        btnLoading.classList.add('hidden');
    }
}

// Function to reset modal state
function resetUpgradeModalState() {
    setUpgradeButtonLoading(false);
}

// Function to show upgrade success message
function showUpgradeSuccessMessage() {
    // Create a temporary success notification
    const successDiv = document.createElement('div');
    successDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-[10001] transition-all transform translate-x-full';
    successDiv.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>Upgrade berhasil! (Simulasi)</span>
        </div>
    `;
    
    document.body.appendChild(successDiv);
    
    // Animate in
    setTimeout(() => {
        successDiv.classList.remove('translate-x-full');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        successDiv.classList.add('translate-x-full');
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.parentNode.removeChild(successDiv);
            }
        }, 300);
    }, 3000);
}

// Function to check if user can create class (to be called after potential upgrade)
function checkClassCreationStatus() {
    // This would typically make an API call to check updated status
    // For simulation purposes, we'll just reload the page
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}