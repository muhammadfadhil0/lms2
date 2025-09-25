        // Tab functionality - updated for 3 tabs (profile, security, appearance)
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.tab;
                
                // Remove active class from all tabs and contents
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active', 'border-orange', 'text-orange');
                    b.classList.add('border-transparent', 'text-gray-500');
                });
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Add active class to clicked tab
                btn.classList.add('active', 'border-orange', 'text-orange');
                btn.classList.remove('border-transparent', 'text-gray-500');
                
                // Show corresponding content
                document.getElementById(tab + '-tab').classList.remove('hidden');
            });
        });
        
        // Set initial active tab
        document.querySelector('.tab-btn[data-tab="profile"]').classList.add('border-orange', 'text-orange');
        document.querySelector('.tab-btn[data-tab="profile"]').classList.remove('border-transparent', 'text-gray-500');
