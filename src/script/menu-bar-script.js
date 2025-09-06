function toggleMobileProfile() {
    const modal = document.getElementById('mobileProfileModal');
    const backdrop = document.getElementById('mobileProfileBackdrop');
    const content = document.getElementById('mobileProfileContent');
    
    if (modal.classList.contains('hidden')) {
        // Show modal with animation
        modal.classList.remove('hidden');
        
        // Force reflow to ensure initial state is applied
        backdrop.offsetHeight;
        content.offsetHeight;
        
        // Add animation classes
        requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            backdrop.classList.add('opacity-100');
            content.classList.remove('translate-y-full');
            content.classList.add('translate-y-0');
        });
    } else {
        // Hide modal with animation
        backdrop.classList.remove('opacity-100');
        backdrop.classList.add('opacity-0');
        content.classList.remove('translate-y-0');
        content.classList.add('translate-y-full');
        
        // Wait for animation to complete before hiding
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300); // Match the transition duration
    }
}

    // Close mobile profile modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('mobileProfileModal');
        const button = event.target.closest('button[onclick="toggleMobileProfile()"]');
        
        if (!button && modal && !modal.classList.contains('hidden') && !modal.querySelector('.bg-white').contains(event.target)) {
            toggleMobileProfile();
        }
    });