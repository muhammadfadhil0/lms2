let sidebarCollapsed = false;

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const logoTextContainer = document.getElementById('logoTextContainer');
        const profileTextContainer = document.getElementById('profileTextContainer');
        const toggleIcon = document.getElementById('toggleIcon');
        const navTexts = document.querySelectorAll('.nav-text');
        const mainContent = document.querySelector('[data-main-content]');
        const icons = document.querySelectorAll('.iconSidebar');
        const btns = document.querySelectorAll('.buttonSidebar');

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
                mainContent.classList.add('md:ml-25');
            }
        } else {
            // Expand sidebar
            sidebar.classList.remove('w-16');
            sidebar.classList.add('w-64');

            // remove justify in button
            btns.forEach(btn => {
                btn.classList.remove('justify-center');
            });

            // add margin
            icons.forEach(icon => {
                icon.classList.remove('m-0');
            });

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
        // If sidebar is collapsed, expand it first
        if (sidebarCollapsed) {
            toggleSidebar();
            // Add a small delay before opening dropdown to allow sidebar animation to complete
            setTimeout(() => {
                const dropdown = document.getElementById('profileDropdown');
                dropdown.classList.toggle('hidden');
            }, 200);
        } else {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('hidden');
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
