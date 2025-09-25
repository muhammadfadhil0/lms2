let sidebarCollapsed = false;

// Load sidebar state from localStorage on page load
function loadSidebarState() {
    const savedState = localStorage.getItem('sidebarCollapsed');
    
    if (savedState !== null) {
        sidebarCollapsed = JSON.parse(savedState);
        
        if (sidebarCollapsed) {
            applySidebarCollapse(false); // Apply without animation on load
        }
    }
}

// Apply sidebar collapse styling immediately
function applySidebarCollapseImmediate() {
    const savedState = localStorage.getItem('sidebarCollapsed');
    
    if (savedState === 'true') {
        sidebarCollapsed = true;
        
        // Apply styles immediately without waiting for DOM ready
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('[data-main-content]');
        
        if (sidebar) {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-28');
            document.documentElement.classList.add('sidebar-collapsed');
        }

        if (mainContent) {
            mainContent.classList.remove('md:ml-64');
            mainContent.classList.add('md:ml-28');
        }
        
        // Apply other styles after a short delay to ensure elements exist
        setTimeout(() => {
            applySidebarCollapse(false);
        }, 10);
    }
}

// Apply sidebar collapse styling
function applySidebarCollapse(withAnimation = true) {
    const sidebar = document.getElementById('sidebar');
    const logoTextContainer = document.getElementById('logoTextContainer');
    const profileTextContainer = document.getElementById('profileTextContainer');
    const toggleIcon = document.getElementById('toggleIcon');
    const navTexts = document.querySelectorAll('.nav-text');
    const mainContent = document.querySelector('[data-main-content]');
    const icons = document.querySelectorAll('.iconSidebar');
    const btns = document.querySelectorAll('.buttonSidebar');

    if (sidebarCollapsed) {
        // Collapse sidebar
        if (sidebar) {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-28');
            document.documentElement.classList.add('sidebar-collapsed');
        }

        // Hide text elements
        if (logoTextContainer) {
            logoTextContainer.classList.add('w-0', 'opacity-0');
        }
        if (profileTextContainer) {
            profileTextContainer.classList.add('w-0', 'opacity-0');
        }
        navTexts.forEach(text => {
            text.classList.add('w-0', 'opacity-0');
        });

        // Change toggle icon
        if (toggleIcon) {
            toggleIcon.classList.remove('ti-menu-2');
            toggleIcon.classList.add('ti-menu');
        }

        // remove margin icon
        icons.forEach(icon => {
            icon.classList.add('m-0');
        });

        // add justify to button
        btns.forEach(btn => {
            btn.classList.add('justify-center');
        });

        // Adjust main content margin
        if (mainContent) {
            mainContent.classList.remove('md:ml-64');
            mainContent.classList.add('md:ml-28');
        }
    } else {
        // Expand sidebar
        if (sidebar) {
            sidebar.classList.remove('w-28');
            sidebar.classList.add('w-64');
            document.documentElement.classList.remove('sidebar-collapsed');
        }

        // remove justify in button
        btns.forEach(btn => {
            btn.classList.remove('justify-center');
        });

        // add margin
        icons.forEach(icon => {
            icon.classList.remove('m-0');
        });

        // Show text elements with delay only if with animation
        if (withAnimation) {
            setTimeout(() => {
                if (logoTextContainer) {
                    logoTextContainer.classList.remove('w-0', 'opacity-0');
                }
                if (profileTextContainer) {
                    profileTextContainer.classList.remove('w-0', 'opacity-0');
                }
                navTexts.forEach(text => {
                    text.classList.remove('w-0', 'opacity-0');
                });
            }, 150);
        } else {
            if (logoTextContainer) {
                logoTextContainer.classList.remove('w-0', 'opacity-0');
            }
            if (profileTextContainer) {
                profileTextContainer.classList.remove('w-0', 'opacity-0');
            }
            navTexts.forEach(text => {
                text.classList.remove('w-0', 'opacity-0');
            });
        }

        // Change toggle icon
        if (toggleIcon) {
            toggleIcon.classList.remove('ti-menu');
            toggleIcon.classList.add('ti-menu-2');
        }

        // Adjust main content margin
        if (mainContent) {
            mainContent.classList.remove('md:ml-28');
            mainContent.classList.add('md:ml-64');
        }
    }
}

function toggleSidebar() {
    sidebarCollapsed = !sidebarCollapsed;
    
    // Save state to localStorage
    localStorage.setItem('sidebarCollapsed', JSON.stringify(sidebarCollapsed));
    
    // Apply the collapse state with animation
    applySidebarCollapse(true);
}

// Force-expand the sidebar immediately and update state, then call callback after animation
function expandSidebarNow(callback) {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('[data-main-content]');
    const logoTextContainer = document.getElementById('logoTextContainer');
    const profileTextContainer = document.getElementById('profileTextContainer');
    const navTexts = document.querySelectorAll('.nav-text');
    const icons = document.querySelectorAll('.iconSidebar');
    const btns = document.querySelectorAll('.buttonSidebar');
    const toggleIcon = document.getElementById('toggleIcon');

    // Update state
    sidebarCollapsed = false;
    localStorage.setItem('sidebarCollapsed', JSON.stringify(sidebarCollapsed));

    // Direct DOM changes to ensure immediate expand
    if (sidebar) {
        sidebar.classList.remove('w-28');
        sidebar.classList.add('w-64');
    }

    if (mainContent) {
        mainContent.classList.remove('md:ml-28');
        mainContent.classList.add('md:ml-64');
    }

    btns.forEach(btn => btn.classList.remove('justify-center'));
    icons.forEach(icon => icon.classList.remove('m-0'));

    if (logoTextContainer) logoTextContainer.classList.remove('w-0', 'opacity-0');
    if (profileTextContainer) profileTextContainer.classList.remove('w-0', 'opacity-0');
    navTexts.forEach(text => text.classList.remove('w-0', 'opacity-0'));

    document.documentElement.classList.remove('sidebar-collapsed');

    if (toggleIcon) {
        toggleIcon.classList.remove('ti-menu');
        toggleIcon.classList.add('ti-menu-2');
    }

    // Apply final adjustments via existing function (no animation)
    setTimeout(() => {
        applySidebarCollapse(false);
        if (typeof callback === 'function') callback();
    }, 300);
}

function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');

    function toggleDropdown() {
        if (!dropdown) return;
        dropdown.classList.toggle('hidden');
    }

    if (sidebarCollapsed) {
        // Force expand, then toggle dropdown when done
        expandSidebarNow(() => {
            toggleDropdown();
        });
    } else {
        toggleDropdown();
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');
    const dropdown = document.getElementById('profileDropdown');

    if (!button && dropdown && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});

// Initialize immediately when script loads
applySidebarCollapseImmediate();

// Initialize sidebar state when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    loadSidebarState();
});
