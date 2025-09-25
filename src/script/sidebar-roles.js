// Role-based Sidebar Enhancements

document.addEventListener('DOMContentLoaded', function() {
    // Initialize role-based sidebar features
    initRoleBasedFeatures();
    
    // Add role-specific event listeners
    addRoleEventListeners();
    
    // Initialize profile dropdown
    initProfileDropdown();
});

function initRoleBasedFeatures() {
    const sidebar = document.getElementById('sidebar');
    const userRole = getUserRole(); // This should get role from session/data attribute
    
    if (sidebar) {
        sidebar.classList.add(`sidebar-${userRole}`);
        
        // Add role-specific styling to navigation items
        const navItems = sidebar.querySelectorAll('.buttonSidebar');
        navItems.forEach(item => {
            item.classList.add(`${userRole}-nav-item`);
        });
        
        // Add role indicator to profile avatar
        const profileAvatar = sidebar.querySelector('.w-10.h-10.rounded-full');
        if (profileAvatar) {
            profileAvatar.classList.add('role-indicator', userRole);
        }
    }
}

function getUserRole() {
    // Get user role from a data attribute or session
    const roleElement = document.querySelector('[data-user-role]');
    return roleElement ? roleElement.dataset.userRole : 'siswa';
}

function addRoleEventListeners() {
    const userRole = getUserRole();
    
    // Add role-specific click handlers
    switch(userRole) {
        case 'admin':
            addAdminEventListeners();
            break;
        case 'guru':
            addGuruEventListeners();
            break;
        case 'siswa':
            addSiswaEventListeners();
            break;
    }
}

function addAdminEventListeners() {
    // Admin-specific event listeners
    const adminActions = document.querySelectorAll('.admin-quick-action');
    adminActions.forEach(action => {
        action.addEventListener('click', function(e) {
            // Add animation effect for admin actions
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
}

function addGuruEventListeners() {
    // Guru-specific event listeners
    const guruNavItems = document.querySelectorAll('.guru-nav-item');
    guruNavItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#eff6ff';
        });
        
        item.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.backgroundColor = '';
            }
        });
    });
}

function addSiswaEventListeners() {
    // Siswa-specific event listeners
    const siswaNavItems = document.querySelectorAll('.siswa-nav-item');
    siswaNavItems.forEach(item => {
        item.addEventListener('click', function() {
            // Add ripple effect for siswa navigation
            createRippleEffect(this);
        });
    });
}

function createRippleEffect(element) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = '50%';
    ripple.style.top = '50%';
    ripple.style.transform = 'translate(-50%, -50%)';
    ripple.style.position = 'absolute';
    ripple.style.borderRadius = '50%';
    ripple.style.backgroundColor = 'rgba(22, 163, 74, 0.3)';
    ripple.style.animation = 'ripple 0.6s linear';
    ripple.style.pointerEvents = 'none';
    
    element.style.position = 'relative';
    element.style.overflow = 'hidden';
    element.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

function initProfileDropdown() {
    const profileButton = document.querySelector('[onclick="toggleProfileDropdown()"]');
    const profileDropdown = document.getElementById('profileDropdown');
    
    if (profileButton && profileDropdown) {
        // Add click outside to close
        document.addEventListener('click', function(e) {
            if (!profileButton.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.add('hidden');
            }
        });
        
        // Add escape key to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !profileDropdown.classList.contains('hidden')) {
                profileDropdown.classList.add('hidden');
            }
        });
    }
}

// Enhanced toggle function for profile dropdown
function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    const chevron = document.querySelector('.ti-chevron-up');
    
    if (dropdown.classList.contains('hidden')) {
        dropdown.classList.remove('hidden');
        dropdown.classList.add('profile-dropdown');
        chevron.style.transform = 'rotate(180deg)';
    } else {
        dropdown.classList.add('hidden');
        dropdown.classList.remove('profile-dropdown');
        chevron.style.transform = 'rotate(0deg)';
    }
}

// Function to update user profile info dynamically
function updateProfileInfo(userData) {
    const profileName = document.querySelector('#profileTextContainer .text-sm.font-medium');
    const profileRole = document.querySelector('#profileTextContainer .text-xs.text-gray-500');
    const dropdownName = document.querySelector('#profileDropdown .text-sm.font-medium');
    const dropdownEmail = document.querySelector('#profileDropdown .text-xs.text-gray-500');
    
    if (profileName) profileName.textContent = userData.name;
    if (profileRole) profileRole.textContent = userData.role;
    if (dropdownName) dropdownName.textContent = userData.name;
    if (dropdownEmail) dropdownEmail.textContent = userData.email;
}

// Add CSS animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: translate(-50%, -50%) scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
