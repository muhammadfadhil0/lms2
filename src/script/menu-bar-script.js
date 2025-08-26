function toggleMobileProfile() {
        const modal = document.getElementById('mobileProfileModal');
        modal.classList.toggle('hidden');
    }

    // Close mobile profile modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('mobileProfileModal');
        const button = event.target.closest('button[onclick="toggleMobileProfile()"]');
        
        if (!button && modal && !modal.classList.contains('hidden') && !modal.querySelector('.bg-white').contains(event.target)) {
            modal.classList.add('hidden');
        }
    });

    // Add rounded corners to active menu items
    document.addEventListener('DOMContentLoaded', function() {
        const activeMenuItem = document.querySelector('.bg-orange-tipis');
        if (activeMenuItem) {
            activeMenuItem.classList.add('rounded-lg');
        }
    });