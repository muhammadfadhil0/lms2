<!-- Mobile Bottom Tab Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 md:hidden z-50">
        <div class="flex justify-around py-2">
            <a href="beranda-user.php" class="flex flex-col items-center p-2 <?php echo ($currentPage == 'beranda') ? 'text-orange bg-orange-tipis' : 'text-gray-500 hover:text-gray-700'; ?>">
                <i class="ti ti-home text-xl mb-1"></i>
                <span class="text-xs">Beranda</span>
            </a>
            <a href="ujian-user.php" class="flex flex-col items-center p-2 <?php echo ($currentPage == 'ujian') ? 'text-orange bg-orange-tipis' : 'text-gray-500 hover:text-gray-700'; ?>">
                <i class="ti ti-clipboard-check text-xl mb-1"></i>
                <span class="text-xs">Ujian</span>
            </a>
            <a href="#" class="flex flex-col items-center p-2 <?php echo ($currentPage == 'ai') ? 'text-orange bg-orange-tipis' : 'text-gray-500 hover:text-gray-700'; ?>">
                <i class="ti ti-robot text-xl mb-1"></i>
                <span class="text-xs">AI</span>
            </a>
            <button onclick="toggleMobileProfile()" class="flex flex-col items-center p-2 text-gray-500 hover:text-gray-700">
                <i class="ti ti-user text-xl mb-1"></i>
                <span class="text-xs">Profile</span>
            </button>
        </div>
    </div>

    <!-- Mobile Profile Modal -->
    <div id="mobileProfileModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden md:hidden">
        <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-lg">
            <div class="p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Profile</h3>
                    <button onclick="toggleMobileProfile()" class="p-2 text-gray-500">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>
                <div class="flex items-center mb-4">
                    <img src="https://via.placeholder.com/40" alt="Profile" class="w-12 h-12 rounded-full">
                    <div class="ml-3">
                        <p class="font-medium text-gray-800">John Doe</p>
                        <p class="text-sm text-gray-500">Student</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50">
                        <i class="ti ti-user text-gray-500"></i>
                        <span class="text-gray-700">Profile</span>
                    </a>
                    <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50">
                        <i class="ti ti-settings text-gray-500"></i>
                        <span class="text-gray-700">Settings</span>
                    </a>
                    <button command="show-modal" commandfor="logout-modal" class="w-full flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 text-red-600">
                        <i class="ti ti-logout text-red-500"></i>
                        <span>Logout</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
