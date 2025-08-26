<?php require 'modal-logout.php'; ?>

<div class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg border-r border-gray-200 flex flex-col hidden md:flex">
    <!-- Logo/Header -->
    <div class="p-6 border-b border-gray-200">
        <h1 class="text-xl font-bold text-gray-800">LMS Dashboard</h1>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="flex-1 p-4">
        <ul class="space-y-2">
            <li>
                <a href="#" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-600 font-medium">
                    <i class="ti ti-home text-xl"></i>
                    <span>Beranda</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition-colors">
                    <i class="ti ti-clipboard-check text-xl"></i>
                    <span>Ujian</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition-colors">
                    <i class="ti ti-robot text-xl"></i>
                    <span>AI</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Profile Section -->
    <div class="p-4 border-t border-gray-200">
        <div class="relative">
            <button onclick="toggleProfileDropdown()" class="w-full flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                <img src="https://via.placeholder.com/40" alt="Profile" class="w-10 h-10 rounded-full">
                <div class="flex-1 text-left">
                    <p class="text-sm font-medium text-gray-800">John Doe</p>
                    <p class="text-xs text-gray-500">Student</p>
                </div>
                <i class="ti ti-chevron-up text-gray-400"></i>
            </button>
            
            <!-- Dropdown Menu -->
            <div id="profileDropdown" class="hidden absolute bottom-full left-0 right-0 mb-2 bg-white border border-gray-200 rounded-lg shadow-lg">
                <a href="#" class="flex items-center space-x-2 p-3 hover:bg-gray-50 transition-colors">
                    <i class="ti ti-user text-gray-500"></i>
                    <span class="text-sm text-gray-700">Profile</span>
                </a>
                <a href="#" class="flex items-center space-x-2 p-3 hover:bg-gray-50 transition-colors">
                    <i class="ti ti-settings text-gray-500"></i>
                    <span class="text-sm text-gray-700">Settings</span>
                </a>
                <hr class="border-gray-200">
                <button command="show-modal" commandfor="logout-modal" class="flex items-center space-x-2 p-3 hover:bg-gray-50 transition-colors text-red-600">
                    <i class="ti ti-logout text-red-500"></i>
                    <span class="text-sm">Logout</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
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
</script>
