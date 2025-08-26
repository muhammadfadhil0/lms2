    let sidebarCollapsed = false;

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const logoTextContainer = document.getElementById('logoTextContainer');
        const profileTextContainer = document.getElementById('profileTextContainer');
        const toggleIcon = document.getElementById('toggleIcon');
        const navTexts = document.querySelectorAll('.nav-text');
        const mainContent = document.querySelector('[data-main-content]');

        sidebarCollapsed = !sidebarCollapsed;

        if (sidebarCollapsed) {
            // Collapse sidebar
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-27');

            // Hide text elements
            logoTextContainer.classList.add('w-0', 'opacity-0');
            profileTextContainer.classList.add('w-0', 'opacity-0');
            navTexts.forEach(text => {
                text.classList.add('w-0', 'opacity-0');
            });

            // Change toggle icon
            toggleIcon.classList.remove('ti-menu-2');
            toggleIcon.classList.add('ti-menu');

            // Adjust main content margin
            if (mainContent) {
                mainContent.classList.remove('md:ml-64');
                mainContent.classList.add('md:ml-25');
            }
        } else {
            // Expand sidebar
            sidebar.classList.remove('w-16');
            sidebar.classList.add('w-64');

            // Show text elements with delay
            setTimeout(() => {
                logoTextContainer.classList.remove('w-0', 'opacity-0');
                profileTextContainer.classList.remove('w-0', 'opacity-0');
                navTexts.forEach(text => {
                    text.classList.remove('w-0', 'opacity-0');
                });
            }, 150);

            // Change toggle icon
            toggleIcon.classList.remove('ti-menu');
            toggleIcon.classList.add('ti-menu-2');

            // Adjust main content margin
            if (mainContent) {
                mainContent.classList.remove('md:ml-16');
                mainContent.classList.add('md:ml-64');
            }
        }
    }


    

    function toggleProfileDropdown() {
        const dropdown = document.getElementById('profileDropdown');
        dropdown.classList.toggle('hidden');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');
        const dropdown = document.getElementById('profileDropdown');

        if (!button && dropdown && !dropdown.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });
