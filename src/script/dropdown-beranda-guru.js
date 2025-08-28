        function toggleDropdown(dropdownId) {
            // Close all other dropdowns
            const allDropdowns = document.querySelectorAll('[id^="dropdown-"]');
            allDropdowns.forEach(dropdown => {
                if (dropdown.id !== dropdownId) {
                    dropdown.classList.add('hidden');
                }
            });
            
            // Toggle the clicked dropdown
            const dropdown = document.getElementById(dropdownId);
            dropdown.classList.toggle('hidden');
            
            // Position the dropdown correctly if it's visible
            if (!dropdown.classList.contains('hidden')) {
                const button = dropdown.previousElementSibling;
                const buttonRect = button.getBoundingClientRect();
                const dropdownHeight = 84; // Approximate height of dropdown (can be adjusted)
                
                // Set position to fixed to escape any overflow constraints
                dropdown.style.position = 'fixed';
                // Position above the button (button's top minus dropdown height)
                dropdown.style.top = (buttonRect.top - dropdownHeight - 5) + 'px';
                dropdown.style.right = (window.innerWidth - buttonRect.right) + 'px';
                dropdown.style.zIndex = '1000'; // Ensure high z-index
                
                // Add a small arrow at the bottom of the dropdown pointing to the button
                dropdown.classList.add('dropup');
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('[onclick*="toggleDropdown"]') && !event.target.closest('[id^="dropdown-"]')) {
                const allDropdowns = document.querySelectorAll('[id^="dropdown-"]');
                allDropdowns.forEach(dropdown => {
                    dropdown.classList.add('hidden');
                });
            }
        });
