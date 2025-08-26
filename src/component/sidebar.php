<?php require 'modal-logout.php'; ?>

<div id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg border-r border-gray-200 flex flex-col hidden md:flex transition-all duration-300 ease-in-out z-40">
    <!-- Logo/Header with Toggle -->
    <div class="p-4 flex items-center gap-2 border-b border-gray-200 min-h-[80px]">
        <button onclick="toggleSidebar()" class="flex items-center justify-center p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors flex-shrink-0">
            <i id="toggleIcon" class="ti ti-menu-2 text-xl"></i>
        </button>
        <img src="../../assets/img/logo.png" alt="Logo" class="h-8 w-8 flex-shrink-0">
        <div id="logoTextContainer" class="transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap">
            <h1 id="logoText" class="text-3xl font-bold text-gray-800">Point</h1>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 p-4">
        <ul class="space-y-2">
            <li>
                <a href="#" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-600 font-medium group">
                    <i class="ti ti-home text-xl flex-shrink-0"></i>
                    <span class="nav-text transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap">Beranda</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition-colors group">
                    <i class="ti ti-clipboard-check text-xl flex-shrink-0"></i>
                    <span class="nav-text transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap">Ujian</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition-colors group">
                    <i class="ti ti-robot text-xl flex-shrink-0"></i>
                    <span class="nav-text transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap">AI</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Profile Section -->
    <div class="p-4 border-t border-gray-200">
        <div class="relative">
            <button onclick="toggleProfileDropdown()" class="w-full flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group min-h-[64px]">
                <img src="https://via.placeholder.com/40" alt="Profile" class="w-10 h-10 rounded-full flex-shrink-0">
                <div id="profileTextContainer" class="flex-1 text-left transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap">
                    <div class="nav-text">
                        <p class="text-sm font-medium text-gray-800">John Doe</p>
                        <p class="text-xs text-gray-500">Student</p>
                    </div>
                </div>
                <i class="ti ti-chevron-up text-gray-400 nav-text transition-all duration-300 ease-in-out flex-shrink-0"></i>
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

<script src="../script/script-sidebar-collaps.js"></script>